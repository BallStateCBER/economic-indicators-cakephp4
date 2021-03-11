<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadsheetSingleDate extends Spreadsheet
{
    /**
     * Spreadsheet constructor.
     *
     * @param array $seriesGroup Array from SeriesGroups
     * @param array|bool $data Data, or FALSE if not found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function __construct(array $seriesGroup, array|bool $data)
    {
        parent::__construct($data);

        $unit = Formatter::getUnit($data);
        $columnTitles = [
            'Metric',
            $unit,
            'Change from One Year Prior',
            'Percent Change from One Year Prior',
            'Date',
        ];
        $this
            ->setUpMetaAndHeaders($seriesGroup['title'], $columnTitles)
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

        $prepend = Formatter::getPrepend($unit);
        $frequency = Formatter::getFrequency($data);
        foreach ($data['series'] as $name => $series) {
            $this
                ->writeRow([
                    $name,
                    Formatter::formatValue($series['value']->value, $prepend),
                    Formatter::formatValue($series['change']->value, $prepend),
                    Formatter::formatValue($series['percentChange']->value) . '%',
                ])
                ->alignHorizontal('right', 2);

            // Write date explicitly as a string so it doesn't get reformatted into a different date format by Excel
            $date = Formatter::getFormattedDate($series['value']->date, $frequency);
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
