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
</ul>
