<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\FredEndpoints;
use App\Fetcher\Fetcher;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

class DataController extends AppController
{
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        //$this->response = $this->render('observations');
    }

    /**
     * Home page
     *
     * @return \Cake\Http\Response
     * @throws \fred_api_exception
     */
    public function housing()
    {
        $this->set([
            'data' => (new Fetcher())->getValuesAndChanges([
                FredEndpoints::HOUSING_TOTAL,
                FredEndpoints::HOUSING_1_UNIT,
                FredEndpoints::HOUSING_2_4_UNIT,
                FredEndpoints::HOUSING_5_UNIT,
            ]),
            'pageTitle' => 'Housing',
        ]);

        return $this->render('observations');
    }
}
