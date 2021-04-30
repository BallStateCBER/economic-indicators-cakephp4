<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Spreadsheet;
use App\Model\Table\SpreadsheetsTable;
use App\Model\Table\StatisticsTable;
use App\Spreadsheet\SpreadsheetSingleDate;
use App\Spreadsheet\SpreadsheetTimeSeries;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
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
class MakeSpreadsheetsCommand extends AppCommand
{
    private bool $verbose = false;
    private SpreadsheetsTable $spreadsheetsTable;
    private StatisticsTable $statisticsTable;

    /**
     * @var string In --auto mode, wait at least this long between attempts to generate a given spreadsheet
     */
    private string $waitUntilRetryingGeneration = '10 minutes';

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
        $parser->addOption('choose', [
            'short' => 'c',
            'help' => 'Choose a specific endpoint group instead of cycling through all',
            'boolean' => true,
        ]);
        $parser->addOption('auto', [
            'short' => 'a',
            'help' => 'Makes the script either loop through all endpoint groups or only generate spreadsheets for a ' .
                'single endpoint group if its previous spreadsheet-generation started at least ' .
                "$this->waitUntilRetryingGeneration ago",
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
        parent::execute($args, $io);
        $start = new FrozenTime();
        $this->io = $io;
        $this->progress = $io->helper('Progress');
        $this->verbose = (bool)$args->getOption('verbose');
        $selectedSpreadsheets = $this->getSelectedSpreadsheets($args);
        $count = count($selectedSpreadsheets);
        $this->showMemoryUsage();
        $i = 1;
        $this->toSlack('Regenerating spreadsheets');
        foreach ($selectedSpreadsheets as $spreadsheetData) {
            $endpointGroup = $spreadsheetData['endpointGroup'];
            $spreadsheetEntity = $spreadsheetData['entity'];
            $io->info(sprintf(
                '%s (%s/%s)',
                $endpointGroup['title'],
                $i,
                $count,
            ));
            $this->generateSpreadsheetFile($spreadsheetEntity, $endpointGroup);
            $this->showMemoryUsage();
            $i++;
        }

        $timeAgo = $start->timeAgoInWords();
        $this->toConsoleAndSlack("Finished (started $timeAgo)", 'success');
    }

    /**
     * Re-generates cached statistic and date ranges for this endpoint group
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     */
    /*
    public function makeSpreadsheetsForGroup(array $endpointGroup): void
    {
        $this->toSlack('- Creating spreadsheets for ' . $endpointGroup['title']);
        foreach ([false, true] as $isTimeSeries) {
            try {
                $newFilename = $this->statisticsTable->getFilename($endpointGroup, $isTimeSeries);
                $spreadsheetEntity = $this->getSpreadsheetRecord($endpointGroup['title'], $isTimeSeries);
                if (!$spreadsheetEntity) {
                    $spreadsheetEntity = $this->createSpreadsheet($endpointGroup['title'], $isTimeSeries);
                    if (!$spreadsheetEntity) {
                        exit;
                    }
                }
                $spreadsheetEntity = $this->spreadsheetsTable->recordFileGenerationStartTime($spreadsheetEntity);
                $oldFilename = $spreadsheetEntity->filename;

                $this->toConsoleAndSlack(sprintf(
                    '- Creating %s spreadsheet',
                    $isTimeSeries ? 'time-series' : 'single-date'
                ));
                $spreadsheetData = $isTimeSeries
                    ? new SpreadsheetTimeSeries($endpointGroup)
                    : new SpreadsheetSingleDate($endpointGroup);

                $this->toConsoleAndSlack('- Saving file: ' . $newFilename);
                $spreadsheetWriter = IOFactory::createWriter($spreadsheetData->get(), 'Xlsx');
                $spreadsheetWriter->save(SpreadsheetsTable::FILE_PATH . $newFilename);
                unset($spreadsheetWriter);

                $this->toConsoleAndSlack('- Updating spreadsheet database record');
                $this->updateSpreadsheetFilename($endpointGroup, $isTimeSeries, $newFilename);

                $oldFileNeedsDeleted = $oldFilename && $oldFilename != $newFilename;
                $oldFilePath = SpreadsheetsTable::FILE_PATH . $oldFilename;
                if ($oldFileNeedsDeleted && file_exists($oldFilePath)) {
                    $this->toConsoleAndSlack('- Removing old file: ' . $oldFilename);
                    unlink($oldFilePath);
                }
            } catch (Exception | PhpOfficeException $e) {
                $this->toConsoleAndSlack('There was an error generating that spreadsheet. Details:', 'error');
                $this->toConsoleAndSlack($e->getMessage());
                exit;
            }
            $this->spreadsheetsTable->recordFileGenerationDone($spreadsheetEntity);
        }
    }*/

    /**
     * Re-generates cached statistic and date ranges for this endpoint group
     *
     * @param \App\Model\Entity\Spreadsheet|\Cake\Datasource\EntityInterface $spreadsheetEntity Spreadsheet entity
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     */
    public function generateSpreadsheetFile(
        Spreadsheet | EntityInterface $spreadsheetEntity,
        array $endpointGroup
    ): void {
        $this->toSlack(
            "- Creating spreadsheet for $spreadsheetEntity->group_name " .
            $spreadsheetEntity->is_time_series ? '(time series)' : '(single date)',
        );
        try {
            $newFilename = $this->statisticsTable->getFilename($endpointGroup, $spreadsheetEntity->is_time_series);
            $spreadsheetEntity = $this->spreadsheetsTable->recordFileGenerationStartTime($spreadsheetEntity);
            $oldFilename = $spreadsheetEntity->filename;
            $spreadsheetData = $spreadsheetEntity->is_time_series
                ? new SpreadsheetTimeSeries($endpointGroup)
                : new SpreadsheetSingleDate($endpointGroup);

            $this->toConsoleAndSlack('- Saving file: ' . $newFilename);
            $spreadsheetWriter = IOFactory::createWriter($spreadsheetData->get(), 'Xlsx');
            $spreadsheetWriter->save(SpreadsheetsTable::FILE_PATH . $newFilename);
            unset($spreadsheetWriter);

            $this->toConsoleAndSlack('- Updating spreadsheet database record');
            $this->updateSpreadsheetFilename($spreadsheetEntity, $newFilename);

            $oldFileNeedsDeleted = $oldFilename && $oldFilename != $newFilename;
            $oldFilePath = SpreadsheetsTable::FILE_PATH . $oldFilename;
            if ($oldFileNeedsDeleted && file_exists($oldFilePath)) {
                $this->toConsoleAndSlack('- Removing old file: ' . $oldFilename);
                unlink($oldFilePath);
            }
        } catch (Exception | PhpOfficeException $e) {
            $this->toConsoleAndSlack('There was an error generating that spreadsheet. Details:', 'error');
            $this->toConsoleAndSlack($e->getMessage());
            exit;
        }
        $this->spreadsheetsTable->recordFileGenerationDone($spreadsheetEntity);
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
     * @param \App\Model\Entity\Spreadsheet $spreadsheet Spreadsheet entity
     * @param string $filename The filename to save to the database
     * @return void
     */
    private function updateSpreadsheetFilename(Spreadsheet $spreadsheet, string $filename)
    {
        $spreadsheet = $this->spreadsheetsTable->patchEntity($spreadsheet, compact('filename'));
        if (!$this->spreadsheetsTable->save($spreadsheet)) {
            $this->toConsoleAndSlack(
                'There was an error updating that spreadsheet\'s database record. Details:',
                'error',
            );
            $this->toConsoleAndSlack(print_r($spreadsheet->getErrors(), true));
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

    /**
     * Returns an array of arrays, each of which contains a spreadsheet entity and its associated endpoint group
     *
     * @param \Cake\Console\Arguments $args Console arguments
     * @return array
     */
    protected function getSelectedSpreadsheets(Arguments $args): array
    {
        $retval = [];

        // Get all spreadsheets in applicable endpoints marked for needing updates
        if (!$args->getOption('auto')) {
            $selectedEndpointGroups = $this->getSelectedEndpointGroups($args);
            foreach ($selectedEndpointGroups as $endpointGroup) {
                $spreadsheets = $this->spreadsheetsTable
                    ->find()
                    ->where([
                        'group_name' => $endpointGroup['title'],
                        'needs_update' => true,
                    ])
                    ->all();
                foreach ($spreadsheets as $entity) {
                    $retval[] = compact('endpointGroup', 'entity');
                }
            }

            return $retval;
        }

        $this->toConsoleAndSlack('Running in --auto mode');

        // Get one spreadsheet marked as having failed file generation
        $query = $this->spreadsheetsTable->find('failedGeneration', ['wait' => $this->waitUntilRetryingGeneration]);
        $allEndpointGroups = $this->getAllEndpointGroups();
        if ($query->count() > 0) {
            /** @var \App\Model\Entity\Spreadsheet $spreadsheet */
            $spreadsheet = $query->first();
            $this->toConsoleAndSlack(sprintf(
                'Regenerating %s %s spreadsheet, which started file generation at %s and never completed.',
                $spreadsheet->group_name,
                $spreadsheet->is_time_series ? 'time-series' : 'single-date',
                $spreadsheet->file_generation_started->timeAgoInWords(),
            ));
            $spreadsheets = [$spreadsheet];

        // Or if none are found, get all spreadsheets that need updated
        } else {
            $spreadsheets = $this->spreadsheetsTable
                ->find()
                ->where(['needs_update' => true])
                ->all();

            $count = $spreadsheets->count();
            if ($count == 0) {
                $this->toConsoleAndSlack('No spreadsheets are marked as having updated data.');

                return [];
            }

            $this->toConsoleAndSlack(sprintf(
                'Regenerating %s %s which are marked for having updated data',
                $count,
                $count == 1 ? 'spreadsheet' : 'spreadsheets',
            ));
        }

        // Return spreadsheets with associated endpoint groups
        foreach ($spreadsheets as $spreadsheet) {
            $spreadsheetsEndpointGroup = null;
            foreach ($allEndpointGroups as $endpointGroup) {
                if ($endpointGroup['title'] == $spreadsheet->group_name) {
                    $spreadsheetsEndpointGroup = $endpointGroup;
                }
            }

            if (!$spreadsheetsEndpointGroup) {
                $this->toConsoleAndSlack(
                    "Error: No endpoint group found associated with group name $spreadsheet->group_name). " .
                    'If an endpoint group was recently renamed, consider removing spreadsheet record #' .
                    "$spreadsheet->id.",
                    'error'
                );
                exit;
            }

            $retval[] = [
                'endpointGroup' => $spreadsheetsEndpointGroup,
                'entity' => $spreadsheet,
            ];
        }

        return $retval;
    }
}
