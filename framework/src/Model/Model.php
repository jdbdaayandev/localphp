<?php

declare(strict_types=1);

namespace LocalPHP\Model;

use LocalPHP\Database\DatabaseManager;
use LocalPHP\Database\QueryBuilder;
use RuntimeException;

abstract class Model
{
    protected ?string $table = null;

    protected string $primaryKey = 'id';

    protected array $fillable = [];

    protected array $guarded = ['id'];

    protected bool $timestamps = true;

    protected array $attributes = [];

    protected array $original = [];

    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /*
    |--------------------------------------------------------------------------
    | Query API
    |--------------------------------------------------------------------------
    */

    public static function query(): QueryBuilder
    {
        $model = new static();

        return QueryBuilder::forModel(
            $model->connection()->connection(),
            $model->getTable(),
            static::class
        );
    }


    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(
        int|string $id
    ): ?static {
        $result = static::query()
            ->where(
                (new static())->getKeyName(),
                $id
            )
            ->first();

        return $result instanceof static
            ? $result
            : null;
    }

    public static function where(
        string $column,
        mixed $operator,
        mixed $value = null
    ): QueryBuilder {
        $query = static::query();

        if (func_num_args() === 2) {
            return $query->where($column, $operator);
        }

        return $query->where(
            $column,
            $operator,
            $value
        );
    }

    public static function orderBy(
        string $column,
        string $direction = 'ASC'
    ): QueryBuilder {
        return static::query()->orderBy(
            $column,
            $direction
        );
    }

    public static function create(array $attributes): static
    {
        $model = new static();

        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public static function firstOrCreate(
        array $conditions,
        array $values = []
    ): static {
        $query = static::query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        $existing = $query->first();

        if ($existing instanceof static) {
            return $existing;
        }

        return static::create(
            array_merge($conditions, $values)
        );
    }

    public static function count(): int
    {
        return static::query()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable((string) $key)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    protected function isFillable(string $key): bool
    {
        if (in_array($key, $this->guarded, true)) {
            return false;
        }

        if ($this->fillable === []) {
            return false;
        }

        return in_array($key, $this->fillable, true);
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(
        string $key,
        mixed $value
    ): static {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(
        string $key,
        mixed $value
    ): void {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /*
    |--------------------------------------------------------------------------
    | Persistence
    |--------------------------------------------------------------------------
    */

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');

        if ($this->timestamps) {
            if (!$this->exists) {
                $this->attributes['created_at'] ??= $now;
            }

            $this->attributes['updated_at'] = $now;
        }

        if ($this->exists) {
            $dirty = [];

            foreach ($this->attributes as $key => $value) {
                if (
                    !array_key_exists($key, $this->original) ||
                    $this->original[$key] !== $value
                ) {
                    $dirty[$key] = $value;
                }
            }

            unset($dirty[$this->primaryKey]);

            if ($dirty === []) {
                return true;
            }

            $this->connection()
                ->table($this->getTable())
                ->where(
                    $this->primaryKey,
                    $this->attributes[$this->primaryKey]
                )
                ->update($dirty);

            $this->original = $this->attributes;

            return true;
        }

        $attributes = $this->attributes;

        if ($attributes === []) {
            throw new RuntimeException(
                'Cannot insert a model without attributes.'
            );
        }

        $columns = array_keys($attributes);
        $quotedColumns = array_map(
            fn ($column) => $this->quoteIdentifier($column),
            $columns
        );

        $placeholders = array_fill(
            0,
            count($columns),
            '?'
        );

        $sql = 'INSERT INTO '
            . $this->quoteIdentifier($this->getTable())
            . ' (' . implode(', ', $quotedColumns) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')';

        $this->connection()->connection()->insert(
            $sql,
            array_values($attributes)
        );

        if (!isset($this->attributes[$this->primaryKey])) {
            $id = $this->connection()
                ->connection()
                ->lastInsertId();

            if ($id !== false && $id !== '0') {
                $this->attributes[$this->primaryKey] =
                    ctype_digit($id)
                        ? (int) $id
                        : $id;
            }
        }

        $this->exists = true;
        $this->original = $this->attributes;

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $id = $this->attributes[$this->primaryKey] ?? null;

        if ($id === null) {
            return false;
        }

        $this->connection()
            ->table($this->getTable())
            ->where($this->primaryKey, $id)
            ->delete();

        $this->exists = false;

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Hydration
    |--------------------------------------------------------------------------
    */

    public static function hydrate(array $attributes): static
    {
        $model = new static();

        $model->attributes = $attributes;
        $model->original = $attributes;
        $model->exists = true;

        return $model;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    /*
    |--------------------------------------------------------------------------
    | Model Metadata
    |--------------------------------------------------------------------------
    */

    public function getTable(): string
    {
        if ($this->table !== null) {
            return $this->table;
        }

        $class = (new \ReflectionClass($this))->getShortName();

        $snake = strtolower(
            preg_replace('/(?<!^)[A-Z]/', '_$0', $class)
        );

        return str_ends_with($snake, 's')
            ? $snake
            : $snake . 's';
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    protected function connection(): DatabaseManager
    {
        $manager = app(DatabaseManager::class);

        if (!$manager instanceof DatabaseManager) {
            throw new RuntimeException(
                'DatabaseManager is not registered.'
            );
        }

        return $manager;
    }

    protected function quoteIdentifier(string $identifier): string
    {
        if (!preg_match(
            '/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            $identifier
        )) {
            throw new RuntimeException(
                "Invalid SQL identifier [{$identifier}]."
            );
        }

        return '`' . $identifier . '`';
    }
}