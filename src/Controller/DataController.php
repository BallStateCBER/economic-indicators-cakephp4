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
     * Home page
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
}
