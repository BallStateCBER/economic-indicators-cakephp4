<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Fetcher\EndpointGroups;
use Cake\Cache\Cache;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenDate;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Releases Model
 *
 * @property \App\Model\Table\MetricsTable&\Cake\ORM\Association\BelongsTo $Metrics
 *
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
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ReleasesTable extends Table
{
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

        return Cache::remember($cacheKey, function () {
            $endpointGroups = EndpointGroups::getAll();
            $dates = [];
            foreach ($endpointGroups as $endpointGroup) {
                foreach ($endpointGroup['endpoints'] as $endpoint) {
                    $date = $this->getNextReleaseDate($endpoint['id']);
                    if ($date) {
                        $group = $endpoint['group'];
                        $dates[$date->format('Y-m-d')][$group][] = $endpoint['name'];
                    }
                }
            }
            ksort($dates);

            return $dates;
        }, 'observations');
    }

    /**
     * Returns the next date on or after the current date in which this metric has a release recorded
     *
     * @param string $metricName String to match with metrics.name
     * @return \Cake\I18n\FrozenDate|null
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function getNextReleaseDate(string $metricName): ?FrozenDate
    {
        $metric = $this->Metrics
            ->find()
            ->select(['id'])
            ->where(['name' => $metricName])
            ->first();
        if (!$metric) {
            throw new NotFoundException('Metric named ' . $metricName . ' not found');
        }

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

        return $release ? $release->date : null;
    }
}
