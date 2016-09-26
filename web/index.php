<?php

define('ROOT_PATH', __DIR__.'/..');
define('PUBLIC_PATH', ROOT_PATH.'/web');
define('SITE_PATH', ROOT_PATH.'/site');

require_once ROOT_PATH.'/vendor/autoload.php';

$application = new Site\Application();
$application->run();
