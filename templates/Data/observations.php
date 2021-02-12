<?php
/**
 * @var \App\View\AppView $this
 * @var array|bool $data
 */

use App\Formatter\Formatter;

$this->Html->css('/fontawesome/css/all.min.css', ['block' => true]);

$unit = Formatter::getUnit($data);
$prepend = Formatter::getPrepend($unit);
$frequency = Formatter::getFrequency($data);
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
            <?php foreach ($data['observations'] as $name => $series): ?>
                <tr>
                    <td>
                        <?= $name ?>
                        <br />
                        <small>
                            <?= Formatter::getFormattedDate($series['value']['date'], $frequency) ?>
                        </small>
                    </td>
                    <td>
                        <?= Formatter::formatValue($series['value']['value'], $prepend) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue($series['change']['value'], $prepend) ?>
                        <?= Formatter::getArrow($series['change']['value']) ?>
                    </td>
                    <td>
                        <?= Formatter::formatValue($series['percentChange']['value']) ?>%
                        <?= Formatter::getArrow($series['percentChange']['value']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p id="download-link">
    <?= $this->Html->link(
        '<i class="fas fa-download"></i> Download this data as an Excel spreadsheet',
        ['_ext' => 'xlsx'],
        ['escape' => false, 'class' => 'alert alert-info']
    ) ?>
</p>
