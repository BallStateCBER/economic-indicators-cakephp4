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

function toLastSigDigit($value, $decimalLimit = 2) {
    $value = round($value, $decimalLimit);
    $value = number_format($value, $decimalLimit);
    if (strpos($value, '.')) {
        $value = rtrim($value, '0');
    }
    if (substr($value, -1) === '.') {
        $value = rtrim($value, '.');
    }

    return $value;
}
?>
<h1 id="page-title">
    <?= $pageTitle ?>
</h1>

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
                        <?= toLastSigDigit($series['value']['value']) ?>
                    </td>
                    <td>
                        <?= toLastSigDigit($series['change']['value']) ?>
                        <?= getArrow($series['change']['value']) ?>
                    </td>
                    <td>
                        <?= toLastSigDigit($series['percentChange']['value']) ?>%
                        <?= getArrow($series['percentChange']['value']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
