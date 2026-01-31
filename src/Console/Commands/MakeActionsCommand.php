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
                $stubPath = $this->getBaseActionTypeStubPath($type);
                $content = $this->getBaseActionTypeStub($type);
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
        // Check for published/custom stubs first
        $customStubPath = base_path("stubs/model-actions/{$type}.stub");

        if ($this->files->exists($customStubPath)) {
            return $this->files->get($customStubPath);
        }

        // Fall back to package stubs
        $packageStubPath = __DIR__ . "/../../../stubs/{$type}.stub";

        if ($this->files->exists($packageStubPath)) {
            return $this->files->get($packageStubPath);
        }

        // Generate inline if stub doesn't exist
        return $this->getInlineStub($type);
    }

    /**
     * Get inline stub content for action types.
     */
    protected function getInlineStub(string $type): string
    {
        $stubs = [
            'Index' => $this->getIndexStub(),
            'Show' => $this->getShowStub(),
            'Store' => $this->getStoreStub(),
            'Update' => $this->getUpdateStub(),
            'Delete' => $this->getDeleteStub(),
            'BulkDelete' => $this->getBulkDeleteStub(),
            'BulkUpdate' => $this->getBulkUpdateStub(),
        ];

        return $stubs[$type] ?? '';
    }

    /**
     * Get base action stub content.
     */
    protected function getBaseActionStub(): string
    {
        $namespace = config('model-actions.actions_namespace', 'App\\Actions');

        return <<<PHP
<?php

namespace {$namespace};

use HosnyAdeeb\ModelActions\Traits\Runnable;

abstract class Action
{
    use Runnable;

    /**
     * Execute the action.
     *
     * @return mixed
     */
    abstract public function __invoke(): mixed;
}
PHP;
    }

    /**
     * Get base action type stub path.
     */
    protected function getBaseActionTypeStubPath(string $type): string
    {
        return __DIR__ . "/../../../stubs/base/{$type}Action.stub";
    }

    /**
     * Get base action type stub content.
     */
    protected function getBaseActionTypeStub(string $type): string
    {
        $stubPath = $this->getBaseActionTypeStubPath($type);

        if ($this->files->exists($stubPath)) {
            $content = $this->files->get($stubPath);
            return str_replace(
                '{{ namespace }}',
                config('model-actions.actions_namespace', 'App\\Actions') . '\\_Base',
                $content
            );
        }

        // Use package base actions as fallback
        $packagePath = __DIR__ . "/../../Actions/_Base/{$type}Action.php";
        if ($this->files->exists($packagePath)) {
            $content = $this->files->get($packagePath);
            // Replace package namespace with app namespace
            return str_replace(
                'HosnyAdeeb\\ModelActions\\Actions\\_Base',
                config('model-actions.actions_namespace', 'App\\Actions') . '\\_Base',
                str_replace(
                    'HosnyAdeeb\\ModelActions\\Actions\\Action',
                    config('model-actions.actions_namespace', 'App\\Actions') . '\\Action',
                    $content
                )
            );
        }

        return '';
    }

    /**
     * Get Index action stub.
     */
    protected function getIndexStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use App\Actions\_Base\IndexAction;
use {{ modelPath }}\{{ model }};

final class {{ actionClass }} extends IndexAction
{
    /**
     * Create a new {{ actionClass }} instance.
     *
     * @param int|null $perPage Number of items per page
     * @param bool $getAll Whether to get all records
     * @param string|null $orderKey Column to order by
     * @param string|null $orderDir Order direction (ASC/DESC)
     * @param array $select Columns to select
     * @param array $with Relations to eager load
     * @param array $withOut Relations to exclude
     * @param array $where Where conditions
     * @param array $request Additional request data for custom queries
     */
    public function __construct(
        private ?int    $perPage = null,
        private bool    $getAll = false,
        private ?string $orderKey = null,
        private ?string $orderDir = null,
        private array   $select = [],
        private array   $with = [],
        private array   $withOut = [],
        private array   $where = [],
        private array   $request = [],
    ) {
        parent::__construct(
            model: new {{ model }}(),
            perPage: $this->perPage,
            getAll: $this->getAll,
            orderKey: $this->orderKey,
            orderDir: $this->orderDir,
            select: $this->select,
            with: $this->with,
            withOut: $this->withOut,
            where: $this->where,
        );
    }

    /**
     * Get the request data.
     *
     * @return array
     */
    protected function getRequest(): array
    {
        return $this->request;
    }
}
STUB;
    }

    /**
     * Get Show action stub.
     */
    protected function getShowStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use App\Actions\_Base\ShowAction;
use {{ modelPath }}\{{ model }};

final class {{ actionClass }} extends ShowAction
{
    /**
     * Create a new {{ actionClass }} instance.
     *
     * @param string $selectKey Column to select by
     * @param string|null $selectValue Value to match
     * @param string $selectOperator Comparison operator
     * @param array $select Columns to select
     * @param array $with Relations to eager load
     * @param array $withOut Relations to exclude
     * @param string $orderKey Column to order by
     * @param string $orderDir Order direction
     */
    public function __construct(
        private string  $selectKey = 'id',
        private ?string $selectValue = null,
        private string  $selectOperator = '=',
        private array   $select = [],
        private array   $with = [],
        private array   $withOut = [],
        private string  $orderKey = 'id',
        private string  $orderDir = 'DESC',
    ) {
        parent::__construct(
            model: new {{ model }}(),
            selectKey: $this->selectKey,
            selectValue: $this->selectValue,
            selectOperator: $this->selectOperator,
            select: $this->select,
            with: $this->with,
            withOut: $this->withOut,
            orderKey: $this->orderKey,
            orderDir: $this->orderDir,
        );
    }
}
STUB;
    }

    /**
     * Get Store action stub.
     */
    protected function getStoreStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use App\Actions\_Base\StoreAction;
use {{ modelPath }}\{{ model }};

final class {{ actionClass }} extends StoreAction
{
    /**
     * Create a new {{ actionClass }} instance.
     *
     * @param array $data The data to store
     */
    public function __construct(
        private array $data
    ) {
        parent::__construct(
            model: new {{ model }}(),
            data: $this->data,
        );
    }
}
STUB;
    }

    /**
     * Get Update action stub.
     */
    protected function getUpdateStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use App\Actions\_Base\UpdateAction;
use {{ modelPath }}\{{ model }};

final class {{ actionClass }} extends UpdateAction
{
    /**
     * Create a new {{ actionClass }} instance.
     *
     * @param array $data The data to update
     * @param string $selectKey Column to select by
     * @param string|null $selectValue Value to match
     * @param string $selectOperator Comparison operator
     */
    public function __construct(
        private array   $data,
        private string  $selectKey = 'id',
        private ?string $selectValue = null,
        private string  $selectOperator = '=',
    ) {
        parent::__construct(
            model: new {{ model }}(),
            data: $this->data,
            selectKey: $this->selectKey,
            selectValue: $this->selectValue,
            selectOperator: $this->selectOperator,
        );
    }
}
STUB;
    }

    /**
     * Get Delete action stub.
     */
    protected function getDeleteStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use App\Actions\_Base\DeleteAction;
use {{ modelPath }}\{{ model }};

final class {{ actionClass }} extends DeleteAction
{
    /**
     * Create a new {{ actionClass }} instance.
     *
     * @param string $selectKey Column to select by
     * @param string|null $selectValue Value to match
     * @param string $selectOperator Comparison operator
     */
    public function __construct(
        private string  $selectKey = 'id',
        private ?string $selectValue = null,
        private string  $selectOperator = '=',
    ) {
        parent::__construct(
            model: new {{ model }}(),
            selectKey: $this->selectKey,
            selectValue: $this->selectValue,
            selectOperator: $this->selectOperator,
        );
    }
}
STUB;
    }

    /**
     * Get BulkDelete action stub.
     */
    protected function getBulkDeleteStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use HosnyAdeeb\ModelActions\Actions\_Base\BulkDeleteAction as BaseBulkDeleteAction;
use {{ modelPath }}\{{ model }};

final class {{ actionClass }} extends BaseBulkDeleteAction
{
    /**
     * Get the model class.
     */
    protected function model(): string
    {
        return {{ model }}::class;
    }
}
STUB;
    }

    /**
     * Get BulkUpdate action stub.
     */
    protected function getBulkUpdateStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use HosnyAdeeb\ModelActions\Actions\_Base\BulkUpdateAction as BaseBulkUpdateAction;
use {{ modelPath }}\{{ model }};

final class {{ actionClass }} extends BaseBulkUpdateAction
{
    /**
     * Get the model class.
     */
    protected function model(): string
    {
        return {{ model }}::class;
    }

    /**
     * Prepare the data before bulk update.
     */
    protected function prepareData(array $data): array
    {
        // Add any data transformations here
        return $data;
    }
}
STUB;
    }
}
