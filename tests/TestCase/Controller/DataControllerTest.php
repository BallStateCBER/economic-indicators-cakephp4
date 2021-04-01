<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Endpoints\EndpointGroups;
use App\Test\Fixture\StatisticsFixture;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\DataController Test Case
 *
 * @uses \App\Controller\DataController
 */
class DataControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Metrics',
        'app.Releases',
        'app.Statistics',
    ];

    private array $groupIds = [
        'county-unemployment',
        'earnings',
        'employment-by-sector',
        'gdp',
        'housing',
        'manufacturing-employment',
        'retail-food-services',
        'unemployment',
        'vehicle-sales',
    ];

    /**
     * Sets up this collection of tests
     *
     * @return void
     */
    public function setUp(): void
    {
        if (!defined('RUNNING_TEST')) {
            define('RUNNING_TEST', true);
        }

        parent::setUp();
    }

    /**
     * Test group method
     *
     * @return void
     */
    public function testGroup(): void
    {
        foreach ($this->groupIds as $groupId) {
            $this->get([
                'controller' => 'Data',
                'action' => 'group',
                'groupId' => $groupId,
            ]);
            $this->assertResponseOk("Not-OK response returned for /data/group/$groupId");

            $endpointGroup = EndpointGroups::get($groupId);
            $this->assertResponseContains($endpointGroup['title']);

            foreach ($endpointGroup['endpoints'] as $endpoint) {
                $this->assertResponseContains($endpoint['name']);
            }

            $this->assertResponseContains(StatisticsFixture::VALUE_FIRST);
            $this->assertResponseContains(StatisticsFixture::VALUE_SECOND);
            $this->assertResponseContains(
                StatisticsFixture::VALUE_CHANGE . '&nbsp;<i class="fas fa-arrow-circle-down"'
            );
            $this->assertResponseContains(
                StatisticsFixture::VALUE_CHANGE_PERCENT . '%&nbsp;<i class="fas fa-arrow-circle-up"'
            );
        }
    }

    /**
     * Test group method
     *
     * @return void
     */
    public function testSeries(): void
    {
        foreach ($this->groupIds as $groupId) {
            $endpointGroup = EndpointGroups::get($groupId);
            foreach ($endpointGroup['endpoints'] as $endpoint) {
                $this->get([
                    'controller' => 'Data',
                    'action' => 'series',
                    'groupId' => $groupId,
                    'seriesId' => $endpoint['seriesId'],
                ]);
                $this->assertResponseOk("Not-OK response returned for /data/series/$groupId/{$endpoint['seriesId']}");
                $this->assertResponseContains(sprintf(
                    '%s: %s',
                    $endpointGroup['title'],
                    $endpoint['name']
                ));
            }
        }
    }

    /**
     * Test download method
     *
     * @return void
     */
    public function testDownload(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
