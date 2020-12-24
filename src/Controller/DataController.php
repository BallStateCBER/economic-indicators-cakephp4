<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\Fetcher;
use App\Fetcher\SeriesGroups;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use fred_api_exception;

class DataController extends AppController
{
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
}
