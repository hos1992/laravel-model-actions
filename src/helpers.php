<?php

use HosnyAdeeb\ModelActions\Actions\Action;

if (!function_exists('run')) {
    /**
     * Run an action instance.
     *
     * This helper function provides a clean syntax for executing actions:
     * run(new UserIndexAction(perPage: 10))
     *
     * @param Action $action The action instance to execute
     * @return mixed The result of the action
     */
    function run(Action $action): mixed
    {
        return $action();
    }
}
