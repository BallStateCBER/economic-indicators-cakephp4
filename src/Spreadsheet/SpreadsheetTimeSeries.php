<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadsheetTimeSeries extends Spreadsheet
{
    /**
     * Spreadsheet constructor.
     *
     * @param array $seriesGroup Array from SeriesGroups
     * @param array|bool $data Data, or FALSE if not found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function __construct(array $seriesGroup, $data)
    {
        parent::__construct($data);

        $type = 'value';
        $dates = $this->getDates($data);
        $unit = Formatter::getUnit($data);
        $this
            ->setUpMetaAndHeaders(
                title: $seriesGroup['title'],
                columnTitles: array_merge(['Metric'], $dates),
            )
            ->writeRow(['Values are in ' . strtolower($unit)])
            ->nextRow()
            ->nextRow()
            ->writeRow(['Metric']);

        // Write dates explicitly as strings so they don't get reformatted into a different date format by Excel
        $frequency = Formatter::getFrequency($data);
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
        foreach ($data['series'] as $name => $series) {
            $row = [$name];
            foreach ($series[$type] as $observation) {
                $row[] = $type == 'percentChange'
                    ? $observation['value'] . '%'
                    : Formatter::formatValue($observation['value'], $prepend);
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
        $firstMetric = reset($data['series']);
        foreach ($firstMetric['value'] as $observation) {
            $years[] = $observation['date'];
        }

        return $years;
    }
}
