<?php
if (!$title) {
    $title = "Server Error";
}
$this->out('header.inc', array(
    'title' => $title,
)); ?>

<div class="section">
    <h2><?php out($title); ?></h2>
    <?php if ($message) { ?>
    <p><?php echo linkify(safe($message)); ?>
    <?php } ?>
</div>