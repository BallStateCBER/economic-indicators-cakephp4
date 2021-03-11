<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Formatter\Formatter;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Statistics Model
 *
 * @property \App\Model\Table\MetricsTable&\Cake\ORM\Association\BelongsTo $Metrics
 *
 * @method \App\Model\Entity\Statistic newEmptyEntity()
 * @method \App\Model\Entity\Statistic newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Statistic[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Statistic get($primaryKey, $options = [])
 * @method \App\Model\Entity\Statistic findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Statistic patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Statistic[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Statistic|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Statistic saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Statistic[]|ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statistic[]|ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statistic[]|ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Statistic[]|ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class StatisticsTable extends Table
{
    public const DATA_TYPE_VALUE = 1;
    public const DATA_TYPE_CHANGE = 2;
    public const DATA_TYPE_PERCENT_CHANGE = 3;

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
     * Returns all of the values, changes since last year, and percent changes for all metrics in the provided group
     *
     * @param array $seriesGroup A group defined in \App\Fetcher\SeriesGroups
     * @return array
     */
    public function getGroup(array $seriesGroup)
    {
        $updated = null;
        $series = [];
        foreach ($seriesGroup['endpoints'] as $endpoint) {
            $seriesId = $endpoint['seriesId'];
            /** @var \App\Model\Entity\Metric $metric */
            $metric = $this->Metrics->find()->where(['name' => $seriesId])->first();
            if (!$metric) {
                throw new InternalErrorException(sprintf('Metric named %s not found', $seriesId));
            }

            if (!$updated) {
                $updated = $metric->last_updated;
            }

            $seriesName = $endpoint['subvar'];
            $series[$seriesName] = [
                'units' => $metric->units,
                'frequency' => $metric->frequency,
                'value' => $this->getValues($metric->id),
                'change' => $this->getChanges($metric->id),
                'percentChange' => $this->getPercentChanges($metric->id),
            ];
        }

        return compact('updated', 'series');
    }

    /**
     * Returns a string describing the date range of known statistics
     *
     * @param array $seriesGroup Group of endpoints
     * @param string $frequency Frequency string
     * @return string
     */
    public function getDateRange(array $seriesGroup, string $frequency): string
    {
        $firstEndpoint = reset($seriesGroup['endpoints']);
        $seriesId = $firstEndpoint['seriesId'];
        $metric = $this->Metrics->find()->where(['name' => $seriesId])->first();
        $query = $this
            ->find()
            ->select(['id', 'date'])
            ->where(['metric_id' => $metric->id]);
        $firstStat = $query->order(['date' => 'ASC'])->first();
        $lastStat = $query->order(['date' => 'DESC'])->first();

        return sprintf(
            '%s - %s',
            Formatter::getFormattedDate($firstStat->date, $frequency),
            Formatter::getFormattedDate($lastStat->date, $frequency),
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
     * Returns all 'value' (normal units) statistics for the given metric
     *
     * @param int $metricId Metric ID
     * @param bool $all TRUE to return all results rather than only the most recent
     * @return \Cake\Datasource\ResultSetInterface|\App\Model\Entity\Statistic
     */
    private function getValues(int $metricId, bool $all = false)
    {
        $query = $this->find(
            'byMetricAndType',
            ['metric_id' => $metricId, 'data_type_id' => StatisticsTable::DATA_TYPE_VALUE]
        );
        if ($all) {
            return $query->all();
        }

        return $query->last();
    }

    /**
     * Returns all 'change from previous year' statistics for the given metric
     *
     * @param int $metricId Metric ID
     * @param bool $all TRUE to return all results rather than only the most recent
     * @return \Cake\Datasource\ResultSetInterface|\App\Model\Entity\Statistic
     */
    private function getChanges(int $metricId, bool $all = false)
    {
        $query = $this->find(
            'byMetricAndType',
            ['metric_id' => $metricId, 'data_type_id' => StatisticsTable::DATA_TYPE_CHANGE]
        );
        if ($all) {
            return $query->all();
        }

        return $query->last();
    }

    /**
     * Returns all 'percent change from previous year' statistics for the given metric
     *
     * @param int $metricId Metric ID
     * @param bool $all TRUE to return all results rather than only the most recent
     * @return \Cake\Datasource\ResultSetInterface|\App\Model\Entity\Statistic
     */
    private function getPercentChanges(int $metricId, bool $all = false)
    {
        $query = $this->find(
            'byMetricAndType',
            ['metric_id' => $metricId, 'data_type_id' => StatisticsTable::DATA_TYPE_PERCENT_CHANGE]
        );
        if ($all) {
            return $query->all();
        }

        return $query->last();
    }
}
