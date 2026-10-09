# LocalPHP Framework Improvements

## Strict typing
`declare(strict_types=1)` is a PHP per-file compile-time directive and cannot be switched on globally by framework config. `config/app.php` now exposes `strict_types` (default true) as the code-generation convention. Generated validators and middleware include the directive when enabled. Keep the directive in framework source files as well.

## Validation
Use `validator($data, $rules, $messages)` to get a validator, or `validate($data, $rules, $messages)` to get validated data (throws `LocalPHP\Validation\ValidationException` on failure).

Supported rules include required, nullable, sometimes, string, integer, numeric, boolean, array, email, url, alpha, alpha_num, alpha_dash, min, max, between, in, not_in, confirmed, same, different, date, date_format, regex, accepted, digits, uuid, json, unique, and exists. Database-backed unique/exists rules use the configured default database connection; the database must be configured and reachable when those rules run.

## Schema migrations
Use `LocalPHP\Database\Schema` from a migration. Example:

```php
Schema::create('users', function (Blueprint $table): void {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->unique('email');
    $table->string('password');
    $table->timestamps();
});
```

Available column helpers: id, bigIncrements, increments, string, char, text, mediumText, longText, integer, bigInteger, tinyInteger, boolean, decimal, float, double, date, dateTime, timestamp, json, timestamps, softDeletes. Index helpers: index, unique, primaryKey, foreign with references/on/onDelete/onUpdate. `Schema::table`, `drop`, and `dropIfExists` are also provided. Schema DDL targets MySQL/MariaDB.

## Routes
Router adds PATCH, route groups with prefix/middleware attributes, and `urlFor()` for named routes. Named routes use the existing fluent `->name('users.show')` method.

## CLI
New commands: `php local make:validator User` and `php local make:middleware Auth`.

## Migration repository
The migration manager already creates its framework-owned `migrations` table with a unique migration filename, batch number, and execution timestamp. Migration DDL is MySQL-specific and may not be transactional on all MySQL table operations.
