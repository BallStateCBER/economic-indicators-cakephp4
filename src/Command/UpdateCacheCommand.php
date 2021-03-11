<?php
declare(strict_types=1);

namespace App\Command;

use App\Fetcher\EndpointGroups;
use App\Model\Table\StatisticsTable;
use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Helper;
use Cake\ORM\TableRegistry;

/**
 * UpdateCache command
 *
 * @property \App\Model\Table\StatisticsTable $statisticsTable
 * @property \Cake\Console\ConsoleIo $io
 * @property \Cake\Shell\Helper\ProgressHelper $progress
 */
class UpdateCacheCommand extends Command
{
    private ConsoleIo $io;
    private Helper $progress;
    private StatisticsTable $statisticsTable;

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
        $parser->setDescription('Refreshes the cache for data pulled from the statistics table');

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
        $this->io = $io;
        $this->progress = $io->helper('Progress');
        $this->statisticsTable = TableRegistry::getTableLocator()->get('Statistics');

        $endpointGroups = (new EndpointGroups())->getAll();
        $count = count($endpointGroups);
        foreach ($endpointGroups as $i => $endpointGroup) {
            $io->out(sprintf(
                'Processing %s (%s/%s)',
                $endpointGroup['title'],
                $i + 1,
                $count,
            ));
            $this->refreshDateRange($endpointGroup);
            $this->refreshLastDateData($endpointGroup);
            $this->refreshDateRangeData($endpointGroup);
        }

        $io->success('Finished');
    }

    /**
     * Deletes and re-generates cached date range for an endpoint group
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     */
    private function refreshDateRange(array $endpointGroup): void
    {
        $this->io->out(' - Refreshing date range');
        $cacheKey = $endpointGroup['title'] . '-range';
        Cache::delete($cacheKey);
        $this->statisticsTable->getDateRange($endpointGroup);
    }

    /**
     * Deletes and re-generates cached single-date data for an endpoint group
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     */
    private function refreshLastDateData(array $endpointGroup)
    {
        $this->io->out(' - Refreshing data for most recent date');
        $cacheKey = $endpointGroup['title'] . '-last';
        Cache::delete($cacheKey);
        $this->statisticsTable->getGroup($endpointGroup);
    }

    /**
     * Deletes and re-generates cached date-range data for an endpoint group
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     */
    private function refreshDateRangeData(mixed $endpointGroup)
    {
        $this->io->out(' - Refreshing data for all dates');
        $cacheKey = $endpointGroup['title'] . '-all';
        Cache::delete($cacheKey);
        $this->statisticsTable->getGroup($endpointGroup, true);
    }
}
