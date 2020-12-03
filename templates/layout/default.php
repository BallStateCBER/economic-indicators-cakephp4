<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\Core\Configure;

$this->extend('DataCenter.default');

// If you have a /templates/elements/sidebar.php file
$this->assign('sidebar', $this->element('sidebar'));
?>

<?php
// If you'd like to have a masthead between the navbar and main content
$this->append('subsite_title');
?>
<h1 id="subsite_title" class="max_width">
    <a href="/">
        <?= Configure::read('data_center_subsite_title') ?>
    </a>
</h1>
<?php $this->end(); ?>

<div id="content">
    <?= $this->fetch('content') ?>
</div>

<?php $this->append('below_content'); ?>
    <div id="data-disclaimer" class="max_width">
        This website uses the FRED&reg; API but is not endorsed or certified by the Federal Reserve Bank of St. Louis.
        By using this website, you agree to be bound by the
        <a href="https://research.stlouisfed.org/docs/api/terms_of_use.html">FRED&reg; API Terms of Use</a>.
    </div>
<?php $this->end(); ?>
