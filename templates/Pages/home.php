<?php
/** @var array $releaseDates */

use Cake\I18n\FrozenDate;

$i = 0;
?>

<div id="twitter-feed">
    <a class="twitter-timeline" data-lang="en" data-width="275" data-height="500" href="https://twitter.com/BallStateCBER?ref_src=twsrc%5Etfw">Tweets by BallStateCBER</a>
    <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
</div>

<section>
    <h1>
        Data at Your Fingertips
    </h1>

    <p>
        <strong>Click on a category in the menu</strong> to start exploring a wealth of economic data collected from
        reliable, primary sources.
    </p>

    <p>
        Notifications about new data releases are emailed weekly through the
        <a href="http://cber.iweb.bsu.edu/IBB" title="Indiana Business Bulletin weekly newsletter">
            Indiana Business Bulletin
        </a>
        and posted daily through our <em>BallStateCBER</em> accounts on
        <a href="http://www.facebook.com/BallStateCBER" title="BallStateCBER on Facebook">Facebook</a> and
        <a href="http://twitter.com/BallStateCBER" title="BallStateCBER on Twitter">Twitter</a>.
        You do not need to be a member of either Facebook or Twitter to view our accounts.
    </p>

    <p>
        If you have any questions or comments, please email project manager
        <a href="mailto:sdevaraj@bsu.edu">Srikant Devaraj</a>.
    </p>
</section>

<?php if ($releaseDates): ?>
    <section id="upcoming-releases">
        <h1>
            Upcoming Data Releases
        </h1>
        <p>
            Click each category to show details
        </p>
        <?php foreach ($releaseDates as $date => $endpoints): ?>
            <div>
                <h2>
                    <?php
                        $dateObj = new FrozenDate($date);
                        echo sprintf(
                            '%s<sup>%s</sup>%s',
                            $dateObj->format('F j'),
                            $dateObj->format('S'),
                            $dateObj->format(', Y'),
                        );
                    ?>
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
        <p class="disclaimer">
            <?= $this->element('release_date_disclaimer') ?>
        </p>
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

<br style="clear: both;" />
