<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
use App\Model\Table\StatisticsTable;
use Cake\ORM\TableRegistry;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Class SpreadsheetSingleDate
 *
 * Generates a spreadsheet with data pertaining to only the most recent available date
 *
 * @package App\Spreadsheet
 */
class SpreadsheetSingleDate extends Spreadsheet
{
    /**
     * Spreadsheet constructor.
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @param array|bool $data Data, or FALSE if not found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function __construct(array $endpointGroup, array | bool $data)
    {
        parent::__construct($data);

        /** @var \App\Model\Table\MetricsTable $metricsTable */
        $metricsTable = TableRegistry::getTableLocator()->get('Metrics');
        $metric = $metricsTable->getFirstForEndpointGroup($endpointGroup);
        $columnTitles = [
            'Metric',
            $metric->units,
            'Change from One Year Prior',
            'Percent Change from One Year Prior',
            'Date',
        ];
        $this
            ->setUpMetaAndHeaders($endpointGroup['title'], $columnTitles)
            ->nextRow()
            ->writeRow($columnTitles)
            ->styleRow([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'outline' => ['style' => Border::BORDER_THIN],
                ],
                'font' => ['bold' => true],
            ])
            ->nextRow();

        $prepend = Formatter::getPrepend($metric->units);
        $frequency = $metricsTable->getFrequency($endpointGroup);
        foreach ($data as $endpointName => $endpoint) {
            $this
                ->writeRow([
                    $endpointName,
                    Formatter::formatValue($endpoint[StatisticsTable::DATA_TYPE_VALUE]['value'], $prepend),
                    Formatter::formatValue($endpoint[StatisticsTable::DATA_TYPE_CHANGE]['value'], $prepend),
                    Formatter::formatValue($endpoint[StatisticsTable::DATA_TYPE_PERCENT_CHANGE]['value']) . '%',
                ])
                ->alignHorizontal('right', 2);

            // Write date explicitly as a string so it doesn't get reformatted into a different date format by Excel
            $date = Formatter::getFormattedDate($endpoint[StatisticsTable::DATA_TYPE_VALUE]['date'], $frequency);
            $dateCol = 5;
            $cell = $this->getColumnKey($dateCol) . $this->currentRow;
            $this->objPHPExcel
                ->getActiveSheet()
                ->getCell($cell)
                ->setValueExplicit($date, DataType::TYPE_STRING);

            $this->nextRow();
        }

        $this->setCellWidth();
    }
}
