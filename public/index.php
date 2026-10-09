<?php

declare(strict_types=1);

use LocalPHP\Http\Request;
use LocalPHP\Http\Response;

$publicPath = __DIR__;

// When used as PHP's built-in server router, let PHP serve existing public
// files directly. Realpath prevents paths such as /../config/app.php escaping
// the public directory. Apache simply executes this file through its rewrite.
if (PHP_SAPI === 'cli-server') {
    $requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $candidate = realpath($publicPath . DIRECTORY_SEPARATOR . ltrim($requestedPath, '/'));
    $root = realpath($publicPath);

    if ($candidate !== false && $root !== false
        && str_starts_with($candidate, $root . DIRECTORY_SEPARATOR)
        && is_file($candidate)) {
        return false;
    }
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

try {
    $app = require dirname(__DIR__) . '/bootstrap/app.php';
    $request = Request::capture();
    $response = $app->handle($request);

    // Baseline browser security headers; applications may override these
    // deliberately in their own response when required.
    $response->header('X-Content-Type-Options', 'nosniff');
    $response->header('X-Frame-Options', 'SAMEORIGIN');
    $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->send();
} catch (Throwable $exception) {
    $debug = (bool) config('app.debug', false);
    $request = $request ?? null;
    $isJson = $request instanceof Request && $request->expectsJson();
    $status = 500;

    if ($isJson) {
        $payload = [
            'success' => false,
            'message' => $debug ? $exception->getMessage() : 'An unexpected server error occurred.',
            'error' => ['code' => 'INTERNAL_SERVER_ERROR'],
        ];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new Response($body === false ? '{"success":false,"message":"Server error"}' : $body,
            $status, ['Content-Type' => 'application/json; charset=UTF-8']))->send();
    } else {
        $message = $debug
            ? htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
            : 'An unexpected server error occurred.';
        (new Response('<h1>500 - Internal Server Error</h1><p>' . $message . '</p>',
            $status, ['Content-Type' => 'text/html; charset=UTF-8']))->send();
    }
}
