<?php
/**
 * @var \App\View\AppView $this
 */

use App\View\AppView;

$usLinks = [
    'housing' => 'Housing',
    'vehicle-sales' => 'Vehicle Sales',
    'retail-food-services' => 'Retail & Food Services',
    'gdp' => 'Gross Domestic Product',
    'manufacturing-employment' => 'Manufacturing Employment by State',
    'durable-goods' => 'Durable Goods Orders',
];

$inLinks = [
    'unemployment' => 'Unemployment Rate',
    'employment-by-sector' => 'Employment by Sector',
    'earnings' => 'Weekly Earnings',
];

$countyLinks = [
    'county-unemployment' => 'Unemployment',
];

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
            <ul>
                <?php showSidebarLinks($this, $usLinks); ?>
            </ul>
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
