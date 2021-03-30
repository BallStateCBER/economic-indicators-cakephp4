<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use App\Fetcher\EndpointGroups;
use App\Model\Table\StatisticsTable;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatisticsFixture
 */
class StatisticsFixture extends TestFixture
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
        'data_type_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'value' => ['type' => 'string', 'length' => 30, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null],
        'date' => ['type' => 'date', 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
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

    public const VALUE_FIRST = '123';
    public const VALUE_SECOND = '234';
    public const VALUE_CHANGE = '-1';
    public const VALUE_CHANGE_PERCENT = '2';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $id = 1;
        $metricId = 1;
        $endpointGroups = EndpointGroups::getAll();
        foreach ($endpointGroups as $endpointGroup) {
            $endpointCount = count($endpointGroup['endpoints']);
            for ($n = 1; $n <= $endpointCount; $n++) {
                $this->records[] = [
                    'id' => $id,
                    'metric_id' => $metricId,
                    'data_type_id' => StatisticsTable::DATA_TYPE_VALUE,
                    'value' => self::VALUE_FIRST,
                    'date' => '2000-01-01 00:00:00',
                    'created' => '2021-01-01 00:00:00',
                    'modified' => '2021-01-01 00:00:00',
                ];
                $id++;
                $this->records[] = [
                    'id' => $id,
                    'metric_id' => $metricId,
                    'data_type_id' => StatisticsTable::DATA_TYPE_VALUE,
                    'value' => self::VALUE_SECOND,
                    'date' => '2001-01-01 00:00:00',
                    'created' => '2021-01-01 00:00:00',
                    'modified' => '2021-01-01 00:00:00',
                ];
                $id++;
                $this->records[] = [
                    'id' => $id,
                    'metric_id' => $metricId,
                    'data_type_id' => StatisticsTable::DATA_TYPE_CHANGE,
                    'value' => self::VALUE_CHANGE,
                    'date' => '2001-01-01 00:00:00',
                    'created' => '2021-01-01 00:00:00',
                    'modified' => '2021-01-01 00:00:00',
                ];
                $id++;
                $this->records[] = [
                    'id' => $id,
                    'metric_id' => $metricId,
                    'data_type_id' => StatisticsTable::DATA_TYPE_PERCENT_CHANGE,
                    'value' => self::VALUE_CHANGE_PERCENT,
                    'date' => '2001-01-01 00:00:00',
                    'created' => '2021-01-01 00:00:00',
                    'modified' => '2021-01-01 00:00:00',
                ];
                $id++;
                $metricId++;
            }
        }
        parent::init();
    }
}
