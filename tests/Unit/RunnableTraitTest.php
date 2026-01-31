<?php

namespace HosnyAdeeb\ModelActions\Tests\Unit;

use HosnyAdeeb\ModelActions\Tests\TestCase;
use HosnyAdeeb\ModelActions\Traits\Runnable;

class RunnableTraitTest extends TestCase
{
    /** @test */
    public function it_can_run_action_statically(): void
    {
        $result = TestAction::run('test-value');

        $this->assertEquals('executed: test-value', $result);
    }

    /** @test */
    public function it_can_run_action_via_execute_method(): void
    {
        $action = new TestAction('test-value');
        $result = $action->execute();

        $this->assertEquals('executed: test-value', $result);
    }

    /** @test */
    public function it_can_invoke_action_directly(): void
    {
        $action = new TestAction('test-value');
        $result = $action();

        $this->assertEquals('executed: test-value', $result);
    }
}

class TestAction
{
    use Runnable;

    public function __construct(
        private string $value
    ) {}

    public function __invoke(): string
    {
        return "executed: {$this->value}";
    }
}
