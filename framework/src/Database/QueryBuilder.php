<?php

declare(strict_types=1);

namespace LocalPHP\Database;

use Closure;
use InvalidArgumentException;
use LocalPHP\Model\Model;
use RuntimeException;
use Throwable;

/**
 * Fluent, PDO-backed SQL query builder for LocalPHP.
 * Identifiers are validated and values are always parameter-bound unless the
 * developer explicitly opts into a RawExpression.
 */
class QueryBuilder
{
    protected Connection $connection;
    protected string $table;
    protected string $tableName;
    protected ?string $modelClass = null;
    protected array $columns = ['*'];
    protected array $selectBindings = [];
    protected array $wheres = [];
    protected array $joins = [];
    protected array $groups = [];
    protected array $groupBindings = [];
    protected array $havings = [];
    protected array $orders = [];
    protected array $orderBindings = [];
    protected array $bindings = [];
    protected bool $distinctValue = false;
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;

    protected function __construct(Connection $connection, string $table, ?string $modelClass = null)
    {
        $this->connection = $connection;
        $this->tableName = $table;
        $this->table = $this->wrapTable($table);
        $this->modelClass = $modelClass;
    }

    public static function table(Connection $connection, string $table): self
    {
        return new self($connection, $table);
    }

    public static function forModel(Connection $connection, string $table, string $modelClass): self
    {
        return new self($connection, $table, $modelClass);
    }

    public static function raw(string $sql, array $bindings = []): RawExpression
    {
        return new RawExpression($sql, $bindings);
    }

    public function from(string $table): static
    {
        $this->tableName = $table;
        $this->table = $this->wrapTable($table);
        return $this;
    }

    public function select(string|array|RawExpression $columns = ['*'], string|RawExpression ...$moreColumns): static
    {
        $columns = is_array($columns) ? $columns : [$columns];
        array_push($columns, ...$moreColumns);
        $this->columns = [];
        $this->selectBindings = [];

        foreach ($columns as $column) {
            if ($column instanceof RawExpression) {
                $this->columns[] = $column->sql;
                array_push($this->selectBindings, ...$column->bindings);
            } else {
                $this->columns[] = $this->wrapAliasedIdentifier((string) $column);
            }
        }

        if ($this->columns === []) {
            $this->columns = ['*'];
        }

        return $this;
    }

    public function addSelect(string|array|RawExpression $columns, string|RawExpression ...$moreColumns): static
    {
        $columns = is_array($columns) ? $columns : [$columns];
        array_push($columns, ...$moreColumns);
        foreach ($columns as $column) {
            if ($column instanceof RawExpression) {
                $this->columns[] = $column->sql;
                array_push($this->selectBindings, ...$column->bindings);
            } else {
                $this->columns[] = $this->wrapAliasedIdentifier((string) $column);
            }
        }
        return $this;
    }

    public function distinct(bool $value = true): static
    {
        $this->distinctValue = $value;
        return $this;
    }

    public function selectRaw(string $sql, array $bindings = []): static
    {
        if ($this->columns === ['*'] && $this->selectBindings === []) {
            $this->columns = [];
        }
        $this->columns[] = $sql;
        array_push($this->selectBindings, ...$bindings);
        return $this;
    }

    public function where(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            return $this->whereNested($column);
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addBasicWhere($column, (string) $operator, $value, 'AND');
    }

    public function orWhere(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            return $this->whereNested($column, 'OR');
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addBasicWhere($column, (string) $operator, $value, 'OR');
    }

    protected function addBasicWhere(string $column, string $operator, mixed $value, string $boolean): static
    {
        $operator = strtoupper(trim($operator));
        $allowed = ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'REGEXP', 'NOT REGEXP'];
        if (!in_array($operator, $allowed, true)) {
            throw new InvalidArgumentException("Unsupported where operator [{$operator}].");
        }

        $column = $this->wrapIdentifier($column);
        if ($value === null && $operator === '=') {
            return $this->addWhere("{$column} IS NULL", [], $boolean);
        }
        if ($value === null && in_array($operator, ['!=', '<>'], true)) {
            return $this->addWhere("{$column} IS NOT NULL", [], $boolean);
        }
        return $this->addWhere("{$column} {$operator} ?", [$value], $boolean);
    }

    protected function addWhere(string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        $this->wheres[] = ['boolean' => $boolean, 'sql' => $sql, 'bindings' => $bindings];
        return $this;
    }

    public function whereColumn(string $first, string $operator, ?string $second = null): static
    {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }
        $operator = strtoupper($operator);
        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException("Unsupported column comparison operator [{$operator}].");
        }
        return $this->addWhere($this->wrapIdentifier($first) . " {$operator} " . $this->wrapIdentifier($second));
    }

    public function whereIn(string $column, array $values): static
    {
        return $this->whereInBoolean($column, $values, 'AND', false);
    }

    public function orWhereIn(string $column, array $values): static
    {
        return $this->whereInBoolean($column, $values, 'OR', false);
    }

    public function whereNotIn(string $column, array $values): static
    {
        return $this->whereInBoolean($column, $values, 'AND', true);
    }

    public function orWhereNotIn(string $column, array $values): static
    {
        return $this->whereInBoolean($column, $values, 'OR', true);
    }

    protected function whereInBoolean(string $column, array $values, string $boolean, bool $not): static
    {
        $column = $this->wrapIdentifier($column);
        if ($values === []) {
            return $this->addWhere($not ? '1 = 1' : '1 = 0', [], $boolean);
        }
        $marks = implode(', ', array_fill(0, count($values), '?'));
        return $this->addWhere("{$column} " . ($not ? 'NOT IN' : 'IN') . " ({$marks})", array_values($values), $boolean);
    }

    public function whereBetween(string $column, array $values): static
    {
        return $this->whereBetweenBoolean($column, $values, 'AND', false);
    }

    public function orWhereBetween(string $column, array $values): static
    {
        return $this->whereBetweenBoolean($column, $values, 'OR', false);
    }

    public function whereNotBetween(string $column, array $values): static
    {
        return $this->whereBetweenBoolean($column, $values, 'AND', true);
    }

    public function orWhereNotBetween(string $column, array $values): static
    {
        return $this->whereBetweenBoolean($column, $values, 'OR', true);
    }

    protected function whereBetweenBoolean(string $column, array $values, string $boolean, bool $not): static
    {
        if (count($values) !== 2) {
            throw new InvalidArgumentException('whereBetween requires exactly two values.');
        }
        return $this->addWhere(
            $this->wrapIdentifier($column) . ($not ? ' NOT BETWEEN ? AND ?' : ' BETWEEN ? AND ?'),
            array_values($values),
            $boolean
        );
    }

    public function whereNull(string|array $columns): static
    {
        foreach ((array) $columns as $column) {
            $this->addWhere($this->wrapIdentifier($column) . ' IS NULL');
        }
        return $this;
    }

    public function orWhereNull(string $column): static
    {
        return $this->addWhere($this->wrapIdentifier($column) . ' IS NULL', [], 'OR');
    }

    public function whereNotNull(string|array $columns): static
    {
        foreach ((array) $columns as $column) {
            $this->addWhere($this->wrapIdentifier($column) . ' IS NOT NULL');
        }
        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        return $this->addWhere($this->wrapIdentifier($column) . ' IS NOT NULL', [], 'OR');
    }

    public function whereDate(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->whereDatePart('DATE', $column, $operator, $value);
    }

    public function whereTime(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->whereDatePart('TIME', $column, $operator, $value);
    }

    public function whereYear(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->whereDatePart('YEAR', $column, $operator, $value);
    }

    public function whereMonth(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->whereDatePart('MONTH', $column, $operator, $value);
    }

    public function whereDay(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) { $value = $operator; $operator = '='; }
        return $this->whereDatePart('DAY', $column, $operator, $value);
    }

    protected function whereDatePart(string $function, string $column, mixed $operator, mixed $value): static
    {
        $operator = strtoupper((string) $operator);
        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException("Unsupported date comparison operator [{$operator}].");
        }
        return $this->addWhere("{$function}(" . $this->wrapIdentifier($column) . ") {$operator} ?", [$value]);
    }

    public function whereLike(string $column, string $pattern): static
    {
        return $this->where($column, 'LIKE', $pattern);
    }

    public function whereNotLike(string $column, string $pattern): static
    {
        return $this->where($column, 'NOT LIKE', $pattern);
    }

    public function whereRaw(string $sql, array $bindings = []): static
    {
        return $this->addWhere($sql, $bindings);
    }

    public function orWhereRaw(string $sql, array $bindings = []): static
    {
        return $this->addWhere($sql, $bindings, 'OR');
    }

    public function whereNested(Closure $callback, string $boolean = 'AND'): static
    {
        $nested = new self($this->connection, $this->tableName, null);
        $callback($nested);
        if ($nested->wheres !== []) {
            $bindings = [];
            foreach ($nested->wheres as $where) {
                array_push($bindings, ...$where['bindings']);
            }
            $nestedSql = substr($nested->compileWheres(), 7);
            return $this->addWhere('(' . $nestedSql . ')', $bindings, strtoupper($boolean));
        }
        return $this;
    }

    public function whereExists(Closure|self $query): static
    {
        return $this->whereExistsBoolean($query, 'AND', false);
    }

    public function orWhereExists(Closure|self $query): static
    {
        return $this->whereExistsBoolean($query, 'OR', false);
    }

    public function whereNotExists(Closure|self $query): static
    {
        return $this->whereExistsBoolean($query, 'AND', true);
    }

    public function orWhereNotExists(Closure|self $query): static
    {
        return $this->whereExistsBoolean($query, 'OR', true);
    }

    protected function whereExistsBoolean(Closure|self $query, string $boolean, bool $not): static
    {
        if ($query instanceof Closure) {
            $subquery = new self($this->connection, $this->tableName);
            $query($subquery);
        } else {
            $subquery = clone $query;
        }
        return $this->addWhere(($not ? 'NOT ' : '') . 'EXISTS (' . $subquery->toSql() . ')', $subquery->getBindings(), $boolean);
    }

    public function join(string $table, string $first, string $operator, string $second): static
    {
        return $this->addJoin('INNER', $table, $first, $operator, $second);
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->addJoin('LEFT', $table, $first, $operator, $second);
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->addJoin('RIGHT', $table, $first, $operator, $second);
    }

    public function crossJoin(string $table): static
    {
        $this->joins[] = ['type' => 'CROSS', 'table' => $this->wrapTable($table), 'sql' => '', 'bindings' => []];
        return $this;
    }

    protected function addJoin(string $type, string $table, string $first, string $operator, string $second): static
    {
        $operator = strtoupper($operator);
        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException("Unsupported join operator [{$operator}].");
        }
        $this->joins[] = [
            'type' => $type,
            'table' => $this->wrapTable($table),
            'sql' => $this->wrapIdentifier($first) . " {$operator} " . $this->wrapIdentifier($second),
            'bindings' => [],
        ];
        return $this;
    }

    public function joinSub(self $query, string $alias, string $first, string $operator, string $second): static
    {
        return $this->addJoinSub('INNER', $query, $alias, $first, $operator, $second);
    }

    public function leftJoinSub(self $query, string $alias, string $first, string $operator, string $second): static
    {
        return $this->addJoinSub('LEFT', $query, $alias, $first, $operator, $second);
    }

    protected function addJoinSub(string $type, self $query, string $alias, string $first, string $operator, string $second): static
    {
        $operator = strtoupper($operator);
        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException("Unsupported join operator [{$operator}].");
        }
        $this->joins[] = [
            'type' => $type,
            'table' => '(' . $query->toSql() . ') AS ' . $this->wrapIdentifier($alias),
            'sql' => $this->wrapIdentifier($first) . " {$operator} " . $this->wrapIdentifier($second),
            'bindings' => $query->getBindings(),
        ];
        return $this;
    }

    public function groupBy(string|array $columns): static
    {
        foreach ((array) $columns as $column) {
            $this->groups[] = $this->wrapIdentifier((string) $column);
        }
        return $this;
    }

    public function groupByRaw(string $sql, array $bindings = []): static
    {
        $this->groups[] = $sql;
        array_push($this->groupBindings, ...$bindings);
        return $this;
    }

    public function having(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addHaving($this->wrapIdentifier($column), (string) $operator, $value, 'AND');
    }

    public function orHaving(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addHaving($this->wrapIdentifier($column), (string) $operator, $value, 'OR');
    }

    protected function addHaving(string $column, string $operator, mixed $value, string $boolean): static
    {
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'], true)) {
            throw new InvalidArgumentException("Unsupported having operator [{$operator}].");
        }
        $this->havings[] = ['boolean' => $boolean, 'sql' => "{$column} {$operator} ?", 'bindings' => [$value]];
        return $this;
    }

    public function havingRaw(string $sql, array $bindings = []): static
    {
        $this->havings[] = ['boolean' => 'AND', 'sql' => $sql, 'bindings' => $bindings];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Order direction must be ASC or DESC.');
        }
        $this->orders[] = $this->wrapIdentifier($column) . ' ' . $direction;
        return $this;
    }

    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function orderByRaw(string $sql, array $bindings = []): static
    {
        $this->orders[] = $sql;
        array_push($this->orderBindings, ...$bindings);
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    public function inRandomOrder(): static
    {
        $this->orders[] = 'RAND()';
        return $this;
    }

    public function limit(int $limit): static
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit cannot be negative.');
        }
        $this->limitValue = $limit;
        return $this;
    }

    public function take(int $limit): static { return $this->limit($limit); }

    public function offset(int $offset): static
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset cannot be negative.');
        }
        $this->offsetValue = $offset;
        return $this;
    }

    public function skip(int $offset): static { return $this->offset($offset); }

    public function forPage(int $page, int $perPage = 15): static
    {
        if ($page < 1 || $perPage < 1) {
            throw new InvalidArgumentException('Page and per-page values must be greater than zero.');
        }
        return $this->limit($perPage)->offset(($page - 1) * $perPage);
    }

    public function get(): array
    {
        $rows = $this->connection->select($this->toSql(), $this->getBindings());
        if ($this->modelClass === null) {
            return $rows;
        }
        $class = $this->modelClass;
        return array_map(static fn (array $row) => $class::hydrate($row), $rows);
    }

    public function first(): Model|array|null
    {
        $clone = clone $this;
        $clone->limit(1);
        return $clone->get()[0] ?? null;
    }

    public function find(int|string $id, string $primaryKey = 'id'): Model|array|null
    {
        return (clone $this)->where($primaryKey, $id)->first();
    }

    public function value(string $column): mixed
    {
        $result = (clone $this)->select($column)->first();
        if ($result instanceof Model) {
            return $result->getAttribute($column);
        }
        if (!is_array($result)) return null;
        if (array_key_exists($column, $result)) return $result[$column];
        return array_values($result)[0] ?? null;
    }

    public function pluck(string $column, ?string $key = null): array
    {
        $query = clone $this;
        $query->select($key === null ? [$column] : [$key, $column]);
        $rows = $query->get();
        $results = [];
        foreach ($rows as $row) {
            $data = $row instanceof Model ? $row->toArray() : $row;
            $value = $data[$column] ?? null;
            if ($key === null) {
                $results[] = $value;
            } else {
                $results[$data[$key] ?? null] = $value;
            }
        }
        return $results;
    }

    public function count(string $column = '*'): int
    {
        if ($this->distinctValue || $this->groups !== [] || $this->havings !== []) {
            $query = clone $this;
            $query->orders = [];
            $query->orderBindings = [];
            $query->limitValue = null;
            $query->offsetValue = null;
            $sql = 'SELECT COUNT(*) AS `aggregate` FROM (' . $query->toSql() . ') AS `count_table`';
            $row = $this->connection->selectOne($sql, $query->getBindings());
            return (int) ($row['aggregate'] ?? 0);
        }
        return (int) $this->aggregate('COUNT', $column);
    }

    public function sum(string $column): int|float
    {
        $value = $this->aggregate('SUM', $column);
        return is_numeric($value) ? $value + 0 : 0;
    }

    public function avg(string $column): int|float|null
    {
        $value = $this->aggregate('AVG', $column);
        return is_numeric($value) ? $value + 0 : null;
    }

    public function min(string $column): mixed { return $this->aggregate('MIN', $column); }
    public function max(string $column): mixed { return $this->aggregate('MAX', $column); }

    protected function aggregate(string $function, string $column): mixed
    {
        $query = clone $this;
        $query->columns = [$function . '(' . ($column === '*' ? '*' : $query->wrapIdentifier($column)) . ') AS `aggregate`'];
        $query->selectBindings = [];
        $query->orders = [];
        $query->orderBindings = [];
        $query->limitValue = null;
        $query->offsetValue = null;
        $row = $this->connection->selectOne($query->toSql(), $query->getBindings());
        return $row['aggregate'] ?? null;
    }

    public function exists(): bool
    {
        $query = clone $this;
        $query->columns = ['1 AS `exists`'];
        $query->selectBindings = [];
        $query->orders = [];
        $query->orderBindings = [];
        $query->limit(1);
        return $this->connection->selectOne($query->toSql(), $query->getBindings()) !== null;
    }

    public function doesntExist(): bool { return !$this->exists(); }

    public function insert(array $values): bool
    {
        if ($values === []) return true;
        if (!array_is_list($values)) $values = [$values];
        $columns = array_keys($values[0]);
        if ($columns === []) return true;
        foreach ($values as $row) {
            if (array_keys($row) !== $columns) {
                throw new InvalidArgumentException('All inserted rows must have identical columns in identical order.');
            }
        }
        $wrapped = array_map(fn ($column) => $this->wrapIdentifier((string) $column), $columns);
        $rowSql = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $wrapped) . ') VALUES '
            . implode(', ', array_fill(0, count($values), $rowSql));
        $bindings = [];
        foreach ($values as $row) array_push($bindings, ...array_values($row));
        $this->connection->execute($sql, $bindings);
        return true;
    }

    public function insertGetId(array $values, ?string $sequence = null): int|string
    {
        if ($values === []) throw new InvalidArgumentException('insertGetId requires at least one column.');
        $columns = array_map(fn ($column) => $this->wrapIdentifier((string) $column), array_keys($values));
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $columns) . ') VALUES ('
            . implode(', ', array_fill(0, count($values), '?')) . ')';
        $id = $this->connection->insert($sql, array_values($values));
        return $id === false ? 0 : (ctype_digit($id) ? (int) $id : $id);
    }

    public function insertOrIgnore(array $values): int
    {
        if ($values === []) return 0;
        if (!array_is_list($values)) $values = [$values];
        $columns = array_keys($values[0]);
        foreach ($values as $row) {
            if (array_keys($row) !== $columns) throw new InvalidArgumentException('All inserted rows must have identical columns.');
        }
        $sql = 'INSERT IGNORE INTO ' . $this->table . ' ('
            . implode(', ', array_map(fn ($c) => $this->wrapIdentifier((string) $c), $columns)) . ') VALUES '
            . implode(', ', array_fill(0, count($values), '(' . implode(', ', array_fill(0, count($columns), '?')) . ')'));
        $bindings = [];
        foreach ($values as $row) array_push($bindings, ...array_values($row));
        return $this->connection->execute($sql, $bindings);
    }

    public function upsert(array $values, array|string $uniqueBy, ?array $update = null): int
    {
        if ($values === []) return 0;
        if (!array_is_list($values)) $values = [$values];
        $columns = array_keys($values[0]);
        foreach ($values as $row) {
            if (array_keys($row) !== $columns) throw new InvalidArgumentException('All upsert rows must have identical columns.');
        }
        $updateColumns = $update ?? array_values(array_diff($columns, (array) $uniqueBy));
        $sql = 'INSERT INTO ' . $this->table . ' ('
            . implode(', ', array_map(fn ($c) => $this->wrapIdentifier((string) $c), $columns)) . ') VALUES '
            . implode(', ', array_fill(0, count($values), '(' . implode(', ', array_fill(0, count($columns), '?')) . ')'));
        if ($updateColumns === []) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->wrapIdentifier((string) $columns[0]) . ' = VALUES(' . $this->wrapIdentifier((string) $columns[0]) . ')';
        } else {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(function ($column) {
                $wrapped = $this->wrapIdentifier((string) $column);
                return $wrapped . ' = VALUES(' . $wrapped . ')';
            }, $updateColumns));
        }
        $bindings = [];
        foreach ($values as $row) array_push($bindings, ...array_values($row));
        return $this->connection->execute($sql, $bindings);
    }

    public function update(array $values): int
    {
        if ($values === []) return 0;
        $sets = [];
        $bindings = [];
        foreach ($values as $column => $value) {
            $sets[] = $this->wrapIdentifier((string) $column) . ' = ?';
            $bindings[] = $value;
        }
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . $this->compileWheres();
        array_push($bindings, ...$this->whereBindings());
        return $this->connection->execute($sql, $bindings);
    }

    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->changeNumericColumn($column, '+', $amount, $extra);
    }

    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->changeNumericColumn($column, '-', $amount, $extra);
    }

    protected function changeNumericColumn(string $column, string $operator, int|float $amount, array $extra): int
    {
        $sets = [$this->wrapIdentifier($column) . " = " . $this->wrapIdentifier($column) . " {$operator} ?"];
        $bindings = [$amount];
        foreach ($extra as $key => $value) {
            $sets[] = $this->wrapIdentifier((string) $key) . ' = ?';
            $bindings[] = $value;
        }
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . $this->compileWheres();
        array_push($bindings, ...$this->whereBindings());
        return $this->connection->execute($sql, $bindings);
    }

    public function delete(): int
    {
        return $this->connection->execute('DELETE FROM ' . $this->table . $this->compileWheres(), $this->whereBindings());
    }

    public function truncate(): void
    {
        $this->connection->execute('TRUNCATE TABLE ' . $this->table);
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        if ($perPage < 1 || $page < 1) throw new InvalidArgumentException('Page and per-page values must be greater than zero.');
        $countQuery = clone $this;
        $total = $countQuery->count();
        $items = (clone $this)->forPage($page, $perPage)->get();
        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'from' => $items === [] ? null : (($page - 1) * $perPage + 1),
            'to' => $items === [] ? null : (($page - 1) * $perPage + count($items)),
        ];
    }

    public function simplePaginate(int $perPage = 15, int $page = 1): array
    {
        if ($perPage < 1 || $page < 1) throw new InvalidArgumentException('Page and per-page values must be greater than zero.');
        $items = (clone $this)->forPage($page, $perPage + 1)->get();
        $hasMore = count($items) > $perPage;
        if ($hasMore) array_pop($items);
        return ['data' => $items, 'current_page' => $page, 'per_page' => $perPage, 'has_more_pages' => $hasMore];
    }

    public function chunk(int $size, callable $callback): bool
    {
        if ($size < 1) throw new InvalidArgumentException('Chunk size must be greater than zero.');
        $page = 1;
        do {
            $results = (clone $this)->forPage($page, $size)->get();
            if ($results === []) break;
            if ($callback($results, $page) === false) return false;
            $page++;
        } while (count($results) === $size);
        return true;
    }

    public function when(mixed $value, callable $callback, ?callable $default = null): static
    {
        if ($value) $callback($this, $value);
        elseif ($default !== null) $default($this, $value);
        return $this;
    }

    public function unless(mixed $value, callable $callback, ?callable $default = null): static
    {
        if (!$value) $callback($this, $value);
        elseif ($default !== null) $default($this, $value);
        return $this;
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . ($this->distinctValue ? 'DISTINCT ' : '') . implode(', ', $this->columns)
            . ' FROM ' . $this->table;
        foreach ($this->joins as $join) {
            $sql .= ' ' . $join['type'] . ' JOIN ' . $join['table'];
            if ($join['type'] !== 'CROSS') $sql .= ' ON ' . $join['sql'];
        }
        $sql .= $this->compileWheres();
        if ($this->groups !== []) $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        if ($this->havings !== []) {
            $parts = [];
            foreach ($this->havings as $index => $having) $parts[] = ($index ? $having['boolean'] . ' ' : '') . $having['sql'];
            $sql .= ' HAVING ' . implode(' ', $parts);
        }
        if ($this->orders !== []) $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        if ($this->limitValue !== null) $sql .= ' LIMIT ' . $this->limitValue;
        if ($this->offsetValue !== null) {
            if ($this->limitValue === null) $sql .= ' LIMIT 18446744073709551615';
            $sql .= ' OFFSET ' . $this->offsetValue;
        }
        return $sql;
    }

    public function getBindings(): array
    {
        $bindings = $this->selectBindings;
        foreach ($this->joins as $join) array_push($bindings, ...$join['bindings']);
        array_push($bindings, ...$this->whereBindings());
        array_push($bindings, ...$this->groupBindings);
        foreach ($this->havings as $having) array_push($bindings, ...$having['bindings']);
        array_push($bindings, ...$this->orderBindings);
        return $bindings;
    }

    protected function whereBindings(): array
    {
        $bindings = [];
        foreach ($this->wheres as $where) array_push($bindings, ...$where['bindings']);
        return $bindings;
    }

    protected function compileWheres(): string
    {
        if ($this->wheres === []) return '';
        $parts = [];
        foreach ($this->wheres as $index => $where) $parts[] = ($index ? $where['boolean'] . ' ' : '') . $where['sql'];
        return ' WHERE ' . implode(' ', $parts);
    }

    public function dump(): static
    {
        var_dump(['sql' => $this->toSql(), 'bindings' => $this->getBindings()]);
        return $this;
    }

    public function dd(): never
    {
        $this->dump();
        exit(1);
    }

    protected function wrapTable(string $table): string
    {
        if (preg_match('/^\s*(.+?)\s+as\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*$/i', $table, $matches)) {
            return $this->wrapIdentifier(trim($matches[1])) . ' AS ' . $this->wrapIdentifier($matches[2]);
        }
        return $this->wrapIdentifier($table);
    }

    protected function wrapAliasedIdentifier(string $identifier): string
    {
        if (preg_match('/^\s*(.+?)\s+as\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*$/i', $identifier, $matches)) {
            return $this->wrapIdentifier(trim($matches[1])) . ' AS ' . $this->wrapIdentifier($matches[2]);
        }
        return $this->wrapIdentifier($identifier);
    }

    protected function wrapIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '*') return '*';
        $parts = explode('.', $identifier);
        $wrapped = [];
        foreach ($parts as $part) {
            if ($part === '*') { $wrapped[] = '*'; continue; }
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $part)) {
                throw new InvalidArgumentException("Invalid SQL identifier [{$identifier}]. Use raw expressions explicitly for SQL expressions.");
            }
            $wrapped[] = '`' . $part . '`';
        }
        return implode('.', $wrapped);
    }
}
