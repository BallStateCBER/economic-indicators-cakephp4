<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\Core\Configure;

$this->extend('DataCenter.default');

$this->assign('sidebar', $this->element('sidebar'));
?>

<?php $this->append('site_title'); ?>
    <h1 class="text">
        <a href="/">
            <?= Configure::read('DataCenter.siteTitle') ?>
        </a>
    </h1>
<?php $this->end(); ?>

<div id="content">
    <?= $this->fetch('content') ?>
</div>

<?php $this->append('below_content'); ?>
    <div id="data-disclaimer" class="container">
        This website uses the FRED&reg; API but is not endorsed or certified by the Federal Reserve Bank of St. Louis.
        By using this website, you agree to be bound by the
        <a href="https://research.stlouisfed.org/docs/api/terms_of_use.html">FRED&reg; API Terms of Use</a>.
    </div>
<?php $this->end(); ?>
