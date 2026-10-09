<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use LocalPHP\Database\Connection;
use LocalPHP\Database\QueryBuilder;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$connectionReflection = new ReflectionClass(Connection::class);
$connection = $connectionReflection->newInstanceWithoutConstructor();

$query = QueryBuilder::table($connection, 'users')
    ->select('users.id', 'users.name')
    ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
    ->where(function ($query): void {
        $query->where('users.active', true)->orWhere('users.role', 'admin');
    })
    ->whereNotIn('users.status', ['banned', 'deleted'])
    ->whereBetween('users.age', [18, 65])
    ->whereNull('users.deleted_at')
    ->orderByDesc('users.id')
    ->limit(20)
    ->offset(10);

check(str_contains($query->toSql(), 'LEFT JOIN `profiles`'), 'left join compiles');
check(str_contains($query->toSql(), 'WHERE (`users`.`active` = ? OR `users`.`role` = ?)'), 'nested where groups compile');
check(str_contains($query->toSql(), 'NOT IN (?, ?)'), 'whereNotIn compiles');
check(str_contains($query->toSql(), 'BETWEEN ? AND ?'), 'whereBetween compiles');
check(str_contains($query->toSql(), 'IS NULL'), 'whereNull compiles');
check($query->getBindings() === [true, 'admin', 'banned', 'deleted', 18, 65], 'bindings follow SQL placeholder order');

$subquery = QueryBuilder::table($connection, 'users')->whereExists(function ($query): void {
    $query->from('orders')->select('id')->whereColumn('orders.user_id', 'users.id')->where('status', 'paid');
});
check(str_contains($subquery->toSql(), 'EXISTS (SELECT `id` FROM `orders`'), 'EXISTS subquery compiles');
check($subquery->getBindings() === ['paid'], 'EXISTS bindings are retained');

$raw = QueryBuilder::table($connection, 'orders')
    ->select('user_id')
    ->selectRaw('SUM(total) AS total_spent')
    ->groupBy('user_id')
    ->having('total_spent', '>', 100)
    ->orderByRaw('total_spent DESC');
check(str_contains($raw->toSql(), 'GROUP BY `user_id` HAVING `total_spent` > ? ORDER BY total_spent DESC'), 'aggregate/group/having query compiles');
check($raw->getBindings() === [100], 'having bindings are retained');

try {
    QueryBuilder::table($connection, 'users')->where('id; DROP TABLE users', 1);
    check(false, 'unsafe identifiers must be rejected');
} catch (InvalidArgumentException) {
    // Expected.
}

fwrite(STDOUT, "Query Builder smoke tests passed.\n");
