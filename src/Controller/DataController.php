<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\Fetcher;
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
            'pageTitle' => 'Housing',
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
            'pageTitle' => 'Vehicle Sales',
        ]);

        return $this->render('observations');
    }
}
