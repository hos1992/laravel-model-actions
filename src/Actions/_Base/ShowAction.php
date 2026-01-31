<?php

namespace HosnyAdeeb\ModelActions\Actions\_Base;

use HosnyAdeeb\ModelActions\Actions\Action;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class ShowAction extends Action
{
    /**
     * Create a new ShowAction instance.
     *
     * @param Model|null $model The model instance to query
     * @param string $selectKey The column to select by
     * @param string|null $selectValue The value to match
     * @param string $selectOperator The comparison operator
     * @param array $select Columns to select
     * @param array $with Relations to eager load
     * @param array $withOut Relations to exclude
     * @param string $orderKey Column to order by
     * @param string $orderDir Order direction
     * @param bool $failIfNotFound Whether to throw exception if not found
     */
    public function __construct(
        private ?Model  $model,
        private string  $selectKey = 'id',
        private ?string $selectValue = null,
        private string  $selectOperator = '=',
        private array   $select = [],
        private array   $with = [],
        private array   $withOut = [],
        private string  $orderKey = 'id',
        private string  $orderDir = 'DESC',
        private bool    $failIfNotFound = false,
    ) {}

    /**
     * Execute the show action.
     *
     * @return Model|Builder|null
     * @throws Exception
     * @throws ModelNotFoundException
     */
    public function handle(): Model|Builder|null
    {
        if (!$this->model instanceof Model) {
            throw new Exception('There is no model instance passed to the action!');
        }

        $this->beforeHandle();

        $query = $this->model->query();
        $query->where($this->selectKey, $this->selectOperator, $this->selectValue);

        if (count($this->select)) {
            $query->select($this->select);
        }

        if (count($this->with)) {
            $query->with($this->with);
        }

        if (count($this->withOut)) {
            $query->withOut($this->withOut);
        }

        $this->customBuilder($query);

        $query->orderBy($this->orderKey, $this->orderDir);

        $result = $this->failIfNotFound ? $query->firstOrFail() : $query->first();

        $this->dispatchEvent($result);

        return $this->afterHandle($result);
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
     * Called before the action executes.
     */
    protected function beforeHandle(): void
    {
        // Override in subclass for pre-show logic
    }

    /**
     * Called after the action executes.
     *
     * @param mixed $result
     * @return mixed
     */
    protected function afterHandle(mixed $result): mixed
    {
        // Override in subclass for post-show logic
        return $result;
    }

    /**
     * Dispatch event after retrieval.
     *
     * @param Model|null $model
     */
    protected function dispatchEvent(?Model $model): void
    {
        if ($this->shouldDispatchEvents() && $model) {
            event('model-actions.retrieved', [$model, $this]);
        }
    }

    /**
     * Determine if events should be dispatched.
     *
     * @return bool
     */
    protected function shouldDispatchEvents(): bool
    {
        return true;
    }

    /**
     * Get the model instance.
     *
     * @return Model|null
     */
    protected function getModel(): ?Model
    {
        return $this->model;
    }
}
