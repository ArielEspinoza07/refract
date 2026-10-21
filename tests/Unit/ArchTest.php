<?php

declare(strict_types=1);

use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\Named;
use Refract\Exceptions\RefractException;

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

it('makes every reflection Named, so collections can list and key it by name', function (): void {
    $files = glob(dirname(__DIR__, 2).'/src/Reflected*.php') ?: [];

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect(class_implements('Refract\\'.basename($file, '.php')))->toHaveKey(Named::class);
    }
});

it('makes every reflection refuse serialization with a clear error', function (): void {
    $files = glob(dirname(__DIR__, 2).'/src/Reflected*.php') ?: [];

    foreach ($files as $file) {
        expect(class_uses('Refract\\'.basename($file, '.php')))->toHaveKey(PreventsSerialization::class);
    }
});
