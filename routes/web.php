<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use LocalPHP\Application;

/**
 * @var Application $app
 */

$router = $app->router();

/** @var Application $app */

$app->router()->get('/', [HomeController::class, 'index']);


// Lightweight health endpoint for local checks and deployment monitoring.
$router->get('/health', function () {
    return response([
        'success' => true,
        'status' => 'ok',
        'framework' => 'LocalPHP',
        'version' => app()->version(),
    ]);
});


$router->get(
    '/hello',
    function () {
        return 'Hello from LocalPHP!';
    }
);

$router->get(
    '/hello/{name}',
    function ($request, $name) {
        return "Hello, {$name}!";
    }
);

$router->get('/session', function () {
    $count = session('count', 0);

    session()->put(
        'count',
        $count + 1
    );

    return response([
        'count' => session('count'),
    ]);
});

$router->get('/flash/set', function () {
    flash(
        'success',
        'Welcome to LocalPHP!'
    );

    return redirect(
        url('flash/show')
    );
});

$router->get('/flash/show', function () {
    $message = session()->getFlash(
        'success'
    );

    session()->clearFlash();

    return response([
        'message' => $message,
    ]);
});