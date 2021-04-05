<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Model\Table\StatisticsTable;
use Cache\Adapter\Redis\RedisCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use Cake\Cache\Cache;
use DataCenter\Spreadsheet\Spreadsheet as DataCenterSpreadsheet;
use Exception;
use PhpOffice\PhpSpreadsheet\Settings as PhpSpreadsheetSettings;
use Redis;

class Spreadsheet extends DataCenterSpreadsheet
{
    /**
     * Spreadsheet constructor
     *
     * @param array|bool $data Data, or FALSE if not found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function __construct(array | bool $data)
    {
        parent::__construct();

        $this->setCacheEngine();

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
            ->nextRow();

        return $this;
    }

    /**
     * Sets up a cache engine
     *
     * This allows PhpSpreadsheet to build large spreadsheets that would otherwise require more RAM than is available
     *
     * @return void
     */
    protected function setCacheEngine()
    {
        $client = new Redis();
        $config = Cache::getConfig(StatisticsTable::CACHE_CONFIG);
        $client->connect($config['host'], $config['port']);
        $pool = new RedisCachePool($client);
        $simpleCache = new SimpleCacheBridge($pool);
        PhpSpreadsheetSettings::setCache($simpleCache);
    }
}
