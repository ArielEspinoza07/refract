<?php

declare(strict_types=1);

use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\Named;
use Refract\Exceptions\RefractException;
use Refract\ReflectedCallable;

it('makes every exception catchable as a RefractException', function (): void {
    $files = glob(dirname(__DIR__, 2).'/src/Exceptions/*.php') ?: [];

    $exceptions = array_filter(
        array_map(static fn (string $file): string => 'Refract\\Exceptions\\'.basename($file, '.php'), $files),
        static fn (string $class): bool => class_exists($class),
    );

    expect($exceptions)->not->toBeEmpty();

    foreach ($exceptions as $exception) {
        expect(class_implements($exception))->toHaveKey(RefractException::class);
    }
});

/**
 * Every Reflected* class that has instances. ReflectedCallable only creates other reflections.
 *
 * @return list<string>
 */
function reflectionClasses(): array
{
    $classes = array_map(
        static fn (string $file): string => 'Refract\\'.basename($file, '.php'),
        glob(dirname(__DIR__, 2).'/src/Reflected*.php') ?: [],
    );

    return array_values(array_filter($classes, static fn (string $class): bool => $class !== ReflectedCallable::class));
}

it('makes every reflection Named, so collections can list and key it by name', function (): void {
    expect(reflectionClasses())->not->toBeEmpty();

    foreach (reflectionClasses() as $class) {
        expect(class_implements($class))->toHaveKey(Named::class);
    }
});

it('makes every reflection refuse serialization with a clear error', function (): void {
    foreach (reflectionClasses() as $class) {
        expect(class_uses($class))->toHaveKey(PreventsSerialization::class);
    }
});
