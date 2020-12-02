<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\Fetcher;
use App\Fetcher\FredEndpoints;
use App\Fetcher\SeriesGroups;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use fred_api_exception;

class DataController extends AppController
{
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
    }

    /**
     * Returns data from the cache or API, or FALSE in the event of an error fetching data
     *
     * @param array $series Series data
     * @return array|bool
     */
    private function getData($series)
    {
        try {
            return (new Fetcher())->getCachedValuesAndChanges($series);
        } catch (NotFoundException | fred_api_exception $e) {
            return false;
        }
    }

    /**
     * Housing
     *
     * @return \Cake\Http\Response
     */
    public function housing()
    {
        $this->set([
            'data' => $this->getData(SeriesGroups::HOUSING),
            'pageTitle' => FredEndpoints::VAR_HOUSING,
        ]);

        return $this->render('observations');
    }

    /**
     * Vehicle sales
     *
     * @return \Cake\Http\Response
     */
    public function vehicleSales()
    {
        $this->set([
            'data' => $this->getData(SeriesGroups::VEHICLE_SALES),
            'pageTitle' => FredEndpoints::VAR_VEHICLE_SALES,
        ]);

        return $this->render('observations');
    }

    /**
     * Retail and food services
     *
     * @return \Cake\Http\Response
     */
    public function retailFoodServices()
    {
        $this->set([
            'data' => $this->getData(SeriesGroups::RETAIL_FOOD_SERVICES),
            'pageTitle' => FredEndpoints::VAR_RETAIL_FOOD,
        ]);

        return $this->render('observations');
    }

    /**
     * Gross domestic product
     *
     * @return \Cake\Http\Response
     */
    public function gdp()
    {
        $this->set([
            'data' => $this->getData(SeriesGroups::GDP),
            'pageTitle' => FredEndpoints::VAR_GDP,
        ]);

        return $this->render('observations');
    }

    /**
     * Unemployment rate
     *
     * @return \Cake\Http\Response
     */
    public function unemployment()
    {
        $this->set([
            'data' => $this->getData(SeriesGroups::UNEMPLOYMENT),
            'pageTitle' => FredEndpoints::VAR_UNEMPLOYMENT,
        ]);

        return $this->render('observations');
    }

    /**
     * Employment by sector
     *
     * @return \Cake\Http\Response
     */
    public function employmentBySector()
    {
        $this->set([
            'data' => $this->getData(SeriesGroups::EMP_BY_SECTOR),
            'pageTitle' => FredEndpoints::VAR_EMPLOYMENT_BY_SECTOR,
        ]);

        return $this->render('observations');
    }

    /**
     * Earnings
     *
     * @return \Cake\Http\Response
     */
    public function earnings()
    {
        $this->set([
            'data' => $this->getData(SeriesGroups::EARNINGS),
            'pageTitle' => FredEndpoints::VAR_EARNINGS,
        ]);

        return $this->render('observations');
    }

    /**
     * County unemployment rates
     *
     * @return \Cake\Http\Response
     */
    public function countyUnemployment()
    {
        $this->set([
            'data' => $this->getData(SeriesGroups::getCountyUnemployment()),
            'pageTitle' => FredEndpoints::VAR_COUNTY_UNEMPLOYMENT,
        ]);

        return $this->render('observations');
    }
}
