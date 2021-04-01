<?php
declare(strict_types=1);

namespace App\Controller;

use App\Endpoints\EndpointGroups;
use App\Formatter\Formatter;
use App\Model\Table\StatisticsTable;
use App\Spreadsheet\SpreadsheetSingleDate;
use App\Spreadsheet\SpreadsheetTimeSeries;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Utility\Text;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Exception as PhpOfficeException;

/**
 * Class DataController
 *
 * @package App\Controller
 * @property \App\Model\Table\MetricsTable $Metrics
 * @property \App\Model\Table\ReleasesTable $Releases
 * @property \App\Model\Table\StatisticsTable $Statistics
 */
class DataController extends AppController
{
    /**
     * Initialization callback method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->request->addDetector(
            'xlsx',
            [
                'accept' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                'param' => '_ext',
                'value' => 'xlsx',
            ]
        );
        $this->loadModel('Metrics');
        $this->loadModel('Statistics');
    }

    /**
     * Displays a page with statistics for a group of endpoints
     *
     * @param string $groupName The name of a group of endpoints
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function group(string $groupName)
    {
        $endpointGroup = EndpointGroups::get($groupName);
        $this->loadModel('Releases');
        $metrics = $this->Metrics->getAllForEndpointGroup($endpointGroup);
        /** @var \App\Model\Entity\Metric $firstMetric */
        $firstMetric = $metrics[0];
        $this->set([
            'dateRange' => $this->Statistics->getDateRange($endpointGroup),
            'frequency' => $firstMetric->frequency,
            'groupName' => $groupName,
            'lastUpdated' => $firstMetric->last_updated->format('F j, Y'),
            'nextRelease' => $this->Releases->getNextReleaseDate(...$metrics),
            'pageTitle' => $endpointGroup['title'],
            'prepend' => Formatter::getPrepend($firstMetric->units),
            'startingDates' => $this->Statistics->getStartingDates($endpointGroup),
            'statistics' => $this->Statistics->getGroup($endpointGroup),
            'statsForSparklines' => $this->Statistics->getStatsForSparklines($endpointGroup),
            'unit' => $firstMetric->units,
        ]);
    }

    /**
     * Initiates a spreadsheet download
     *
     * The ?timeSeries=1 query string is used to download an alternate version of the spreadsheet
     *
     * @param string $groupName The name of a group of endpoints
     * @return \Cake\Http\Response|null
     */
    public function download(string $groupName): ?Response
    {
        $endpointGroup = EndpointGroups::get($groupName);
        $isTimeSeries = (bool)$this->getRequest()->getQuery('timeSeries');

        try {
            $filename = sprintf(
                '%s-%s.xlsx',
                str_replace([' ', '_'], '-', strtolower($endpointGroup['title'])),
                $this->getDateForFilename($endpointGroup, $isTimeSeries)
            );
            $this->response = $this->response
                ->withType('xlsx')
                ->withDownload($filename);

            $data = $this->Statistics->getGroup($endpointGroup, $isTimeSeries);
            $spreadsheet = $isTimeSeries
                ? new SpreadsheetTimeSeries($endpointGroup, $data)
                : new SpreadsheetSingleDate($endpointGroup, $data);
            unset($data);
            $spreadsheetWriter = IOFactory::createWriter($spreadsheet->get(), 'Xlsx');
            $this->set(compact('spreadsheetWriter'));
            unset($spreadsheetWriter);

            $this->viewBuilder()->setPlugin('DataCenter');

            return $this->render('/Spreadsheet/spreadsheet', 'xlsx/spreadsheet');
        } catch (Exception | PhpOfficeException $e) {
            $adminEmail = Configure::read('admin_email');
            $this->Flash->error(
                sprintf(
                    'Sorry, there was an error generating the requested spreadsheet. ' .
                    'Please try again later, or contact <a href="mailto:%s">%s</a> for assistance. ' .
                    '<br />Details: %s',
                    $adminEmail,
                    $adminEmail,
                    $e->getMessage()
                ),
                ['escape' => false]
            );
        }

        return $this->redirect([
            'action' => 'group',
            'groupName' => $groupName,
            '_ext' => null,
        ]);
    }

    /**
     * Returns a date string for use in a spreadsheet filename
     *
     * @param array $endpointGroup  A group defined in \App\Fetcher\EndpointGroups
     * @param bool $isTimeSeries TRUE if a date range should be generated
     * @return string
     */
    private function getDateForFilename(array $endpointGroup, bool $isTimeSeries): string
    {
        if ($isTimeSeries) {
            $dateRange = $this->Statistics->getDateRange($endpointGroup);

            return Text::slug(strtolower($dateRange[0] . '-' . $dateRange[1]));
        }

        $firstEndpoint = reset($endpointGroup['endpoints']);
        $metricName = $firstEndpoint['id'];
        /** @var \App\Model\Entity\Metric $metric */
        $metric = $this->Metrics->find()->where(['series_id' => $metricName])->first();
        /** @var \App\Model\Entity\Statistic $statistic */
        $statistic = $this->Statistics->find()
            ->select(['id', 'date'])
            ->where(['metric_id' => $metric->id])
            ->orderDesc('date')
            ->first();
        $date = Formatter::getFormattedDate($statistic->date, $metric->frequency);

        return Text::slug(strtolower($date));
    }

    /**
     * Displays a page with a line graph of this metric's values over time
     *
     * @param string $endpointGroupId String used for accessing an endpoint group
     * @param string $seriesId Metric seriesID, used in API calls
     * @return void
     */
    public function series(string $endpointGroupId, string $seriesId)
    {
        /** @var \App\Model\Entity\Metric|null $metric */
        $metric = $this->Metrics->findBySeriesId($seriesId)->first();
        if (!$metric) {
            throw new NotFoundException('Metric with series ID ' . $seriesId . ' not found');
        }
        $statistics = $this->Statistics->getByMetricAndType(
            metricId: $metric->id,
            dataTypeId: StatisticsTable::DATA_TYPE_VALUE,
            all: true,
        );

        $statsForGraph = [
            [
                (object)[
                    'label' => 'Date',
                    'type' => 'date',
                ],
                ucwords($metric->units),
            ],
        ];
        foreach ($statistics as $statistic) {
            $statsForGraph[] = [
                sprintf(
                    'Date(%s, %s, %s)',
                    $statistic['date']->format('Y'),
                    (int)$statistic['date']->format('n') - 1,
                    $statistic['date']->format('j'),
                ),
                (float)$statistic['value'],
            ];
        }
        unset($statistics, $statistic);

        $endpointGroup = EndpointGroups::get($endpointGroupId);

        $this->set([
            'endpointGroupId' => $endpointGroupId,
            'endpointGroupName' => $endpointGroup['title'],
            'pageTitle' => sprintf('%s: %s', $endpointGroup['title'], $this->getMetricName($metric->series_id)),
            'statsForGraph' => $statsForGraph,
            'units' => ucwords($metric->units),
        ]);
    }

    /**
     * An awful stepping-stone method for fetching the metric's human-readable name until a better solution is developed
     *
     * @param string $seriesId Metric seriesID
     * @return string
     * @throws \Cake\Http\Exception\NotFoundException
     */
    private function getMetricName(string $seriesId): string
    {
        // Override to account for 'Indiana Manufacturing Employment' having different names on different pages
        if ($seriesId == 'INMFG') {
            $groupName = $this->getRequest()->getParam('groupName');

            return $groupName == 'manufacturing-employment' ? 'Indiana' : 'Manufacturing';
        }

        $endpointGroups = EndpointGroups::getAll();
        foreach ($endpointGroups as $endpointGroup) {
            foreach ($endpointGroup['endpoints'] as $endpoint) {
                if ($endpoint['id'] == $seriesId) {
                    return $endpoint['name'];
                }
            }
        }

        throw new NotFoundException('Metric with seriesID ' . $seriesId . ' not found');
    }
}
