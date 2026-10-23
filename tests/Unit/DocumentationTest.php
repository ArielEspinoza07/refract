<?php

declare(strict_types=1);

use Refract\Enums\ClassKind;
use Refract\ReflectedClass;
use Refract\ReflectedMethod;

/**
 * @return list<class-string>
 */
function publicTypes(string $directory = ''): array
{
    $files = glob(dirname(__DIR__, 2)."/src/{$directory}*.php") ?: [];

    return array_map(
        static function (string $file) use ($directory): string {
            /** @var class-string $class */
            $class = 'Refract\\'.str_replace('/', '\\', $directory).basename($file, '.php');

            return $class;
        },
        $files,
    );
}

function documentation(string $page = '*'): string
{
    return implode("\n", array_map(
        static fn (string $file): string => (string) file_get_contents($file),
        glob(dirname(__DIR__, 2)."/docs/{$page}.md") ?: [],
    ));
}

it('documents every public method', function (string $class): void {
    /** @var class-string $class */
    $undocumented = ReflectedClass::from($class)->methods()->public()
        ->reject(static fn (ReflectedMethod $method): bool => str_starts_with($method->name(), '__')
            || str_contains($method->docComment() ?? '', '@internal'))
        ->reject(static fn (ReflectedMethod $method): bool => str_contains(documentation(), "`{$method->name()}("))
        ->map(static fn (ReflectedMethod $method): string => "{$class}::{$method->name()}()");

    expect($undocumented)->toBe([], 'Missing from docs/: '.implode(', ', $undocumented));
})->with(array_merge(publicTypes(), publicTypes('Collections/'), publicTypes('Contracts/')));

it('documents every exception', function (string $exception): void {
    /** @var class-string $exception */
    expect(documentation('errors'))->toContain('`'.ReflectedClass::from($exception)->shortName().'`');
})->with(array_values(array_filter(
    publicTypes('Exceptions/'),
    static fn (string $class): bool => ReflectedClass::from($class)->kind() === ClassKind::Standard,
)));

it('mentions every public type and enum', function (string $class): void {
    /** @var class-string $class */
    expect(documentation())->toContain(ReflectedClass::from($class)->shortName());
})->with(array_merge(publicTypes(), publicTypes('Collections/'), publicTypes('Contracts/'), publicTypes('Enums/')));
