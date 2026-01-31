<?php

namespace HosnyAdeeb\ModelActions\Actions\_Base;

use HosnyAdeeb\ModelActions\Actions\Action;
use Exception;
use Illuminate\Database\Eloquent\Model;

abstract class BulkDeleteAction extends Action
{
    /**
     * The IDs of records to delete.
     */
    protected array $ids = [];

    /**
     * Whether to use soft deletes (if available).
     */
    protected bool $forceDelete = false;

    /**
     * Create a new BulkDeleteAction instance.
     *
     * @param array $ids The IDs of records to delete
     * @param bool $forceDelete Whether to force delete (permanently)
     */
    public function __construct(array $ids, bool $forceDelete = false)
    {
        $this->ids = $ids;
        $this->forceDelete = $forceDelete;
    }

    /**
     * Get the model class to delete from.
     * 
     * @return string The fully qualified model class name
     */
    abstract protected function model(): string;

    /**
     * Execute the bulk delete action.
     *
     * @return int Number of records deleted
     * @throws Exception
     */
    public function handle(): int
    {
        $modelClass = $this->model();

        if (!class_exists($modelClass)) {
            throw new Exception("Model class {$modelClass} does not exist!");
        }

        if (empty($this->ids)) {
            return 0;
        }

        /** @var Model $instance */
        $instance = new $modelClass();

        $query = $instance->newQuery()->whereIn($instance->getKeyName(), $this->ids);

        if ($this->forceDelete && method_exists($instance, 'forceDelete')) {
            return $query->forceDelete();
        }

        return $query->delete();
    }

    /**
     * Get the IDs being deleted.
     *
     * @return array
     */
    protected function getIds(): array
    {
        return $this->ids;
    }
}
