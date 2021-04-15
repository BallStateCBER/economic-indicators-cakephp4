<?php
declare(strict_types=1);

namespace App\Command;

use App\Endpoints\EndpointGroups;
use App\Model\Table\MetricsTable;
use App\Model\Table\ReleasesTable;
use Cake\Cache\Cache;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use fred_api_release;
use fred_api_series;

/**
 * UpdateReleaseDates command
 *
 * Updates the releases table
 *
 * @property \App\Model\Table\MetricsTable $metricsTable
 * @property \App\Model\Table\ReleasesTable $releasesTable
 */
class UpdateReleaseDatesCommand extends AppCommand
{
    private fred_api_release $releaseApi;
    private fred_api_series $seriesApi;
    private MetricsTable $metricsTable;
    private ReleasesTable $releasesTable;

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
        $parser->setDescription('Adds any new releases to the Releases table');

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @throws \fred_api_exception
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);
        $this->metricsTable = TableRegistry::getTableLocator()->get('Metrics');
        $this->releasesTable = TableRegistry::getTableLocator()->get('Releases');
        $this->seriesApi = $this->api->factory('series');
        $this->releaseApi = $this->api->factory('release');
        $endpointGroups = EndpointGroups::getAll();
        foreach ($endpointGroups as $endpointGroup) {
            $this->io->info($endpointGroup['title']);
            foreach ($endpointGroup['endpoints'] as $seriesId => $name) {
                $releaseId = $this->getReleaseId($seriesId, $name);
                $releaseDates = $this->getUpcomingReleaseDates($releaseId);
                $metric = $this->metricsTable->getFromSeriesId($seriesId);
                $this->removeInvalidReleases($releaseDates, $metric->id);
                $this->addMissingReleases($releaseDates, $metric->id);
            }
            $this->io->out();
        }
        $this->io->out('Rebuilding cache');
        Cache::clear(ReleasesTable::CACHE_CONFIG);
        $this->releasesTable->getNextReleaseDates();
        $this->io->success('Done');
    }

    /**
     * Fetches and returns the release ID from the API
     *
     * This is fetched from the API every time instead of being stored in the database because it's unclear if the FRED
     * API ever changes a series's release ID
     *
     * @param string $seriesId A FRED API series_id argument
     * @param string $name The name of this endpoint
     * @return int
     */
    private function getReleaseId(string $seriesId, string $name): int
    {
        $this->io->out($name);
        $this->io->out('- Fetching release ID');
        for ($attempts = 1 + $this->apiRetryCount; $attempts > 0; $attempts--) {
            $finalAttempt = $attempts == 1;
            $response = $this->seriesApi->release([
                'file_type' => 'json',
                'series_id' => $seriesId,
            ]);
            $responseObj = $this->decodeResponse(
                response: $response,
                requiredProperty: 'releases',
                haltOnError: $finalAttempt,
            );
            $this->throttle();
            if ($responseObj) {
                break;
            }
        }

        return $responseObj->releases[0]->id;
    }

    /**
     * Fetches and returns upcoming release dates from the API
     *
     * @param int $releaseId Release ID, as fetched from the API
     * @return array
     */
    private function getUpcomingReleaseDates(int $releaseId): array
    {
        $this->io->out('- Fetching upcoming release dates');
        for ($attempts = 1 + $this->apiRetryCount; $attempts > 0; $attempts--) {
            $finalAttempt = $attempts == 1;
            $this->throttle();
            $response = $this->releaseApi->dates([
                'file_type' => 'json',
                'release_id' => $releaseId,
                'realtime_start' => date('Y-m-d'),
                'include_release_dates_with_no_data' => 'true',
            ]);
            $responseObj = $this->decodeResponse(
                response: $response,
                requiredProperty: 'release_dates',
                haltOnError: $finalAttempt,
            );
            if ($responseObj) {
                break;
            }
        }

        return Hash::extract($responseObj->release_dates, '{n}.date');
    }

    /**
     * Removes any of this metric's upcoming releases that aren't included in $releaseDates
     *
     * This is meant to prevent invalid dates from lingering in the database after data sources change a release date
     *
     * @param string[] $releaseDates An array of YYYY-MM-DD date strings
     * @param int $metricId Metric ID
     * @return void
     */
    private function removeInvalidReleases(array $releaseDates, int $metricId)
    {
        $today = new FrozenDate();
        $invalidReleases = $this->releasesTable
            ->find()
            ->where([
                'metric_id' => $metricId,
                function (QueryExpression $exp) use ($releaseDates, $today) {
                    $exp->gte('date', $today);
                    if ($releaseDates) {
                        $exp->notIn('date', $releaseDates);
                    }

                    return $exp;
                },
            ])
            ->all();

        if (!$invalidReleases->count()) {
            return;
        }

        $this->io->out('- Removing release dates that are no longer valid');
        foreach ($invalidReleases as $release) {
            $this->io->out(sprintf(
                '   Release #%s (%s)',
                $release->id,
                $release->date,
            ));
            if (!$this->releasesTable->delete($release)) {
                $this->io->error('There was an error removing that release. Details:');
                $this->io->out(print_r($release->getErrors(), true));
            }
        }
    }

    /**
     * Adds records to the releases table if they aren't already present
     *
     * @param string[] $releaseDates An array of YYYY-MM-DD date strings
     * @param int $metricId Metric ID
     * @return void
     */
    private function addMissingReleases(array $releaseDates, int $metricId)
    {
        $newReleases = [];
        foreach ($releaseDates as $date) {
            $data = [
                'metric_id' => $metricId,
                'date' => $date,
            ];
            $releaseIsSaved = $this->releasesTable->exists($data);
            if ($releaseIsSaved) {
                continue;
            }
            $newReleases[] = $this->releasesTable->newEntity($data);
        }

        if (!$newReleases) {
            $this->io->out('- No new releases to add');

            return;
        }

        $this->io->out(sprintf(
            '- Adding new release %s',
            count($newReleases) > 1 ? 'dates' : 'date'
        ));
        foreach ($newReleases as $release) {
            $this->io->out('   ' . $release->date->format('Y-m-d'));
            if (!$this->releasesTable->save($release)) {
                $this->io->error('There was an error saving that release. Details:');
                $this->io->out(print_r($release->getErrors(), true));
            }
        }
    }
}
