<?php

declare(strict_types=1);

use LocalPHP\Application;

$app = new Application(
    basePath: dirname(__DIR__)
);

return $app;