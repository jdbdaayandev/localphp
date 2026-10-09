# LocalPHP Framework Documentation

> **Project status:** Active development\
> **Project name:** LocalPHP\
> **Target:** PHP 8.2+\
> **Primary environment:** Local development with Laragon, Apache, and
> MySQL\
> **Documentation status:** Living document --- update this file
> whenever the framework architecture, commands, configuration, or APIs
> change.

------------------------------------------------------------------------

## 1. Introduction

LocalPHP is a lightweight PHP framework being built for local
application development. The goal is to provide a clean, understandable
framework structure without requiring a large dependency stack.

The project is being developed from scratch and takes inspiration from
common MVC frameworks while keeping the implementation under the
project's control.

### Design goals

-   **Lightweight:** avoid unnecessary dependencies.
-   **Readable:** keep application and framework code understandable.
-   **Developer-friendly:** provide simple helpers, routing, views,
    models, and CLI commands.
-   **Configurable:** use environment variables and configuration files
    for application settings.
-   **Extensible:** organize framework features into focused classes and
    namespaces.
-   **Local-development focused:** make it straightforward to develop
    and run applications on a local machine.
-   **Consistent:** use Composer PSR-4 autoloading and PHP 8.2+ language
    features.

### Current scope

The framework has been developed around these areas:

-   Application bootstrap and request handling
-   HTTP request and response objects
-   Routing and controller dispatch
-   Helper functions
-   Environment and configuration access
-   Session configuration and helpers
-   A custom PHP view/template engine
-   Database connectivity and migration/seeder command support
-   Model/ORM development
-   CLI commands and code generators
-   A clean, light welcome page
-   Planned configuration-driven HTTP filters

Some areas are still evolving. This document distinguishes current
project direction and previously discussed implementations from features
that still need to be completed or verified.

------------------------------------------------------------------------

## 2. Requirements

The project is designed for:

-   PHP **8.2 or newer**
-   Composer
-   A local PHP server stack such as Laragon
-   MySQL when database functionality is used
-   A web browser

Database commands require the database server to be running and the
database connection settings to be correct. Commands that do not need
the database should not open a database connection unnecessarily.

------------------------------------------------------------------------

## 3. Project Structure

The following is the intended organization based on the project
structure developed so far. Some directories may be created as features
are added.

``` text
localphp/
├── app/
│   ├── Controllers/
│   ├── Filters/
│   └── Models/
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   ├── database.php
│   └── filters.php              # Planned/configuration-driven HTTP filters
├── database/
│   ├── migrations/
│   └── seeders/
├── framework/
│   └── src/
│       ├── Console/
│       │   └── Kernel.php
│       ├── Support/
│       │   └── Environment.php
│       ├── Http/
│       │   ├── Request.php
│       │   └── Response.php
│       ├── View/
│       │   └── Local.php
│       └── helpers.php
├── public/                      # Optional future public document root
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.php
│       └── home.php
├── routes/
│   └── web.php
├── storage/
│   ├── cache/
│   └── logs/
├── .env
├── composer.json
├── index.php
├── local
└── vendor/
```

> **Important:** This tree documents the current direction, not a
> guarantee that every listed directory or class is already present.
> Keep it synchronized with the actual repository.

### Directory responsibilities

  -----------------------------------------------------------------------
  Path                                Responsibility
  ----------------------------------- -----------------------------------
  `app/Controllers/`                  Application controllers that
                                      respond to routes.

  `app/Models/`                       Application models and
                                      database-related domain logic.

  `app/Filters/`                      Application-level HTTP filters,
                                      once the filter pipeline is
                                      implemented.

  `bootstrap/`                        Creates and configures the
                                      framework application.

  `config/`                           PHP configuration files.

  `database/migrations/`              Database schema changes.

  `database/seeders/`                 Scripts that populate
                                      development/test data.

  `framework/src/`                    Framework core implementation.

  `resources/views/`                  PHP template files.

  `routes/`                           Route definitions.

  `storage/cache/`                    Generated cache files, if enabled.

  `storage/logs/`                     Application and framework logs, if
                                      logging is configured.

  `vendor/`                           Composer-managed autoloader and
                                      dependencies.
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 4. Installation and Setup

### 4.1 Install dependencies

From the project root:

``` bash
composer install
composer dump-autoload
```

`composer dump-autoload` should be run after changing the PSR-4 mappings
or adding files that are listed in Composer's `autoload.files`.

### 4.2 Composer configuration

The current Composer configuration follows this pattern:

``` json
{
    "name": "localphp/framework",
    "description": "A lightweight PHP framework designed for localhost development.",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": "^8.2"
    },
    "autoload": {
        "psr-4": {
            "LocalPHP\\": "framework/src/",
            "App\\": "app/"
        },
        "files": [
            "framework/src/helpers.php"
        ]
    }
}
```

The `LocalPHP\\` namespace maps to `framework/src/`, and the `App\\`
namespace maps to `app/`.

### 4.3 Environment configuration

Create a `.env` file in the project root. A development example is:

``` dotenv
APP_NAME=LocalPHP
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/localphp
APP_TIMEZONE=Asia/Manila

SESSION_NAME=localphp_session
SESSION_LIFETIME=120
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE=false
SESSION_SAME_SITE=Lax

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=localphp
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

Adjust `APP_URL` to match the actual folder and local web-server
configuration. Set database credentials to match the local MySQL
installation.

**Security note:** These are local-development settings. Do not use
debug mode or development database credentials in a public production
deployment. Never commit real secrets to version control.

### 4.4 Create the database

If the configured database does not exist, create it in MySQL or through
your database administration tool before running migrations.

For the example environment above, the database name is `localphp`.

### 4.5 Start the application

With Laragon, start the required web-server service. Start MySQL only
when using database-dependent features.

Open the local URL configured for the project, for example:

``` text
http://localhost/localphp/
```

The exact URL depends on where the project is installed and how Apache
is configured.

------------------------------------------------------------------------

## 5. Application Bootstrap

### 5.1 `bootstrap/app.php`

The bootstrap file creates the application instance:

``` php
<?php

declare(strict_types=1);

use LocalPHP\Application;

$app = new Application(basePath: dirname(__DIR__));

return $app;
```

The `basePath` points to the project root. Framework code should use the
application base-path method when constructing paths rather than relying
on the current working directory.

### 5.2 `index.php`

The root entry point loads Composer, loads the application, captures the
request, dispatches it, and sends the response:

``` php
<?php

declare(strict_types=1);

use LocalPHP\Http\Request;

require_once __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$request = Request::capture();

$response = $app->handle($request);

$response->send();
```

This describes the intended request lifecycle. The exact internal
implementation of `Application::handle()` should remain the source of
truth for how routing, filters, exceptions, and responses are processed.

### 5.3 Route loading

The application loads route definitions from `routes/web.php`. When
route files expect an `$app` variable, the route loader must define that
variable before requiring the file.

For example, the route-loading method should provide the application
instance to the route file rather than requiring the route file in a
scope where `$app` is undefined.

------------------------------------------------------------------------

## 6. Routing

Routes connect HTTP methods and URI paths to controller actions or other
supported route handlers.

### 6.1 Route file

The current home route pattern is:

``` php
<?php

declare(strict_types=1);

use App\Controllers\HomeController;

/** @var \LocalPHP\Application $app */
$app->router()->get('/', [HomeController::class, 'index']);
```

### 6.2 Controller actions

A controller action handles the request and returns a framework
response. The current convention is to use the global `view()` helper
when returning a rendered page:

``` php
<?php

declare(strict_types=1);

namespace App\Controllers;

use LocalPHP\Http\Response;

class HomeController
{
    public function index(): Response
    {
        return view('home');
    }
}
```

This example assumes that `view()` returns a `LocalPHP\Http\Response`.
Keep controller return types consistent with the actual helper and
response implementation.

### 6.3 Route features to verify

The project has discussed or worked on routing features such as:

-   HTTP method and URI matching
-   Controller actions
-   Route parameters
-   Route groups and prefixes
-   Route middleware/filter declarations
-   Route listing from the CLI

Verify each feature against the current `Router`, route definition,
dispatcher, and CLI implementation before documenting it as stable. In
particular, route syntax such as `->middleware('auth')` should not be
used until that method is actually implemented.

------------------------------------------------------------------------

## 7. Controllers

Controllers belong in `app/Controllers/` and use the `App\Controllers`
namespace.

Example:

``` php
<?php

declare(strict_types=1);

namespace App\Controllers;

use LocalPHP\Http\Response;

class HomeController
{
    public function index(): Response
    {
        return view('home');
    }
}
```

### Controller conventions

-   Use a clear class name ending in `Controller`.
-   Use one public method per route action where appropriate.
-   Return a `Response` for HTTP actions.
-   Keep request handling in controllers and reusable business logic in
    separate classes.
-   Use `view('name')` for rendered pages, according to the project's
    current convention.

The `make:controller` generator is intended to create controllers under
`app/Controllers/`, support nested namespaces/directories, and avoid
overwriting existing files. Check the current CLI implementation for its
exact accepted names and behavior.

------------------------------------------------------------------------

## 8. Views and the Template Engine

### 8.1 View location

Views are stored in:

``` text
resources/views/
```

The custom engine is located at:

``` text
framework/src/View/Local.php
```

Its namespace is `LocalPHP\View`. The view naming convention discussed
so far maps dot notation to nested directories and uses `.php` files.
For example:

``` php
view('home');
view('layouts.app');
```

correspond to files such as:

``` text
resources/views/home.php
resources/views/layouts/app.php
```

Use the naming and extension rules implemented in `LocalPHP\View\Local`
as the definitive behavior.

### 8.2 `view()` helper

The desired helper pattern is:

``` php
if (!function_exists('view')) {
    function view(
        string $name,
        array $data = []
    ): \LocalPHP\Http\Response {
        $engine = new \LocalPHP\View\Local(
            app()->basePath('resources/views')
        );

        return response($engine->render($name, $data));
    }
}
```

This is the documented intended pattern. Keep it aligned with the real
`helpers.php`, `Application`, and `Response` APIs.

### 8.3 Template syntax

The custom engine has been designed to support these directives and
expressions:

  -----------------------------------------------------------------------
  Syntax                              Purpose
  ----------------------------------- -----------------------------------
  `{{ $value }}`                      Escaped output.

  `{!! $value !!}`                    Raw output; only use for trusted or
                                      properly sanitized HTML.

  `@extends('layouts.app')`           Extend a layout.

  `@include('partials.nav')`          Include another view.

  `@section('content')`               Start a section.

  `@endsection`                       End a section.

  `@yield('content')`                 Render a section in a layout.

  `@if (...)`                         Start a conditional.

  `@elseif (...)`                     Additional conditional branch.

  `@else`                             Alternative conditional branch.

  `@endif`                            End a conditional.

  `@foreach (...)` / `@endforeach`    Loop over values.

  `@for (...)` / `@endfor`            Counted loop.

  `@while (...)` / `@endwhile`        While loop.

  `@break`                            Break from a loop.

  `@continue`                         Continue a loop.

  `@php` / `@endphp`                  Execute a PHP block in a template.
  -----------------------------------------------------------------------

This syntax is implemented by a custom template engine, not Laravel's
Blade engine. Test directives and escaping against the current engine
implementation before relying on edge cases.

### 8.4 Example layout

File: `resources/views/layouts/app.php`

``` php
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'LocalPHP')</title>
</head>
<body>
    <main>
        @yield('content')
    </main>
</body>
</html>
```

This is a minimal illustration of the layout convention; use the
project's actual light-theme layout and CSS when present.

### 8.5 Welcome page

The welcome page is intended to provide a clean, light introduction to
LocalPHP, including a hero section, a short code example, and feature
highlights. It is served through the home route and `HomeController`.

Expected files:

``` text
resources/views/layouts/app.php
resources/views/home.php
app/Controllers/HomeController.php
routes/web.php
```

------------------------------------------------------------------------

## 9. Helpers

Framework helper functions are loaded through Composer's
`autoload.files` entry:

``` json
"files": [
    "framework/src/helpers.php"
]
```

The following helpers have been discussed or used:

  ---------------------------------------------------------------------------------------------
  Helper                                                    Intended responsibility
  --------------------------------------------------------- -----------------------------------
  `app()`                                                   Access the application instance.

  `view($name, $data = [])`                                 Render a view and return a
                                                            response.

  `response($content = '', $status = 200, $headers = [])`   Create an HTTP response.

  `base_url($path = '')`                                    Generate a URL relative to the
                                                            configured application URL.

  Session helpers                                           Read, write, or manage session
                                                            data.

  `env($key, $default = null)`                              Read an environment value.

  `config($key = null, $default = null)`                    Read configuration values.
  ---------------------------------------------------------------------------------------------

**Implementation status:** Helper names and intended behavior are based
on the project's development history. Consult
`framework/src/helpers.php` for the authoritative list and exact
signatures. Do not assume every helper in this table is complete until
verified in code.

### `app()` and route scope

The `app()` helper has been designed to use the application instance
stored in `$GLOBALS['app']`. Ensure that the application is registered
there during bootstrap if that is how the current implementation works.

Route files that use `$app` directly must also receive a correctly
scoped `$app` variable from the route loader.

------------------------------------------------------------------------

## 10. Environment and Configuration

LocalPHP reads environment settings from the project-root `.env` file through `LocalPHP\Support\Environment`.

### Loading lifecycle

- The web application loads `.env` in `Application::__construct()` before configuration files are evaluated.
- The CLI entry point `local` loads `.env` before running commands.
- The global `env($key, $default = null)` helper delegates to `Environment::get()`.
- Configuration files use `env()` to populate the configuration repository.
- Existing process/server environment variables take precedence over values in `.env`.
- The configured `APP_TIMEZONE` is applied during application startup. An invalid timezone causes startup to fail with a clear exception.

### Supported `.env` syntax

The loader supports:

- `KEY=value` assignments.
- Blank lines and full-line comments beginning with `#`.
- Optional `export KEY=value`.
- Single-quoted and double-quoted values.
- Inline comments after unquoted values when `#` is preceded by whitespace.
- Basic double-quoted escapes (`\n`, `\r`, `\t`, escaped quotes and backslashes).
- Simple `${OTHER_VARIABLE}` references.
- Boolean literals `true` and `false`, null literals `null`, and empty literals `empty` (including their parenthesized forms).
- A UTF-8 BOM at the beginning of the file.

Quoted values remain strings. Missing keys return the supplied default. A missing `.env` file is allowed; values can still be supplied by the process environment or defaults.

### Usage

```php
$name = env('APP_NAME', 'LocalPHP');
$debug = env('APP_DEBUG', false);
$missing = env('NOT_CONFIGURED', 'fallback');

$appName = config('app.name', 'LocalPHP');
$databaseDriver = config('database.default');
```

Use `env()` in configuration files and prefer `config()` in application code once configuration has been loaded.

### Current application configuration

`config/app.php` reads:

- `APP_NAME`
- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `APP_TIMEZONE`
- `SESSION_NAME`
- `SESSION_LIFETIME`
- `SESSION_PATH`
- `SESSION_DOMAIN`
- `SESSION_SECURE`
- `SESSION_SAME_SITE`

`config/database.php` reads `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_CHARSET`.

### Security

- Keep `.env` out of version control; `.gitignore` already excludes it.
- The project `.htaccess` explicitly denies HTTP access to `.env` and `.env.*` files.
- Do not put real production secrets in source control.
- Set `APP_DEBUG=false` before exposing an application to a public network.
- `.env` values are configuration input, not a substitute for validation or safe secret management.

------------------------------------------------------------------------

## 11. Sessions

Session configuration has been included in the project's `.env` plan:

``` dotenv
SESSION_NAME=localphp_session
SESSION_LIFETIME=120
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE=false
SESSION_SAME_SITE=Lax
```

These values are intended to control the session cookie name, lifetime,
path, domain, secure-cookie behavior, and SameSite policy.

The session layer should be checked for:

-   Safe session startup and repeated-call behavior
-   Session ID regeneration after authentication
-   Correct cookie settings
-   Flash data behavior, if supported
-   Session destruction/logout behavior
-   Secure cookie settings when HTTPS is used

The environment variables alone do not implement these behaviors. Verify
the session manager and helper implementation before treating each item
as complete.

------------------------------------------------------------------------

## 12. HTTP Request and Response

### Request

The entry point uses:

``` php
$request = Request::capture();
```

The request object is intended to centralize access to HTTP request
information instead of scattering direct superglobal access throughout
the framework.

Verify the current `LocalPHP\Http\Request` class for the exact APIs
available, including method, URI, query parameters, body input, headers,
cookies, and uploaded files.

### Response

Controllers and the view helper are expected to return a response
object, and the entry point sends it using:

``` php
$response->send();
```

Verify `LocalPHP\Http\Response` for its supported methods, status
handling, headers, and body behavior.

------------------------------------------------------------------------

## 13. Database and Models

Database functionality uses PDO in the current project direction. The
example environment is configured for MySQL.

### Environment settings

``` dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=localphp
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

### Connection behavior

The CLI kernel was updated to use a **lazy database connection**
pattern: commands that do not require the database should not connect to
MySQL merely to start. This is important because a stopped local MySQL
service previously caused a connection-refused exception when running a
basic CLI command.

Preserve this behavior when modifying the CLI or database manager.

### Models / ORM

The project is developing a model/ORM layer. The `make:model` generator
has been discussed as creating classes under `app/Models/`.

The exact base model API, query builder, relationships, mass-assignment
rules, casts, and schema conventions must be documented after checking
the current ORM source. Do not assume that an example `extends Model`
template is compatible until the base `Model` class is verified.

------------------------------------------------------------------------

## 14. Migrations and Seeders

The CLI work includes migration and seeder commands.

### Migration directory

``` text
database/migrations/
```

Migration files have been designed to return anonymous objects extending
or implementing the project's migration abstraction, with methods
resembling:

``` php
public function up(PDO $pdo): void
{
    // Apply schema changes.
}

public function down(PDO $pdo): void
{
    // Reverse schema changes.
}
```

The actual required base class/interface and method signatures are
defined by the current migration implementation.

### Seeder directory

``` text
database/seeders/
```

Seeder files have been designed to return objects with a method
resembling:

``` php
public function run(PDO $pdo): void
{
    // Insert development or test data.
}
```

Again, follow the actual `Seeder` abstraction in the repository.

### Migration safety

-   Back up important data before destructive operations.
-   Review migration code before running it.
-   Treat `migrate:fresh` as destructive: it is intended to
    drop/recreate database structures.
-   Use a force flag only when the command's implementation requires it
    and you understand the consequences.
-   Ensure MySQL is running and `.env` connection values are correct.

------------------------------------------------------------------------

## 15. Command-Line Interface

The project uses a root-level PHP CLI file named `local`, invoked from
the project root:

``` bash
php local list
```

The CLI kernel is located at:

``` text
framework/src/Console/Kernel.php
```

### Commands developed or discussed

  -----------------------------------------------------------------------------------
  Command                                         Purpose / status
  ----------------------------------------------- -----------------------------------
  `php local list`                                Display available commands.

  `php local help`                                Display command help.

  `php local about`                               Display framework/project
                                                  information.

  `php local version`                             Display framework version
                                                  information.

  `php local make:controller Home`                Generate a controller, if
                                                  implemented in the current CLI.

  `php local make:model User`                     Generate a model, if implemented in
                                                  the current CLI.

  `php local make:filter AuthFilter`              Generate a filter class, if
                                                  implemented in the current CLI.

  `php local make:migration create_users_table`   Generate a migration file.

  `php local migrate`                             Run pending migrations.

  `php local migrate:status`                      Show migration status.

  `php local migrate:rollback`                    Roll back migrations according to
                                                  the manager's rules.

  `php local migrate:fresh`                       Destructively rebuild schema;
                                                  inspect and follow the command's
                                                  safety requirements.

  `php local make:seeder UserSeeder`              Generate a seeder file.

  `php local db:seed`                             Run seeders.

  `php local clean`                               Run general cleanup, if
                                                  implemented.

  `php local clean:cache`                         Clean cache files, if implemented.

  `php local clean:logs`                          Clean log files, if implemented.
  -----------------------------------------------------------------------------------

This list combines commands present in the development history with
commands whose generator methods were being added. Run `php local list`
and inspect `Kernel.php` to determine the exact commands currently
registered.

### Lazy database connection requirement

Do not add unconditional PDO construction to the CLI kernel constructor.
Resolve the database connection only inside database-dependent commands
or through a lazy accessor.

This allows commands such as `list`, `help`, `about`, and `version` to
work even when MySQL is not running.

### Generator naming

The controller generator has been discussed with a naming correction: a
name already ending in `Controller`, including a name such as
`HomeController2`, should not blindly receive another `Controller`
suffix. Test generated filenames, namespaces, class names, nested paths,
and overwrite protection.

------------------------------------------------------------------------

## 16. HTTP Filters --- Planned Integration

The latest architectural direction is to configure HTTP filters in
`config/filters.php`, similar to the filter configuration pattern found
in MVC frameworks.

This is different from a data filter that filters an array of products.
HTTP filters participate in request processing and may run before or
after a request or be attached to selected routes.

### Proposed configuration

``` php
<?php

declare(strict_types=1);

return [
    'global' => [
        'before' => [
            // \App\Filters\BeforeRequestFilter::class,
        ],
        'after' => [
            // \App\Filters\AfterRequestFilter::class,
        ],
    ],

    'aliases' => [
        // 'auth' => \App\Filters\AuthFilter::class,
        // 'guest' => \App\Filters\GuestFilter::class,
        // 'csrf' => \App\Filters\CsrfFilter::class,
    ],

    'routes' => [
        // 'admin' => ['auth'],
        // 'account' => ['auth', 'csrf'],
    ],
];
```

### Important status note

The configuration above is a **proposed design**, not proof that the
full filter execution system is implemented. A PHP configuration file
only returns data. To make filters work, the framework must also:

1.  Load `config/filters.php`.
2.  Resolve aliases to filter classes.
3.  Define a consistent filter contract/interface.
4.  Execute global before-filters before dispatching the route.
5.  Execute route-specific filters for matched routes.
6.  Execute after-filters at the correct point in the response
    lifecycle.
7.  Allow a filter to stop or redirect a request when appropriate.
8.  Handle filter exceptions consistently.
9.  Test filter order and behavior.

Do not rely on route syntax such as `->middleware('auth')` until it is
supported by the actual router and dispatcher. Decide whether the
framework will call these components **filters**, **middleware**, or
both; document the distinction if both terms are retained.

------------------------------------------------------------------------

## 17. Security Practices

Security features should be implemented and tested incrementally.

Current development priorities and recommended safeguards include:

-   Escape template output with `{{ ... }}` by default.
-   Use raw template output only for trusted or properly sanitized
    content.
-   Use PDO prepared statements for values in database queries.
-   Keep secrets in `.env` and out of version control.
-   Disable debug output in any public deployment.
-   Validate request input before using it.
-   Protect state-changing forms against CSRF when the filter/security
    layer is implemented.
-   Regenerate session IDs after successful authentication.
-   Apply secure cookie flags when using HTTPS.
-   Do not expose stack traces or sensitive configuration in HTTP
    responses.
-   Restrict destructive migration and cleanup commands.
-   Use explicit authorization checks for protected routes.

These are security requirements and development guidance, not a claim
that every security feature is already implemented.

------------------------------------------------------------------------

## 18. Error Handling and Troubleshooting

### `PDOException`: connection refused

**Likely cause:** MySQL is stopped, the host/port is wrong, or the
configured database service is not accepting connections.

**Steps:**

1.  Start MySQL in Laragon.
2.  Check `DB_HOST` and `DB_PORT` in `.env`.
3.  Verify that the database exists.
4.  Verify the username and password.
5.  Confirm that only database-dependent commands attempt a connection.

If `php local list` fails because MySQL is unavailable, inspect the CLI
kernel for eager PDO construction. Basic command listing should not
require a database connection.

### `Undefined variable $app` in `routes/web.php`

**Cause:** The route file is being required without an `$app` variable
in its scope.

**Fix:** Ensure the application route loader assigns `$app = $this` (or
the equivalent application instance) before requiring the route file, or
use a deliberately designed route registration API that does not depend
on a scoped variable.

### `Call to a member function router() on null`

**Likely cause:** The route file's `$app` variable is missing or null.

**Fix:** Resolve the route-loading scope problem first. Do not mask it
by creating a second application instance inside the route file.

### `404 - Page Not Found`

Check:

1.  The requested URI matches the registered route.
2.  The route is loaded.
3.  The HTTP method matches.
4.  The router normalizes leading/trailing slashes consistently.
5.  The web-server rewrite/document-root configuration forwards the
    request to the correct entry point.
6.  The controller action is callable.

### `View [home] not found`

Check that the expected file exists at:

``` text
resources/views/home.php
```

Also verify that the view engine receives the correct base path, maps
dot notation as expected, and uses the correct file extension.

### Controller generator adds a duplicate suffix

Inspect the class-name normalization in `make:controller`. A name ending
in `Controller` should not automatically become `ControllerController`.
Also test names containing digits, such as `HomeController2`.

### `view()` is undefined

Check that:

1.  `framework/src/helpers.php` defines `view()`.
2.  Composer's `autoload.files` includes that helper file.
3.  `composer dump-autoload` has been run.
4.  The controller executes after Composer autoloading is loaded.

### Route filter/middleware method does not exist

Do not call route methods such as `middleware()` until they are
implemented on the route definition or router. Complete the filter
configuration loader and dispatcher integration before treating the
filter API as available.

------------------------------------------------------------------------

## 19. Development Workflow

Recommended workflow when adding a feature:

1.  Inspect the existing class and its callers before changing its API.
2.  Keep existing methods and comments unless the change specifically
    requires modifying them.
3.  Implement the complete feature across its relevant layers rather
    than adding an unused config or helper.
4.  Add or update configuration and documentation.
5.  Run Composer autoload generation when class mappings or autoloaded
    files change.
6.  Run CLI smoke tests such as `php local list`.
7.  Test web routes in the browser.
8.  Test database-dependent features with MySQL running.
9.  Test failure cases, not only successful cases.
10. Update this document when the implementation changes.

### Compatibility rule

The real source code is authoritative. If this document describes a
proposed API that is not in the code yet, treat it as a plan and update
either the implementation or the documentation before using it as a
contract.

------------------------------------------------------------------------

## 20. Current Progress Summary

### Established project decisions

-   The framework is named **LocalPHP**.
-   The target PHP version is **8.2+**.
-   The project uses Composer and PSR-4 autoloading.
-   `LocalPHP\\` maps to `framework/src/`; `App\\` maps to `app/`.
-   The root `local` file is the framework's CLI entry point.
-   The web entry point loads `bootstrap/app.php` and captures an HTTP
    request.
-   The application view helper currently uses `LocalPHP\\View\\View` at
    `framework/src/View/View.php`; `framework/src/View/Local.php` is also present in the repository.
-   The preferred view helper is `view()`.
-   The welcome page should have a clean, light visual design.
-   CLI database access should be lazy so non-database commands can run
    while MySQL is stopped.
-   CLI generators for controllers, models, and filters are part of the
    current development work.
-   The next architectural direction is configuration-driven HTTP
    filters in `config/filters.php`.

### Areas that need verification or further implementation

-   Exact API and feature coverage of `Application`, `Router`,
    `Request`, and `Response`.
-   Route parameters, groups, prefixes, and route middleware/filter
    support.
-   Expand configuration coverage as new components are implemented; `.env` loading and the `env()` helper are implemented.
-   Session manager and session helper behavior.
-   Exact model/ORM API and query builder behavior.
-   Migration and seeder contracts and rollback semantics.
-   Filter interface, alias resolution, execution order, and router
    integration.
-   Automated tests, error handling, logging, and security hardening.

------------------------------------------------------------------------

## 21. Roadmap

The roadmap below records sensible next steps based on the current work.
It is not a promise that these features already exist.

### Foundation

-   [ ] Confirm the application bootstrap and request lifecycle.
-   [x] Load `.env` in web and CLI startup and connect the `env()` helper.
-   [x] Apply `APP_TIMEZONE` during application startup.
-   [ ] Expand configuration coverage as additional components are implemented.
-   [ ] Finalize HTTP request/response contracts.
-   [ ] Add consistent exception handling and logging.

### Routing and filters

-   [ ] Finalize route registration and dispatch.
-   [ ] Confirm route parameters and groups.
-   [ ] Implement `config/filters.php` loading.
-   [ ] Define the HTTP filter contract.
-   [ ] Implement global before/after filters.
-   [ ] Implement route-specific filter aliases and ordering.
-   [ ] Add tests for short-circuiting, redirects, exceptions, and
    response handling.

### Views and helpers

-   [ ] Confirm all template directives and escaping behavior.
-   [ ] Finalize `view()`, `response()`, `base_url()`, `config()`, and
    session helper signatures.
-   [ ] Add view-engine tests for layouts, includes, sections, and
    missing views.

### Database and ORM

-   [ ] Finalize PDO connection management.
-   [ ] Confirm the base model and query builder APIs.
-   [ ] Complete migration and rollback safety.
-   [ ] Complete seeder discovery and execution.
-   [ ] Add tests for connection errors and migration failures.

### CLI and quality

-   [ ] Verify all registered CLI commands.
-   [ ] Verify generator naming and nested paths.
-   [ ] Prevent accidental file overwrites.
-   [ ] Add automated tests and a repeatable smoke-test checklist.
-   [ ] Keep documentation synchronized with shipped behavior.

------------------------------------------------------------------------

## 22. Documentation Maintenance

Treat this file as the primary project overview.

Whenever a feature changes:

-   Update the project structure.
-   Update examples to match the actual public API.
-   Mark proposed features as implemented only after the full execution
    path exists.
-   Document breaking changes and command behavior.
-   Add troubleshooting notes for errors encountered during development.
-   Keep the roadmap focused on unfinished work.

**Project principle:** Prefer complete, integrated implementations over
disconnected helpers, unused configuration, or partial code that removes
existing functionality.


## Production Error Handling, Logging, and Configuration Cache

LocalPHP registers a web exception handler before bootstrapping the application. With `APP_DEBUG=true`, the browser receives a detailed escaped exception page for development. With `APP_DEBUG=false`, visitors receive a generic HTTP 500 page; exception details are written to `storage/logs/localphp-YYYY-MM-DD.log`. Apache deny rules protect the storage directory, and log/cache files are ignored by Git. Keep `APP_DEBUG=false` in production.

Configuration files can be compiled after setting the desired environment values:

```bash
php local config:cache
# or
php local optimize
```

The cache is stored in `storage/cache/config.php` and is used by `config()`. After changing `.env` or files in `config/`, rebuild it with `php local config:cache`, or remove it with `php local config:clear`. `php local optimize:clear` clears cache files. PHP OPcache is a server-level optimization and must be enabled in the PHP configuration separately.

Routes are currently registered from `routes/web.php` at application startup and are not serialized into a route cache; route closures make safe route serialization unsuitable without a dedicated route compiler. Avoid exposing `storage/` publicly and verify Apache honors `.htaccess` rules in deployment.

## Query Builder

LocalPHP provides a PDO-backed fluent query builder through `DB::table()` or `db()->table()`, and model queries through `Model::query()`. Values passed to normal query methods are parameter-bound. Table and column identifiers are validated; raw SQL methods must only contain developer-authored SQL.

```php
$users = db()->table('users')
    ->select(['id', 'name', 'email'])
    ->where('active', true)
    ->where(function ($query) {
        $query->where('role', 'admin')->orWhere('role', 'editor');
    })
    ->orderBy('name')
    ->paginate(15, 1);

$userId = db()->table('users')->insertGetId([
    'name' => 'Example User',
    'email' => 'example@example.com',
]);

$totals = db()->table('orders')
    ->select('user_id')
    ->selectRaw('SUM(total) AS total_spent')
    ->groupBy('user_id')
    ->having('total_spent', '>', 1000)
    ->get();
```

Implemented Query Builder method groups include select/retrieval (`select`, `addSelect`, `selectRaw`, `distinct`, `get`, `first`, `find`, `value`, `pluck`); conditions (`where`, `orWhere`, `whereColumn`, `whereIn`, `whereNotIn`, `whereBetween`, `whereNotBetween`, null checks, date/time filters, LIKE and raw/nested/EXISTS conditions); joins (`join`, `leftJoin`, `rightJoin`, `crossJoin`, `joinSub`, `leftJoinSub`); grouping (`groupBy`, `groupByRaw`, `having`, `orHaving`, `havingRaw`); sorting and limits (`orderBy`, `orderByDesc`, `orderByRaw`, `latest`, `oldest`, `inRandomOrder`, `limit`, `take`, `offset`, `skip`, `forPage`); aggregates (`count`, `sum`, `avg`, `min`, `max`, `exists`, `doesntExist`); writes (`insert`, `insertGetId`, `insertOrIgnore`, `upsert`, `update`, `increment`, `decrement`, `delete`, `truncate`); pagination (`paginate`, `simplePaginate`, `chunk`); conditional methods (`when`, `unless`); and debugging (`toSql`, `getBindings`, `dump`, `dd`).

`paginate($perPage, $page)` returns an array with `data`, `current_page`, `per_page`, `total`, `last_page`, `from`, and `to`. `simplePaginate()` returns `data`, `current_page`, `per_page`, and `has_more_pages`. `upsert()` uses MySQL's `ON DUPLICATE KEY UPDATE`; the database table must have a corresponding primary or unique key. `insertOrIgnore()` and `upsert()` are MySQL-specific in this release.

Raw SQL is an explicit escape hatch. For example, `selectRaw('SUM(total) AS total', [])`, `whereRaw('score > ?', [$score])`, or `DB::raw('COUNT(*) AS total')`. Never concatenate request input into raw SQL. Use `DB::raw($sql, $bindings)` or `raw($sql, $bindings)` when passing a `RawExpression` to `select()`.
