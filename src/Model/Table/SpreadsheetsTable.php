<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Spreadsheets Model
 *
 * @method \App\Model\Entity\Spreadsheet newEmptyEntity()
 * @method \App\Model\Entity\Spreadsheet newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Spreadsheet[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Spreadsheet get($primaryKey, $options = [])
 * @method \App\Model\Entity\Spreadsheet findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Spreadsheet patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Spreadsheet[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Spreadsheet|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Spreadsheet saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Spreadsheet[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Spreadsheet[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Spreadsheet[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Spreadsheet[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SpreadsheetsTable extends Table
{
    public const FILE_PATH = WWW_ROOT . 'spreadsheets' . DS;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('spreadsheets');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->scalar('group_name')
            ->maxLength('group_name', 50)
            ->requirePresence('group_name', 'create')
            ->notEmptyString('group_name');

        $validator
            ->boolean('is_time_series')
            ->notEmptyString('is_time_series');

        $validator
            ->scalar('filename')
            ->maxLength('filename', 100)
            ->requirePresence('filename', 'create')
            ->notEmptyFile('filename');

        return $validator;
    }
}
