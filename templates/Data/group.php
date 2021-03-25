<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\I18n\FrozenDate $nextRelease
 * @var array $statsForSparklines
 * @var array|bool $statistics
 * @var string $dateRange
 * @var string $frequency
 * @var string $groupName
 * @var string $lastUpdated
 * @var string $prepend
 * @var string $unit
 */

use App\Formatter\Formatter;
use App\Model\Table\StatisticsTable;

$this->Html->css('/fontawesome/css/all.min.css', ['block' => true]);
?>

<?php if ($statistics === false): ?>
    <p class="alert alert-info">
        Sorry, this data set is currently unavailable. Please check back for an update soon.
    </p>
<?php else: ?>
    <p class="text-info">
        <i class="fas fa-info-circle"></i> Click on metric graphs to view expanded time series data
    </p>

    <div class="row">
        <p class="col-lg">
            <?= ucfirst($frequency) ?> data -
            Last updated <?= $lastUpdated ?>
        </p>
        <?php if ($nextRelease): ?>
            <p class="col-lg text-lg-right">
                Next update: <?= $nextRelease->format('F j, Y') ?>*
            </p>
        <?php endif; ?>
    </div>

    <table class="table observations">
        <thead>
            <tr>
                <th></th>
                <th>
                    Latest Value
                    <br />
                    <small>
                        <?= $unit ?>
                    </small>
                </th>
                <th>
                    Change
                    <br />
                    <small>
                        from One Year Ago
                    </small>
                </th>
                <th>
                    % Change
                    <br />
                    <small>
                        from One Year Ago
                    </small>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0; ?>
            <?php foreach ($statistics as $seriesId => $seriesData): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            sprintf('%s <div id="sparkline-%d" class="sparkline"></div>', $seriesData['name'], $i),
                            [
                                'action' => 'series',
                                'groupName' => $groupName,
                                'seriesId' => $seriesId,
                            ],
                            [
                                'escape' => false,
                                'title' => 'Click to view time series',
                            ]
                        ) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue(
                            $seriesData['statistics'][StatisticsTable::DATA_TYPE_VALUE]['value'],
                            $prepend
                        ) ?>
                        <br />
                        <span class="date-footnote">
                            <?= Formatter::getFormattedDate(
                                $seriesData['statistics'][StatisticsTable::DATA_TYPE_VALUE]['date'],
                                $frequency
                            ) ?>
                        </span>
                    </td>
                    <td>
                        <?= Formatter::formatValue(
                            $seriesData['statistics'][StatisticsTable::DATA_TYPE_CHANGE]['value'],
                            $prepend
                        ) ?>
                        <?= Formatter::getArrow(
                            $seriesData['statistics'][StatisticsTable::DATA_TYPE_CHANGE]['value']
                        ) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue(
                            $seriesData['statistics'][StatisticsTable::DATA_TYPE_PERCENT_CHANGE]['value']
                        ) ?>%
                        <?= Formatter::getArrow(
                            $seriesData['statistics'][StatisticsTable::DATA_TYPE_PERCENT_CHANGE]['value']
                        ) ?>
                    </td>
                </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row">
        <p class="download-link col-lg">
            <?= $this->Html->link(
                '<i class="fas fa-download"></i> Download this data as an Excel spreadsheet',
                [
                    'action' => 'download',
                    'groupName' => $this->getRequest()->getParam('groupName'),
                ],
                ['escape' => false, 'class' => 'alert alert-info']
            ) ?>
        </p>
        <p class="download-link col-lg">
            <?= $this->Html->link(
                '<i class="fas fa-download"></i> Download ' . $dateRange . ' time series data as an Excel spreadsheet',
                [
                    'action' => 'download',
                    'groupName' => $this->getRequest()->getParam('groupName'),
                    '?' => ['timeSeries' => 1],
                ],
                ['escape' => false, 'class' => 'alert alert-info']
            ) ?>
        </p>
    </div>

    <p class="disclaimer">
        * <?= $this->element('release_date_disclaimer') ?>
    </p>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawSparklines);

        function drawSparklines() {
            const options = {
                axisTitlesPosition: 'none',
                chartArea: {
                    height: '100%',
                    width: '100%'
                },
                enableInteractivity: false,
                hAxis: {
                    baselineColor: 'transparent',
                    gridlines: {count: 0},
                    textPosition: 'none',
                    viewWindowMode: 'maximized',
                },
                legend: {position: 'none'},
                vAxis: {
                    baselineColor: 'transparent',
                    gridlines: {count: 0},
                    textPosition: 'none',
                    viewWindowMode: 'maximized',
                },
            };

            let data, chart;
            <?php $i = 0; ?>
            <?php foreach ($statsForSparklines as $seriesId => $statistics): ?>
                data = google.visualization.arrayToDataTable(<?= json_encode($statistics) ?>);
                chart = new google.visualization.LineChart(document.getElementById('sparkline-<?= $i ?>'));
                chart.draw(data, options);
                <?php $i++; ?>
            <?php endforeach; ?>
        }
    </script>
<?php endif; ?>
