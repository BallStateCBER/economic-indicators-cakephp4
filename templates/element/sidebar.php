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
        foreach ($links as $groupName => $label) {
            echo '<li>';
            echo $view->Html->link(
                $label,
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => $groupName,
                ]
            );
            echo '</li>';
        }
    }
}
?>

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
