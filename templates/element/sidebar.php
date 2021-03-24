<section>
    <h2>
        United States
    </h2>
    <ul>
        <li>
            <?= $this->Html->link(
                'Housing',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'housing',
                ]
            ) ?>
        </li>
        <li>
            <?= $this->Html->link(
                'Vehicle Sales',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'vehicle-sales',
                ]
            ) ?>
        </li>
        <li>
            <?= $this->Html->link(
                'Retail & Food Services',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'retail-food-services',
                ]
            ) ?>
        </li>
        <li>
            <?= $this->Html->link(
                'Gross Domestic Product',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'gdp',
                ]
            ) ?>
        </li>
        <li>
            <?= $this->Html->link(
                'Manufacturing Employment by State',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'manufacturing-employment',
                ]
            ) ?>
        </li>
    </ul>
</section>

<section>
    <h2>
        Indiana
    </h2>
    <ul>
        <li>
            <?= $this->Html->link(
                'Unemployment Rate',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'unemployment',
                ]
            ) ?>
        </li>
        <li>
            <?= $this->Html->link(
                'Employment by Sector',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'employment-by-sector',
                ]
            ) ?>
        </li>
        <li>
            <?= $this->Html->link(
                'Weekly Earnings',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'earnings',
                ]
            ) ?>
        </li>
    </ul>
</section>

<section>
    <h2>
        Indiana Counties
    </h2>
    <ul>
        <li>
            <?= $this->Html->link(
                'Unemployment ',
                [
                    'controller' => 'Data',
                    'action' => 'group',
                    'groupName' => 'county-unemployment',
                ]
            ) ?>
        </li>
    </ul>
</section>
