<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Formatter\Formatter;
use App\Model\Entity\Metric;
use Cake\Cache\Cache;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenDate;
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
    public const DATA_TYPES = [
        self::DATA_TYPE_VALUE,
        self::DATA_TYPE_CHANGE,
        self::DATA_TYPE_PERCENT_CHANGE,
    ];
    public const CACHE_CONFIG = 'observations';
    private bool $overwriteCache;
    private bool $useCache;

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

        $this->useCache = !(defined('RUNNING_TEST') && RUNNING_TEST);
        $this->overwriteCache = (defined('OVERWRITE_CACHE') && OVERWRITE_CACHE);
    }

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
     * Returns a cache key to be used for caching statistics for generating sparklines
     *
     * @param string $groupCacheKey The name of a group of endpoints
     * @return string
     */
    public static function getSparklinesCacheKey(string $groupCacheKey): string
    {
        return sprintf(
            '%s-sparklines',
            $groupCacheKey,
        );
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
     * Reads from and writes to the cache if not in a test context
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @param bool $all TRUE to return statistics for all dates
     * @return array
     */
    public function getGroup(array $endpointGroup, bool $all = false): array
    {
        $retval = [];
        $generateNewResults = !$this->useCache || $this->overwriteCache;
        foreach ($endpointGroup['endpoints'] as $endpoint) {
            $seriesId = $endpoint['id'];
            $retval[$seriesId]['name'] = $endpoint['name'];
            $metric = $this->Metrics->getFromSeriesId($seriesId);
            foreach (self::DATA_TYPES as $dataTypeId) {
                // Use cached value if possible
                $cacheKey = self::getStatsCacheKey($seriesId, $dataTypeId, $all);
                $cachedResult = $generateNewResults ? false : Cache::read($cacheKey, self::CACHE_CONFIG);
                if ($cachedResult) {
                    $retval[$seriesId]['statistics'][$dataTypeId] = $cachedResult;
                    unset($cachedResult);
                    continue;
                }

                // Generate new value
                $generatedResult = $this->getByMetricAndType($metric->id, $dataTypeId, $all);
                $retval[$seriesId]['statistics'][$dataTypeId] = $generatedResult;

                // And cache it, if appropriate
                if ($this->useCache) {
                    Cache::write($cacheKey, $generatedResult, self::CACHE_CONFIG);
                }
                unset($generatedResult);
            }
        }

        return $retval;
    }

    /**
     * Returns an array describing the date range of known statistics, using the cache if appropriate
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return array
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function getDateRange(array $endpointGroup): array
    {
        $firstEndpoint = reset($endpointGroup['endpoints']);
        $seriesId = $firstEndpoint['id'];
        $cacheKey = self::getDateRangeCacheKey($seriesId);
        $generateNewResults = !$this->useCache || $this->overwriteCache;
        $cachedResult = $generateNewResults ? false : Cache::read($cacheKey, self::CACHE_CONFIG);
        if ($cachedResult) {
            return $cachedResult;
        }

        $firstEndpoint = reset($endpointGroup['endpoints']);
        $seriesId = $firstEndpoint['id'];
        $metric = $this->Metrics->getFromSeriesId($seriesId);
        $frequency = $this->Metrics->getFrequency($endpointGroup);
        $dateRange = [
            Formatter::getFormattedDate($this->getDateBoundaryForMetric($metric), $frequency),
            Formatter::getFormattedDate($this->getDateBoundaryForMetric($metric, 'last'), $frequency),
        ];

        if ($this->useCache) {
            Cache::write($cacheKey, $dateRange, self::CACHE_CONFIG);
        }

        unset(
            $cacheKey,
            $firstEndpoint,
            $frequency,
            $metric,
            $seriesId,
        );

        return $dateRange;
    }

    /**
     * Returns the first or last date of statistics associated with this metric
     *
     * @param \App\Model\Entity\Metric $metric Metric entity
     * @param string $boundary Either 'first' or 'last'
     * @return \Cake\I18n\FrozenDate
     * @throws \Cake\Http\Exception\InternalErrorException
     * @throws \Cake\Http\Exception\NotFoundException
     */
    private function getDateBoundaryForMetric(Metric $metric, $boundary = 'first'): FrozenDate
    {
        if (!in_array($boundary, ['first', 'last'])) {
            throw new InternalErrorException('Invalid date boundary: ' . $boundary);
        }

        /** @var \App\Model\Entity\Statistic|null $stat */
        $stat = $this
            ->find()
            ->select(['id', 'date'])
            ->where(['metric_id' => $metric->id])
            ->order([
                'date' => $boundary == 'first' ? 'ASC' : 'DESC',
            ])
            ->first();

        if (!$stat) {
            throw new NotFoundException(sprintf(
                'No statistics were found for metric #%s',
                $metric->id,
            ));
        }

        return $stat->date;
    }

    /**
     * Returns an array of data used to create sparklines
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return array|null
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function getStatsForSparklines(array $endpointGroup): ?array
    {
        $generateNewResults = !$this->useCache || $this->overwriteCache;
        $parentCacheKey = self::getSparklinesCacheKey($endpointGroup['cacheKey']);
        $cachedResults = $generateNewResults ? false : Cache::read($parentCacheKey, self::CACHE_CONFIG);

        if ($cachedResults) {
            return $cachedResults;
        }

        $statsForSparklines = [];
        $maxDataPointsPerGraph = 50; // Applied inexactly
        $metrics = $this->Metrics->getAllForEndpointGroup($endpointGroup);

        foreach ($metrics as $metric) {
            // Get cached statistics
            $childCacheKey = self::getStatsCacheKey(
                seriesId: $metric->name,
                dataTypeId: StatisticsTable::DATA_TYPE_VALUE,
                all: true
            );
            $cachedResults = $generateNewResults ? false : Cache::read($childCacheKey, self::CACHE_CONFIG);
            if ($cachedResults) {
                $statistics = $cachedResults;

            // Or fetch a new set of statistics
            } else {
                $generatedResults = $this->getByMetricAndType(
                    metricId: $metric->id,
                    dataTypeId: self::DATA_TYPE_VALUE,
                    all: true
                );

                if (!$generatedResults) {
                    throw new NotFoundException(sprintf(
                        'Statistics not found for metric #%s, data type %d',
                        $metric->id,
                        self::DATA_TYPE_VALUE
                    ));
                }

                $statistics = $generatedResults;
            }

            $columnData = [['#', 'Value']];
            $count = count($statistics);
            $rate = round($count / $maxDataPointsPerGraph);
            foreach ($statistics as $i => $statistic) {
                // Limit number of data points stored
                if ($count > $maxDataPointsPerGraph && $i % $rate != 0) {
                    continue;
                }

                $columnData[] = [$i, (float)$statistic['value']];
            }

            $statsForSparklines[$metric->name] = $columnData;
        }

        // Write to the cache, if appropriate
        if ($this->useCache) {
            Cache::write($parentCacheKey, $statsForSparklines, self::CACHE_CONFIG);
        }

        return $statsForSparklines;
    }

    /**
     * Returns an array of the first date associated with statistics belonging to each endpoint
     *
     * Uses ths cache if appropriate
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return array
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function getStartingDates(array $endpointGroup): array
    {
        $generateNewResults = !$this->useCache || $this->overwriteCache;
        $cacheKey = $this->getStartingDateCacheKey($endpointGroup['cacheKey']);

        // Fetch from cache
        $cachedResults = $generateNewResults ? false : Cache::read($cacheKey, self::CACHE_CONFIG);
        if ($cachedResults) {
            return $cachedResults;
        }

        // Or generate new results
        $startingDates = [];
        foreach ($endpointGroup['endpoints'] as $endpoint) {
            $seriesId = $endpoint['id'];
            $metric = $this->Metrics->getFromSeriesId($seriesId);
            $startingDates[$seriesId] = $this->getDateBoundaryForMetric($metric);
        }

        // Write to the cache, if appropriate
        if ($this->useCache) {
            Cache::write($cacheKey, $startingDates, self::CACHE_CONFIG);
        }

        return $startingDates;
    }

    /**
     * Returns a cache key to be used for caching the starting dates for a group of endpoints
     *
     * @param string $groupCacheKey String used for caching data associated with a group of endpoints
     * @return string
     */
    public function getStartingDateCacheKey(string $groupCacheKey): string
    {
        return sprintf(
            '%s-starting',
            $groupCacheKey,
        );
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
