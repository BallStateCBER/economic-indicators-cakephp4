<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
use App\Model\Table\StatisticsTable;
use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Hash;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Class SpreadsheetTimeSeries
 *
 * Generates a spreadsheet with data pertaining to all available dates
 *
 * @package App\Spreadsheet
 */
class SpreadsheetTimeSeries extends Spreadsheet
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
        $this->isTimeSeries = true;

        $dates = $this->getDates();
        $this
            ->setUpMetaAndHeaders(
                title: $endpointGroup['title'],
                columnTitles: array_merge(['Metric'], $dates),
            )
            ->writeRow(['Values are in ' . strtolower($this->firstMetric->units)])
            ->nextRow()
            ->nextRow()
            ->writeRow(['Metric']);

        // Write dates explicitly as strings so they don't get reformatted into a different date format by Excel
        foreach ($dates as $i => $date) {
            $date = Formatter::getFormattedDate($date, $this->frequency);
            $colNum = $i + 2;
            $cell = $this->getColumnKey($colNum) . $this->currentRow;
            $this->objPHPExcel
                ->getActiveSheet()
                ->getCell($cell)
                ->setValueExplicit($date, DataType::TYPE_STRING);
        }
        unset(
            $cell,
            $colNum,
            $date,
            $dates,
            $i,
        );

        $this
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
            $metric = $this->metricsTable->getFromSeriesId($endpoint['seriesId']);
            $dataTypeId = StatisticsTable::DATA_TYPE_VALUE;
            $statistics = $this->statisticsTable->getByMetricAndType(
                metricId: $metric->id,
                dataTypeId: $dataTypeId,
                all: $this->isTimeSeries,
                withCache: true
            );
            $row = [$endpoint['name']];
            foreach ($statistics as $statistic) {
                $row[] = Formatter::formatValue($statistic['value'], $this->prepend);
            }
            unset($statistics);
            $this
                ->writeRow($row)
                ->alignHorizontal('right', 2)
                ->nextRow();
            unset($row);
        }

        unset($metric);

        $this->setCellWidth();
    }

    /**
     * Returns an array of dates
     *
     * @return array
     */
    private function getDates(): array
    {
        $dates = $this->statisticsTable
            ->find()
            ->select(['date'])
            ->distinct(['date'])
            ->where([
                'metric_id' => $this->firstMetric->id,
                'data_type_id' => StatisticsTable::DATA_TYPE_VALUE,
            ])
            ->orderAsc('date')
            ->enableHydration(false)
            ->toArray();

        if (!$dates) {
            throw new NotFoundException('No statistics found for metric #' . $this->firstMetric->id);
        }

        return Hash::extract($dates, '{n}.date');
    }
}
