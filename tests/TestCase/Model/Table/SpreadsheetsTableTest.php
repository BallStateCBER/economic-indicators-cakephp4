<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SpreadsheetsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SpreadsheetsTable Test Case
 */
class SpreadsheetsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SpreadsheetsTable
     */
    protected $Spreadsheets;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Spreadsheets',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Spreadsheets') ? [] : ['className' => SpreadsheetsTable::class];
        $this->Spreadsheets = $this->getTableLocator()->get('Spreadsheets', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Spreadsheets);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
