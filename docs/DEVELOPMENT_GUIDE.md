# LocalPHP Development Guide

This guide describes how to use LocalPHP from the project root. Framework internals live in `framework/`; application code belongs in `app/`, routes in `routes/`, views in `resources/views/`, public files in `public/`, and project documentation in `docs/`.

## 1. Requirements

- PHP 8.2 or newer
- Composer (the project already includes `vendor/`)
- MySQL/MariaDB only if using database features
- Apache with `mod_rewrite` for the `http://localhost/localphp` option

## 2. Two ways to run the application

### Option A — Apache / XAMPP / Laragon `localhost`

1. Put the project folder at a web root, for example `htdocs/localphp`.
2. Ensure Apache has `mod_rewrite` enabled and allows `.htaccess` overrides.
3. Open `http://localhost/localphp`.
4. The root `.htaccess` forwards requests internally to `public/`. URLs do not include `/public`.
5. Configure `APP_URL=http://localhost/localphp` in `.env`.

For production, the preferred setup is to configure the virtual host's document root directly to `localphp/public`. The root rewrite is a development convenience; never expose `.env`, `framework/`, `vendor/`, or application source as public files.

### Option B — PHP built-in server

From the project root run:

```bash
php local serve
```

Optional host and port:

```bash
php local serve 127.0.0.1:8080
```

Open the URL printed in the terminal. The server uses `public/` as its document root, so URLs also do not include `/public`.

## 3. Public directory and URL helpers

Put browser-accessible files under `public/`, such as `public/assets/css/app.css` and `public/assets/js/app.js`.

```php
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="<?= asset('js/app.js') ?>" defer></script>
<a href="<?= url('users') ?>">Users</a>
```

`base_url()` uses `APP_URL`; if its configured URL accidentally ends in `/public`, the helper removes that suffix. `asset()` prefixes `assets/`. Neither helper should produce `/public` in a public URL.

## 4. Routes and controllers

Define routes in `routes/web.php`:

```php
$router->get('/status', function () {
    return response(['success' => true, 'message' => 'LocalPHP is running']);
});

$router->post('/api/profile', function ($request) {
    return json([
        'success' => true,
        'data' => ['name' => $request->input('name')],
    ], 201);
});
```

Controller actions can return a `Response`, a string (HTML/text), or an array (JSON). Prefer `json($data, $status, $headers)` when the endpoint is explicitly an API response.

## 5. JSON responses

```php
return json([
    'success' => true,
    'message' => 'Record created.',
    'data' => ['id' => 42],
], 201);
```

Custom headers are supported:

```php
return json(['success' => false, 'message' => 'Not found'], 404, [
    'Cache-Control' => 'no-store',
]);
```

The `response([...], 200)` helper also serializes arrays as JSON. JSON request bodies (`Content-Type: application/json`) are decoded so `$request->input('field')` works for JSON and regular form requests. Use meaningful HTTP status codes: 200 OK, 201 Created, 400 Bad Request, 401 Unauthorized, 403 Forbidden, 404 Not Found, 419 CSRF mismatch, and 422 Validation Error.

## 6. CSRF protection

LocalPHP checks CSRF tokens automatically on `POST`, `PUT`, `PATCH`, and `DELETE`. `GET`, `HEAD`, and `OPTIONS` are not checked. Create tokens with `csrf_token()` and form fields with `csrf_field()`.

HTML form:

```php
<form method="POST" action="<?= url('profile') ?>">
    <?= csrf_field() ?>
    <input name="display_name" required>
    <button type="submit">Save</button>
</form>
```

AJAX / fetch:

```php
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
fetch('<?= url('api/profile') ?>', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({ name: 'Ada' })
}).then(response => response.json()).then(console.log);
</script>
```

For JSON errors, a failed CSRF check returns HTTP `419` with a JSON object and `error.code = CSRF_TOKEN_MISMATCH`. For normal browser forms it returns a small HTML 419 page. CSRF protection is not a replacement for authentication, authorization, input validation, or output escaping. Do not disable it globally for convenience; for external stateless APIs, implement an explicit, documented authentication strategy instead.

## 7. Sessions and flash data

```php
session()->put('theme', 'dark');
$theme = session('theme', 'light');
flash('success', 'Saved successfully.');
```

Flash values are intended for the next request. Use sessions only over HTTPS in deployed environments and review cookie settings in `config/app.php`.

## 8. Database configuration

Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`. Keep real credentials out of source control. The database connection is lazy, so routes that do not use the database can run without MySQL.

## 9. Suggested project layout

```text
app/                 Application controllers, models, filters
config/              Application and database configuration
docs/                Project guides (outside framework source)
framework/src/       LocalPHP framework core
public/              Public document root, assets, front controller
resources/views/     View templates
routes/web.php       Route definitions
storage/             Runtime files, if enabled
vendor/              Composer autoloader and dependencies
.env                 Local environment configuration; do not publish
local                Framework CLI entry point
```

## 10. Security and deployment checklist

- Point the deployed web server's document root at `public/`.
- Never publish `.env`, `app/`, `config/`, `framework/`, `vendor/`, or database dumps.
- Set `APP_DEBUG=false` in production.
- Use HTTPS and `SESSION_SECURE=true` in production.
- Keep CSRF enabled for cookie/session-authenticated browser requests.
- Validate all input, authorize access to each record, use prepared database statements, and escape output in HTML.
- Back up the database and test restore procedures.


## Built-in server without `public/router.php`

LocalPHP uses `public/index.php` as both the front controller and the PHP built-in
server router. This avoids maintaining a second router file. Start it with:

```bash
php local serve
php local serve 127.0.0.1:8080
```

The front controller returns existing files under `public/` to PHP for direct
serving and forwards application paths to the framework. Keep private code,
configuration, and `.env` outside `public/`.

## Useful baseline features

- **JSON 404 responses:** send `Accept: application/json` or an `X-Requested-With`
  header on API requests to receive a structured JSON error for unknown routes.
- **Consistent server errors:** unhandled exceptions become HTTP 500 responses;
  JSON clients receive a JSON error envelope. Set `APP_DEBUG=false` outside local
  development so exception details are not exposed.
- **Browser security headers:** the front controller adds `X-Content-Type-Options`,
  `X-Frame-Options`, and `Referrer-Policy` to normal responses.
- **Health check:** `GET /health` returns framework status and version.
- **CSRF:** keep using `csrf_field()` for forms or `csrf_meta()` plus the
  `X-CSRF-TOKEN` header for JavaScript requests that change server state.

The PHP built-in server is for development only. In production, point Apache or
Nginx's document root directly at `public/` and enable HTTPS.
