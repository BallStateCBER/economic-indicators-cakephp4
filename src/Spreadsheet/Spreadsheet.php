<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use DataCenter\Spreadsheet\Spreadsheet as DataCenterSpreadsheet;
use Exception;
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
        $columnTitles = [
            'Metric',
            'Latest Value',
            'Change',
            'Percent Change',
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

        foreach ($data['observations'] as $name => $series) {
            $this
                ->writeRow([
                    $name,
                    $series['value']['value'],
                    $series['change']['value'],
                    $series['percentChange']['value'] . '%',
                    $series['value']['date'],
                ])
                ->nextRow();
        }

        $this->setCellWidth();
    }
}
