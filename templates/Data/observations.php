<?php
/**
 * @var \App\View\AppView $this
 * @var array|bool $statistics
 * @var string $dateRange
 * @var string $frequency
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
    <p>
        <?= ucfirst($frequency) ?> data -
        Last updated <?= $lastUpdated ?>
    </p>

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
            <?php foreach ($statistics as $seriesId => $statsByDataType): ?>
                <tr>
                    <td>
                        <?= $seriesId ?>
                        <br />
                        <small>
                            <?= Formatter::getFormattedDate(
                                $statsByDataType[StatisticsTable::DATA_TYPE_VALUE]['date'],
                                $frequency
                            ) ?>
                        </small>
                    </td>
                    <td>
                        <?= Formatter::formatValue(
                            $statsByDataType[StatisticsTable::DATA_TYPE_VALUE]['value'],
                            $prepend
                        ) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue(
                            $statsByDataType[StatisticsTable::DATA_TYPE_CHANGE]['value'],
                            $prepend
                        ) ?>
                        <?= Formatter::getArrow(
                            $statsByDataType[StatisticsTable::DATA_TYPE_CHANGE]['value']
                        ) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue(
                            $statsByDataType[StatisticsTable::DATA_TYPE_PERCENT_CHANGE]['value']
                        ) ?>%
                        <?= Formatter::getArrow(
                            $statsByDataType[StatisticsTable::DATA_TYPE_PERCENT_CHANGE]['value']
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p class="download-link">
    <?= $this->Html->link(
        '<i class="fas fa-download"></i> Download this data as an Excel spreadsheet',
        ['_ext' => 'xlsx'],
        ['escape' => false, 'class' => 'alert alert-info']
    ) ?>
</p>
<p class="download-link">
    <?= $this->Html->link(
        '<i class="fas fa-download"></i> Download ' . $dateRange . ' time series data as an Excel spreadsheet',
        [
            '?' => ['timeSeries' => 1],
            '_ext' => 'xlsx',
        ],
        ['escape' => false, 'class' => 'alert alert-info']
    ) ?>
</p>
