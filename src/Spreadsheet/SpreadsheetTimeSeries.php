<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
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

        $type = 'value';
        $dates = $this->getDates($data);
        $unit = Formatter::getUnit($data);
        $this
            ->setUpMetaAndHeaders(
                title: $endpointGroup['title'],
                columnTitles: array_merge(['Metric'], $dates),
            )
            ->writeRow(['Values are in ' . strtolower($unit)])
            ->nextRow()
            ->nextRow()
            ->writeRow(['Metric']);

        // Write dates explicitly as strings so they don't get reformatted into a different date format by Excel
        /** @var \App\Model\Table\MetricsTable $metricsTable */
        $metricsTable = TableRegistry::getTableLocator()->get('Metrics');
        $frequency = $metricsTable->getFrequency($endpointGroup);
        foreach ($dates as $i => $date) {
            $date = Formatter::getFormattedDate($date, $frequency);
            $colNum = $i + 2;
            $cell = $this->getColumnKey($colNum) . $this->currentRow;
            $this->objPHPExcel
                ->getActiveSheet()
                ->getCell($cell)
                ->setValueExplicit($date, DataType::TYPE_STRING);
        }

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

        $prepend = Formatter::getPrepend($unit);
        foreach ($data['endpoints'] as $endpointName => $endpoint) {
            $row = [$endpointName];
            foreach ($endpoint[$type] as $observation) {
                $row[] = $type == 'percentChange'
                    ? $observation->value . '%'
                    : Formatter::formatValue($observation->value, $prepend);
            }
            $this
                ->writeRow($row)
                ->alignHorizontal('right', 2)
                ->nextRow();
        }

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
        $firstEndpoint = reset($data['endpoints']);
        foreach ($firstEndpoint['value'] as $statistic) {
            $years[] = $statistic->date;
        }

        return $years;
    }
}
