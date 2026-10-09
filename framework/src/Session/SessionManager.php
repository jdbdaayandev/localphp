<?php

declare(strict_types=1);

namespace LocalPHP\Session;

class SessionManager
{
    protected array $config;

    public function __construct(
        array $config = []
    ) {
        $this->config = $config;

        $this->start();
    }

    /**
     * Start the PHP session.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $name = $this->config['name']
            ?? 'localphp_session';

        $lifetime = (int) (
            $this->config['lifetime']
            ?? 120
        );

        $path = $this->config['path']
            ?? '/';

        $domain = $this->config['domain']
            ?? '';

        $secure = (bool) (
            $this->config['secure']
            ?? false
        );

        $httpOnly = (bool) (
            $this->config['http_only']
            ?? true
        );

        $sameSite = $this->config['same_site']
            ?? 'Lax';

        session_name($name);

        ini_set(
            'session.gc_maxlifetime',
            (string) ($lifetime * 60)
        );

        session_set_cookie_params([
            'lifetime' => $lifetime * 60,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);

        session_start();
    }

    /**
     * Get a session value.
     */
    public function get(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null) {
            return $_SESSION;
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Store a session value.
     */
    public function put(
        string $key,
        mixed $value
    ): void {
        $_SESSION[$key] = $value;
    }

    /**
     * Store multiple values.
     */
    public function putMany(
        array $values
    ): void {
        foreach ($values as $key => $value) {
            $this->put(
                (string) $key,
                $value
            );
        }
    }

    /**
     * Determine whether a key exists.
     */
    public function has(
        string $key
    ): bool {
        return array_key_exists(
            $key,
            $_SESSION
        );
    }

    /**
     * Remove a session value.
     */
    public function forget(
        string|array $keys
    ): void {
        foreach ((array) $keys as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Get and remove a session value.
     */
    public function pull(
        string $key,
        mixed $default = null
    ): mixed {
        $value = $this->get(
            $key,
            $default
        );

        $this->forget($key);

        return $value;
    }

    /**
     * Remove all session data.
     */
    public function flush(): void
    {
        $_SESSION = [];
    }

    /**
     * Regenerate the session ID.
     */
    public function regenerate(
        bool $deleteOldSession = true
    ): bool {
        return session_regenerate_id(
            $deleteOldSession
        );
    }

    /**
     * Destroy the session.
     */
    public function destroy(): void
    {
        if (
            session_status() !==
            PHP_SESSION_ACTIVE
        ) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Flash a value for the next request.
     */
    public function flash(
        string $key,
        mixed $value
    ): void {
        $flash = $this->get(
            '_flash',
            []
        );

        $flash[$key] = $value;

        $this->put(
            '_flash',
            $flash
        );
    }

    /**
     * Get a flash value.
     */
    public function getFlash(
        string $key,
        mixed $default = null
    ): mixed {
        $flash = $this->get(
            '_flash',
            []
        );

        return $flash[$key] ?? $default;
    }

    /**
     * Check whether a flash value exists.
     */
    public function hasFlash(
        string $key
    ): bool {
        $flash = $this->get(
            '_flash',
            []
        );

        return array_key_exists(
            $key,
            $flash
        );
    }

    /**
     * Clear flash data.
     */
    public function clearFlash(): void
    {
        $this->forget('_flash');
    }
}