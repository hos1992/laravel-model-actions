<?php

namespace HosnyAdeeb\ModelActions\Traits;

trait HasHooks
{
    /**
     * Called before the action's handle method is executed.
     * Override this method to add custom logic before the action runs.
     *
     * @return void
     */
    protected function before(): void
    {
        // Override in child class to add before logic
    }

    /**
     * Called after the action's handle method is executed.
     * Override this method to add custom logic after the action runs.
     *
     * @param mixed $result The result from the handle method
     * @return mixed The potentially modified result
     */
    protected function after(mixed $result): mixed
    {
        return $result;
    }

    /**
     * Called when the action fails with an exception.
     * Override this method to add custom error handling.
     *
     * @param \Throwable $exception
     * @return void
     */
    protected function onError(\Throwable $exception): void
    {
        // Override in child class to add error handling
    }
}
