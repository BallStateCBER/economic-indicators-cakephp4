<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\Fetcher;
use App\Fetcher\SeriesGroups;
use App\Spreadsheet\Spreadsheet;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use DateTime;
use DateTimeZone;
use Exception;
use fred_api_exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Exception as PhpOfficeException;

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
    }

    /**
     * Returns data from the cache or API, or FALSE in the event of an error fetching data
     *
     * @param array $series Series data
     * @return array|bool
     */
    private function getData(array $series)
    {
        try {
            return (new Fetcher())->getCachedValuesAndChanges($series);
        } catch (NotFoundException | fred_api_exception $e) {
            return false;
        }
    }

    /**
     * Sets up and renders an observations page
     *
     * @param array $group Series group metadata
     * @return \Cake\Http\Response
     */
    private function renderObservations(array $group): Response
    {
        if ($this->request->is('xlsx')) {
            return $this->renderSpreadsheet($group);
        }

        $this->set([
            'data' => $this->getData($group),
            'pageTitle' => $group['title'],
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
        return $this->renderObservations(SeriesGroups::HOUSING);
    }

    /**
     * Vehicle sales
     *
     * @return \Cake\Http\Response
     */
    public function vehicleSales(): Response
    {
        return $this->renderObservations(SeriesGroups::VEHICLE_SALES);
    }

    /**
     * Retail and food services
     *
     * @return \Cake\Http\Response
     */
    public function retailFoodServices(): Response
    {
        return $this->renderObservations(SeriesGroups::RETAIL_FOOD_SERVICES);
    }

    /**
     * Gross domestic product
     *
     * @return \Cake\Http\Response
     */
    public function gdp(): Response
    {
        return $this->renderObservations(SeriesGroups::GDP);
    }

    /**
     * Unemployment rate
     *
     * @return \Cake\Http\Response
     */
    public function unemployment(): Response
    {
        return $this->renderObservations(SeriesGroups::UNEMPLOYMENT);
    }

    /**
     * Employment by sector
     *
     * @return \Cake\Http\Response
     */
    public function employmentBySector(): Response
    {
        return $this->renderObservations(SeriesGroups::EMP_BY_SECTOR);
    }

    /**
     * Earnings
     *
     * @return \Cake\Http\Response
     */
    public function earnings(): Response
    {
        return $this->renderObservations(SeriesGroups::EARNINGS);
    }

    /**
     * County unemployment rates
     *
     * @return \Cake\Http\Response
     */
    public function countyUnemployment(): Response
    {
        return $this->renderObservations(SeriesGroups::getCountyUnemployment());
    }

    /**
     * Renders a spreadsheet, or redirects back to the appropriate page with an error message
     *
     * @param array $group Series group metadata
     * @return \Cake\Http\Response
     */
    private function renderSpreadsheet(array $group): Response
    {
        try {
            $title = str_replace([' ', '_'], '-', strtolower($group['title']));
            $timezone = new DateTimeZone(Configure::read('local_timezone'));
            $date = (new DateTime('now', $timezone))->format('-Y-m-d');
            $filename = $title . $date . '.xlsx';
            $this->response = $this->response
                ->withType('xlsx')
                ->withDownload($filename);

            $spreadsheet = new Spreadsheet($group, $this->getData($group));
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
}
