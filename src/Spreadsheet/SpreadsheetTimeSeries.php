<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
use App\Model\Table\StatisticsTable;
use Cake\Database\Expression\QueryExpression;
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
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function __construct(array $endpointGroup)
    {
        parent::__construct($endpointGroup);
        $this->isTimeSeries = true;
        $dateKeyFormat = 'n/j/Y';

        $endpointsByFrequency = [];
        foreach ($this->endpointGroup['endpoints'] as $seriesId => $name) {
            $metric = $this->metricsTable->getFromSeriesId($seriesId);
            $endpointsByFrequency[$metric->frequency][] = [
                'name' => $name,
                'metric' => $metric,
            ];
        }

        $firstSheet = true;
        foreach ($endpointsByFrequency as $frequency => $endpoints) {
            $sheetTitle = ucwords($frequency) . ' Data';
            if (!$firstSheet) {
                $this->newSheet($sheetTitle);
            }

            // Set headers and metadata
            $dates = $this->getDates($endpoints);
            $this
                ->setUpMetaAndHeaders(
                    title: $endpointGroup['title'],
                    columnTitles: array_merge(['Category'], $dates),
                )
                ->nextRow()
                ->writeRow(['Category']);

            // Write dates explicitly as strings so they don't get reformatted into a different date format by Excel
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
                $i,
            );

            // Style headers
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

            // Write each data row
            foreach ($endpoints as $endpoint) {
                $metric = $endpoint['metric'];

                // Key each statistic by its date so that it can be placed in the correct column
                $statistics = $this->statisticsTable->getByMetricAndType(
                    metricId: $metric->id,
                    dataTypeId: StatisticsTable::DATA_TYPE_VALUE,
                    all: $this->isTimeSeries,
                    withCache: true
                );
                $statisticsKeyed = [];
                foreach ($statistics as $statistic) {
                    $dateKey = $statistic['date']->format($dateKeyFormat);
                    $statisticsKeyed[$dateKey] = $statistic['value'];
                }
                unset($statistics);

                // Write row
                $row = ["{$endpoint['name']} ($metric->units)"];
                foreach ($dates as $date) {
                    $dateKey = $date->format($dateKeyFormat);
                    $row[] = isset($statisticsKeyed[$dateKey])
                        ? Formatter::formatValue($statisticsKeyed[$dateKey], Formatter::getPrepend($metric->units))
                        : null;
                }
                $this
                    ->writeRow($row)
                    ->alignHorizontal('right', 2)
                    ->nextRow();
                unset(
                    $metric,
                    $row,
                    $statisticsKeyed,
                );
            }

            $this
                ->setCellWidth()
                ->setActiveSheetTitle($sheetTitle);
            $firstSheet = false;
        }
        $this->selectFirstSheet();
    }

    /**
     * Returns an array of dates for the specified metrics
     *
     * @param array $endpoints Array of endpoint data
     * @return array
     */
    private function getDates(array $endpoints): array
    {
        $metricIds = [];
        foreach ($endpoints as $endpoint) {
            $metricIds[] = $endpoint['metric']->id;
        }

        $dates = $this->statisticsTable
            ->find()
            ->select(['date'])
            ->distinct(['date'])
            ->where([
                function (QueryExpression $exp) use ($metricIds) {
                    return $exp->in('metric_id', $metricIds);
                },
                'data_type_id' => StatisticsTable::DATA_TYPE_VALUE,
                function (QueryExpression $exp) {
                    return $exp->isNotNull('value');
                },
            ])
            ->orderAsc('date')
            ->enableHydration(false)
            ->toArray();

        return Hash::extract($dates, '{n}.date');
    }
}
