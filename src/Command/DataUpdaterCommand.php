<?php
declare(strict_types=1);

namespace App\Command;

use App\Fetcher\Fetcher;
use App\Fetcher\SeriesGroups;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Http\Exception\NotFoundException;
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
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->info('Updating data for pages with expired caches');
        $io->out();

        $fetcher = new Fetcher($io);
        $groups = (new SeriesGroups())->getAll();
        foreach ($groups as $group) {
            $io->info(sprintf('Processing %s...', $group['endpoints'][0]['var']));
            $expired = $this->cachedValueIsExpired($group['cacheKey']);
            try {
                $fetcher->getCachedValuesAndChanges($group, $expired);
            } catch (NotFoundException | fred_api_exception $e) {
                $io->error($e->getMessage());
            }
            $io->out(' - Done');
        }

        $io->success('Finished');
    }

    /**
     * Returns TRUE if the cached value should be overwritten
     *
     * @param string $cacheKey Cache key
     * @return bool
     */
    private function cachedValueIsExpired(string $cacheKey)
    {
        $cacheFile = new SplFileInfo(CACHE . 'observations' . DS . 'observations_' . $cacheKey);
        if (!$cacheFile->isFile()) {
            return true;
        }
        $age = time() - $cacheFile->getMTime();
        $cacheDuration = 60 * 60 * 23; // 23 hours (does not necessarily reflect Configure-level setting)

        return $age > $cacheDuration;
    }
}
