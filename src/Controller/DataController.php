<?php
declare(strict_types=1);

namespace App\Controller;

use App\Fetcher\FredEndpoints;
use App\Fetcher\Fetcher;
use Cake\Event\EventInterface;

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
        $seriesGroup = [
            FredEndpoints::HOUSING_TOTAL,
            FredEndpoints::HOUSING_1_UNIT,
            FredEndpoints::HOUSING_2_4_UNIT,
            FredEndpoints::HOUSING_5_UNIT,
        ];

        $fetcher = new Fetcher();
        $data = [];
        foreach ($seriesGroup as $series) {
            $fetcher
                ->setSeries($series)
                ->latest();

            $data[$series['subvar']] = [
                'value' => $series + $fetcher->getObservations()[0],
                'change' => $series + $fetcher->changeFromYearAgo()->getObservations()[0],
                'percentChange' => $series + $fetcher->percentChangeFromYearAgo()->getObservations()[0],
            ];
        }

        $this->set([
            'data' => $data,
            'pageTitle' => 'Housing',
        ]);

        return $this->render('observations');
    }
}
