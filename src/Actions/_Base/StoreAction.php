<?php

namespace HosnyAdeeb\ModelActions\Actions\_Base;

use HosnyAdeeb\ModelActions\Actions\Action;
use Exception;
use Illuminate\Database\Eloquent\Model;

abstract class StoreAction extends Action
{
    /**
     * Create a new StoreAction instance.
     *
     * @param Model|null $model The model instance to create
     * @param array $data The data to store
     */
    public function __construct(
        private ?Model $model,
        private array  $data,
    ) {}

    /**
     * Execute the store action.
     *
     * @return Model
     * @throws Exception
     */
    public function handle(): mixed
    {
        if (!$this->model instanceof Model) {
            throw new Exception('There is no model instance passed to the action!');
        }

        return $this->model->create($this->data);
    }

    /**
     * Get the data array.
     *
     * @return array
     */
    protected function getData(): array
    {
        return $this->data;
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
