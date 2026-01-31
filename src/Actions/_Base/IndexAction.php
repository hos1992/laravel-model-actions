<?php

namespace HosnyAdeeb\ModelActions\Actions\_Base;

use HosnyAdeeb\ModelActions\Actions\Action;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class IndexAction extends Action
{
    private ?Model $model;
    private int $perPage;
    private bool $getAll = false;
    private string $orderKey = 'id';
    private string $orderDir = 'DESC';
    private array $select = [];
    private array $with = [];
    private array $withOut = [];
    private array $where = [];

    /**
     * Create a new IndexAction instance.
     *
     * @param Model|null $model The model instance to query
     * @param int|null $perPage Number of items per page
     * @param bool|null $getAll Whether to get all records without pagination
     * @param string|null $orderKey Column to order by
     * @param string|null $orderDir Order direction (ASC/DESC)
     * @param array|null $select Columns to select
     * @param array|null $with Relations to eager load
     * @param array|null $withOut Relations to exclude
     * @param array|null $where Where conditions
     */
    public function __construct(
        ?Model  $model,
        ?int    $perPage = null,
        ?bool   $getAll = null,
        ?string $orderKey = null,
        ?string $orderDir = null,
        ?array  $select = null,
        ?array  $with = null,
        ?array  $withOut = null,
        ?array  $where = null,
    ) {
        $this->model = $model;
        $this->perPage = $perPage ?? $this->getPaginationPerPageDefaultCount();

        if ($getAll !== null) {
            $this->getAll = $getAll;
        }
        if ($orderKey !== null) {
            $this->orderKey = $orderKey;
        }
        if ($orderDir !== null) {
            $this->orderDir = $orderDir;
        }
        if ($select !== null && count($select)) {
            $this->select = $select;
        }
        if ($with !== null && count($with)) {
            $this->with = $with;
        }
        if ($withOut !== null && count($withOut)) {
            $this->withOut = $withOut;
        }
        if ($where !== null && count($where)) {
            $this->where = $where;
        }
    }

    /**
     * Execute the index action.
     *
     * @return LengthAwarePaginator|Collection|array
     * @throws Exception
     */
    public function handle(): Collection|LengthAwarePaginator|array
    {
        if (!$this->model instanceof Model) {
            throw new Exception('There is no model instance passed to the action!');
        }

        $query = $this->model->query();

        if (count($this->select)) {
            $query->select($this->select);
        }

        if (count($this->where)) {
            $query->where($this->where);
        }

        if (count($this->with)) {
            $query->with($this->with);
        }

        if (count($this->withOut)) {
            $query->withOut($this->withOut);
        }

        $this->customBuilder($query);

        $query->orderBy($this->orderKey, $this->orderDir);

        return $this->getAll ? $query->get() : $query->paginate($this->perPage)->withQueryString();
    }

    /**
     * Custom query builder method for subclasses to override.
     *
     * @param Builder $builder
     * @return void
     */
    protected function customBuilder(Builder $builder): void
    {
        // Override in subclass for custom query logic
    }

    /**
     * Get the default pagination count from config or fallback.
     *
     * @return int
     */
    private function getPaginationPerPageDefaultCount(): int
    {
        return (int) config('model-actions.pagination_per_page', 20);
    }
}
