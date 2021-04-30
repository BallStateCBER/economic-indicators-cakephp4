<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Spreadsheet;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Spreadsheets Model
 *
 * @method Spreadsheet newEmptyEntity()
 * @method Spreadsheet newEntity(array $data, array $options = [])
 * @method Spreadsheet[] newEntities(array $data, array $options = [])
 * @method Spreadsheet get($primaryKey, $options = [])
 * @method Spreadsheet findOrCreate($search, ?callable $callback = null, $options = [])
 * @method Spreadsheet patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Spreadsheet[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method Spreadsheet|false save(EntityInterface $entity, $options = [])
 * @method Spreadsheet saveOrFail(EntityInterface $entity, $options = [])
 * @method Spreadsheet[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method Spreadsheet[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method Spreadsheet[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method Spreadsheet[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
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
            ->boolean('needs_update')
            ->notEmptyString('needs_update');

        $validator
            ->dateTime('file_generation_started')
            ->allowEmptyDateTime('file_generation_started');

        $validator
            ->scalar('filename')
            ->maxLength('filename', 100);

        return $validator;
    }

    /**
     * Sets the file_generation_started field to the current time
     *
     * @param \App\Model\Entity\Spreadsheet|\Cake\Datasource\EntityInterface $spreadsheet Spreadsheet entity
     * @return \App\Model\Entity\Spreadsheet
     */
    public function recordFileGenerationStartTime(Spreadsheet | EntityInterface $spreadsheet)
    {
        $spreadsheet = $this->patchEntity(
            $spreadsheet,
            ['file_generation_started' => new FrozenTime()],
        );
        $this->save($spreadsheet);

        return $spreadsheet;
    }

    /**
     * Sets the file_generation_started field to NULL and needs_update to FALSE
     *
     * @param \App\Model\Entity\Spreadsheet|\Cake\Datasource\EntityInterface $spreadsheet Spreadsheet entity
     * @return \App\Model\Entity\Spreadsheet
     */
    public function recordFileGenerationDone(Spreadsheet | EntityInterface $spreadsheet)
    {
        $spreadsheet = $this->patchEntity($spreadsheet, [
            'file_generation_started' => null,
            'needs_update' => false,
        ]);
        $this->save($spreadsheet);

        return $spreadsheet;
    }

    /**
     * Modifies a query to fetch all spreadsheets that appear to have started file generation and failed to complete it
     *
     * @param \Cake\ORM\Query $query Query object
     * @param array $options Options, with 'wait' expected
     * @return \Cake\ORM\Query
     */
    protected function findFailedGeneration(Query $query, array $options): Query
    {
        $startedBefore = new FrozenTime('now - ' . $options['wait']);

        return $query
            ->where([
                function (QueryExpression $exp) use ($startedBefore) {
                    return $exp->lte('file_generation_started', $startedBefore);
                },
            ]);
    }
}
