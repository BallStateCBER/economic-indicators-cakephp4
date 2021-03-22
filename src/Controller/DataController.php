<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\EndpointGroups;
use App\Formatter\Formatter;
use App\Spreadsheet\SpreadsheetSingleDate;
use App\Spreadsheet\SpreadsheetTimeSeries;
use Cake\Core\Configure;
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
     * Sets up and renders an observations page
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return \Cake\Http\Response
     */
    private function renderObservations(array $endpointGroup): Response
    {
        $isTimeSeries = (bool)$this->getRequest()->getQuery('timeSeries');
        if ($this->request->is('xlsx')) {
            return $this->renderSpreadsheet($endpointGroup, $isTimeSeries);
        }

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
            'statistics' => $this->Statistics->getGroup($endpointGroup, $isTimeSeries),
            'unit' => $firstMetric->units,
        ]);

        return $this->render('observations');
    }

    /**
     * Housing
     *
     * @return \Cake\Http\Response
     */
    public function housing(): Response
    {
        return $this->renderObservations(EndpointGroups::HOUSING);
    }

    /**
     * Vehicle sales
     *
     * @return \Cake\Http\Response
     */
    public function vehicleSales(): Response
    {
        return $this->renderObservations(EndpointGroups::VEHICLE_SALES);
    }

    /**
     * Retail and food services
     *
     * @return \Cake\Http\Response
     */
    public function retailFoodServices(): Response
    {
        return $this->renderObservations(EndpointGroups::RETAIL_FOOD_SERVICES);
    }

    /**
     * Gross domestic product
     *
     * @return \Cake\Http\Response
     */
    public function gdp(): Response
    {
        return $this->renderObservations(EndpointGroups::GDP);
    }

    /**
     * Unemployment rate
     *
     * @return \Cake\Http\Response
     */
    public function unemployment(): Response
    {
        return $this->renderObservations(EndpointGroups::UNEMPLOYMENT);
    }

    /**
     * Employment by sector
     *
     * @return \Cake\Http\Response
     */
    public function employmentBySector(): Response
    {
        return $this->renderObservations(EndpointGroups::EMP_BY_SECTOR);
    }

    /**
     * Earnings
     *
     * @return \Cake\Http\Response
     */
    public function earnings(): Response
    {
        return $this->renderObservations(EndpointGroups::EARNINGS);
    }

    /**
     * County unemployment rates
     *
     * @return \Cake\Http\Response
     */
    public function countyUnemployment(): Response
    {
        return $this->renderObservations(EndpointGroups::getCountyUnemployment());
    }

    /**
     * Renders a spreadsheet, or redirects back to the appropriate page with an error message
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @param bool $isTimeSeries TRUE if outputting spreadsheet with a series of values on all available dates
     * @return \Cake\Http\Response
     */
    private function renderSpreadsheet(array $endpointGroup, $isTimeSeries = false): Response
    {
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

        return $this->redirect(['_ext' => null]);
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
