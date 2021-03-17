<?php
/** @var array $releaseDates */

use Cake\I18n\FrozenDate;

$i = 0;
?>

<?php if ($releaseDates): ?>
    <section id="upcoming-releases">
        <h1>
            Upcoming Data Releases
        </h1>
        <?php foreach ($releaseDates as $date => $endpoints): ?>
            <div>
                <h2>
                    <?= (new FrozenDate($date))->format('F jS, Y') ?>
                </h2>
                <ul class="list-unstyled">
                    <?php foreach ($endpoints as $group => $names): ?>
                        <li>
                            <button class="btn btn-link" data-target="details-<?= $i ?>">
                                <i class="fas fa-caret-square-right" title="Click to toggle details"></i>
                                <span>
                                    <?= $group ?>
                                </span>
                            </button>
                            <ul id="details-<?= $i ?>" style="display: none;">
                                <?php foreach ($names as $name): ?>
                                    <li>
                                        <?= $name ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<script>
    const buttons = document.getElementById('upcoming-releases').querySelectorAll('button');
    buttons.forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            let button = event.target;
            if (button.tagName !== 'button') {
                button = button.closest('button');
            }
            console.log(button);
            const targetId = button.dataset.target;
            console.log(targetId);
            const details = document.getElementById(targetId);
            console.log(details);
            slideToggle(details);
            const icon = button.querySelector('i');
            if (icon.classList.contains('fa-caret-square-right')) {
                icon.classList.remove('fa-caret-square-right');
                icon.classList.add('fa-caret-square-down');
            } else {
                icon.classList.remove('fa-caret-square-down');
                icon.classList.add('fa-caret-square-right');
            }
        });
    });
</script>
