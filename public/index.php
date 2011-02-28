<?php

require_once($_SERVER['ROOT_DIR'] . '/init.php');

$web = new Core\Web();
$web->handleRequest();
