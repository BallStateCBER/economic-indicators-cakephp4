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
            'data' => (new Fetcher())->getCachedValuesAndChanges('housing', SeriesGroups::HOUSING),
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
            'data' => (new Fetcher())->getCachedValuesAndChanges('vehicle_sales', SeriesGroups::VEHICLE_SALES),
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
            'data' => (new Fetcher())->getCachedValuesAndChanges('retail_food', SeriesGroups::RETAIL_FOOD_SERVICES),
            'pageTitle' => FredEndpoints::VAR_RETAIL_FOOD,
        ]);

        return $this->render('observations');
    }
}
