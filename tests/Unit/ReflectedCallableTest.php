<?php

declare(strict_types=1);

use Refract\Contracts\FunctionLike;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\FunctionNotFound;
use Refract\Exceptions\InvalidFunctionReference;
use Refract\Exceptions\MethodNotFound;
use Refract\ReflectedCallable;
use Refract\ReflectedFunction;
use Refract\ReflectedMethod;
use Refract\Tests\Fixtures\Model;
use Refract\Tests\Fixtures\Signatures;
use Refract\Tests\Fixtures\User;

use function Refract\Tests\Fixtures\greet;

describe('from()', function (): void {
    it('reflects every callable form as the function or method it refers to', function (mixed $callable, string $type, string $qualifiedName): void {
        /** @var Closure|object|string|array{0: class-string|object, 1: string} $callable */
        $reflected = ReflectedCallable::from($callable);

        expect($reflected)->toBeInstanceOf(FunctionLike::class)
            ->and($reflected::class)->toBe($type)
            ->and($reflected->qualifiedName())->toBe($qualifiedName);
    })->with([
        'function name' => ['Refract\Tests\Fixtures\greet', ReflectedFunction::class, 'Refract\Tests\Fixtures\greet()'],
        'internal function name' => ['strlen', ReflectedFunction::class, 'strlen()'],
        'static call string' => [Signatures::class.'::send', ReflectedMethod::class, Signatures::class.'::send()'],
        'class and method' => [[Signatures::class, 'send'], ReflectedMethod::class, Signatures::class.'::send()'],
        'object and method' => [[new Signatures(), 'make'], ReflectedMethod::class, Signatures::class.'::make()'],
        'invokable object' => [new Signatures(), ReflectedMethod::class, Signatures::class.'::__invoke()'],
    ]);

    it('keeps a closure written with fn or function as a function', function (): void {
        $reflected = ReflectedCallable::from(static fn (int $id): int => $id);

        expect($reflected)->toBeInstanceOf(ReflectedFunction::class)
            ->and($reflected->parameter('id')->type()?->name())->toBe('int');
    });

    it('keeps a closure declared inside a class as a function', function (): void {
        expect(ReflectedCallable::from((new Signatures())->boundClosure()))->toBeInstanceOf(ReflectedFunction::class);
    });

    it('resolves a first-class callable to the method it was created from', function (Closure $callable, string $qualifiedName): void {
        $reflected = ReflectedCallable::from($callable);

        expect($reflected)->toBeInstanceOf(ReflectedMethod::class)
            ->and($reflected->qualifiedName())->toBe($qualifiedName);
    })->with([
        'instance method' => [(new Signatures())->send(...), Signatures::class.'::send()'],
        'static method' => [Signatures::make(...), Signatures::class.'::make()'],
        'inherited method, named after its declaring class' => [(new User('ada@example.com'))->id(...), Model::class.'::id()'],
        'Closure::fromCallable()' => [Closure::fromCallable([new Signatures(), 'send']), Signatures::class.'::send()'],
    ]);

    it('resolves a first-class callable of a function to that function', function (Closure $callable, string $qualifiedName): void {
        $reflected = ReflectedCallable::from($callable);

        expect($reflected)->toBeInstanceOf(ReflectedFunction::class)
            ->and($reflected->qualifiedName())->toBe($qualifiedName);
    })->with([
        'internal function' => [strlen(...), 'strlen()'],
        'user-defined function' => [greet(...), 'Refract\Tests\Fixtures\greet()'],
    ]);

    it('throws FunctionNotFound for an unknown function', function (): void {
        ReflectedCallable::from('Refract\Tests\Fixtures\missing');
    })->throws(FunctionNotFound::class);

    it('throws ClassNotFound for an unknown class', function (mixed $callable): void {
        /** @var Closure|object|string|array{0: class-string|object, 1: string} $callable */
        ReflectedCallable::from($callable);
    })->with([
        'static call string' => ['Refract\Tests\Fixtures\Missing::send'],
        'class and method' => [['Refract\Tests\Fixtures\Missing', 'send']],
    ])->throws(ClassNotFound::class, 'Refract\Tests\Fixtures\Missing');

    it('throws MethodNotFound for an unknown method or an object that is not invokable', function (mixed $callable): void {
        /** @var Closure|object|string|array{0: class-string|object, 1: string} $callable */
        ReflectedCallable::from($callable);
    })->with([
        'unknown method' => [[Signatures::class, 'missing']],
        'object that is not invokable' => [new User('ada@example.com')],
    ])->throws(MethodNotFound::class);

    it('throws InvalidFunctionReference for a malformed array', function (): void {
        /** @var array{0: class-string|object, 1: string} $callable deliberately malformed at runtime */
        $callable = [Signatures::class];

        ReflectedCallable::from($callable);
    })->throws(InvalidFunctionReference::class, '[class-or-object, method]');

    it('throws InvalidFunctionReference for self::, parent:: and static:: strings', function (string $callable): void {
        ReflectedCallable::from($callable);
    })->with(['self::send', 'parent::send', 'Static::send'])->throws(InvalidFunctionReference::class, 'only has a meaning inside a class');
});

describe('tryFrom()', function (): void {
    it('reflects every resolvable callable', function (): void {
        expect(ReflectedCallable::tryFrom('strlen'))->toBeInstanceOf(ReflectedFunction::class)
            ->and(ReflectedCallable::tryFrom(Signatures::class.'::send'))->toBeInstanceOf(ReflectedMethod::class)
            ->and(ReflectedCallable::tryFrom([Signatures::class, 'send']))->toBeInstanceOf(ReflectedMethod::class)
            ->and(ReflectedCallable::tryFrom(new Signatures()))->toBeInstanceOf(ReflectedMethod::class)
            ->and(ReflectedCallable::tryFrom((new Signatures())->send(...)))->toBeInstanceOf(ReflectedMethod::class)
            ->and(ReflectedCallable::tryFrom(static fn (): int => 1))->toBeInstanceOf(ReflectedFunction::class);
    });

    it('returns null for anything that does not resolve', function (mixed $callable): void {
        expect(ReflectedCallable::tryFrom($callable))->toBeNull();
    })->with([
        'unknown function' => ['Refract\Tests\Fixtures\missing'],
        'unknown class' => ['Refract\Tests\Fixtures\Missing::send'],
        'unknown method' => [[Signatures::class, 'missing']],
        'object that is not invokable' => [new User('ada@example.com')],
        'malformed array' => [[Signatures::class, 'send', 'extra']],
        'relative static call' => ['parent::send'],
        'integer' => [42],
        'null' => [null],
    ]);
});

it('cannot be instantiated', function (): void {
    expect((new ReflectionClass(ReflectedCallable::class))->isInstantiable())->toBeFalse();
});
