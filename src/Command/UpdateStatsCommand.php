<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Metric;
use App\Model\Table\MetricsTable;
use App\Model\Table\ReleasesTable;
use App\Model\Table\StatisticsTable;
use Cake\Cache\Cache;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Mailer;
use Cake\Mailer\Transport\DebugTransport;
use Cake\ORM\TableRegistry;
use fred_api_exception;

/**
 * UpdateStats command.
 *
 * This command is intended to be run via a cron job and overwrite cached values every 24 hours.
 * This application's cache configuration has cached values retained for a longer period of time so that in the event
 * of an update failure, cached values are retained and used instead of forcing new data to be fetched during a user
 * request.
 *
 * @property \App\Model\Entity\Metric[] $metrics
 * @property \App\Model\Table\MetricsTable $metricsTable
 * @property \App\Model\Table\ReleasesTable $releasesTable
 * @property \App\Model\Table\StatisticsTable $statisticsTable
 * @property \Cake\Console\ConsoleIo $io
 * @property \Cake\Shell\Helper\ProgressHelper $progress
 * @property bool $onlyNew
 */
class UpdateStatsCommand extends AppCommand
{
    private ?FrozenTime $lastSlackMsgTime;
    private array $apiParameters;
    private array $metrics;
    private bool $alertAdminOnStall = false;
    private bool $auto = false;
    private bool $onlyNew;
    private const UNITS_CHANGE_FROM_1_YEAR_AGO = 'ch1';
    private const UNITS_PERCENT_CHANGE_FROM_1_YEAR_AGO = 'pc1';
    private const UNITS_VALUE = 'lin';
    private FrozenTime $timeStartedStatLoop;
    private MetricsTable $metricsTable;
    private ReleasesTable $releasesTable;
    private StatisticsTable $statisticsTable;
    private string $timeBetweenAutoFullUpdates = '1 day';
    private string | null $clearLockIfDurationExceeds = '30 minutes';
    public const CACHE_CONFIG = 'update_stats';

    /**
     * The frequency of updates sent to Slack while in a statistic adding/updating loop for a single endpoint
     *
     * @var string
     */
    private string $slackUpdatesEvery = '5 minutes';

    /**
     * UpdateStatsCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->metricsTable = TableRegistry::getTableLocator()->get('Metrics');
        $this->releasesTable = TableRegistry::getTableLocator()->get('Releases');
        $this->statisticsTable = TableRegistry::getTableLocator()->get('Statistics');
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Updates API-fetched data stored in the database');
        $parser->addOption('only-new', [
            'short' => 'n',
            'help' => 'Only checks endpoints with releases on or before today that have not yet been imported, '
                . 'and only adds new stats, rather than also updating existing stats',
            'boolean' => true,
        ]);
        $parser->addOption('ignore-lock', [
            'short' => 'i',
            'help' => 'Allows multiple update processes to take place concurrently (not recommended unless if there\'s '
                . 'a problem with the process lock',
            'boolean' => true,
        ]);
        $parser->addOption('auto', [
            'short' => 'a',
            'help' => 'Activates the --only-new option if the last full update was > 1 day ago',
            'boolean' => true,
        ]);
        $parser->addOption('choose', [
            'short' => 'c',
            'help' => 'Choose a specific endpoint group instead of cycling through all',
            'boolean' => true,
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     * @throws \fred_api_exception
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);
        $start = new FrozenTime();
        if (!$args->getOption('ignore-lock')) {
            $this->avoidConcurrentProcesses();
        }

        $cacheUpdater = new UpdateCacheCommand($io);
        $spreadsheetWriter = new MakeSpreadsheetsCommand($io);
        $this->onlyNew = (bool)$args->getOption('only-new');
        $this->initAutoMode($args);

        $this->toSlack(
            'Running update_stats' .
            ($this->auto ? ' --auto' : null) .
            (!$this->auto && $this->onlyNew ? ' --only-new' : null) .
            ($args->getOption('choose') ? ' --choose' : null) .
            ($args->getOption('ignore-lock') ? ' --ignore-lock' : null)
        );

        $selectedEndpointGroups = $this->getSelectedEndpointGroups($args);
        $groupsCount = count($selectedEndpointGroups);
        $i = 1;
        foreach ($selectedEndpointGroups as $endpointGroup) {
            $this->toConsoleAndSlack("{$endpointGroup['title']} ($i/$groupsCount)", 'info');
            $i++;
            $this->loadMetrics($endpointGroup);
            $groupUpdated = false;

            foreach ($endpointGroup['endpoints'] as $seriesId => $name) {
                $this->updateLock();

                if ($this->skipSeries($seriesId)) {
                    $this->io->out("$seriesId: Skipping; no new release expected");
                    continue;
                }

                if (!$this->updateIsAvailable($seriesId)) {
                    $this->io->out("$seriesId: No update available");
                    continue;
                }

                $this->toConsoleAndSlack("$seriesId: Update available");
                try {
                    $this->updateEndpoint(
                        group: $endpointGroup['title'],
                        seriesId: $seriesId,
                        name: $name,
                    );
                    $groupUpdated = true;
                } catch (NotFoundException | fred_api_exception $e) {
                    $this->toConsoleAndSlack('Error: ' . $e->getMessage(), 'error');
                    $this->shutdown();
                    exit;
                }
                $this->markReleasesImported($seriesId);
            }

            if ($groupUpdated) {
                $cacheUpdater->refreshGroup($endpointGroup);
                $spreadsheetWriter->makeSpreadsheetsForGroup($endpointGroup);
            }

            $io->out();
        }

        $timeAgo = $start->timeAgoInWords();
        $this->toConsoleAndSlack("Finished (started $timeAgo)", 'success');
        $this->setLastFullUpdateTime();
        $this->shutdown();
    }

    /**
     * Returns TRUE if the API has more recently-updated data than the database
     *
     * @param string $seriesId Matching metric->series_id and a seriesID in the FRED API
     * @return bool
     * @throws \fred_api_exception
     * @throws \Cake\Http\Exception\NotFoundException
     */
    private function updateIsAvailable(string $seriesId): bool
    {
        $metric = $this->metrics[$seriesId];
        if (!$metric->last_updated) {
            return true;
        }

        $this->setEndpoint($seriesId);
        $endpointMeta = $this->getEndpointMetadata();
        $seriesUpdated = new FrozenTime($endpointMeta['@attributes']['last_updated']);
        $realtimeEnd = new FrozenTime($endpointMeta['@attributes']['realtime_end']);

        return $seriesUpdated->gt($metric->last_updated) || $realtimeEnd->gt($metric->last_updated);
    }

    /**
     * Loads metrics into $this->metrics, creating them if necessary
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     * @throws \fred_api_exception
     */
    private function loadMetrics(array $endpointGroup): void
    {
        foreach ($endpointGroup['endpoints'] as $seriesId => $name) {
            // Find existing metric
            try {
                $metric = $this->metricsTable->getFromSeriesId($seriesId);
                if ($metric) {
                    $this->metrics[$seriesId] = $metric;
                    continue;
                }
            } catch (NotFoundException $e) {
            }

            // Create missing metric
            $this->io->out('Adding ' . $seriesId . ' to metrics table');
            $this->setEndpoint($seriesId);
            $endpointMeta = $this->getEndpointMetadata();
            $data = [
                'series_id' => $seriesId,
                'units' => $endpointMeta['@attributes']['units'],
                'frequency' => $endpointMeta['@attributes']['frequency'],
            ];
            $metric = $this->metricsTable->newEntity($data);
            if (!$this->metricsTable->save($metric)) {
                $this->toConsoleAndSlack(
                    "There was an error saving a new metric record for series $seriesId. Details:",
                    'error',
                );
                $this->toConsoleAndSlack(print_r($metric->getErrors(), true));
                $this->shutdown();
                exit;
            }
            $this->metrics[$seriesId] = $metric;
        }
    }

    /**
     * Sets the series_id parameter for the next API request
     *
     * @param string $seriesId Valid FRED API series_id argument
     * @return void
     */
    private function setEndpoint(string $seriesId): void
    {
        $this->apiParameters['series_id'] = $seriesId;
    }

    /**
     * Returns information about a data series provided by a given endpoint
     *
     * @param array $parameters Additional optional parameters
     * @return array|null
     * @throws \fred_api_exception
     * @link https://fred.stlouisfed.org/docs/api/fred/
     */
    private function getEndpointMetadata(array $parameters = []): ?array
    {
        /** @var \fred_api_series $api */
        $api = $this->api->factory('series');
        $parameters += $this->apiParameters;
        $this->throttle();

        for ($attempts = 1 + $this->apiRetryCount; $attempts > 0; $attempts--) {
            $finalAttempt = $attempts == 1;

            try {
                $response = $api->get($parameters);
            } catch (fred_api_exception $e) {
                if ($finalAttempt) {
                    $this->toSlack('Exception encountered on final re-attempt to get endpoint metadata:');
                    $this->toSlack($e->getMessage());
                    throw $e;
                }

                $this->io->error('Failed, retrying');
                $this->waitAfterError();
                continue;
            }

            if ($response && property_exists($response, 'series')) {
                return (array)($response->series);
            }

            if ($finalAttempt) {
                $msg = 'Metadata could not be retrieved for series ' . $this->apiParameters['series_id'];
                $this->toSlack($msg);
                throw new NotFoundException($msg);
            }

            $this->io->error('Failed, retrying');
        }

        return null;
    }

    /**
     * Returns the observations or data values for a data series
     *
     * @param array $parameters Additional optional parameters
     * @return array
     * @throws \fred_api_exception
     * @throws \Cake\Http\Exception\NotFoundException
     * @link https://fred.stlouisfed.org/docs/api/fred/
     */
    private function getObservations(array $parameters = []): array
    {
        for ($attempts = 1 + $this->apiRetryCount; $attempts > 0; $attempts++) {
            $finalAttempt = $attempts == 1;
            /** @var \fred_api_series $api */
            $api = $this->api->factory('series');
            $parameters += $this->apiParameters;
            $parameters['file_type'] = 'json';

            // Fetch observations
            $this->throttle();
            try {
                $response = $api->observations($parameters);

            // Handle error fetching observations
            } catch (fred_api_exception $e) {
                if ($finalAttempt) {
                    $this->toSlack('Exception encountered on final re-attempt to fetch observations:');
                    $this->toSlack($e->getMessage());
                    throw $e;
                }

                $this->io->error('Failed, retrying');
                $this->waitAfterError();
                continue;
            }

            // Decode response
            $responseObj = $this->decodeResponse(
                response: $response,
                requiredProperty: 'observations',
                haltOnError: $finalAttempt,
            );

            // Handle invalid response
            if (!$responseObj) {
                if ($finalAttempt) {
                    $this->toConsoleAndSlack(
                        'Invalid response received on final re-attempt to fetch observations',
                        'error',
                    );
                    $this->shutdown();
                    exit;
                }

                $this->io->error('Invalid response, retrying');
                $this->waitAfterError();
                continue;
            }

            $observations = $responseObj->observations;

            // Adjust for requests with limit = 1
            if (isset($observations['@attributes'])) {
                $observations = [$observations];
            }

            $retval = [];
            foreach ($observations as $observation) {
                $retval[] = [
                    'date' => $observation->date,
                    'value' => $observation->value,
                ];
            }

            return $retval;
        }

        return [];
    }

    /**
     * Fetches data from the API and updates/adds corresponding records in the database
     *
     * @param string $group Title of endpoint group
     * @param string $seriesId Endpoint seriesID
     * @param string $name Title of endpoint
     * @return void
     * @throws \fred_api_exception
     */
    private function updateEndpoint(string $group, string $seriesId, string $name): void
    {
        $this->toSlack("- Updating stats for $group: $name");
        $this->io->out('- Retrieving from API...');
        $this->io->out("- $group: $name metadata");

        // Fetch metadata from API
        $this->setEndpoint($seriesId);
        $endpointMeta = $this->getEndpointMetadata();

        // Pull in new statistics from the three data types
        $this->io->out('- Values');
        $metric = $this->metrics[$seriesId];
        $this->apiParameters['sort_order'] = 'asc';
        $this->setStartDate($metric);
        $this->saveAllStatistics(
            observations: $this->getObservations(['units' => self::UNITS_VALUE]),
            metricId: $metric->id,
            dataTypeId: StatisticsTable::DATA_TYPE_VALUE,
        );

        $this->io->out('- Changes');
        $this->saveAllStatistics(
            observations: $this->getObservations(['units' => self::UNITS_CHANGE_FROM_1_YEAR_AGO]),
            metricId: $metric->id,
            dataTypeId: StatisticsTable::DATA_TYPE_CHANGE,
        );

        $this->io->out('- Percent changes');
        $this->saveAllStatistics(
            observations: $this->getObservations(['units' => self::UNITS_PERCENT_CHANGE_FROM_1_YEAR_AGO]),
            metricId: $metric->id,
            dataTypeId: StatisticsTable::DATA_TYPE_PERCENT_CHANGE,
        );

        $lastUpdated = $endpointMeta['@attributes']['last_updated'];
        $lastUpdatedObj = new FrozenTime($lastUpdated, $this->getApiTimezone($lastUpdated));
        $realtimeEnd = $endpointMeta['@attributes']['realtime_end'];
        $realtimeEndObj = new FrozenTime($realtimeEnd, $this->getApiTimezone($realtimeEnd));
        $latestDate = $lastUpdatedObj->gte($realtimeEndObj) ? $lastUpdatedObj : $realtimeEndObj;
        $this->updateMetricUpdatedDate($metric, $latestDate);
    }

    /**
     * Adds or updates a statistic record
     *
     * @param int $metricId Metric ID
     * @param int $dataTypeId ID corresponding to value, change, or percent change
     * @param \Cake\I18n\FrozenDate $date Date that stat was observed
     * @param string $value Stat value
     * @return void
     */
    private function saveStatistic(int $metricId, int $dataTypeId, FrozenDate $date, string $value)
    {
        // Standardize non-values as NULL
        $value = $value == '.' ? null : $value;

        $data = [
            'metric_id' => $metricId,
            'data_type_id' => $dataTypeId,
            'date' => $date,
        ];

        if ($this->onlyNew && $this->statisticsTable->exists([$data])) {
            return;
        }

        /** @var \App\Model\Entity\Statistic|null $statistic */
        $statistic = $this->statisticsTable
            ->find()
            ->select(['id', 'value'])
            ->where($data)
            ->first();

        $isNewStat = false;
        $updateNeeded = false;
        if (!$statistic) {
            $isNewStat = true;
            $updateNeeded = true;
            $data['value'] = $value;
            $statistic = $this->statisticsTable->newEntity($data);
        } elseif ($statistic->value != $value) {
            $updateNeeded = true;
            $this->statisticsTable->patchEntity($statistic, ['value' => $value]);
        }

        if (!$updateNeeded) {
            return;
        }

        if (!$this->statisticsTable->save($statistic)) {
            $this->toConsoleAndSlack(
                sprintf(
                    'There was an error %s that statistic. Details:',
                    $isNewStat ? 'adding' : 'updating'
                ),
                'error',
            );
            $this->toConsoleAndSlack(
                print_r($statistic->getErrors(), true) .
                "\nData:\n" .
                print_r($data, true)
            );
            $this->shutdown();
            exit;
        }
    }

    /**
     * Updates the last_updated value for the provided metric
     *
     * @param \App\Model\Entity\Metric $metric Metric entity
     * @param FrozenTime $lastUpdated A time object representing when this metric's stats were last updated
     * @return void
     */
    private function updateMetricUpdatedDate(Metric $metric, FrozenTime $lastUpdated): void
    {
        $this->metricsTable->patchEntity($metric, ['last_updated' => $lastUpdated]);
        if (!$this->metricsTable->save($metric)) {
            $this->toConsoleAndSlack('There was an error updating that metric. Details:', 'error');
            $this->toConsoleAndSlack(print_r($metric->getErrors(), true));
            $this->shutdown();
            exit;
        }
    }

    /**
     * Iterates through $observations and saves them to the database
     *
     * @param array $observations Array of ['date' => ..., 'value' => ...] API call results
     * @param int $metricId Metric ID
     * @param int $dataTypeId ID corresponding to value, change, or percent change
     * @return void
     */
    private function saveAllStatistics(array $observations, int $metricId, int $dataTypeId)
    {
        $observationCount = count($observations);
        $this->progress->init([
            'total' => $observationCount,
            'width' => 40,
        ]);
        $this->progress->draw();

        $this->timeStartedStatLoop = new FrozenTime();
        $this->lastSlackMsgTime = null;
        $i = 1;
        foreach ($observations as $observation) {
            $this->updateLock();
            $percentDone = round($i / $observationCount);
            $this->sendStatLoopSlackMsg("- Saving stats ($percentDone% completed)");
            $this->saveStatistic(
                metricId: $metricId,
                dataTypeId: $dataTypeId,
                date: new FrozenDate($observation['date']),
                value: $observation['value'],
            );
            $this->progress->increment()->draw();
            $i++;
        }
        $this->io->overwrite('- Done');
    }

    /**
     * Sends a message to Slack in five-minute intervals
     *
     * Meant to reassure anyone monitoring these messages that a long stats-updating loop is still running and has not
     * silently crashed
     *
     * @param string $msg Message to send
     * @return void
     */
    private function sendStatLoopSlackMsg(string $msg)
    {
        if ($this->muteSlack) {
            return;
        }
        if ($this->timeStartedStatLoop->wasWithinLast($this->slackUpdatesEvery)) {
            return;
        }
        if ($this->lastSlackMsgTime && $this->lastSlackMsgTime->wasWithinLast($this->slackUpdatesEvery)) {
            return;
        }

        $this->toSlack($msg);
        $this->lastSlackMsgTime = new FrozenTime();
    }

    /**
     * Returns a valid timezone string gleaned from the API-returned date string
     *
     * According to the API docs, the date should end with a three-character GMT offset string, such as '-06'
     *
     * @param string $lastUpdated Returned by the API in this format: "2013-07-31 09:26:16-05"
     * @return string
     */
    private function getApiTimezone(string $lastUpdated): string
    {
        $timeOffset = substr($lastUpdated, -3);
        $operator = substr($timeOffset, 0, 1);
        $hours = substr($timeOffset, 1, 2);

        return $operator . $hours . '00';
    }

    /**
     * Marks all releases on or before today for the specified series as having been imported
     *
     * @param string $seriesId Matching metric->series_id and a seriesID in the FRED API
     * @return void
     */
    private function markReleasesImported(string $seriesId)
    {
        $metric = $this->metrics[$seriesId];
        /** @var \Cake\ORM\ResultSet|array $releases */
        $releases = $this->releasesTable
            ->find('currentAndPast')
            ->where([
                'metric_id' => $metric->id,
                'imported' => false,
            ])
            ->all();
        if (!$releases) {
            $this->io->out('- No releases to mark imported');

            return;
        }

        $count = count($releases);
        if ($count) {
            $this->toConsoleAndSlack(sprintf(
                '- Recording %s as having been imported',
                $count == 1 ? 'last release' : "$count releases",
            ));
            foreach ($releases as $release) {
                $release = $this->releasesTable->patchEntity($release, ['imported' => true]);
                if (!$this->releasesTable->save($release)) {
                    $this->toConsoleAndSlack(
                        'There was an error marking that release as having been imported. Details:',
                        'error'
                    );
                    $this->toConsoleAndSlack(print_r($release->getErrors(), true));
                    $this->shutdown();
                    exit;
                }
            }
        }
    }

    /**
     * Returns TRUE if no updates are expected for this series and the user is using the --only-new option
     *
     * @param string $seriesId Matching metric->series_id and a seriesID in the FRED API
     * @return bool
     */
    private function skipSeries(string $seriesId): bool
    {
        if (!$this->onlyNew) {
            return false;
        }

        $metric = $this->metrics[$seriesId];

        return !$this->releasesTable->newDataExpected($metric->id);
    }

    /**
     * Halts execution if it appears that another update process is currently underway
     *
     * If it looks like the previous process aborted and left a lingering lock, the lock is cleared and an admin is
     * alerted
     *
     * @return void
     */
    private function avoidConcurrentProcesses(): void
    {
        $running = Cache::read('last_running', self::CACHE_CONFIG);
        if (!$running) {
            return;
        }

        // Allow this script to proceed if it appears that the last process stalled, but email the administrator
        $runningObj = new FrozenTime($running);
        if ($this->clearLockIfDurationExceeds && !$runningObj->wasWithinLast($this->clearLockIfDurationExceeds)) {
            $this->clearLock();
            $this->toConsoleAndSlack(
                "Previous process was running $this->clearLockIfDurationExceeds ago and never completed. " .
                ($this->alertAdminOnStall ? 'Alerting administrator and clearing lock.' : 'Clearing lock.'),
                'warning',
            );

            if (!$this->alertAdminOnStall) {
                return;
            }

            $mailer = new Mailer('default');
            if (Configure::read('debug')) {
                $mailer->setTransport(new DebugTransport());
            }
            $mailer->setFrom(['noreply@cberdata.org' => 'Economic Indicators'])
                ->setTo(Configure::read('admin_email'))
                ->setSubject('Script update_stats stalled')
                ->deliver(
                    "The update_stats script was running over $this->clearLockIfDurationExceeds ago and has " .
                    'not finished or reported progress since then. It may have been aborted due to an error or ' .
                    'manually terminated. The process lock has been cleared and a new update process has started.'
                );

            return;
        }

        $this->toConsoleAndSlack(sprintf(
            'Another update_stats process reported that it was running %s and hasn\'t completed. Aborting.',
            $runningObj->timeAgoInWords()
        ));
        $this->toConsoleAndSlack('Run this command with the option --ignore-lock to run it anyway.');
        exit;
    }

    /**
     * Updates this script's lock file with the current time
     *
     * @return void
     */
    private function updateLock(): void
    {
        Cache::write(
            'last_running',
            (new FrozenTime())->toIso8601String(),
            self::CACHE_CONFIG,
        );
    }

    /**
     * Clears this script's lock that prevents it from being run concurrently
     *
     * @return void
     */
    private function clearLock(): void
    {
        Cache::delete('last_running', self::CACHE_CONFIG);
    }

    /**
     * Performs end-of-script cleanup operations
     *
     * @return void
     */
    private function shutdown(): void
    {
        $this->clearLock();
    }

    /**
     * Sets the 'observation_start' API parameter
     *
     * This will be NULL unless if the --only-new option is in use and there's a "most recent statistic" to use to
     * determine the earliest date to request statistics for.
     *
     * @param \App\Model\Entity\Metric $metric Metric entity to find associated an statistic date for
     * @return void
     */
    private function setStartDate(Metric $metric): void
    {
        // Clear this parameter to avoid its value carrying over between loop iterations
        $this->apiParameters['observation_start'] = null;

        if (!$this->onlyNew) {
            return;
        }
        /** @var \App\Model\Entity\Statistic|null $mostRecentStatistic */
        $mostRecentStatistic = $this->statisticsTable
            ->find()
            ->select(['date'])
            ->where(['metric_id' => $metric->id])
            ->orderDesc('date')
            ->first();

        if (!$mostRecentStatistic) {
            return;
        }

        $this->apiParameters['observation_start'] = $mostRecentStatistic->date->format('Y-m-d');
    }

    /**
     * Sets $this->auto and, if in auto mode, also sets $this->onlyNew
     *
     * @param \Cake\Console\Arguments $args Command arguments
     * @return void
     */
    private function initAutoMode(Arguments $args): void
    {
        $this->auto = (bool)$args->getOption('auto');

        if (!$this->auto) {
            return;
        }

        if ($args->getOption('only-new')) {
            $this->toConsoleAndSlack('Cannot run update_stats with both --auto and --only-new modes active', 'error');
            exit;
        }

        $lastFullUpdate = $this->getLastFullUpdateTime();
        $msg = $lastFullUpdate
            ? sprintf('Last full update completed %s.', $lastFullUpdate->timeAgoInWords())
            : 'Last full update time not recorded.';
        if ($lastFullUpdate && $lastFullUpdate->wasWithinLast($this->timeBetweenAutoFullUpdates)) {
            $this->toConsoleAndSlack($msg . ' Running in --only-new mode.');
            $this->onlyNew = true;

            return;
        }


        $this->toConsoleAndSlack($msg . ' Running full update.');
        $this->onlyNew = false;
    }

    /**
     * Sets the cached last_full_update time to now
     *
     * @return void
     */
    private function setLastFullUpdateTime(): void
    {
        if ($this->onlyNew) {
            return;
        }
        Cache::write('last_full_update', new FrozenTime(), self::CACHE_CONFIG);
    }

    /**
     * Returns the cached last_full_update time
     *
     * @return \Cake\I18n\FrozenTime|null
     */
    private function getLastFullUpdateTime()
    {
        return Cache::read('last_full_update', self::CACHE_CONFIG);
    }
}
