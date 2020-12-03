<?php
/**
 * @var \App\View\AppView $this
 * @var array|bool $data
 * @var string $pageTitle
 */

use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;

$this->Html->css('/fontawesome/css/all.min.css', ['block' => true]);

/**
 * Returns an up or down arrow to indicate positive or negative, or no arrow for zero
 *
 * @param mixed $value Observation value
 * @return string|null
 */
function getArrow($value): ?string
{
    if ($value > 0) {
        return '<i class="fas fa-arrow-circle-up"></i>';
    }

    if ($value < 0) {
        return '<i class="fas fa-arrow-circle-down"></i>';
    }

    return null;
}

/**
 * Returns a formatted observation value
 *
 * @param mixed $value Observation value
 * @param ?string $prepend String to apply before numeric part of return value
 * @return string
 */
function formatValue($value, $prepend = null): string
{
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

/**
 * Returns a formatted date string for the provided series
 *
 * @param string $date Date string
 * @param string $frequency e.g. 'monthly'
 * @return string
 */
function getFormattedDate(string $date, string $frequency): string
{
    if (str_contains($frequency, 'quarterly')) {
        $dateObj = new FrozenDate($date);
        $month = $dateObj->format('n');
        $quarter = ceil($month / 3);

        return sprintf('Q%s %s', $quarter, $dateObj->format('Y'));
    }

    if (str_contains($frequency, 'monthly')) {
        $format = 'F Y';
    } else {
        $format = 'F j, Y';
    }

    return (new FrozenDate($date))->format($format);
}

$unit = reset($data['observations'])['units'];
$prepend = str_contains(strtolower($unit), 'dollar') ? '$' : null;
$frequency = strtolower(reset($data['observations'])['frequency']);
?>
<h1 id="page-title">
    <?= $pageTitle ?>
</h1>
<p>
    <?= ucfirst($frequency) ?> data -
    Last updated <?= (new FrozenTime($data['updated']))->format('F j, Y') ?>
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
                            <?= getFormattedDate($series['value']['date'], $frequency) ?>
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
