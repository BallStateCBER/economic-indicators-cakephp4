<?php
declare(strict_types=1);

namespace App\Command;

use App\Endpoints\EndpointGroups;
use App\Model\Table\StatisticsTable;
use App\Spreadsheet\SpreadsheetSingleDate;
use App\Spreadsheet\SpreadsheetTimeSeries;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Helper;
use Cake\ORM\TableRegistry;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Exception as PhpOfficeException;

/**
 * MakeSpreadsheets command
 *
 * @property \App\Model\Table\StatisticsTable $statisticsTable
 * @property \Cake\Console\ConsoleIo $io
 * @property \Cake\Shell\Helper\ProgressHelper $progress
 */
class MakeSpreadsheetsCommand extends Command
{
    private bool $verbose = false;
    private ConsoleIo $io;
    private Helper $progress;
    private StatisticsTable $statisticsTable;

    /**
     * MakeSpreadsheetsCommand constructor
     *
     * @param \Cake\Console\ConsoleIo|null $io ConsoleIo object
     */
    public function __construct(?ConsoleIo $io = null)
    {
        parent::__construct();
        if ($io) {
            $this->io = $io;
            $this->progress = $io->helper('Progress');
        }
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
        $parser->setDescription('Refreshes the cache for data pulled from the statistics table');
        $parser->addOption('verbose', [
            'short' => 'v',
            'help' => 'Output information about memory usage',
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
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->io = $io;
        $this->progress = $io->helper('Progress');
        $this->verbose = (bool)$args->getOption('verbose');
        $endpointGroups = EndpointGroups::getAll();
        $count = count($endpointGroups);
        foreach ($endpointGroups as $i => $endpointGroup) {
            $io->info(sprintf(
                '%s (%s/%s)',
                $endpointGroup['title'],
                $i + 1,
                $count,
            ));
            $this->makeSpreadsheetsForGroup($endpointGroup);
        }

        $io->success('Finished');
    }

    /**
     * Re-generates cached statistic and date ranges for this endpoint group
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     */
    public function makeSpreadsheetsForGroup(array $endpointGroup): void
    {
        $this->showMemoryUsage();
        foreach ([false, true] as $isTimeSeries) {
            try {
                $filename = $this->statisticsTable->getFilename($endpointGroup, $isTimeSeries);

                $this->io->out(sprintf(
                    '- Creating %s spreadsheet',
                    $isTimeSeries ? 'time-series' : 'single-date'
                ));
                $spreadsheet = $isTimeSeries
                    ? new SpreadsheetTimeSeries($endpointGroup)
                    : new SpreadsheetSingleDate($endpointGroup);
                $spreadsheetWriter = IOFactory::createWriter($spreadsheet->get(), 'Xlsx');
                $this->io->out('- Saving file');
                $spreadsheetWriter->save(WWW_ROOT . 'spreadsheets' . DS . $filename);
                unset($spreadsheet, $spreadsheetWriter);
            } catch (Exception | PhpOfficeException $e) {
                $this->io->error('There was an error generating that spreadsheet. Details:');
                $this->io->out($e->getMessage());
                exit;
            }
        }
    }

    /**
     * Displays the peak memory usage since the start of this script
     *
     * @return void
     */
    private function showMemoryUsage()
    {
        if (!$this->verbose) {
            return;
        }
        $peakMemoryKb = number_format(round(memory_get_peak_usage() / 1024));
        $currentMemoryKb = number_format(round(memory_get_usage() / 1024));
        $this->io->out("- Current and peak memory usage: {$currentMemoryKb}KB, {$peakMemoryKb}KB");
    }
}
