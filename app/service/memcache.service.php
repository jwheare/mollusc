<?php

namespace App\Service;
use Core\ServiceManager;
use Memcache;

$services = new ServiceManager();
$services->register('memcache', new Memcache());
$services->get('memcache')->connect(MEMCACHE_HOST, MEMCACHE_PORT);
