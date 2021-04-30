<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Spreadsheet Entity
 *
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $file_generation_started
 * @property \Cake\I18n\FrozenTime $modified
 * @property bool $is_time_series
 * @property bool $needs_update
 * @property int $id
 * @property string $filename
 * @property string $group_name
 */
class Spreadsheet extends Entity
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
        'created' => true,
        'file_generation_started' => true,
        'filename' => true,
        'group_name' => true,
        'is_time_series' => true,
        'modified' => true,
        'needs_update' => true,
    ];
}
