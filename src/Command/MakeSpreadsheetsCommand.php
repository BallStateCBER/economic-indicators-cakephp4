<?php
declare(strict_types=1);

namespace App\Command;

use App\Endpoints\EndpointGroups;
use App\Model\Entity\Spreadsheet;
use App\Model\Table\SpreadsheetsTable;
use App\Model\Table\StatisticsTable;
use App\Spreadsheet\SpreadsheetSingleDate;
use App\Spreadsheet\SpreadsheetTimeSeries;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Helper;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Exception as PhpOfficeException;

/**
 * MakeSpreadsheets command
 *
 * @property \App\Model\Table\SpreadsheetsTable $spreadsheetsTable
 * @property \App\Model\Table\StatisticsTable $statisticsTable
 * @property \Cake\Console\ConsoleIo $io
 * @property \Cake\Shell\Helper\ProgressHelper $progress
 */
class MakeSpreadsheetsCommand extends Command
{
    private bool $verbose = false;
    private ConsoleIo $io;
    private Helper $progress;
    private SpreadsheetsTable $spreadsheetsTable;
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
        $this->spreadsheetsTable = TableRegistry::getTableLocator()->get('Spreadsheets');
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
        $this->showMemoryUsage();
        foreach ($endpointGroups as $i => $endpointGroup) {
            $io->info(sprintf(
                '%s (%s/%s)',
                $endpointGroup['title'],
                $i + 1,
                $count,
            ));
            $this->makeSpreadsheetsForGroup($endpointGroup);
            $this->showMemoryUsage();
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
        foreach ([false, true] as $isTimeSeries) {
            try {
                $newFilename = $this->statisticsTable->getFilename($endpointGroup, $isTimeSeries);
                $spreadsheetRecord = $this->getSpreadsheetRecord($endpointGroup['title'], $isTimeSeries);
                $oldFilename = $spreadsheetRecord ? $spreadsheetRecord->filename : null;

                $this->io->out(sprintf(
                    '- Creating %s spreadsheet',
                    $isTimeSeries ? 'time-series' : 'single-date'
                ));
                $spreadsheet = $isTimeSeries
                    ? new SpreadsheetTimeSeries($endpointGroup)
                    : new SpreadsheetSingleDate($endpointGroup);

                $this->io->out('- Saving file: ' . $newFilename);
                $spreadsheetWriter = IOFactory::createWriter($spreadsheet->get(), 'Xlsx');
                $spreadsheetWriter->save(SpreadsheetsTable::FILE_PATH . $newFilename);
                unset($spreadsheet, $spreadsheetWriter);

                $this->io->out('- Updating spreadsheet database record');
                $this->updateSpreadsheetDbRecord($endpointGroup, $isTimeSeries, $newFilename);

                $oldFileNeedsDeleted = $oldFilename && $oldFilename != $newFilename;
                $oldFilePath = SpreadsheetsTable::FILE_PATH . $oldFilename;
                if ($oldFileNeedsDeleted && file_exists($oldFilePath)) {
                    $this->io->out('- Removing old file: ' . $oldFilename);
                    unlink($oldFilePath);
                }
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

    /**
     * Updates this spreadsheet's record in the database, creating a new record if necessary
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @param bool $isTimeSeries TRUE if this is a time-series spreadsheet
     * @param string $filename The filename to save to the database
     * @return void
     */
    private function updateSpreadsheetDbRecord(array $endpointGroup, bool $isTimeSeries, string $filename)
    {
        $groupName = $endpointGroup['title'];

        // Get existing record
        $spreadsheetRecord = $this->getSpreadsheetRecord($groupName, $isTimeSeries);
        if ($spreadsheetRecord) {
            $spreadsheetRecord = $this->spreadsheetsTable->patchEntity($spreadsheetRecord, compact('filename'));
            if (!$this->spreadsheetsTable->save($spreadsheetRecord)) {
                $this->io->error('There was an error updating that spreadsheet\'s DB record. Details:');
                $this->io->out(print_r($spreadsheetRecord->getErrors(), true));
                exit;
            }

            return;
        }

        // Or create new record
        $spreadsheetRecord = $this->spreadsheetsTable->newEntity([
            'filename' => $filename,
            'group_name' => $groupName,
            'is_time_series' => $isTimeSeries,
        ]);
        if (!$this->spreadsheetsTable->save($spreadsheetRecord)) {
            $this->io->error('There was an error saving that spreadsheet\'s record to the database. Details:');
            $this->io->out(print_r($spreadsheetRecord->getErrors(), true));
            exit;
        }
    }

    /**
     * Returns a spreadsheet record, or NULL if the specified record doesn't exist
     *
     * @param string $groupName A string used to uniquely identify an endpoint group
     * @param bool $isTimeSeries TRUE if this is a time-series spreadsheet
     * @return \App\Model\Entity\Spreadsheet|\Cake\Datasource\EntityInterface|null
     */
    private function getSpreadsheetRecord(string $groupName, bool $isTimeSeries): Spreadsheet | EntityInterface | null
    {
        return $this->spreadsheetsTable
            ->find()
            ->where([
                'group_name' => $groupName,
                'is_time_series' => $isTimeSeries,
            ])
            ->first();
    }
}
