<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Endpoints\EndpointGroups;
use Cake\I18n\FrozenDate;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * ReleasesFixture
 */
class ReleasesFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'metric_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'date' => ['type' => 'date', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'imported' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
        'modified' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci'
        ],
    ];
    // phpcs:enable

    public const NEXT_RELEASE_DATE = '+1 day';
    public const FOLLOWING_RELEASE_DATE = '+2 days';

    /**
     * Init method
     *
     * Creates two upcoming releases for each metric, the first being tomorrow, and the following being the next day
     *
     * @return void
     */
    public function init(): void
    {
        $id = 1;
        $metricId = 1;
        $endpointGroups = EndpointGroups::getAll();
        $this->records = [];
        foreach ($endpointGroups as $endpointGroup) {
            $endpointCount = count($endpointGroup['endpoints']);
            for ($n = 1; $n <= $endpointCount; $n++) {
                $this->records[] = [
                    'id' => $id,
                    'metric_id' => $metricId,
                    'date' => new FrozenDate(self::NEXT_RELEASE_DATE),
                    'imported' => 0,
                    'created' => '2021-01-01 00:00:00',
                    'modified' => '2021-01-01 00:00:00',
                ];
                $id++;
                $this->records[] = [
                    'id' => $id,
                    'metric_id' => $metricId,
                    'date' => new FrozenDate(self::FOLLOWING_RELEASE_DATE),
                    'imported' => 0,
                    'created' => '2021-01-01 00:00:00',
                    'modified' => '2021-01-01 00:00:00',
                ];
                $id++;
            }
        }
        parent::init();
    }
}
