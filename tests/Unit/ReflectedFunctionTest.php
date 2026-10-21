<?php

declare(strict_types=1);

use Refract\Contracts\FunctionLike;
use Refract\Exceptions\AttributeNotFound;
use Refract\Exceptions\FunctionNotFound;
use Refract\Exceptions\ParameterNotFound;
use Refract\ReflectedFunction;
use Refract\ReflectedParameter;
use Refract\Tests\Fixtures\Signatures;
use Refract\Tests\Fixtures\Table;
use Refract\Tests\Fixtures\Tag;

use function Refract\Tests\Fixtures\greet;

describe('from()', function (): void {
    it('reflects a function by name', function (): void {
        $function = ReflectedFunction::from('Refract\Tests\Fixtures\greet');

        expect($function)->toBeInstanceOf(FunctionLike::class)
            ->and($function->name())->toBe('Refract\Tests\Fixtures\greet')
            ->and($function->shortName())->toBe('greet');
    });

    it('reflects a closure', function (): void {
        expect(ReflectedFunction::from(static fn (): int => 1)->isClosure())->toBeTrue();
    });

    it('throws FunctionNotFound for an unknown function', function (): void {
        ReflectedFunction::from('Refract\Tests\Fixtures\missing');
    })->throws(FunctionNotFound::class, 'Refract\Tests\Fixtures\missing');
});

describe('tryFrom()', function (): void {
    it('reflects a known function or a closure', function (): void {
        expect(ReflectedFunction::tryFrom('strlen')?->name())->toBe('strlen')
            ->and(ReflectedFunction::tryFrom(static fn (): int => 1))->not->toBeNull();
    });

    it('returns null for an unknown function', function (): void {
        expect(ReflectedFunction::tryFrom('Refract\Tests\Fixtures\missing'))->toBeNull();
    });
});

describe('fromNative()', function (): void {
    it('wraps the given native reflection', function (): void {
        $native = new ReflectionFunction('strlen');

        expect(ReflectedFunction::fromNative($native)->native())->toBe($native);
    });
});

describe('closures', function (): void {
    it('tells anonymous closures apart from first-class callables', function (): void {
        $anonymous = ReflectedFunction::from(static fn (): int => 1);
        $firstClass = ReflectedFunction::from(strlen(...));
        $named = ReflectedFunction::from('strlen');

        expect($anonymous->isAnonymous())->toBeTrue()
            ->and($anonymous->isClosure())->toBeTrue()
            ->and($firstClass->isAnonymous())->toBeFalse()
            ->and($firstClass->isClosure())->toBeTrue()
            ->and($named->isClosure())->toBeFalse();
    });

    it('exposes what a closure is bound and scoped to', function (): void {
        $signatures = new Signatures(7);
        $closure = ReflectedFunction::from($signatures->boundClosure());

        expect($closure->closureThis())->toBe($signatures)
            ->and($closure->closureScopeClass()?->name())->toBe(Signatures::class)
            ->and($closure->returnType()?->name())->toBe('int');
    });

    it('returns null for the binding and scope of a plain function', function (): void {
        $function = ReflectedFunction::from('Refract\Tests\Fixtures\greet');

        expect($function->closureThis())->toBeNull()
            ->and($function->closureScopeClass())->toBeNull();
    });

    it('detects static closures', function (): void {
        expect(ReflectedFunction::from(static fn (): int => 1)->isStatic())->toBeTrue()
            ->and(ReflectedFunction::from((new Signatures())->boundClosure())->isStatic())->toBeFalse();
    });

    it('returns captured and static variables', function (): void {
        $captured = 5;
        $closure = static function () use ($captured): int {
            return $captured;
        };

        expect(ReflectedFunction::from($closure)->staticVariables())->toBe(['captured' => 5])
            ->and(ReflectedFunction::from('Refract\Tests\Fixtures\counter')->staticVariables())->toBe(['count' => 0]);
    });
});

describe('calling', function (): void {
    it('invokes the function, including with named arguments', function (): void {
        $function = ReflectedFunction::from('Refract\Tests\Fixtures\greet');

        expect($function->invoke('Ada'))->toBe('Hello, Ada')
            ->and($function->invoke(name: 'Ada', greeting: 'Hi'))->toBe('Hi, Ada');
    });

    it('returns the function as a closure', function (): void {
        expect((ReflectedFunction::from('strlen')->closure())('abc'))->toBe(3);
    });
});

describe('signature', function (): void {
    it('lists the parameters in order', function (): void {
        expect(ReflectedFunction::from('Refract\Tests\Fixtures\greet')->parameters()->names())
            ->toBe(['name', 'greeting', 'others']);
    });

    it('finds a parameter by name or position', function (): void {
        $function = ReflectedFunction::from('Refract\Tests\Fixtures\greet');

        expect($function->hasParameter('greeting'))->toBeTrue()
            ->and($function->hasParameter(0))->toBeTrue()
            ->and($function->hasParameter('missing'))->toBeFalse()
            ->and($function->hasParameter(5))->toBeFalse()
            ->and($function->parameter('greeting')->position())->toBe(1)
            ->and($function->parameter(2)->name())->toBe('others');
    });

    it('throws ParameterNotFound for an unknown parameter', function (string|int $parameter, string $label): void {
        expect(static fn (): ReflectedParameter => ReflectedFunction::from('Refract\Tests\Fixtures\greet')->parameter($parameter))
            ->toThrow(ParameterNotFound::class, "Parameter [{$label}] does not exist on [Refract\\Tests\\Fixtures\\greet()].");
    })->with([
        'by name' => ['missing', '$missing'],
        'by position' => [9, '#9'],
    ]);

    it('returns the return type, or null when there is none', function (): void {
        expect(ReflectedFunction::from('Refract\Tests\Fixtures\greet')->returnType()?->name())->toBe('string')
            ->and(ReflectedFunction::from(static fn () => 1)->returnType())->toBeNull();
    });

    it('detects variadic, generator and by-reference functions', function (): void {
        expect(ReflectedFunction::from('Refract\Tests\Fixtures\greet')->isVariadic())->toBeTrue()
            ->and(ReflectedFunction::from('Refract\Tests\Fixtures\numbers')->isGenerator())->toBeTrue()
            ->and(ReflectedFunction::from('Refract\Tests\Fixtures\counter')->returnsReference())->toBeTrue()
            ->and(ReflectedFunction::from('Refract\Tests\Fixtures\greet')->isGenerator())->toBeFalse();
    });

    it('tells internal and user-defined functions apart', function (): void {
        $internal = ReflectedFunction::from('strlen');
        $userDefined = ReflectedFunction::from('Refract\Tests\Fixtures\greet');

        expect($internal->isInternal())->toBeTrue()
            ->and($internal->isUserDefined())->toBeFalse()
            ->and($userDefined->isUserDefined())->toBeTrue()
            ->and($userDefined->isDeprecated())->toBeFalse();
    });
});

describe('source location', function (): void {
    it('exposes the file, lines and doc comment of a user-defined function', function (): void {
        $function = ReflectedFunction::from('Refract\Tests\Fixtures\greet');

        expect($function->fileName())->toEndWith('functions.php')
            ->and($function->startLine())->toBeInt()
            ->and($function->endLine())->toBeGreaterThan($function->startLine() ?? PHP_INT_MAX)
            ->and($function->docComment())->toBe('/** Greets one or more people. */');
    });

    it('returns null for the location of an internal function', function (): void {
        $function = ReflectedFunction::from('strlen');

        expect($function->fileName())->toBeNull()
            ->and($function->startLine())->toBeNull()
            ->and($function->endLine())->toBeNull()
            ->and($function->docComment())->toBeNull();
    });
});

describe('attributes', function (): void {
    it('reads attributes declared on the function', function (): void {
        expect(ReflectedFunction::from('Refract\Tests\Fixtures\greet')->hasAttribute(Tag::class))->toBeTrue();
    });

    it('names the function when an attribute is missing', function (): void {
        ReflectedFunction::from('Refract\Tests\Fixtures\greet')->attribute(Table::class);
    })->throws(AttributeNotFound::class, 'is not declared on [Refract\Tests\Fixtures\greet()]');
});

it('can call the fixture function directly', function (): void {
    expect(greet('Ada', 'Hi', 'Grace'))->toBe('Hi, Ada, Grace');
});
