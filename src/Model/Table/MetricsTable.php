<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Metric;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Metrics Model
 *
 * @property \App\Model\Table\StatisticsTable&\Cake\ORM\Association\HasMany $Statistics
 * @method \App\Model\Entity\Metric newEmptyEntity()
 * @method \App\Model\Entity\Metric newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Metric[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Metric get($primaryKey, $options = [])
 * @method \App\Model\Entity\Metric findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Metric patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Metric[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Metric|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Metric saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Metric[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Metric[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Metric[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Metric[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MetricsTable extends Table
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

        $this->setTable('metrics');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Statistics', [
            'foreignKey' => 'metric_id',
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
            ->scalar('name')
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->dateTime('last_updated')
            ->allowEmptyDateTime('last_updated');

        $validator
            ->scalar('units')
            ->maxLength('units', 100)
            ->requirePresence('units', 'create')
            ->notEmptyString('units');

        $validator
            ->scalar('frequency')
            ->maxLength('frequency', 20)
            ->requirePresence('frequency', 'create')
            ->notEmptyString('frequency');

        return $validator;
    }

    /**
     * Looks up the frequency (Monthly, Quarterly, etc.) of the first metric associated with this group of endpoints
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return string
     */
    public function getFrequency(array $endpointGroup): string
    {
        $metricName = $endpointGroup['endpoints'][0]['id'];
        /** @var \App\Model\Entity\Metric $metric */
        $metric = $this
            ->find()
            ->select(['frequency'])
            ->where(['name' => $metricName])
            ->first();

        if (!$metric) {
            throw new InternalErrorException("Metric $metricName not found");
        }

        return $metric->frequency;
    }

    /**
     * Returns the first metric associated with this group of endpoints
     *
     * @param array $endpointGroup A group defined in \App\Fetcher\EndpointGroups
     * @return \App\Model\Entity\Metric
     */
    public function getForEndpointGroup(array $endpointGroup): Metric
    {
        $metricName = $endpointGroup['endpoints'][0]['id'];
        /** @var \App\Model\Entity\Metric $metric */
        $metric = $this
            ->find()
            ->where(['name' => $metricName])
            ->first();

        if (!$metric) {
            throw new InternalErrorException("Metric $metricName not found");
        }

        return $metric;
    }
}
