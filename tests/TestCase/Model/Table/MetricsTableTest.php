<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MetricsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MetricsTable Test Case
 */
class MetricsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MetricsTable
     */
    protected $Metrics;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Metrics',
        'app.Statistics',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Metrics') ? [] : ['className' => MetricsTable::class];
        $this->Metrics = $this->getTableLocator()->get('Metrics', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Metrics);

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
