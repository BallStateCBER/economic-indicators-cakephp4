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
            <?php foreach ($data['endpoints'] as $name => $endpoint): ?>
                <tr>
                    <td>
                        <?= $name ?>
                        <br />
                        <small>
                            <?= Formatter::getFormattedDate($endpoint['observation']['date'], $frequency) ?>
                        </small>
                    </td>
                    <td>
                        <?= Formatter::formatValue($endpoint['observation']['value'], $prepend) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue($endpoint['change']['value'], $prepend) ?>
                        <?= Formatter::getArrow($endpoint['change']['value']) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue($endpoint['percentChange']['value']) ?>%
                        <?= Formatter::getArrow($endpoint['percentChange']['value']) ?>
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
