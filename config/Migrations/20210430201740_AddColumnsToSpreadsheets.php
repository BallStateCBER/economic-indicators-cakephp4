<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddColumnsToSpreadsheets extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('spreadsheets');
        $table
            ->addColumn('needs_update', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'is_time_series',
            ])
            ->addColumn('file_generation_started', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'needs_update',
            ]);
        $table->update();
    }
}
