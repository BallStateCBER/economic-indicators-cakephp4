<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Fetcher\EndpointGroups;
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

    private array $groupNames = [
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
     */
    public function setUp(): void
    {
        define('RUNNING_TEST', true);

        parent::setUp();
    }

    /**
     * Test group method
     *
     * @return void
     */
    public function testGroup(): void
    {
        foreach ($this->groupNames as $groupName) {
            $this->get([
                'controller' => 'Data',
                'action' => 'group',
                'groupName' => $groupName,
            ]);
            $this->assertResponseOk("Not-OK response returned for /data/group/$groupName");

            $endpointGroup = EndpointGroups::get($groupName);
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
        foreach ($this->groupNames as $groupName) {
            $endpointGroup = EndpointGroups::get($groupName);
            foreach ($endpointGroup['endpoints'] as $endpoint) {
                $this->get([
                    'controller' => 'Data',
                    'action' => 'series',
                    'groupName' => $groupName,
                    'seriesId' => $endpoint['id'],
                ]);
                $this->assertResponseOk("Not-OK response returned for /data/series/$groupName/{$endpoint['id']}");
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
