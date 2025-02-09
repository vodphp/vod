<?php

namespace Vod\Vod\Commands;

use Exception;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Vod\Vod\TypescriptTransformer\ResolveClassesInPhpFileAction;
use Vod\Vod\Vod;

class VodTransform extends Command
{
    protected $signature = 'vod:transform';

    public function handle()
    {
        if (config('vod.output_json_schema') || config('vod.output_stubs')) {
            $this->setup();
            $this->extractTypes();
            $this->info('Vod json schema/stubs generated');
        } else {
            $this->info('Vod is not enabled');
        }
    }

    private static function setup()
    {
        if (config('vod.output_stubs')) {
            File::ensureDirectoryExists(config('vod.output_stubs_location'));
            // cleare the directory
            File::deleteDirectory(config('vod.output_stubs_location'));
            File::ensureDirectoryExists(config('vod.output_stubs_location'));
            File::put(config('vod.output_stubs_location').'/Vod.stub.php', '<?php ');
        }

        if (config('vod.output_json_schema')) {
            File::ensureDirectoryExists(config('vod.output_json_schema_location'));
            // cleare the directory
            File::deleteDirectory(config('vod.output_json_schema_location'));
            File::ensureDirectoryExists(config('vod.output_json_schema_location'));
        }
    }

    private static function appendToVodStub(string $content)
    {
        File::append(config('vod.output_stubs_location').'/Vod.stub.php', "\n".$content."\n");
    }

    private function extractTypes(): void
    {
        $iterator = $this->resolveIterator(config('vod.auto_discover_types') ?? []);

        foreach ($iterator as $name => $reflectionClass) {
            if ($reflectionClass->isSubclassOf(Vod::class) && $reflectionClass->isInstantiable()) {

                if (config('vod.output_stubs')) {
                    self::appendToVodStub($reflectionClass->getName()::toStub());
                }

                if (config('vod.output_json_schema')) {
                    $schema = $reflectionClass->getName()::schema();
                    $fileName = str($name)->replace('\\', '.');
                    File::put(config('vod.output_json_schema_location').'/'.$fileName.'.json', json_encode($schema->toJsonSchema(), JSON_PRETTY_PRINT));
                }
            }
        }
    }

    private function resolveIterator(array $paths): Generator
    {
        $paths = array_map(
            fn (string $path) => is_dir($path) ? $path : dirname($path),
            $paths
        );

        $finder = new Finder;
        foreach ($finder->in($paths) as $fileInfo) {
            try {

                $classes = (new ResolveClassesInPhpFileAction)->execute($fileInfo);

                foreach ($classes as $name) {

                    yield $name => new ReflectionClass($name);
                }
            } catch (Exception $exception) {
            }
        }

        return $finder;
    }
}
