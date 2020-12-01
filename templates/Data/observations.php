<?php
/**
 * @var \App\View\AppView $this
 * @var array|bool $data
 * @var string $pageTitle
 */

use Cake\I18n\FrozenDate;

$this->Html->css('/fontawesome/css/all.min.css', ['block' => true]);

function getArrow($value) {
    if ($value > 0) {
        return '<i class="fas fa-arrow-circle-up"></i>';
    }

    if ($value < 0) {
        return '<i class="fas fa-arrow-circle-down"></i>';
    }

    return null;
}

function formatValue($value, $prepend = null) {
    $decimalLimit = 2;
    $negative = (float)$value < 0;
    $value = round($value, $decimalLimit);
    $value = number_format($value, $decimalLimit);
    if (str_contains($value, '.')) {
        $value = rtrim($value, '0');
    }
    if (substr($value, -1) === '.') {
        $value = rtrim($value, '.');
    }
    if ($negative) {
        return str_replace('-', '-' . $prepend, $value);
    }

    return $prepend . $value;
}

$unit = reset($data)['units'];
$prepend = str_contains(strtolower($unit), 'dollar') ? '$' : null;
?>
<h1 id="page-title">
    <?= $pageTitle ?>
</h1>
<p>
    Updated <?= strtolower(reset($data)['frequency']) ?>
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
            <?php foreach ($data as $name => $series): ?>
                <tr>
                    <td>
                        <?= $name ?>
                        <br />
                        <small>
                            <?= (new FrozenDate($series['value']['date']))->format('F j, Y') ?>
                        </small>
                    </td>
                    <td>
                        <?= formatValue($series['value']['value'], $prepend) ?>
                    </td>
                    <td>
                        <?= formatValue($series['change']['value'], $prepend) ?>
                        <?= getArrow($series['change']['value']) ?>
                    </td>
                    <td>
                        <?= formatValue($series['percentChange']['value']) ?>%
                        <?= getArrow($series['percentChange']['value']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
