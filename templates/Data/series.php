<?php
/**
 * @var array $statsForGraph
 * @var string $endpointGroupId
 * @var string $endpointGroupName
 */
?>

<p>
    <?= $this->Html->link(
        '<i class="fas fa-arrow-circle-left"></i> Back to ' . $endpointGroupName,
        [
            'action' => 'group',
            'groupName' => $endpointGroupId,
        ],
        [
            'class' => 'btn btn-secondary',
            'escape' => false,
        ]
    ) ?>
</p>

<div id="series-graph">
    Loading graph... <i class="fas fa-spinner fa-spin"></i>
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawSparklines);

    function drawSparklines() {
        const options = {
            chartArea: {
                height: '70%',
                width: '80%'
            },
            hAxis: {
                format: 'y',
            },
            legend: {position: 'bottom'},
        };

        const data = google.visualization.arrayToDataTable(<?= json_encode($statsForGraph) ?>);
        const chart = new google.visualization.LineChart(document.getElementById('series-graph'));
        chart.draw(data, options);
    }
</script>
