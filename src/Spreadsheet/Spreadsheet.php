<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
use DataCenter\Spreadsheet\Spreadsheet as DataCenterSpreadsheet;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Spreadsheet extends DataCenterSpreadsheet
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
        parent::__construct();

        if ($data === false) {
            throw new Exception('No data found');
        }

        $title = $seriesGroup['title'];
        $author = 'Center for Business and Economic Research, Ball State University';
        $unit = Formatter::getUnit($data);
        $columnTitles = [
            'Metric',
            "Latest Value in $unit",
            'Change from One Year Ago',
            'Percent Change from One Year Ago',
            'Date',
        ];
        $this
            ->setMetadataTitle($title)
            ->setAuthor($author)
            ->setColumnTitles($columnTitles)
            ->setActiveSheetTitle($title)
            ->writeSheetTitle($title)
            ->writeSheetSubtitle($author)
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
        foreach ($data['observations'] as $name => $series) {
            $this
                ->writeRow([
                    $name,
                    Formatter::formatValue($series['value']['value'], $prepend),
                    Formatter::formatValue($series['change']['value'], $prepend),
                    Formatter::formatValue($series['percentChange']['value']) . '%',
                ]);

                // Write date explicitly as a string so it doesn't get reformatted into a different date format by Excel
                $date = Formatter::getFormattedDate($series['value']['date'], $frequency);
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
