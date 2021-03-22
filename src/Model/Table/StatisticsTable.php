<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Formatter\Formatter;
use Cake\Cache\Cache;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Statistics Model
 *
 * @property \App\Model\Table\MetricsTable&\Cake\ORM\Association\BelongsTo $Metrics
 * @method \App\Model\Entity\Statistic newEmptyEntity()
 * @method \App\Model\Entity\Statistic newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Statistic[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Statistic get($primaryKey, $options = [])
 * @method \App\Model\Entity\Statistic findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Statistic patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Statistic[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Statistic|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Statistic saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Statistic[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statistic[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statistic[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statistic[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class StatisticsTable extends Table
{
    public const DATA_TYPE_VALUE = 1;
    public const DATA_TYPE_CHANGE = 2;
    public const DATA_TYPE_PERCENT_CHANGE = 3;
    public const CACHE_CONFIG = 'observations';

    /**
     * Returns a cache key to be used for caching sets of statistics
     *
     * @param string $seriesId A FRED API seriesID string
     * @param int $dataTypeId An integer representing observations, changes, or percent changes
     * @param bool $all TRUE if data for all dates rather than all
     * @return string
     */
    public static function getStatsCacheKey(mixed $seriesId, int $dataTypeId, bool $all): string
    {
        return sprintf(
            '%s-%s-%s',
            $seriesId,
            $dataTypeId,
            $all ? 'all' : 'last'
        );
    }

    /**
     * Returns a cache key to be used for caching a date range for a set of statistics
     *
     * @param string $seriesId A FRED API seriesID string
     * @return string
     */
    public static function getDateRangeCacheKey(mixed $seriesId): string
    {
        return sprintf(
            '%s-range',
            $seriesId,
        );
    }

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('statistics');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Metrics', [
            'foreignKey' => 'metric_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('DataTypes', [
            'foreignKey' => 'data_type_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('value')
            ->maxLength('value', 30)
            ->allowEmptyString('value');

        $validator
            ->date('date')
            ->requirePresence('date', 'create');

        $validator
            ->integer('data_type_id')
            ->requirePresence('data_type_id', 'create')
            ->greaterThan('data_type_id', 0);

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['metric_id'], 'Metrics'), ['errorField' => 'metric_id']);

        return $rules;
    }

    /**
     * Returns values, changes since last year, and percent changes for all metrics in the provided group
     *
     * If $all is TRUE, returns statistics for all dates. Otherwise, only returns the most recent statistic.
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @param bool $all TRUE to return statistics for all dates
     * @return array
     */
    public function getGroup(array $endpointGroup, bool $all = false): array
    {
        $statistics = [];
        $dataTypeIds = [
            self::DATA_TYPE_VALUE,
            self::DATA_TYPE_CHANGE,
            self::DATA_TYPE_PERCENT_CHANGE,
        ];
        foreach ($endpointGroup['endpoints'] as $endpoint) {
            $seriesId = $endpoint['id'];
            $seriesName = $endpoint['name'];
            foreach ($dataTypeIds as $dataTypeId) {
                $cacheKey = self::getStatsCacheKey($seriesId, $dataTypeId, $all);
                $result = Cache::read($cacheKey, self::CACHE_CONFIG);
                if (!$result) {
                    $this->cacheGroup($endpointGroup, $all);
                    $result = Cache::read($cacheKey, self::CACHE_CONFIG);
                }
                $statistics[$seriesName][$dataTypeId] = $result;
                unset($result);
            }
        }

        return $statistics;
    }

    /**
     * Returns a string describing the date range of known statistics
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return string
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function getDateRange(array $endpointGroup): string
    {
        $firstEndpoint = reset($endpointGroup['endpoints']);
        $seriesId = $firstEndpoint['id'];
        $cacheKey = self::getDateRangeCacheKey($seriesId);
        $result = Cache::read($cacheKey, self::CACHE_CONFIG);

        if (!$result) {
            $this->cacheDateRange($endpointGroup);
            $result = Cache::read($cacheKey, self::CACHE_CONFIG);
        }

        return $result;
    }

    /**
     * Creates cache files for all metrics in the provided group, deleting any existing cache files
     *
     * If $all is TRUE, caches statistics for all dates. Otherwise, only caches the most recent statistic.
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @param bool $all TRUE to return statistics for all dates
     * @return void
     */
    public function cacheGroup(array $endpointGroup, bool $all = false)
    {
        $dataTypeIds = [
            self::DATA_TYPE_VALUE,
            self::DATA_TYPE_CHANGE,
            self::DATA_TYPE_PERCENT_CHANGE,
        ];
        foreach ($endpointGroup['endpoints'] as $endpoint) {
            $seriesId = $endpoint['id'];
            /** @var \App\Model\Entity\Metric $metric */
            $metric = $this->Metrics->find()->where(['name' => $seriesId])->first();
            if (!$metric) {
                throw new InternalErrorException(sprintf('Metric named %s not found', $seriesId));
            }

            foreach ($dataTypeIds as $dataTypeId) {
                $cacheKey = self::getStatsCacheKey($seriesId, $dataTypeId, $all);
                $statistics = $this->getByMetricAndType($metric->id, StatisticsTable::DATA_TYPE_VALUE, $all);
                Cache::write($cacheKey, $statistics, self::CACHE_CONFIG);
                unset($statistics);
                unset($cacheKey);
            }
            unset($metric);
        }
        unset($dataTypeIds);
    }

    /**
     * Caches a string describing the date range of known statistics in this group
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function cacheDateRange(array $endpointGroup)
    {
        $firstEndpoint = reset($endpointGroup['endpoints']);
        $seriesId = $firstEndpoint['id'];
        $metric = $this->Metrics->find()->where(['name' => $seriesId])->first();
        $query = $this
            ->find()
            ->select(['id', 'date'])
            ->where(['metric_id' => $metric->id]);
        $firstStat = $query->order(['date' => 'ASC'])->first();
        if (!$firstStat) {
            throw new NotFoundException(sprintf(
                'No statistics were found for metric #%s (%s)',
                $metric->id,
                $seriesId,
            ));
        }
        $lastStat = $query->order(['date' => 'DESC'])->first();
        $frequency = $this->Metrics->getFrequency($endpointGroup);
        $dateRange = sprintf(
            '%s - %s',
            Formatter::getFormattedDate($firstStat->date, $frequency),
            Formatter::getFormattedDate($lastStat->date, $frequency),
        );

        $cacheKey = self::getDateRangeCacheKey($seriesId);
        Cache::write($cacheKey, $dateRange, self::CACHE_CONFIG);

        unset(
            $cacheKey,
            $dateRange,
            $firstEndpoint,
            $firstStat,
            $frequency,
            $lastStat,
            $metric,
            $query,
            $seriesId,
        );
    }

    /**
     * Returns an array of data used to create sparklines
     *
     * @param \App\Model\Entity\Metric[]|\Cake\Datasource\ResultSetInterface $metrics Array or ResultSet of metrics
     * @return array
     */
    public function getStatsForSparklines(array | ResultSetInterface $metrics): array
    {
        $statsForSparklines = [];

        // Applied inexactly
        $maxDataPointsPerGraph = 50;

        foreach ($metrics as $metric) {
            $cacheKey = self::getStatsCacheKey(
                seriesId: $metric->name,
                dataTypeId: StatisticsTable::DATA_TYPE_VALUE,
                all: true
            );
            $statistics = Cache::read($cacheKey, StatisticsTable::CACHE_CONFIG);
            $columnData = [['#', 'Value']];
            $count = count($statistics);
            $rate = round($count / $maxDataPointsPerGraph);
            foreach ($statistics as $i => $statistic) {
                // Limit number of data points collected
                if ($count > $maxDataPointsPerGraph) {
                    if ($i % $rate != 0) {
                        continue;
                    }
                }

                $columnData[] = [$i, (float)$statistic['value']];
            }
            $statsForSparklines[$metric->name] = $columnData;
        }

        return $statsForSparklines;
    }

    /**
     * Custom finder for getting statistics by metric ID and data type ID
     *
     * @param \Cake\ORM\Query $query Query object
     * @param array $options Options array, expecting metric_id and data_type_id
     * @return \Cake\ORM\Query
     */
    protected function findByMetricAndType(Query $query, array $options): Query
    {
        return $query
            ->select(['value', 'date'])
            ->where([
                'metric_id' => $options['metric_id'],
                'data_type_id' => $options['data_type_id'],
            ])
            ->orderAsc('date');
    }

    /**
     * Returns all statistics for the given metric and the given observations/changes/percent-changes
     *
     * @param int $metricId Metric ID
     * @param int $dataTypeId Data type ID
     * @param bool $all TRUE to return all results rather than only the most recent
     * @return \Cake\Datasource\ResultSetInterface|array
     */
    public function getByMetricAndType(int $metricId, int $dataTypeId, bool $all = false): ResultSetInterface | array
    {
        $query = $this
            ->find(
                'byMetricAndType',
                ['metric_id' => $metricId, 'data_type_id' => $dataTypeId]
            )
            ->enableHydration(false);
        if ($all) {
            return $query->all();
        }

        return $query->last();
    }
}
