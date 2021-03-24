<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\EndpointGroups;
use App\Formatter\Formatter;
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
     * Returns an endpoint group, identified by the $griyoBane string
     *
     * @param string $groupName String used for accessing an endpoint group
     * @return array
     * @throws \Cake\Http\Exception\NotFoundException
     */
    private function getEndpointGroup(string $groupName): array
    {
        switch ($groupName) {
            case 'housing':
                return EndpointGroups::HOUSING;
            case 'vehicle-sales':
                return EndpointGroups::VEHICLE_SALES;
            case 'retail-food-services':
                return EndpointGroups::RETAIL_FOOD_SERVICES;
            case 'gdp':
                return EndpointGroups::GDP;
            case 'unemployment':
                return EndpointGroups::UNEMPLOYMENT;
            case 'employment-by-sector':
                return EndpointGroups::EMP_BY_SECTOR;
            case 'earnings':
                return EndpointGroups::EARNINGS;
            case 'county-unemployment':
                return EndpointGroups::getCountyUnemployment();
            case 'manufacturing-employment':
                return EndpointGroups::getStateManufacturing();
        }

        throw new NotFoundException('Data group ' . $groupName . ' not found');
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
        $endpointGroup = $this->getEndpointGroup($groupName);
        $this->loadModel('Releases');
        $metrics = $this->Metrics->getAllForEndpointGroup($endpointGroup);
        $firstMetric = $metrics[0];
        $this->set([
            'dateRange' => $this->Statistics->getDateRange($endpointGroup),
            'frequency' => $firstMetric->frequency,
            'lastUpdated' => $firstMetric->last_updated->format('F j, Y'),
            'nextRelease' => $this->Releases->getNextReleaseDate(...$metrics),
            'pageTitle' => $endpointGroup['title'],
            'prepend' => Formatter::getPrepend($firstMetric->units),
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
        $endpointGroup = $this->getEndpointGroup($groupName);
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
            $spreadsheetWriter = IOFactory::createWriter($spreadsheet->get(), 'Xlsx');
            $this->set(compact('spreadsheetWriter'));

            $this->viewBuilder()->setPlugin('DataCenter');

            return $this->render('/Spreadsheet/spreadsheet', 'DataCenter.spreadsheet');
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

            return Text::slug(strtolower($dateRange));
        }

        $firstEndpoint = reset($endpointGroup['endpoints']);
        $metricName = $firstEndpoint['id'];
        /** @var \App\Model\Entity\Metric $metric */
        $metric = $this->Metrics->find()->where(['name' => $metricName])->first();
        /** @var \App\Model\Entity\Statistic $statistic */
        $statistic = $this->Statistics->find()
            ->select(['id', 'date'])
            ->where(['metric_id' => $metric->id])
            ->orderDesc('date')
            ->first();
        $date = Formatter::getFormattedDate($statistic->date, $metric->frequency);

        return Text::slug(strtolower($date));
    }
}
