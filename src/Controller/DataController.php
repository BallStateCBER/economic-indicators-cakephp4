<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\Fetcher;
use App\Fetcher\FredEndpoints;
use App\Fetcher\SeriesGroups;
use Cake\Event\EventInterface;

class DataController extends AppController
{
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
    }

    /**
     * Housing
     *
     * @return \Cake\Http\Response
     */
    public function housing()
    {
        $this->set([
            'data' => (new Fetcher())->getCachedValuesAndChanges(SeriesGroups::HOUSING),
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
            'data' => (new Fetcher())->getCachedValuesAndChanges(SeriesGroups::VEHICLE_SALES),
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
            'data' => (new Fetcher())->getCachedValuesAndChanges(SeriesGroups::RETAIL_FOOD_SERVICES),
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
            'data' => (new Fetcher())->getCachedValuesAndChanges(SeriesGroups::GDP),
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
            'data' => (new Fetcher())->getCachedValuesAndChanges(SeriesGroups::UNEMPLOYMENT),
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
            'data' => (new Fetcher())->getCachedValuesAndChanges(SeriesGroups::EMP_BY_SECTOR),
            'pageTitle' => FredEndpoints::VAR_EMPLOYMENT_BY_SECTOR,
        ]);

        return $this->render('observations');
    }
}
