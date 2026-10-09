<?php

declare(strict_types=1);

use LocalPHP\Http\Request;
use LocalPHP\Support\Environment;
use LocalPHP\Support\ExceptionHandler;

require_once __DIR__ . '/vendor/autoload.php';

Environment::load(__DIR__);
ExceptionHandler::register(__DIR__);

$app = require __DIR__ . '/bootstrap/app.php';
$request = Request::capture();
$response = $app->handle($request);
$response->send();
