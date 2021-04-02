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
     * @param array|bool $data Data, or FALSE if not found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function __construct(array $endpointGroup, $data)
    {
        parent::__construct($data);

        /** @var \App\Model\Table\MetricsTable $metricsTable */
        $metricsTable = TableRegistry::getTableLocator()->get('Metrics');
        $metric = $metricsTable->getFirstForEndpointGroup($endpointGroup);
        $dates = $this->getDates($data);
        $this
            ->setUpMetaAndHeaders(
                title: $endpointGroup['title'],
                columnTitles: array_merge(['Metric'], $dates),
            )
            ->writeRow(['Values are in ' . strtolower($metric->units)])
            ->nextRow()
            ->nextRow()
            ->writeRow(['Metric']);

        // Write dates explicitly as strings so they don't get reformatted into a different date format by Excel
        $frequency = $metricsTable->getFrequency($endpointGroup);
        unset($endpointGroup);
        foreach ($dates as $i => $date) {
            $date = Formatter::getFormattedDate($date, $frequency);
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
            $frequency,
            $i,
            $metricsTable,
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

        $prepend = Formatter::getPrepend($metric->units);
        foreach ($data as $seriesId => $endpoint) {
            $row = [$endpoint['name']];
            foreach ($endpoint['statistics'][StatisticsTable::DATA_TYPE_VALUE] as $statistic) {
                $row[] = Formatter::formatValue($statistic['value'], $prepend);
            }
            $this
                ->writeRow($row)
                ->alignHorizontal('right', 2)
                ->nextRow();
            unset($row);
        }
        unset(
            $data,
            $endpoint,
            $prepend,
            $seriesId,
        );

        $this->setCellWidth();
    }

    /**
     * Returns an array of dates
     *
     * Dates are presumed to be in ascending order
     *
     * @param array $data Spreadsheet data
     * @return array
     */
    private function getDates(array $data): array
    {
        $years = [];
        $firstEndpoint = reset($data);
        foreach ($firstEndpoint['statistics'][StatisticsTable::DATA_TYPE_VALUE] as $statistic) {
            $years[] = $statistic['date'];
        }

        return $years;
    }
}
