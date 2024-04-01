<?php

require_once __DIR__.'/../vendor/autoload.php';

use App\Application;

$app = new Application(dirname(__DIR__));

return $app;
