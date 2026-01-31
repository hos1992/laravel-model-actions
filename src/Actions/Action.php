<?php

namespace HosnyAdeeb\ModelActions\Actions;

use HosnyAdeeb\ModelActions\Traits\HasHooks;
use HosnyAdeeb\ModelActions\Traits\Runnable;

abstract class Action
{
    use Runnable, HasHooks;

    /**
     * Execute the action with lifecycle hooks.
     * 
     * Calls before() hook, then handle(), then after() hook.
     * 
     * @return mixed
     */
    public function __invoke(): mixed
    {
        try {
            $this->before();

            $result = $this->handle();

            return $this->after($result);
        } catch (\Throwable $e) {
            $this->onError($e);
            throw $e;
        }
    }

    /**
     * The main action logic.
     * 
     * This method should be implemented by child classes or base action types.
     * 
     * @return mixed
     */
    abstract public function handle(): mixed;
}
