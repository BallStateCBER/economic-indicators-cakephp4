<h2>
    United States
</h2>
<ul>
    <li>
        <?= $this->Html->link(
            'Housing',
            [
                'controller' => 'Data',
                'action' => 'housing',
            ]
        ) ?>
    </li>
    <li>
        <?= $this->Html->link(
            'Vehicle Sales',
            [
                'controller' => 'Data',
                'action' => 'vehicleSales',
            ]
        ) ?>
    </li>
    <li>
        <?= $this->Html->link(
            'Retail and Food Services',
            [
                'controller' => 'Data',
                'action' => 'retailFoodServices',
            ]
        ) ?>
    </li>
    <li>
        <?= $this->Html->link(
            'Gross Domestic Product',
            [
                'controller' => 'Data',
                'action' => 'gdp',
            ]
        ) ?>
    </li>
</ul>

<h2>
    Indiana
</h2>
<ul>
    <li>
        <?= $this->Html->link(
            'Unemployment Rate',
            [
                'controller' => 'Data',
                'action' => 'unemployment',
            ]
        ) ?>
    </li>
</ul>
