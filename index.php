<?php

declare(strict_types=1);

use LocalPHP\Http\Request;

require_once __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$request = Request::capture();

/**
 * Temporary debugging.
 *
 * Remove this after routing works.
 */
if (isset($_GET['_debug'])) {
    header('Content-Type: text/plain');

    echo "METHOD:\n";
    var_dump($request->method());

    echo "\nURI:\n";
    var_dump($request->uri());

    echo "\nBASE PATH:\n";
    var_dump($request->basePath());

    echo "\nREQUEST_URI:\n";
    var_dump($_SERVER['REQUEST_URI'] ?? null);

    echo "\nSCRIPT_NAME:\n";
    var_dump($_SERVER['SCRIPT_NAME'] ?? null);

    exit;
}

$response = $app->handle($request);

$response->send();