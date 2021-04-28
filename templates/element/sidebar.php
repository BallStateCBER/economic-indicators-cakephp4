<?php
/**
 * @var \App\View\AppView $this
 */

use App\View\AppView;

$usLinks = [
    'Consumer & Housing' => [
        'csi' => 'Consumer Sentiment Index',
        'housing-starts' => 'Housing Starts',
        'personal-income' => 'Personal Income',
        'housing-indicators' => 'Housing Indicators',
    ],
    'GDP' => [
        'gdp' => 'Gross Domestic Product',
    ],
    'Industry' => [
        'durable-goods' => 'Durable Goods Orders',
        'industrial-production' => 'Industrial Production',
        'vehicle-sales' => 'Vehicle Sales',
        'retail-food-services' => 'Retail & Food Services',
    ],
    'Labor' => [
        'manufacturing-employment' => 'Manufacturing Employment by State',
        'labor-force-statistics' => 'Labor Force Statistics',
        'unemployment-by-state' => 'Unemployment Rate by State',
    ],
    'Money' => [
        'interest-rates' => 'Interest Rates',
        'money-supply' => 'Money Supply',
    ],
    'Prices' => [
        'cpi' => 'Consumer Price Index',
        'iei' => 'Inflation Expectation Index',
        'gold' => 'Price of Gold',
        'oil' => 'Price of Oil',
    ],
];
asort($usLinks);

$inLinks = [
    'unemployment' => 'Unemployment Rate',
    'employment-by-sector' => 'Employment by Sector',
    'earnings' => 'Weekly Earnings',
    'indiana-gdp' => 'State GDP',
    'indiana-house-prices' => 'House Prices',
    'indiana-labor-force' => 'Labor Force',
    'indiana-unemployment-claims' => 'Unemployment Claims',
];
asort($inLinks);

$countyLinks = [
    'county-unemployment' => 'Unemployment',
    'income-by-county' => 'Personal Income',
    'population-by-county' => 'Population',
];
asort($countyLinks);

if (!function_exists('showSidebarLinks')) {
    /**
     * @param \App\View\AppView $view AppView
     * @param array $links Links array
     * @return void
     */
    function showSidebarLinks(AppView $view, array $links)
    {
        foreach ($links as $groupId => $label) {
            echo '<li>';
            echo $view->Html->link(
                $label,
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupId' => $groupId,
                ]
            );
            echo '</li>';
        }
    }
}
?>

<div class="navbar-light navbar-expand-md sidebar-collapse">
    <div class="row d-md-none">
        <div class="col">
            <h2 data-toggle="collapse" data-target="#sidebar-links">
                Menu
            </h2>
        </div>
        <div class="col-2">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidebar-links"
                    aria-controls="sidebar-links" aria-expanded="false" aria-label="Toggle menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </div>

    <div id="sidebar-links" class="collapse navbar-collapse">
        <section>
            <?= $this->Html->link('<h2>Home</h2>', '/', ['escape' => false])  ?>
        </section>

        <section>
            <h2>
                United States
            </h2>
            <?php foreach ($usLinks as $category => $catLinks): ?>
                <section class="us-group">
                    <h3>
                        <?= $category ?>
                    </h3>
                    <ul>
                        <?php
                            asort($catLinks);
                            showSidebarLinks($this, $catLinks);
                        ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </section>

        <section>
            <h2>
                Indiana
            </h2>
            <ul>
                <?php showSidebarLinks($this, $inLinks); ?>
            </ul>
        </section>

        <section>
            <h2>
                Indiana Counties
            </h2>
            <ul>
                <?php showSidebarLinks($this, $countyLinks); ?>
            </ul>
        </section>
    </div>
</div>
