<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class UpdateMetricsFrequency extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up()
    {
        $this->table('metrics')
            ->changeColumn('frequency', 'string', ['limit' => 100])
            ->save();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down()
    {
        $this->table('metrics')
            ->changeColumn('frequency', 'string', ['limit' => 20])
            ->save();
    }
}
