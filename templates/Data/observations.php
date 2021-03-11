<?php
/**
 * @var \App\View\AppView $this
 * @var array|bool $data
 * @var string $dateRange
 * @var string $frequency
 */

use App\Formatter\Formatter;

$this->Html->css('/fontawesome/css/all.min.css', ['block' => true]);

$unit = Formatter::getUnit($data);
$prepend = Formatter::getPrepend($unit);
?>

<p>
    <?= ucfirst($frequency) ?> data -
    Last updated <?= Formatter::getLastUpdated($data) ?>
</p>

<?php if ($data === false): ?>
    <p class="alert alert-info">
        Sorry, this data set is currently unavailable. Please check back for an update soon.
    </p>
<?php else: ?>
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
            <?php foreach ($data['series'] as $name => $series): ?>
                <?php
                    $lastObservations = [];
                    foreach (['value', 'change', 'percentChange'] as $type) {
                        $lastObservations[$type] = $series[$type]->last();
                    }
                ?>
                <tr>
                    <td>
                        <?= $name ?>
                        <br />
                        <small>
                            <?= Formatter::getFormattedDate($lastObservations['value']['date'], $frequency) ?>
                        </small>
                    </td>
                    <td>
                        <?= Formatter::formatValue($lastObservations['value']['value'], $prepend) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue($lastObservations['change']['value'], $prepend) ?>
                        <?= Formatter::getArrow($lastObservations['change']['value']) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue($lastObservations['percentChange']['value']) ?>%
                        <?= Formatter::getArrow($lastObservations['percentChange']['value']) ?>
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
            '_ext' => 'xlsx'
        ],
        ['escape' => false, 'class' => 'alert alert-info']
    ) ?>
</p>
