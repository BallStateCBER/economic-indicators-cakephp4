<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Endpoints\EndpointGroups;
use App\Model\Entity\Metric;
use Cake\Cache\Cache;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\FrozenDate;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Releases Model
 *
 * @property \App\Model\Table\MetricsTable&\Cake\ORM\Association\BelongsTo $Metrics
 * @method \App\Model\Entity\Release newEmptyEntity()
 * @method \App\Model\Entity\Release newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Release[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Release get($primaryKey, $options = [])
 * @method \App\Model\Entity\Release findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Release patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Release[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Release|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Release saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Release[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Release[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Release[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Release[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ReleasesTable extends Table
{
    private bool $useCache;
    public const CACHE_CONFIG = 'releases';

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('releases');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Metrics', [
            'foreignKey' => 'metric_id',
            'joinType' => 'INNER',
        ]);

        $this->useCache = !(defined('RUNNING_TEST') && RUNNING_TEST);
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
            ->date('date')
            ->requirePresence('date', 'create')
            ->notEmptyDate('date');

        $validator
            ->boolean('imported')
            ->notEmptyString('imported');

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
     * Returns a YYYY-MM-DD keyed array of arrays of endpoints, with dates representing each endpoint's next release
     *
     * @return array
     */
    public function getNextReleaseDates(): array
    {
        $cacheKey = 'next_release_dates';
        $cachedResult = $this->useCache ? Cache::read($cacheKey, self::CACHE_CONFIG) : false;
        if ($cachedResult) {
            return $cachedResult;
        }

        $endpointGroups = EndpointGroups::getAll();
        $dates = [];
        foreach ($endpointGroups as $endpointGroup) {
            foreach ($endpointGroup['endpoints'] as $seriesId => $name) {
                $date = $this->getNextReleaseDate($seriesId);
                if ($date) {
                    $group = $endpointGroup['title'];
                    $dates[$date->format('Y-m-d')][$group][] = $name;
                }
            }
        }
        ksort($dates);

        if ($this->useCache) {
            Cache::write($cacheKey, $dates, self::CACHE_CONFIG);
        }

        return $dates;
    }

    /**
     * Returns the next date on or after the current date in which this metric has a release recorded
     *
     * @param string|\App\Model\Entity\Metric ...$metricNames Strings to match with metrics.name, or metric entities
     * @return \Cake\I18n\FrozenDate|null
     */
    public function getNextReleaseDate(string | Metric ...$metricNames): ?FrozenDate
    {
        $nextReleaseDate = null;

        foreach ($metricNames as $metricName) {
            $metric = is_string($metricName) ? $this->Metrics->getFromSeriesId($metricName) : $metricName;

            /** @var \App\Model\Entity\Release $release */
            $release = $this
                ->find()
                ->select(['date'])
                ->where([
                    'metric_id' => $metric->id,
                    function (QueryExpression $exp) {
                        return $exp->gte('date', (new FrozenDate())->format('Y-m-d'));
                    },
                ])
                ->orderAsc('date')
                ->first();

            if ($release && (!$nextReleaseDate || $release->date->lessThan($nextReleaseDate))) {
                $nextReleaseDate = $release->date;
            }
        }

        return $nextReleaseDate;
    }

    /**
     * Modifies a query to fetch all releases on or before the current date
     *
     * @param \Cake\ORM\Query $query Query object
     * @return \Cake\ORM\Query
     */
    protected function findCurrentAndPast(Query $query): Query
    {
        return $query
            ->where([
                function (QueryExpression $exp) {
                    return $exp->lte('date', (new FrozenDate())->format('Y-m-d'));
                },
            ]);
    }

    /**
     * Returns TRUE if there is a release on or before the current date that has not yet been imported
     *
     * @param int $metricId Metric ID
     * @return bool
     */
    public function newDataExpected(int $metricId): bool
    {
        $count = $this
            ->find('currentOrPast')
            ->where([
                'metric_id' => $metricId,
                'imported' => false,
            ])
            ->count();

        return $count > 0;
    }
}
