<?php

namespace App\Service;
use Core\ServiceManager;
use Core\DB;

$services = new ServiceManager();
$services->register('db', new DB('mysql', MYSQL_DB));
if (defined('MYSQL_SOCKET')) {
    $services->get('db')->setSocket(MYSQL_SOCKET);
} else {
    $services->get('db')->setHost(MYSQL_HOST, MYSQL_PORT);
}
$services->get('db')->setCredentials(MYSQL_USER, MYSQL_PASSWORD);
