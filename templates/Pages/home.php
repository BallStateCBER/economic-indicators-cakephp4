<?php
/** @var array $releaseDates */

use App\Formatter\Formatter;
use Cake\I18n\FrozenDate;

$i = 0;
?>

<div class="row">
    <section class="col">
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
            If you have any questions or comments, please email
            <a href="mailto:sdevaraj@bsu.edu">Srikant Devaraj</a>.
        </p>
    </section>

    <div class="col-lg-auto" id="twitter-feed">
        <a class="twitter-timeline" data-lang="en" data-width="275" data-height="500" href="https://twitter.com/BallStateCBER?ref_src=twsrc%5Etfw">Tweets by BallStateCBER</a>
        <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
    </div>
</div>

<?php if ($releaseDates): ?>
    <section id="upcoming-releases">
        <h1>
            Upcoming Data Releases
        </h1>
        <p>
            The following are the next dates in which new data is expected to be released for each category.
            Click a category to see which specific metrics will be included in the anticipated release.
        </p>
        <?php foreach ($releaseDates as $date => $endpoints): ?>
            <div>
                <h2>
                    <?= Formatter::formatReleaseDate(new FrozenDate($date)) ?>
                </h2>
                <ul class="list-unstyled">
                    <?php foreach ($endpoints as $group => $metrics): ?>
                        <li>
                            <button class="btn btn-link" data-target="details-<?= $i ?>">
                                <i class="fas fa-caret-square-right" title="Click to toggle details"></i>
                                <span>
                                    <?= $group ?>
                                </span>
                            </button>
                            <ul id="details-<?= $i ?>" style="display: none;">
                                <?php foreach ($metrics as $metric): ?>
                                    <li>
                                        <?= $metric['name'] ?>
                                        <?php if ($metric['frequency']): ?>
                                            <span class="frequency">
                                                <?= $metric['frequency'] ?>
                                            </span>
                                        <?php endif; ?>
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
