<?php

namespace HosnyAdeeb\ModelActions\Traits;

trait Runnable
{
    /**
     * Run the action statically.
     * 
     * Allows calling: UserIndexAction::run(...$params)
     * 
     * @param mixed ...$arguments
     * @return mixed
     */
    public static function run(mixed ...$arguments): mixed
    {
        $instance = new static(...$arguments);

        return $instance();
    }

    /**
     * Execute the action instance.
     * 
     * Allows calling: $action->execute()
     * 
     * @return mixed
     */
    public function execute(): mixed
    {
        return $this();
    }
}
