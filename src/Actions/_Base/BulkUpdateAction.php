<?php

namespace HosnyAdeeb\ModelActions\Actions\_Base;

use HosnyAdeeb\ModelActions\Actions\Action;
use Exception;
use Illuminate\Database\Eloquent\Model;

abstract class BulkUpdateAction extends Action
{
    /**
     * The IDs of records to update.
     */
    protected array $ids = [];

    /**
     * The data to update.
     */
    protected array $data = [];

    /**
     * Create a new BulkUpdateAction instance.
     *
     * @param array $ids The IDs of records to update
     * @param array $data The data to update
     */
    public function __construct(array $ids, array $data)
    {
        $this->ids = $ids;
        $this->data = $data;
    }

    /**
     * Get the model class to update.
     * 
     * @return string The fully qualified model class name
     */
    abstract protected function model(): string;

    /**
     * Execute the bulk update action.
     *
     * @return int Number of records updated
     * @throws Exception
     */
    public function handle(): int
    {
        $modelClass = $this->model();

        if (!class_exists($modelClass)) {
            throw new Exception("Model class {$modelClass} does not exist!");
        }

        if (empty($this->ids) || empty($this->data)) {
            return 0;
        }

        /** @var Model $instance */
        $instance = new $modelClass();

        return $instance->newQuery()
            ->whereIn($instance->getKeyName(), $this->ids)
            ->update($this->prepareData($this->data));
    }

    /**
     * Prepare the data before updating.
     * Override this method to transform data before update.
     *
     * @param array $data
     * @return array
     */
    protected function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * Get the IDs being updated.
     *
     * @return array
     */
    protected function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Get the update data.
     *
     * @return array
     */
    protected function getData(): array
    {
        return $this->data;
    }
}
