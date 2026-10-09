<?php

declare(strict_types=1);

namespace LocalPHP\Database;

use InvalidArgumentException;
use LocalPHP\Model\Model;

class QueryBuilder
{
    protected Connection $connection;
    protected string $table;
    protected ?string $modelClass = null;

    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $orders = [];

    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;

    protected function __construct(
        Connection $connection,
        string $table,
        ?string $modelClass = null
    ) {
        $this->connection = $connection;
        $this->table = $this->identifier($table);
        $this->modelClass = $modelClass;
    }

    public static function table(
        Connection $connection,
        string $table
    ): self {
        return new self($connection, $table);
    }

    public static function forModel(
        Connection $connection,
        string $table,
        string $modelClass
    ): self {
        return new self(
            $connection,
            $table,
            $modelClass
        );
    }

    public function select(
        string|array $columns = ['*']
    ): static {
        $columns = is_array($columns)
            ? $columns
            : [$columns];

        $this->columns = array_map(
            fn ($column) => $column === '*'
                ? '*'
                : $this->identifier((string) $column),
            $columns
        );

        return $this;
    }

    public function where(
        string $column,
        mixed $operator,
        mixed $value = null
    ): static {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtoupper((string) $operator);

        $allowed = [
            '=', '!=', '<>', '>', '>=', '<', '<=',
            'LIKE', 'NOT LIKE',
        ];

        if (!in_array($operator, $allowed, true)) {
            throw new InvalidArgumentException(
                "Unsupported where operator [{$operator}]."
            );
        }

        $column = $this->identifier($column);

        if ($value === null) {
            if ($operator === '=') {
                $this->wheres[] = [
                    'boolean' => 'AND',
                    'sql' => "{$column} IS NULL",
                ];

                return $this;
            }

            if (in_array($operator, ['!=', '<>'], true)) {
                $this->wheres[] = [
                    'boolean' => 'AND',
                    'sql' => "{$column} IS NOT NULL",
                ];

                return $this;
            }
        }

        $this->wheres[] = [
            'boolean' => 'AND',
            'sql' => "{$column} {$operator} ?",
        ];

        $this->bindings[] = $value;

        return $this;
    }

    public function orWhere(
        string $column,
        mixed $operator,
        mixed $value = null
    ): static {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtoupper((string) $operator);

        $allowed = [
            '=', '!=', '<>', '>', '>=', '<', '<=',
            'LIKE', 'NOT LIKE',
        ];

        if (!in_array($operator, $allowed, true)) {
            throw new InvalidArgumentException(
                "Unsupported where operator [{$operator}]."
            );
        }

        $column = $this->identifier($column);

        if ($value === null && $operator === '=') {
            $sql = "{$column} IS NULL";
        } elseif (
            $value === null &&
            in_array($operator, ['!=', '<>'], true)
        ) {
            $sql = "{$column} IS NOT NULL";
        } else {
            $sql = "{$column} {$operator} ?";

            $this->bindings[] = $value;
        }

        $this->wheres[] = [
            'boolean' => 'OR',
            'sql' => $sql,
        ];

        return $this;
    }

    public function whereIn(
        string $column,
        array $values
    ): static {
        $column = $this->identifier($column);

        if ($values === []) {
            $this->wheres[] = [
                'boolean' => 'AND',
                'sql' => '1 = 0',
            ];

            return $this;
        }

        $placeholders = implode(
            ', ',
            array_fill(0, count($values), '?')
        );

        $this->wheres[] = [
            'boolean' => 'AND',
            'sql' => "{$column} IN ({$placeholders})",
        ];

        array_push($this->bindings, ...$values);

        return $this;
    }

    public function orderBy(
        string $column,
        string $direction = 'ASC'
    ): static {
        $column = $this->identifier($column);
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException(
                'Order direction must be ASC or DESC.'
            );
        }

        $this->orders[] = "{$column} {$direction}";

        return $this;
    }

    public function latest(
        string $column = 'created_at'
    ): static {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(
        string $column = 'created_at'
    ): static {
        return $this->orderBy($column, 'ASC');
    }

    public function limit(int $limit): static
    {
        if ($limit < 0) {
            throw new InvalidArgumentException(
                'Limit cannot be negative.'
            );
        }

        $this->limitValue = $limit;

        return $this;
    }

    public function offset(int $offset): static
    {
        if ($offset < 0) {
            throw new InvalidArgumentException(
                'Offset cannot be negative.'
            );
        }

        $this->offsetValue = $offset;

        return $this;
    }

    public function get(): array
    {
        $sql = 'SELECT '
            . implode(', ', $this->columns)
            . ' FROM `' . $this->table . '`'
            . $this->compileWheres();

        if ($this->orders !== []) {
            $sql .= ' ORDER BY '
                . implode(', ', $this->orders);
        }

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            if ($this->limitValue === null) {
                $sql .= ' LIMIT 18446744073709551615';
            }

            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        $rows = $this->connection->select(
            $sql,
            $this->bindings
        );

        if ($this->modelClass === null) {
            return $rows;
        }

        $class = $this->modelClass;

        return array_map(
            static fn (array $row) => $class::hydrate($row),
            $rows
        );
    }

    public function first(): Model|array|null
    {
        $clone = clone $this;
        $clone->limit(1);

        $results = $clone->get();

        return $results[0] ?? null;
    }

    public function find(
        int|string $id,
        string $primaryKey = 'id'
    ): Model|array|null {
        return $this->where($primaryKey, $id)->first();
    }

    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();

        if ($result instanceof Model) {
            return $result->getAttribute($column);
        }

        return is_array($result)
            ? ($result[$column] ?? null)
            : null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) AS aggregate FROM `'
            . $this->table . '`'
            . $this->compileWheres();

        $row = $this->connection->selectOne(
            $sql,
            $this->bindings
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function update(array $values): int
    {
        if ($values === []) {
            return 0;
        }

        $sets = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $column = $this->identifier((string) $column);

            $sets[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $bindings = array_merge(
            $bindings,
            $this->bindings
        );

        $sql = 'UPDATE `' . $this->table . '` SET '
            . implode(', ', $sets)
            . $this->compileWheres();

        return $this->connection->execute(
            $sql,
            $bindings
        );
    }

    public function delete(): int
    {
        $sql = 'DELETE FROM `' . $this->table . '`'
            . $this->compileWheres();

        return $this->connection->execute(
            $sql,
            $this->bindings
        );
    }

    protected function compileWheres(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $sql = ' WHERE ';

        foreach ($this->wheres as $index => $where) {
            if ($index > 0) {
                $sql .= ' ' . $where['boolean'] . ' ';
            }

            $sql .= $where['sql'];
        }

        return $sql;
    }

    protected function identifier(string $identifier): string
    {
        if (!preg_match(
            '/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            $identifier
        )) {
            throw new InvalidArgumentException(
                "Invalid SQL identifier [{$identifier}]."
            );
        }

        return '`' . $identifier . '`';
    }
}