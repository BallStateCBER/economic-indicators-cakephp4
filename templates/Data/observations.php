<?php
/**
 * @var array|bool $data
 * @var string $pageTitle
 */
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
                    </td>
                    <td>
                        <?= round($series['percentChange']['value'], 2) ?>%
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
