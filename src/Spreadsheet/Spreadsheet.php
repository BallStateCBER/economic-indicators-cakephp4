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
     * Spreadsheet constructor
     *
     * @param array|bool $data Data, or FALSE if not found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function __construct(array|bool $data)
    {
        parent::__construct();

        if ($data === false) {
            throw new Exception('No data found');
        }
    }

    /**
     * Set attribution cell to span all columns
     *
     * Must be called after $this->columnTitles is set
     *
     * @param string $title Spreadsheet title
     * @param string[] $columnTitles Titles for each column
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function setUpMetaAndHeaders(string $title, array $columnTitles)
    {
        $author = 'Center for Business and Economic Research, Ball State University';
        $this
            ->setColumnTitles($columnTitles)
            ->setMetadataTitle($title)
            ->setAuthor($author)
            ->setActiveSheetTitle($title)
            ->writeSheetTitle($title)
            ->nextRow()
            ->writeSheetSubtitle($author)
            ->nextRow()
            ->writeRow(['Data provided by the Economic Research Division of the Federal Reserve Bank of St. Louis']);

        $span = sprintf(
            'A%s:%s%s',
            $this->currentRow,
            $this->getLastColumnLetter(),
            $this->currentRow
        );
        $this->objPHPExcel->getActiveSheet()->mergeCells($span);

        $this
            ->nextRow()
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

        return $this;
    }
}
