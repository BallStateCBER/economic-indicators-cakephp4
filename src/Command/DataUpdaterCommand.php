<?php
declare(strict_types=1);

namespace App\Command;

use App\Fetcher\Fetcher;
use App\Fetcher\SeriesGroups;
use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use fred_api_exception;
use SplFileInfo;

/**
 * DataUpdater command.
 *
 * This command is intended to be run via a cron job and overwrite cached values every 24 hours.
 * This application's cache configuration has cached values retained for a longer period of time so that in the event
 * of an update failure, cached values are retained and used instead of forcing new data to be fetched during a user
 * request.
 */
class DataUpdaterCommand extends Command
{
    private Fetcher $fetcher;

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
        $parser->setDescription('Updates API-fetched data for any pages with expired caches');

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
        $this->fetcher = new Fetcher($io);
        $groups = (new SeriesGroups())->getAll();
        foreach ($groups as $group) {
            $io->info($group['endpoints'][0]['var']);
            $expired = $this->waitingPeriodHasPassed($group['cacheKey']);
            if (!$expired) {
                $io->out(' - Data retrieved too recently to try again');
                continue;
            }
            $io->out(' - Checking for updates');
            if (!$this->updateIsAvailable($group)) {
                $io->out(' - API does not yet have an update');
                continue;
            }

            try {
                $this->fetcher->getCachedValuesAndChanges($group, true);
            } catch (NotFoundException | fred_api_exception $e) {
                $io->error($e->getMessage());
            }
            $io->out(' - Done');
        }

        $io->success('Finished');
    }

    /**
     * Returns TRUE if enough time has passed to check for updates
     *
     * @param string $cacheKey Cache key
     * @return bool
     */
    private function waitingPeriodHasPassed(string $cacheKey)
    {
        $cacheFile = new SplFileInfo(CACHE . 'observations' . DS . 'observations_' . $cacheKey);
        if (!$cacheFile->isFile()) {
            return true;
        }
        $age = time() - $cacheFile->getMTime();
        $waitingPeriod = 60 * 60 * 23; // 23 hours

        return $age > $waitingPeriod;
    }

    /**
     * Returns TRUE if the API has more recently-updated data than the cache
     *
     * @param array $seriesGroup Array of data about a group of data series
     * @return bool
     * @throws \fred_api_exception
     */
    private function updateIsAvailable(array $seriesGroup)
    {
        $cacheKey = $seriesGroup['cacheKey'];
        $cachedData = Cache::read($cacheKey, 'observations');
        if (!$cachedData) {
            return true;
        }

        $firstSeries = $seriesGroup['endpoints'][0];
        $this->fetcher->setSeries($firstSeries);
        $seriesResponse = $this->fetcher->getSeries();
        $seriesMeta = (array)($seriesResponse->series);
        $responseUpdated = $seriesMeta['@attributes']['last_updated'];
        $cachedDate = new FrozenTime($cachedData['updated']);

        return (new FrozenTime($responseUpdated))->gt($cachedDate);
    }
}
