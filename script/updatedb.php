#!/usr/bin/env php
<?php

$_SERVER['ROOT_DIR'] = realpath(dirname(__FILE__) . '/..');
require_once($_SERVER['ROOT_DIR'] . '/init.php');

$script = new Core\DBMigrationScript();
$script->run();
