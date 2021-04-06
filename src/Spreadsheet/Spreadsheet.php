<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use App\Formatter\Formatter;
use App\Model\Entity\Metric;
use App\Model\Table\MetricsTable;
use App\Model\Table\StatisticsTable;
use Cache\Adapter\Redis\RedisCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use DataCenter\Spreadsheet\Spreadsheet as DataCenterSpreadsheet;
use Generator;
use PhpOffice\PhpSpreadsheet\Settings as PhpSpreadsheetSettings;
use Redis;

/**
 * Class Spreadsheet
 *
 * @package App\Spreadsheet
 * @property \App\Model\Entity\Metric $firstMetric
 * @property \App\Model\Table\MetricsTable $metricsTable
 * @property \App\Model\Table\StatisticsTable $statisticsTable
 * @property bool $isTimeSeries
 * @property string $frequency
 * @property string|null $prepend
 */
class Spreadsheet extends DataCenterSpreadsheet
{
    protected MetricsTable $metricsTable;
    protected StatisticsTable $statisticsTable;
    protected ?string $prepend;
    protected array $endpointGroup;
    protected bool $isTimeSeries;
    protected Metric $firstMetric;
    protected string $frequency;

    /**
     * Spreadsheet constructor
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function __construct(array $endpointGroup)
    {
        $this->setCacheEngine();
        parent::__construct();
        $this->endpointGroup = $endpointGroup;
        $this->metricsTable = TableRegistry::getTableLocator()->get('Metrics');
        $this->statisticsTable = TableRegistry::getTableLocator()->get('Statistics');
        $this->firstMetric = $this->metricsTable->getFirstForEndpointGroup($endpointGroup);
        $this->frequency = $this->metricsTable->getFrequency($endpointGroup);
        $this->prepend = Formatter::getPrepend($this->firstMetric->units);
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

    /**
     * Generator that yields data for one spreadsheet row, corresponding to a metric/endpoint
     *
     * @return array|\Generator
     */
    protected function getDataRows(): array | Generator
    {
        $dataTypeIds = $this->isTimeSeries
            ? [StatisticsTable::DATA_TYPE_VALUE]
            : StatisticsTable::DATA_TYPES;

        foreach ($this->endpointGroup['endpoints'] as $endpoint) {
            $row = [];
            $row['name'] = $endpoint['name'];
            $metric = $this->metricsTable->getFromSeriesId($endpoint['seriesId']);
            foreach ($dataTypeIds as $dataTypeId) {
                $row['statistics'][$dataTypeId] = $this->statisticsTable->getByMetricAndType(
                    metricId: $metric->id,
                    dataTypeId: $dataTypeId,
                    all: $this->isTimeSeries,
                    withCache: true
                );
            }
            unset($metric);

            yield $row;
        }
    }
}
