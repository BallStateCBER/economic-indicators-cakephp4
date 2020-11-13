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
