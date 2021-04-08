<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
use App\Model\Table\StatisticsTable;
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
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function __construct(array $endpointGroup)
    {
        parent::__construct($endpointGroup);
        $this->isTimeSeries = false;

        $columnTitles = [
            'Metric',
            $this->firstMetric->units,
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

        foreach ($this->endpointGroup['endpoints'] as $endpoint) {
            $rowData = [];
            $metric = $this->metricsTable->getFromSeriesId($endpoint['seriesId']);
            foreach (StatisticsTable::DATA_TYPES as $dataTypeId) {
                $rowData['statistics'][$dataTypeId] = $this->statisticsTable->getByMetricAndType(
                    metricId: $metric->id,
                    dataTypeId: $dataTypeId,
                    all: $this->isTimeSeries,
                    withCache: true
                );
            }
            unset($metric);

            $this
                ->writeRow([
                    $endpoint['name'],
                    Formatter::formatValue(
                        $rowData['statistics'][StatisticsTable::DATA_TYPE_VALUE]['value'],
                        $this->prepend
                    ),
                    Formatter::formatValue(
                        $rowData['statistics'][StatisticsTable::DATA_TYPE_CHANGE]['value'],
                        $this->prepend
                    ),
                    Formatter::formatValue(
                        $rowData['statistics'][StatisticsTable::DATA_TYPE_PERCENT_CHANGE]['value']
                    ) . '%',
                ])
                ->alignHorizontal('right', 2);

            // Write date explicitly as a string so it doesn't get reformatted into a different date format by Excel
            $date = Formatter::getFormattedDate(
                $rowData['statistics'][StatisticsTable::DATA_TYPE_VALUE]['date'],
                $this->frequency,
            );
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
