<?php

declare(strict_types=1);

namespace LocalPHP\View;

class View
{
    protected Local $engine;

    public function __construct(string $viewsPath)
    {
        $this->engine = new Local($viewsPath);
    }

    public function render(
        string $view,
        array $data = []
    ): string {
        return $this->engine->render($view, $data);
    }
}