<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSpreadsheets extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up()
    {
        $table = $this->table('spreadsheets');
        $table
            ->addColumn('group_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('is_time_series', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('filename', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ]);
        $table->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down()
    {
        $this->table('spreadsheets')->drop()->save();
    }
}
