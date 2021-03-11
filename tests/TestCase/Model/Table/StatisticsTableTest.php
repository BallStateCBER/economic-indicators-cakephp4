<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StatisticsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\StatisticsTable Test Case
 */
class StatisticsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\StatisticsTable
     */
    protected $Statistics;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Statistics',
        'app.Metrics',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Statistics') ? [] : ['className' => StatisticsTable::class];
        $this->Statistics = $this->getTableLocator()->get('Statistics', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Statistics);

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

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
