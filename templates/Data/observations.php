<?php
/**
 * @var \App\View\AppView $this
 * @var array|bool $data
 * @var string $pageTitle
 */

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
                            <?= $series['value']['date'] ?>
                        </small>
                    </td>
                    <td>
                        <?= number_format(round($series['value']['value'], 2)) ?>
                    </td>
                    <td>
                        <?= number_format(round($series['change']['value'], 2)) ?>
                        <?= getArrow($series['change']['value']) ?>
                    </td>
                    <td>
                        <?= round($series['percentChange']['value'], 2) ?>%
                        <?= getArrow($series['percentChange']['value']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
