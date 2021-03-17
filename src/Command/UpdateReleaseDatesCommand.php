<?php
declare(strict_types=1);

namespace App\Command;

use App\Fetcher\EndpointGroups;
use App\Model\Entity\Metric;
use App\Model\Table\MetricsTable;
use App\Model\Table\ReleasesTable;
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
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);
        $this->metricsTable = TableRegistry::getTableLocator()->get('Metrics');
        $this->releasesTable = TableRegistry::getTableLocator()->get('Releases');
        $this->seriesApi = $this->api->factory('series');
        $this->releaseApi = $this->api->factory('release');
        $endpointGroups = (new EndpointGroups())->getAll();
        foreach ($endpointGroups as $endpointGroup) {
            $this->io->info($endpointGroup['title']);
            foreach ($endpointGroup['endpoints'] as $endpoint) {
                $releaseId = $this->getReleaseId($endpoint);
                $this->throttle();

                $releaseDates = $this->getUpcomingReleaseDates($releaseId);
                $metric = $this->getMetric($endpoint['id']);
                $this->removeInvalidReleases($releaseDates, $metric->id);
                $this->addMissingReleases($releaseDates, $metric->id);
                $this->throttle();
            }
            $this->io->out();
        }
        $this->io->success('Done');
    }

    /**
     * Fetches and returns the release ID from the API
     *
     * This is fetched from the API every time instead of being stored in the database because it's unclear if the FRED
     * API ever changes a series's release ID
     *
     * @param array $endpoint API endpoint information
     * @return int
     */
    private function getReleaseId(mixed $endpoint): int
    {
        $this->io->out($endpoint['name']);
        $endpointName = $endpoint['id'];
        $this->io->out(' - Fetching release ID');
        $series = $this->seriesApi->release([
            'file_type' => 'json',
            'series_id' => $endpointName,
        ]);
        $series = json_decode($series);

        return $series->releases[0]->id;
    }

    /**
     * Fetches and returns upcoming release dates from the API
     *
     * @param int $releaseId Release ID, as fetched from the API
     * @return array
     */
    private function getUpcomingReleaseDates(int $releaseId): array
    {
        $this->io->out(' - Fetching upcoming release dates');
        $releaseDates = $this->releaseApi->dates([
            'file_type' => 'json',
            'release_id' => $releaseId,
            'realtime_start' => date('Y-m-d'),
            'include_release_dates_with_no_data' => 'true',
        ]);
        $releaseDates = json_decode($releaseDates);
        $releaseDates = $releaseDates->release_dates;

        return Hash::extract($releaseDates, '{n}.date');
    }

    /**
     * Returns a metric matching $endpointName
     *
     * @param string $endpointName A valid "series_id" string for use with the API
     * @return \App\Model\Entity\Metric
     */
    private function getMetric(string $endpointName): Metric
    {
        /** @var \App\Model\Entity\Metric $metric */
        $metric = $this->metricsTable
            ->find()
            ->where(['name' => $endpointName])
            ->first();

        if (!$metric) {
            $this->io->error('No metric record was found for ' . $endpointName);
            exit;
        }

        return $metric;
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
                    return $exp
                        ->gte('date', $today)
                        ->notIn('date', $releaseDates);
                },
            ])
            ->all();

        if (!$invalidReleases->count()) {
            return;
        }

        $this->io->out(' - Removing release dates that are no longer valid');
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
            $this->io->out(' - No new releases to add');

            return;
        }

        $this->io->out(sprintf(
            ' - Adding new release %s',
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
