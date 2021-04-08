<?php
declare(strict_types=1);

namespace App\Controller;

use App\Endpoints\EndpointGroups;
use App\Formatter\Formatter;
use App\Model\Table\SpreadsheetsTable;
use App\Model\Table\StatisticsTable;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

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
     * @param string $groupId An identifier of a group of endpoints
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function group(string $groupId)
    {
        $endpointGroup = EndpointGroups::get($groupId);
        $this->loadModel('Releases');
        $metrics = $this->Metrics->getAllForEndpointGroup($endpointGroup);
        /** @var \App\Model\Entity\Metric $firstMetric */
        $firstMetric = $metrics[0];
        $this->set([
            'dateRange' => $this->Statistics->getDateRange($endpointGroup),
            'frequency' => $firstMetric->frequency,
            'groupId' => $groupId,
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
     * @param string $groupId An identifier for a group of endpoints
     * @return \Cake\Http\Response
     */
    public function download(string $groupId): Response
    {
        $endpointGroup = EndpointGroups::get($groupId);
        $isTimeSeries = (bool)$this->getRequest()->getQuery('timeSeries');
        $spreadsheetsTable = TableRegistry::getTableLocator()->get('Spreadsheets');
        /** @var \App\Model\Entity\Spreadsheet|null $spreadsheet */
        $spreadsheet = $spreadsheetsTable
            ->find()
            ->where([
                'group_name' => $endpointGroup['title'],
                'is_time_series' => $isTimeSeries,
            ])
            ->first();

        if ($spreadsheet) {
            $this->response = $this->response->withFile(SpreadsheetsTable::FILE_PATH . $spreadsheet->filename);
            $this->response = $this->response->withType('xlsx');

            return $this->response;
        }

        $adminEmail = Configure::read('admin_email');
        $this->Flash->error(
            sprintf(
                'Sorry, there was an error retrieving the requested spreadsheet. ' .
                'Please try again later, or contact <a href="mailto:%s">%s</a> for assistance. ',
                $adminEmail,
                $adminEmail
            ),
            ['escape' => false]
        );

        return $this->redirect([
            'action' => 'group',
            'groupId' => $groupId,
            '_ext' => null,
        ]);
    }

    /**
     * Displays a page with a line graph of this metric's values over time
     *
     * @param string $groupId Identifier for accessing an endpoint group
     * @param string $seriesId Metric seriesID, used in API calls
     * @return void
     */
    public function series(string $groupId, string $seriesId)
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

        $endpointGroup = EndpointGroups::get($groupId);

        $this->set([
            'groupId' => $groupId,
            'groupTitle' => $endpointGroup['title'],
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
            $groupId = $this->getRequest()->getParam('groupId');

            return $groupId == 'manufacturing-employment' ? 'Indiana' : 'Manufacturing';
        }

        $endpointGroups = EndpointGroups::getAll();
        foreach ($endpointGroups as $endpointGroup) {
            foreach ($endpointGroup['endpoints'] as $endpoint) {
                if ($endpoint['seriesId'] == $seriesId) {
                    return $endpoint['name'];
                }
            }
        }

        throw new NotFoundException('Metric with seriesID ' . $seriesId . ' not found');
    }
}
