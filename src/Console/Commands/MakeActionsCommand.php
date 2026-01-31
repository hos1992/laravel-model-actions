<?php

namespace HosnyAdeeb\ModelActions\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeActionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:actions 
                            {model : The model name (e.g., User, Post)}
                            {--force : Overwrite existing actions}
                            {--actions= : Comma-separated list of specific actions to generate (index,show,store,update,delete)}
                            {--model-path= : Custom model namespace path (default: App\\Models)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate action classes for a model';

    /**
     * Available action types.
     */
    protected array $actionTypes = [
        'Index',
        'Show',
        'Store',
        'Update',
        'Delete',
        'BulkDelete',
        'BulkUpdate',
    ];

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = Str::studly($this->argument('model'));
        $modelPath = $this->option('model-path') ?? config('model-actions.model_namespace', 'App\\Models');
        $actionsPath = config('model-actions.actions_path', app_path('Actions'));

        // Determine which actions to generate
        $actionsToGenerate = $this->getActionsToGenerate();

        // Check if model exists
        $fullModelClass = $modelPath . '\\' . $modelName;
        if (!class_exists($fullModelClass)) {
            $this->warn("⚠️  Model [{$fullModelClass}] does not exist. Actions will still be created.");
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Action generation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Check if actions directory already exists
        $modelActionsPath = $actionsPath . DIRECTORY_SEPARATOR . $modelName;

        if ($this->files->isDirectory($modelActionsPath) && !$this->option('force')) {
            $existingFiles = $this->files->files($modelActionsPath);

            if (count($existingFiles) > 0) {
                $this->warn("⚠️  Actions already exist for model [{$modelName}]:");
                foreach ($existingFiles as $file) {
                    $this->line("   - " . $file->getFilename());
                }

                if (!$this->confirm('Do you want to overwrite existing actions?', false)) {
                    $this->info('Action generation cancelled.');
                    return Command::SUCCESS;
                }
            }
        }

        // Ensure base actions are published
        $this->ensureBaseActionsExist($actionsPath);

        // Create the model actions directory
        if (!$this->files->isDirectory($modelActionsPath)) {
            $this->files->makeDirectory($modelActionsPath, 0755, true);
        }

        // Generate each action type
        $generated = [];
        foreach ($actionsToGenerate as $actionType) {
            $actionType = Str::studly($actionType);

            if (!in_array($actionType, $this->actionTypes)) {
                $this->error("Unknown action type: {$actionType}");
                continue;
            }

            $this->generateAction($modelName, $actionType, $modelActionsPath, $modelPath);
            $generated[] = "{$modelName}{$actionType}Action";
        }

        // Success message
        $this->newLine();
        $this->info("✅ Successfully generated actions for [{$modelName}] model:");
        foreach ($generated as $action) {
            $this->line("   ✓ {$action}");
        }

        $this->newLine();
        $this->info("📁 Actions created at: " . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $modelActionsPath));
        $this->newLine();
        $this->info("💡 Usage examples:");
        $this->line("   // Static method:");
        $this->line("   {$modelName}IndexAction::run(perPage: 10);");
        $this->newLine();
        $this->line("   // Helper function:");
        $this->line("   run(new {$modelName}IndexAction(perPage: 10));");
        $this->newLine();
        $this->line("   // Instance method:");
        $this->line("   (new {$modelName}IndexAction(perPage: 10))->execute();");

        return Command::SUCCESS;
    }

    /**
     * Get the list of actions to generate.
     */
    protected function getActionsToGenerate(): array
    {
        if ($this->option('actions')) {
            return array_map('trim', explode(',', $this->option('actions')));
        }

        return $this->actionTypes;
    }

    /**
     * Ensure base action classes exist in the project.
     */
    protected function ensureBaseActionsExist(string $actionsPath): void
    {
        $baseActionsPath = $actionsPath . DIRECTORY_SEPARATOR . '_Base';
        $actionFilePath = $actionsPath . DIRECTORY_SEPARATOR . 'Action.php';

        // Create base Action class if it doesn't exist
        if (!$this->files->exists($actionFilePath)) {
            $this->files->ensureDirectoryExists($actionsPath);
            $this->files->put($actionFilePath, $this->getBaseActionStub());
            $this->info("Created base Action class.");
        }

        // Create _Base directory and base action types
        if (!$this->files->isDirectory($baseActionsPath)) {
            $this->files->makeDirectory($baseActionsPath, 0755, true);

            foreach ($this->actionTypes as $type) {
                $content = $this->getBaseActionTypeStub($type);

                if (empty($content)) {
                    continue;
                }

                $targetPath = $baseActionsPath . DIRECTORY_SEPARATOR . "{$type}Action.php";

                $this->files->put($targetPath, $content);
            }

            $this->info("Created base action classes in _Base directory.");
        }
    }

    /**
     * Generate a specific action for the model.
     */
    protected function generateAction(
        string $modelName,
        string $actionType,
        string $targetPath,
        string $modelPath
    ): void {
        $actionClass = "{$modelName}{$actionType}Action";
        $filePath = $targetPath . DIRECTORY_SEPARATOR . "{$actionClass}.php";

        $stub = $this->getStub($actionType);

        $content = str_replace(
            [
                '{{ namespace }}',
                '{{ model }}',
                '{{ modelPath }}',
                '{{ modelVariable }}',
                '{{ actionClass }}',
            ],
            [
                config('model-actions.actions_namespace', 'App\\Actions') . '\\' . $modelName,
                $modelName,
                $modelPath,
                Str::camel($modelName),
                $actionClass,
            ],
            $stub
        );

        $this->files->put($filePath, $content);
    }

    /**
     * Get the stub for a specific action type.
     */
    protected function getStub(string $type): string
    {
        return $this->getActionStub($type);
    }

    /**
     * Resolve the potentially published stub path.
     */
    protected function resolveStubPath(string $stub): string
    {
        $customPath = base_path("stubs/model-actions/{$stub}");

        return file_exists($customPath)
            ? $customPath
            : __DIR__ . "/../../../stubs/{$stub}";
    }

    /**
     * Get base action stub content.
     */
    protected function getBaseActionStub(): string
    {
        $stubPath = $this->resolveStubPath('base/Action.stub');

        if (!$this->files->exists($stubPath)) {
            return '';
        }

        $content = $this->files->get($stubPath);

        return str_replace(
            '{{ namespace }}',
            config('model-actions.actions_namespace', 'App\\Actions'),
            $content
        );
    }

    /**
     * Get base action type stub content from stubs directory.
     */
    protected function getBaseActionTypeStub(string $type): string
    {
        $stubPath = $this->resolveStubPath("base/{$type}Action.stub");

        if (!$this->files->exists($stubPath)) {
            return '';
        }

        $content = $this->files->get($stubPath);

        return str_replace(
            '{{ namespace }}',
            config('model-actions.actions_namespace', 'App\\Actions') . '\\_Base',
            $content
        );
    }

    /**
     * Get action stub content from stubs directory.
     */
    protected function getActionStub(string $type): string
    {
        $stubPath = $this->resolveStubPath("{$type}.stub");

        if (!$this->files->exists($stubPath)) {
            return '';
        }

        return $this->files->get($stubPath);
    }

    /**
     * Get Index action stub.
     */
    protected function getIndexStub(): string
    {
        return $this->getActionStub('Index');
    }

    /**
     * Get Show action stub.
     */
    protected function getShowStub(): string
    {
        return $this->getActionStub('Show');
    }

    /**
     * Get Store action stub.
     */
    protected function getStoreStub(): string
    {
        return $this->getActionStub('Store');
    }

    /**
     * Get Update action stub.
     */
    protected function getUpdateStub(): string
    {
        return $this->getActionStub('Update');
    }

    /**
     * Get Delete action stub.
     */
    protected function getDeleteStub(): string
    {
        return $this->getActionStub('Delete');
    }

    /**
     * Get BulkDelete action stub.
     */
    protected function getBulkDeleteStub(): string
    {
        return $this->getActionStub('BulkDelete');
    }

    /**
     * Get BulkUpdate action stub.
     */
    protected function getBulkUpdateStub(): string
    {
        return $this->getActionStub('BulkUpdate');
    }
}
