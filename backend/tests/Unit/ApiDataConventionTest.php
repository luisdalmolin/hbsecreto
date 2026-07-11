<?php

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Resource;

/**
 * @return array<int, string>
 */
function apiPhpFiles(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    $files = [];

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $files[] = $file->getPathname();
    }

    return $files;
}

function apiApplicationPath(): string
{
    return dirname(__DIR__, 2).'/app';
}

/**
 * @return array<int, string>
 */
function apiDataClassNames(): array
{
    $classNames = [];

    foreach (apiPhpFiles(apiApplicationPath().'/Data/Api') as $filePath) {
        $relativePath = str($filePath)
            ->after(apiApplicationPath().DIRECTORY_SEPARATOR)
            ->beforeLast('.php')
            ->replace(DIRECTORY_SEPARATOR, '\\');

        $classNames[] = 'App\\'.$relativePath;
    }

    return $classNames;
}

test('API payload classes use Spatie Laravel Data', function (): void {
    foreach (apiDataClassNames() as $className) {
        expect(is_a($className, Data::class, true) || is_a($className, Resource::class, true))
            ->toBeTrue("{$className} must extend a Spatie Laravel Data class.");
    }
});

test('the API does not use Laravel HTTP resource classes', function (): void {
    $resourceFiles = apiPhpFiles(apiApplicationPath().'/Http/Resources');
    $controllerFiles = apiPhpFiles(apiApplicationPath().'/Http/Controllers/Api');

    expect($resourceFiles)->toBeEmpty();

    foreach ($controllerFiles as $controllerFile) {
        expect(file_get_contents($controllerFile))->not->toContain('Illuminate\\Http\\Resources');
    }
});
