<?php
/**
 * Base Model Class
 * Provides common database operations for models
 */

abstract class Model {
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    public function __construct(array $attributes = []) {
        $this->attributes = $attributes;
        $this->original = $attributes;
        $this->exists = !empty($attributes);
    }

    /**
     * Get table name
     */
    public function getTable(): string {
        if (!$this->table) {
            $this->table = strtolower(class_basename($this)) . 's';
        }
        return $this->table;
    }

    /**
     * Get primary key
     */
    public function getKey(): ?int {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Get attribute
     */
    public function getAttribute(string $key) {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attribute
     */
    public function setAttribute(string $key, $value): self {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Fill attributes
     */
    public function fill(array $attributes): self {
        foreach ($this->fillable as $key) {
            if (isset($attributes[$key])) {
                $this->attributes[$key] = $attributes[$key];
            }
        }
        return $this;
    }

    /**
     * Get all attributes
     */
    public function toArray(): array {
        return $this->attributes;
    }

    /**
     * Get attribute via magic method
     */
    public function __get(string $key) {
        return $this->getAttribute($key);
    }

    /**
     * Set attribute via magic method
     */
    public function __set(string $key, $value): void {
        $this->setAttribute($key, $value);
    }

    /**
     * Find by ID
     */
    public static function find(int $id): ?static {
        $instance = new static();
        $result = Database::fetchOne(
            "SELECT * FROM {$instance->getTable()} WHERE {$instance->primaryKey} = ?",
            [$id]
        );
        return $result ? new static($result) : null;
    }

    /**
     * Find or fail by ID
     */
    public static function findOrFail(int $id): static {
        $model = static::find($id);
        if (!$model) {
            throw new Exception('Record not found');
        }
        return $model;
    }

    /**
     * Find by column
     */
    public static function where(string $column, $operator, $value = null): QueryBuilder {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $instance = new static();
        return new QueryBuilder($instance->getTable(), $instance);
    }

    /**
     * Get all records
     */
    public static function all(): array {
        $instance = new static();
        $results = Database::fetchAll("SELECT * FROM {$instance->getTable()}");
        return array_map(fn($row) => new static($row), $results);
    }

    /**
     * Create new record
     */
    public function save(): bool {
        try {
            if ($this->exists) {
                $changes = array_diff_assoc($this->attributes, $this->original);
                if (empty($changes)) {
                    return true; // No changes
                }
                Database::update($this->getTable(), $changes, [$this->primaryKey => $this->getKey()]);
            } else {
                $id = Database::insert($this->getTable(), $this->attributes);
                $this->attributes[$this->primaryKey] = $id;
                $this->exists = true;
            }
            $this->original = $this->attributes;
            return true;
        } catch (Exception $e) {
            Logger::error('Model save failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete record
     */
    public function delete(): bool {
        try {
            if (!$this->exists) {
                return false;
            }
            Database::delete($this->getTable(), [$this->primaryKey => $this->getKey()]);
            $this->exists = false;
            return true;
        } catch (Exception $e) {
            Logger::error('Model delete failed: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Query Builder
 */
class QueryBuilder {
    private string $table;
    private Model $model;
    private array $wheres = [];
    private array $orders = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
    private array $params = [];

    public function __construct(string $table, Model $model) {
        $this->table = $table;
        $this->model = $model;
    }

    /**
     * Add where clause
     */
    public function where(string $column, $operator, $value = null): self {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        $this->params[] = $value;
        return $this;
    }

    /**
     * Order by
     */
    public function orderBy(string $column, string $direction = 'ASC'): self {
        $this->orders[] = "$column $direction";
        return $this;
    }

    /**
     * Limit results
     */
    public function limit(int $limit): self {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Offset results
     */
    public function offset(int $offset): self {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Build SQL query
     */
    private function buildQuery(): array {
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($this->wheres)) {
            $conditions = array_map(fn($w) => "{$w['column']} {$w['operator']} ?", $this->wheres);
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limitValue) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return ['sql' => $sql, 'params' => $this->params];
    }

    /**
     * Get first result
     */
    public function first(): ?Model {
        $query = $this->buildQuery();
        $result = Database::fetchOne($query['sql'], $query['params']);
        return $result ? new $this->model(...[$result]) : null;
    }

    /**
     * Get all results
     */
    public function get(): array {
        $query = $this->buildQuery();
        $results = Database::fetchAll($query['sql'], $query['params']);
        return array_map(fn($row) => new $this->model(...[$row]), $results);
    }

    /**
     * Count results
     */
    public function count(): int {
        $query = $this->buildQuery();
        $sql = str_replace('SELECT *', 'SELECT COUNT(*) as count', $query['sql']);
        $result = Database::fetchOne($sql, $query['params']);
        return $result['count'] ?? 0;
    }
}

function class_basename(string $class): string {
    return basename(str_replace('\\', '/', $class));
}
