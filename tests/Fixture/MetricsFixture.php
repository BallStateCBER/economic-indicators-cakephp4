<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Endpoints\EndpointGroups;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * MetricsFixture
 */
class MetricsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'series_id' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'last_updated' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
        'units' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'frequency' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
        'modified' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'latin1_swedish_ci'
        ],
    ];
    // phpcs:enable
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $id = 1;
        $endpointGroups = EndpointGroups::getAll();
        foreach ($endpointGroups as $endpointGroup) {
            foreach ($endpointGroup['endpoints'] as $endpoint) {
                $this->records[] = [
                    'id' => $id,
                    'series_id' => $endpoint['seriesId'],
                    'last_updated' => '2021-01-01 00:00:00',
                    'units' => 'Units',
                    'frequency' => 'Frequency',
                    'created' => '2021-01-01 00:00:00',
                    'modified' => '2021-01-01 00:00:00',
                ];
                $id++;
            }
        }
        parent::init();
    }
}
