<?php

declare(strict_types=1);

namespace LocalPHP\View;

use RuntimeException;
use Throwable;

class Local
{
    protected string $viewsPath;

    protected array $sections = [];

    protected array $sectionStack = [];

    protected ?string $layout = null;

    protected array $renderStack = [];

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = rtrim($viewsPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Render a view.
     */
    public function render(string $view, array $data = []): string
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->layout = null;
        $this->renderStack = [];

        return $this->renderFile($view, $data);
    }

    /**
     * Render a template file and resolve its layout.
     */
    protected function renderFile(
        string $view,
        array $data = [],
        bool $resolveLayout = true
    ): string {
        $path = $this->resolvePath($view);

        if (!is_file($path)) {
            throw new RuntimeException(
                "View [{$view}] was not found at [{$path}]."
            );
        }

        if (in_array($path, $this->renderStack, true)) {
            throw new RuntimeException(
                "Circular view inclusion detected for [{$view}]."
            );
        }

        $this->renderStack[] = $path;

        try {
            $template = file_get_contents($path);

            if ($template === false) {
                throw new RuntimeException(
                    "Unable to read view [{$view}]."
                );
            }

            $compiled = $this->compile($template);

            ob_start();

            try {
                extract($data, EXTR_SKIP);

                eval('?>' . $compiled);

                $content = ob_get_contents();

                if ($content === false) {
                    throw new RuntimeException(
                        "Unable to capture output for view [{$view}]."
                    );
                }
            } catch (Throwable $exception) {
                ob_end_clean();
                throw $exception;
            }

            ob_end_clean();

            if (!$resolveLayout || $this->layout === null) {
                return $content;
            }

            $layout = $this->layout;
            $this->layout = null;

            return $this->renderFile(
                $layout,
                array_merge($data, ['content' => $content]),
                true
            );
        } finally {
            array_pop($this->renderStack);
        }
    }

    /**
     * Compile Local template syntax into PHP.
     */
    public function compile(string $template): string
    {
        // Template comments: {{-- comment --}}
        $template = preg_replace(
            '/\{\{--.*?--\}\}/s',
            '',
            $template
        ) ?? $template;

        // Compile raw output first so it is not mistaken for {{ }}.
        $template = preg_replace_callback(
            '/\{!!\s*(.*?)\s*!!\}/s',
            static function (array $matches): string {
                return '<?php echo ' . $matches[1] . '; ?>';
            },
            $template
        ) ?? $template;

        // Escaped output: {{ $name }}
        $template = preg_replace_callback(
            '/\{\{\s*(.*?)\s*\}\}/s',
            static function (array $matches): string {
                return '<?php echo htmlspecialchars((string)('
                    . $matches[1]
                    . '), ENT_QUOTES | ENT_SUBSTITUTE, \'UTF-8\'); ?>';
            },
            $template
        ) ?? $template;

        // Parent layout.
        $template = preg_replace_callback(
            '/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            fn (array $m): string =>
                '<?php $this->extend('
                . var_export($m[1], true)
                . '); ?>',
            $template
        ) ?? $template;

        // Include a partial.
        $template = preg_replace_callback(
            '/@include\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            fn (array $m): string =>
                '<?php echo $this->includeView('
                . var_export($m[1], true)
                . ', get_defined_vars()); ?>',
            $template
        ) ?? $template;

        // Start a section.
        $template = preg_replace_callback(
            '/@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            fn (array $m): string =>
                '<?php $this->startSection('
                . var_export($m[1], true)
                . '); ?>',
            $template
        ) ?? $template;

        // End a section.
        $template = str_replace(
            '@endsection',
            '<?php $this->stopSection(); ?>',
            $template
        );

        // Render a section with an optional default.
        $template = preg_replace_callback(
            '/@yield\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*[\'"]([^\'"]*)[\'"]\s*)?\)/',
            fn (array $m): string =>
                '<?php echo $this->yieldSection('
                . var_export($m[1], true)
                . ', '
                . var_export($m[2] ?? '', true)
                . '); ?>',
            $template
        ) ?? $template;

        // Directives with balanced parentheses.
        $template = $this->compileDirective($template, 'if', 'if');
        $template = $this->compileDirective($template, 'elseif', 'elseif');

        $template = str_replace(
            '@else',
            '<?php else: ?>',
            $template
        );

        $template = str_replace(
            '@endif',
            '<?php endif; ?>',
            $template
        );

        $template = $this->compileDirective(
            $template,
            'foreach',
            'foreach'
        );

        $template = str_replace(
            '@endforeach',
            '<?php endforeach; ?>',
            $template
        );

        $template = $this->compileDirective(
            $template,
            'for',
            'for'
        );

        $template = str_replace(
            '@endfor',
            '<?php endfor; ?>',
            $template
        );

        $template = $this->compileDirective(
            $template,
            'while',
            'while'
        );

        $template = str_replace(
            '@endwhile',
            '<?php endwhile; ?>',
            $template
        );

        // Loop control.
        $template = preg_replace(
            '/@break\b/',
            '<?php break; ?>',
            $template
        ) ?? $template;

        $template = preg_replace(
            '/@continue\b/',
            '<?php continue; ?>',
            $template
        ) ?? $template;

        // Embedded PHP in trusted template files.
        $template = str_replace(
            '@php',
            '<?php ',
            $template
        );

        $template = str_replace(
            '@endphp',
            ' ?>',
            $template
        );

        return $template;
    }

    /**
     * Compile a directive while respecting nested parentheses
     * and parentheses inside quoted strings.
     */
    protected function compileDirective(
        string $template,
        string $directive,
        string $phpKeyword
    ): string {
        $pattern = '/@' . preg_quote($directive, '/') . '\s*\(/';
        $offset = 0;

        while (preg_match(
            $pattern,
            $template,
            $matches,
            PREG_OFFSET_CAPTURE,
            $offset
        )) {
            $start = $matches[0][1];
            $openParen = $start + strlen($matches[0][0]) - 1;

            $depth = 1;
            $quote = null;
            $escaped = false;
            $length = strlen($template);
            $end = $openParen + 1;

            for (; $end < $length; $end++) {
                $char = $template[$end];

                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($quote !== null && $char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($quote !== null) {
                    if ($char === $quote) {
                        $quote = null;
                    }

                    continue;
                }

                if ($char === "'" || $char === '"') {
                    $quote = $char;
                    continue;
                }

                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;

                    if ($depth === 0) {
                        break;
                    }
                }
            }

            if ($depth !== 0) {
                throw new RuntimeException(
                    "Unclosed @{$directive} directive."
                );
            }

            $expression = substr(
                $template,
                $openParen + 1,
                $end - $openParen - 1
            );

            $replacement = '<?php '
                . $phpKeyword
                . ' ('
                . $expression
                . '): ?>';

            $template = substr_replace(
                $template,
                $replacement,
                $start,
                $end - $start + 1
            );

            $offset = $start + strlen($replacement);
        }

        return $template;
    }

    /**
     * Set the parent layout.
     */
    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Begin capturing a section.
     */
    public function startSection(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    /**
     * Stop capturing and save a section.
     */
    public function stopSection(): void
    {
        if ($this->sectionStack === []) {
            throw new RuntimeException(
                'Cannot end a section that was not started.'
            );
        }

        $name = array_pop($this->sectionStack);
        $content = ob_get_clean();

        $this->sections[$name] = $content === false
            ? ''
            : $content;
    }

    /**
     * Retrieve a section.
     */
    public function yieldSection(
        string $name,
        string $default = ''
    ): string {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Render a partial using the current template variables.
     */
    public function includeView(
        string $view,
        array $variables = []
    ): string {
        unset(
            $variables['this'],
            $variables['GLOBALS'],
            $variables['_SERVER'],
            $variables['_ENV'],
            $variables['_POST'],
            $variables['_GET'],
            $variables['_COOKIE'],
            $variables['_FILES'],
            $variables['_SESSION'],
            $variables['_REQUEST']
        );

        return $this->renderFile($view, $variables, false);
    }

    /**
     * Resolve and validate a view path.
     *
     * Dot notation maps to folders:
     * layouts.app => layouts/app.php
     */
    protected function resolvePath(string $view): string
    {
        if (
            $view === ''
            || str_contains($view, "\0")
            || str_contains($view, '\\')
            || str_starts_with($view, '/')
            || preg_match('/^[A-Za-z]:/', $view)
        ) {
            throw new RuntimeException('Invalid view name.');
        }

        $segments = explode('.', $view);

        foreach ($segments as $segment) {
            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
                || !preg_match('/^[A-Za-z0-9_-]+$/', $segment)
            ) {
                throw new RuntimeException(
                    "Invalid view name [{$view}]."
                );
            }
        }

        return $this->viewsPath
            . DIRECTORY_SEPARATOR
            . implode(DIRECTORY_SEPARATOR, $segments)
            . '.php';
    }
}