<?php

namespace HosnyAdeeb\ModelActions\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeActionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:action 
                            {name : The name of the action (e.g., UserActivateAction)}
                            {model? : The model name (optional, will be extracted from action name if not provided)}
                            {--force : Overwrite existing action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new custom action class for a model';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filesystem = new Filesystem();

        $name = $this->argument('name');
        $model = $this->argument('model') ?? $this->extractModelFromName($name);

        if (!$model) {
            $this->error('Could not determine model name. Please provide it as the second argument.');
            return Command::FAILURE;
        }

        $namespace = config('model-actions.actions_namespace', 'App\\Actions');
        $basePath = config('model-actions.actions_path', app_path('Actions'));

        // Create model directory
        $modelDir = $basePath . DIRECTORY_SEPARATOR . $model;
        if (!$filesystem->isDirectory($modelDir)) {
            $filesystem->makeDirectory($modelDir, 0755, true);
        }

        // Generate action file
        $actionPath = $modelDir . DIRECTORY_SEPARATOR . $name . '.php';

        if ($filesystem->exists($actionPath) && !$this->option('force')) {
            $this->warn("Action [{$name}] already exists!");
            if (!$this->confirm('Do you want to overwrite it?')) {
                $this->info('Action creation cancelled.');
                return Command::SUCCESS;
            }
        }

        $stub = $this->getStub($filesystem);
        $content = $this->replacePlaceholders($stub, $name, $model, $namespace);

        $filesystem->put($actionPath, $content);

        $this->info("Action [{$name}] created successfully at: {$actionPath}");

        return Command::SUCCESS;
    }

    /**
     * Extract model name from action name.
     */
    protected function extractModelFromName(string $name): ?string
    {
        // Remove 'Action' suffix if present
        $name = Str::replaceLast('Action', '', $name);

        // Try to extract model name (first capital word)
        if (preg_match('/^([A-Z][a-z]+)/', $name, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get the stub content.
     */
    protected function getStub(Filesystem $filesystem): string
    {
        $customStub = base_path('stubs/model-actions/CustomAction.stub');

        if ($filesystem->exists($customStub)) {
            return $filesystem->get($customStub);
        }

        return <<<'STUB'
<?php

namespace {{ namespace }}\{{ model }};

use App\Actions\Action;
use App\Models\{{ model }};

final class {{ class }} extends Action
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        // Add your constructor parameters here
    ) {}

    /**
     * Execute the action.
     */
    public function handle(): mixed
    {
        // Add your action logic here
        
        return null;
    }

    /**
     * Called before the action executes.
     */
    protected function before(): void
    {
        // Pre-execution logic
    }

    /**
     * Called after the action executes.
     */
    protected function after(mixed $result): mixed
    {
        // Post-execution logic
        return $result;
    }
}
STUB;
    }

    /**
     * Replace placeholders in the stub.
     */
    protected function replacePlaceholders(string $stub, string $name, string $model, string $namespace): string
    {
        return str_replace(
            ['{{ namespace }}', '{{ model }}', '{{ class }}'],
            [$namespace, $model, $name],
            $stub
        );
    }
}
