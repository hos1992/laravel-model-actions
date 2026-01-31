<?php

namespace HosnyAdeeb\ModelActions\Tests\Feature;

use HosnyAdeeb\ModelActions\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeActionsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing test actions
        $this->cleanUpTestActions();
    }

    protected function tearDown(): void
    {
        // Clean up test actions after each test
        $this->cleanUpTestActions();

        parent::tearDown();
    }

    protected function cleanUpTestActions(): void
    {
        $actionsPath = app_path('Actions/TestModel');
        if (File::isDirectory($actionsPath)) {
            File::deleteDirectory($actionsPath);
        }
    }

    /** @test */
    public function it_can_generate_actions_for_a_model(): void
    {
        $this->artisan('make:actions', ['model' => 'TestModel'])
            ->expectsQuestion('Do you want to continue?', true)
            ->assertSuccessful();

        $this->assertFileExists(app_path('Actions/TestModel/TestModelIndexAction.php'));
        $this->assertFileExists(app_path('Actions/TestModel/TestModelShowAction.php'));
        $this->assertFileExists(app_path('Actions/TestModel/TestModelStoreAction.php'));
        $this->assertFileExists(app_path('Actions/TestModel/TestModelUpdateAction.php'));
        $this->assertFileExists(app_path('Actions/TestModel/TestModelDeleteAction.php'));
    }

    /** @test */
    public function it_can_generate_specific_actions_only(): void
    {
        $this->artisan('make:actions', [
            'model' => 'TestModel',
            '--actions' => 'index,store',
        ])
            ->expectsQuestion('Do you want to continue?', true)
            ->assertSuccessful();

        $this->assertFileExists(app_path('Actions/TestModel/TestModelIndexAction.php'));
        $this->assertFileExists(app_path('Actions/TestModel/TestModelStoreAction.php'));
        $this->assertFileDoesNotExist(app_path('Actions/TestModel/TestModelShowAction.php'));
        $this->assertFileDoesNotExist(app_path('Actions/TestModel/TestModelUpdateAction.php'));
        $this->assertFileDoesNotExist(app_path('Actions/TestModel/TestModelDeleteAction.php'));
    }

    /** @test */
    public function it_asks_for_confirmation_when_actions_already_exist(): void
    {
        // First generation
        $this->artisan('make:actions', ['model' => 'TestModel'])
            ->expectsQuestion('Do you want to continue?', true)
            ->assertSuccessful();

        // Second generation should ask for confirmation
        $this->artisan('make:actions', ['model' => 'TestModel'])
            ->expectsConfirmation('Do you want to overwrite existing actions?', 'no')
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_force_overwrite_existing_actions(): void
    {
        // First generation
        $this->artisan('make:actions', ['model' => 'TestModel'])
            ->expectsQuestion('Do you want to continue?', true)
            ->assertSuccessful();

        // Second generation with --force should not ask
        $this->artisan('make:actions', ['model' => 'TestModel', '--force' => true])
            ->assertSuccessful();
    }

    /** @test */
    public function generated_actions_have_correct_namespace(): void
    {
        $this->artisan('make:actions', ['model' => 'TestModel'])
            ->expectsQuestion('Do you want to continue?', true)
            ->assertSuccessful();

        $content = File::get(app_path('Actions/TestModel/TestModelIndexAction.php'));

        $this->assertStringContainsString('namespace App\Actions\TestModel;', $content);
        $this->assertStringContainsString('class TestModelIndexAction', $content);
    }
}
