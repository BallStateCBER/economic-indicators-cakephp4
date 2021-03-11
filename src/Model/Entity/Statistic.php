<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Statistic Entity
 *
 * @property int $id
 * @property int $metric_id
 * @property int $data_type_id
 * @property string|null $value
 * @property string $date
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\Metric $metric
 */
class Statistic extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'metric_id' => true,
        'data_type_id' => true,
        'value' => true,
        'date' => true,
        'created' => true,
        'modified' => true,
        'metric' => true,
    ];
}
