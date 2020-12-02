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

/**
 * DataUpdater command.
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

        $fetcher = new Fetcher();
        $groups = (new SeriesGroups())->getAll();
        foreach ($groups as $group) {
            $io->info(sprintf('Processing %s...', $group['endpoints'][0]['var']));
            try {
                $fetcher->getCachedValuesAndChanges($group);
            } catch (NotFoundException | fred_api_exception $e) {
                $io->err($e->getMessage());
            }
            $io->out(' - Done');
        }

        $io->success('Finished');
    }
}
