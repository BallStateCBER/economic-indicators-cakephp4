<?php
declare(strict_types=1);

namespace App\Command;

use App\Fetcher\EndpointGroups;
use App\Model\Entity\Metric;
use App\Model\Table\MetricsTable;
use App\Model\Table\StatisticsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Helper;
use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use fred_api;
use fred_api_exception;

/**
 * DataUpdater command.
 *
 * This command is intended to be run via a cron job and overwrite cached values every 24 hours.
 * This application's cache configuration has cached values retained for a longer period of time so that in the event
 * of an update failure, cached values are retained and used instead of forcing new data to be fetched during a user
 * request.
 *
 * @property \App\Model\Table\MetricsTable $metricsTable
 * @property \App\Model\Table\StatisticsTable $statisticsTable
 * @property \App\Model\Entity\Metric[] $metrics
 * @property \Cake\Console\ConsoleIo $io
 * @property \Cake\Shell\Helper\ProgressHelper $progress
 */
class DataUpdaterCommand extends Command
{
    private array $apiParameters;
    private array $metrics;
    private ConsoleIo $io;
    private const UNITS_CHANGE_FROM_1_YEAR_AGO = 'ch1';
    private const UNITS_PERCENT_CHANGE_FROM_1_YEAR_AGO = 'pc1';
    private const UNITS_VALUE = 'lin';
    private fred_api $api;
    private MetricsTable $metricsTable;
    private Helper $progress;
    private StatisticsTable $statisticsTable;

    /**
     * @var int The number of times to retry a failing API call after the first attempt
     */
    private int $apiRetryCount = 2;

    /**
     * @var float Seconds to wait between each API call
     */
    private float $rateThrottle = 1;

    /**
     * DataUpdaterCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Used in calls to file_exists() in the FredApi library
        if (!defined('FRED_API_ROOT')) {
            define('FRED_API_ROOT', ROOT . DS . 'lib' . DS . 'FredApi' . DS);
        }

        require_once ROOT . DS . 'lib' . DS . 'FredApi' . DS . 'fred_api.php';
        $apiKey = Configure::read('fred_api_key');
        try {
            $this->api = new fred_api($apiKey);
        } catch (fred_api_exception $e) {
            throw new InternalErrorException('Error creating FRED API object: ' . $e->getMessage());
        }

        $this->metricsTable = TableRegistry::getTableLocator()->get('Metrics');
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
        $this->io = $io;
        $this->progress = $io->helper('Progress');

        $groups = (new EndpointGroups())->getAll();
        foreach ($groups as $group) {
            $io->info($group['endpoints'][0]['group']);
            $this->loadMetrics($group);

            $io->out(' - Checking for updates');
            if (!$this->updateIsAvailable($group)) {
                $io->out(' - API does not yet have an update');
                continue;
            }

            try {
                $this->updateGroup($group);
            } catch (NotFoundException | fred_api_exception $e) {
                $io->error($e->getMessage());
            }
            $io->out(' - Done');
        }

        $io->success('Finished');
    }

    /**
     * Returns TRUE if the API has more recently-updated data than the database
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return bool
     * @throws \fred_api_exception
     */
    private function updateIsAvailable(array $endpointGroup): bool
    {
        $firstEndpoint = $endpointGroup['endpoints'][0];
        $endpointName = $firstEndpoint['id'];

        /** @var \App\Model\Entity\Metric $metric */
        $metric = $this->metricsTable
            ->find()
            ->where(['name' => $endpointName])
            ->first();
        if (!$metric) {
            $this->io->error('No metric record was found for ' . $endpointName);
            exit;
        }

        if (!$metric->last_updated) {
            return true;
        }

        $this->setEndpoint($firstEndpoint);
        $endpointMeta = $this->getEndpointMetadata();
        $responseUpdated = $endpointMeta['@attributes']['last_updated'];

        return (new FrozenTime($responseUpdated))->gt($metric->last_updated);
    }

    /**
     * Loads metrics into $this->metrics, creating them if necessary
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     * @throws \fred_api_exception
     */
    private function loadMetrics(array $endpointGroup)
    {
        foreach ($endpointGroup['endpoints'] as $endpoint) {
            $endpointName = $endpoint['id'];

            // Find existing metric
            $metric = $this->metricsTable
                ->find()
                ->where(['name' => $endpointName])
                ->first();
            if ($metric) {
                $this->metrics[$endpointName] = $metric;
                continue;
            }

            // Create missing metric
            $this->io->out(' - Adding ' . $endpointName . ' to metrics table');
            $this->setEndpoint($endpointName);
            $endpointMeta = $this->getEndpointMetadata();
            $data = [
                'name' => $endpointName,
                'units' => $endpointMeta['@attributes']['units'],
                'frequency' => $endpointMeta['@attributes']['frequency'],
            ];
            $metric = $this->metricsTable->newEntity($data);
            if (!$this->metricsTable->save($metric)) {
                $this->io->error('There was an error saving that metric. Details:');
                $this->io->out(print_r($metric->getErrors(), true));
                exit;
            }
            $this->metrics[$endpointName] = $metric;
        }
    }

    /**
     * Pauses execution for $this->rateThrottle seconds
     *
     * @return void
     */
    private function throttle()
    {
        $microseconds = (int)($this->rateThrottle * 1000000);
        usleep($microseconds);
    }

    /**
     * Sets the series_id parameter for the next API request
     *
     * @param string|array $endpointName Endpoint name (aka series ID) or array that contains 'id' key
     * @return void
     */
    public function setEndpoint(mixed $endpointName): void
    {
        if (is_array($endpointName)) {
            if (!isset($endpointName['id'])) {
                throw new InternalErrorException('Series ID not provided');
            }
            $endpointName = $endpointName['id'];
        }

        $this->apiParameters['series_id'] = $endpointName;
    }

    /**
     * Returns information about a data series provided by a given endpoint
     *
     * @param array $parameters Additional optional parameters
     * @return array|null
     * @throws \fred_api_exception
     * @link https://fred.stlouisfed.org/docs/api/fred/
     */
    public function getEndpointMetadata(array $parameters = []): ?array
    {
        /** @var \fred_api_series $api */
        $api = $this->api->factory('series');
        $parameters += $this->apiParameters;
        $this->throttle();

        for ($attempts = 1 + $this->apiRetryCount; $attempts > 0; $attempts--) {
            $finalAttempt = $attempts == 1;
            try {
                $response = $api->get($parameters);
                if (property_exists($response, 'series')) {
                    return (array)($response->series);
                }

                if ($finalAttempt) {
                    throw new NotFoundException('Metadata not found');
                }

                $this->io->error('Failed, retrying');
                continue;
            } catch (fred_api_exception $e) {
                if ($finalAttempt) {
                    throw $e;
                }

                $this->io->error('Failed, retrying');
                continue;
            }
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
    public function getObservations(array $parameters = []): array
    {
        for ($attempts = 1 + $this->apiRetryCount; $attempts > 0; $attempts++) {
            $finalAttempt = $attempts == 1;
            /** @var \fred_api_series $api */
            $api = $this->api->factory('series');
            $parameters += $this->apiParameters;
            $parameters['file_type'] = 'json';

            $this->throttle();
            $response = $api->observations($parameters);
            $response = json_decode($response);
            $responseIsValid = json_last_error() != JSON_ERROR_NONE;
            $responseIsValid = $responseIsValid && !is_object($response) || !property_exists($response, 'observations');
            if ($responseIsValid) {
                if ($finalAttempt) {
                    throw new NotFoundException();
                } else {
                    $this->io->error('Failed, retrying');
                    continue;
                }
            }

            $observations = $response->observations;

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
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     * @throws \fred_api_exception
     */
    public function updateGroup(array $endpointGroup): void
    {
        // Fetch from API
        $this->io->out(' - Retrieving from API...');
        foreach ($endpointGroup['endpoints'] as $endpoint) {
            $this->setEndpoint($endpoint);
            $endpointName = $endpoint['id'];
            $this->io->out(sprintf(' - %s > %s metadata', $endpoint['group'], $endpoint['name']));
            $endpointMeta = $this->getEndpointMetadata();
            $metric = $this->metrics[$endpointName];
            $this->apiParameters['sort_order'] = 'asc';

            $this->io->out('   - Values');
            $this->saveAllStatistics(
                observations: $this->getObservations(['units' => self::UNITS_VALUE]),
                metricId: $metric->id,
                dataTypeId: StatisticsTable::DATA_TYPE_VALUE,
            );

            $this->io->out('   - Changes');
            $this->saveAllStatistics(
                observations: $this->getObservations(['units' => self::UNITS_CHANGE_FROM_1_YEAR_AGO]),
                metricId: $metric->id,
                dataTypeId: StatisticsTable::DATA_TYPE_CHANGE,
            );

            $this->io->out('   - Percent changes');
            $this->saveAllStatistics(
                observations: $this->getObservations(['units' => self::UNITS_PERCENT_CHANGE_FROM_1_YEAR_AGO]),
                metricId: $metric->id,
                dataTypeId: StatisticsTable::DATA_TYPE_PERCENT_CHANGE,
            );

            $this->updateMetricUpdatedDate($metric, $endpointMeta['@attributes']['last_updated']);
        }
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
        $statistic = $this->statisticsTable
            ->find()
            ->where($data)
            ->first();

        $updating = false;
        if ($statistic) {
            $updating = true;
            $this->statisticsTable->patchEntity($statistic, ['value' => $value]);
        } else {
            $data['value'] = $value;
            $statistic = $this->statisticsTable->newEntity($data);
        }

        if (!$this->statisticsTable->save($statistic)) {
            $this->io->error(sprintf(
                'There was an error %s that statistic. Details:',
                $updating ? 'updating' : 'adding'
            ));
            $this->io->out(print_r($statistic->getErrors(), true));
            $this->io->out('Data:');
            $this->io->out(print_r($data, true));
            exit;
        }
    }

    /**
     * Updates the last_updated value for the provided metric
     *
     * @param \App\Model\Entity\Metric $metric Metric entity
     * @param string $lastUpdated A string representing the last_updated date
     * @return void
     */
    private function updateMetricUpdatedDate(Metric $metric, string $lastUpdated): void
    {
        $this->metricsTable->patchEntity($metric, ['last_updated' => new FrozenTime($lastUpdated)]);
        if (!$this->metricsTable->save($metric)) {
            $this->io->error('There was an error updating that metric. Details:');
            $this->io->out(print_r($metric->getErrors(), true));
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
        $this->progress->init([
            'total' => count($observations),
            'width' => 40,
        ]);
        $this->progress->draw();
        foreach ($observations as $observation) {
            $this->saveStatistic(
                metricId: $metricId,
                dataTypeId: $dataTypeId,
                date: new FrozenDate($observation['date']),
                value: $observation['value'],
            );
            $this->progress->increment()->draw();
        }
        $this->io->overwrite('   - Done');
    }
}
