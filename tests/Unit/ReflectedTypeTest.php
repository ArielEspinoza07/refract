<?php

declare(strict_types=1);

use Refract\Enums\TypeKind;
use Refract\Exceptions\TypeNotResolvable;
use Refract\ReflectedType;
use Refract\Tests\Fixtures\Model;
use Refract\Tests\Fixtures\Money;
use Refract\Tests\Fixtures\TypeSamples;
use Refract\Tests\Fixtures\User;

function sampleType(string $property): ReflectedType
{
    $type = (new ReflectionProperty(TypeSamples::class, $property))->getType()
        ?? throw new LogicException("Property [{$property}] has no type.");

    return ReflectedType::fromNative($type, TypeSamples::class);
}

function sampleReturnType(string $method): ReflectedType
{
    $type = (new ReflectionMethod(TypeSamples::class, $method))->getReturnType()
        ?? throw new LogicException("Method [{$method}] has no return type.");

    return ReflectedType::fromNative($type, TypeSamples::class);
}

describe('fromNative()', function (): void {
    it('wraps the given native reflection', function (): void {
        $native = (new ReflectionProperty(TypeSamples::class, 'int'))->getType();
        assert($native instanceof ReflectionType);

        expect(ReflectedType::fromNative($native)->native())->toBe($native);
    });
});

describe('shape', function (): void {
    it('tells the kind of type', function (string $property, TypeKind $kind): void {
        expect(sampleType($property)->kind())->toBe($kind);
    })->with([
        'named' => ['int', TypeKind::Named],
        'nullable' => ['nullable', TypeKind::Named],
        'union' => ['union', TypeKind::Union],
        'intersection' => ['intersection', TypeKind::Intersection],
        'dnf' => ['dnf', TypeKind::Union],
    ]);

    it('prints the type as PHP does', function (string $property, string $name): void {
        expect(sampleType($property)->name())->toBe($name);
    })->with([
        'named' => ['int', 'int'],
        'nullable' => ['nullable', '?string'],
        'class' => ['class', Model::class],
        'intersection' => ['intersection', 'Countable&Traversable'],
        'dnf' => ['dnf', '(Countable&Traversable)|null'],
    ]);

    it('tells whether null is allowed', function (string $property, bool $allowsNull): void {
        expect(sampleType($property)->allowsNull())->toBe($allowsNull);
    })->with([
        'int' => ['int', false],
        'nullable' => ['nullable', true],
        'mixed' => ['mixed', true],
        'union' => ['union', false],
        'dnf' => ['dnf', true],
    ]);

    it('tells whether it is a single built-in type', function (string $property, bool $builtin): void {
        expect(sampleType($property)->isBuiltin())->toBe($builtin);
    })->with([
        'int' => ['int', true],
        'mixed' => ['mixed', true],
        'class' => ['class', false],
        'union' => ['union', false],
    ]);

    it('lists the members of a union type', function (): void {
        expect(sampleType('union')->types()->names())
            ->toEqualCanonicalizing(['int', 'string']);
    });

    it('lists the members of a DNF type', function (): void {
        expect(sampleType('dnf')->types()->names())
            ->toEqualCanonicalizing(['Countable&Traversable', 'null']);
    });

    it('contains only itself when it is a named type', function (): void {
        $type = sampleType('int');

        expect($type->types()->all())->toBe([$type]);
    });
});

describe('allows()', function (): void {
    it('accepts values of the type', function (ReflectedType $type, mixed $value): void {
        expect($type->allows($value))->toBeTrue();
    })->with([
        'int' => [sampleType('int'), 1],
        'float' => [sampleType('float'), 1.5],
        'int as float' => [sampleType('float'), 1],
        'string' => [sampleType('string'), 'a'],
        'bool' => [sampleType('bool'), false],
        'true' => [sampleType('true'), true],
        'false' => [sampleType('false'), false],
        'null' => [sampleType('null'), null],
        'array' => [sampleType('array'), []],
        'iterable' => [sampleType('iterable'), new ArrayIterator([])],
        'object' => [sampleType('object'), new stdClass()],
        'mixed' => [sampleType('mixed'), 'anything'],
        'nullable with null' => [sampleType('nullable'), null],
        'union' => [sampleType('union'), 'a'],
        'intersection' => [sampleType('intersection'), new ArrayIterator([])],
        'dnf with null' => [sampleType('dnf'), null],
        'class' => [sampleType('class'), new User('ada@example.com')],
        'self resolved by PHP' => [sampleType('self'), new TypeSamples()],
        'self in a trait' => [sampleReturnType('selfReturn'), new TypeSamples()],
        'static' => [sampleReturnType('static'), new TypeSamples()],
        'parent in a trait' => [sampleReturnType('parentReturn'), new User('ada@example.com')],
        'callable' => [
            ReflectedType::fromNative((new ReflectionMethod(TypeSamples::class, 'callable'))->getParameters()[0]->getType() ?? throw new LogicException()),
            'strlen',
        ],
    ]);

    it('rejects values outside the type', function (ReflectedType $type, mixed $value): void {
        expect($type->allows($value))->toBeFalse();
    })->with([
        'int' => [sampleType('int'), '1'],
        'float' => [sampleType('float'), '1.5'],
        'string' => [sampleType('string'), 1],
        'bool' => [sampleType('bool'), 0],
        'true' => [sampleType('true'), false],
        'false' => [sampleType('false'), true],
        'null' => [sampleType('null'), 0],
        'array' => [sampleType('array'), new ArrayIterator([])],
        'iterable' => [sampleType('iterable'), 'a'],
        'object' => [sampleType('object'), 'stdClass'],
        'int with null' => [sampleType('int'), null],
        'union' => [sampleType('union'), 1.5],
        'intersection with only one member' => [sampleType('intersection'), new class implements Countable {
            public function count(): int
            {
                return 0;
            }
        }],
        'class' => [sampleType('class'), new Money(1, 'EUR')],
        'self resolved by PHP' => [sampleType('self'), new User('ada@example.com')],
        'self in a trait' => [sampleReturnType('selfReturn'), new User('ada@example.com')],
        'parent in a trait' => [sampleReturnType('parentReturn'), new Money(1, 'EUR')],
        'void' => [sampleReturnType('callable'), null],
        'never' => [sampleReturnType('never'), null],
    ]);

    it('rejects everything for parent when the scope has no parent class', function (): void {
        $type = (new ReflectionMethod(TypeSamples::class, 'parentReturn'))->getReturnType() ?? throw new LogicException();

        expect(ReflectedType::fromNative($type, Model::class)->allows(new User('ada@example.com')))->toBeFalse();
    });

    it('throws TypeNotResolvable for a relative type without a class scope', function (string $method): void {
        $type = (new ReflectionMethod(TypeSamples::class, $method))->getReturnType() ?? throw new LogicException();

        ReflectedType::fromNative($type)->allows(new TypeSamples());
    })->with(['static', 'selfReturn', 'parentReturn'])->throws(TypeNotResolvable::class);
});

describe('classNames()', function (): void {
    it('lists the classes a type accepts', function (ReflectedType $type, array $classNames): void {
        expect($type->classNames())->toBe($classNames);
    })->with([
        'built-in type' => [sampleType('int'), []],
        'nullable built-in type' => [sampleType('nullable'), []],
        'union of built-in types' => [sampleType('union'), []],
        'class' => [sampleType('class'), [Model::class]],
        'nullable class, without the question mark' => [sampleType('nullableClass'), [Model::class]],
        'intersection' => [sampleType('intersection'), [Countable::class, Traversable::class]],
        'DNF type, flattened' => [sampleType('dnf'), [Countable::class, Traversable::class]],
        'self resolved by PHP' => [sampleType('self'), [TypeSamples::class]],
        'static' => [sampleReturnType('static'), [TypeSamples::class]],
        'self in a trait' => [sampleReturnType('selfReturn'), [TypeSamples::class]],
        'parent in a trait' => [sampleReturnType('parentReturn'), [Model::class]],
        'the same class twice, deduplicated' => [sampleReturnType('staticOrSelf'), [TypeSamples::class]],
        'a duplicate before another class, reindexed' => [sampleReturnType('duplicateBeforeOther'), [TypeSamples::class, Countable::class]],
    ]);

    it('lists no class for parent when the scope has no parent class', function (): void {
        $type = (new ReflectionMethod(TypeSamples::class, 'parentReturn'))->getReturnType() ?? throw new LogicException();

        expect(ReflectedType::fromNative($type, Model::class)->classNames())->toBe([]);
    });

    it('throws TypeNotResolvable for a relative type without a class scope', function (string $method): void {
        $type = (new ReflectionMethod(TypeSamples::class, $method))->getReturnType() ?? throw new LogicException();

        ReflectedType::fromNative($type)->classNames();
    })->with(['static', 'selfReturn', 'parentReturn'])->throws(TypeNotResolvable::class);
});

describe('nonNullName()', function (): void {
    it('prints the type without the part that allows null', function (string $property, string $expected): void {
        expect(sampleType($property)->nonNullName())->toBe($expected);
    })->with([
        'named type' => ['int', 'int'],
        'nullable built-in type' => ['nullable', 'string'],
        'nullable class' => ['nullableClass', Model::class],
        'mixed, which cannot be split' => ['mixed', 'mixed'],
        'null on its own' => ['null', 'null'],
        'union without null, in the order PHP reports' => ['union', 'string|int'],
        'union with null' => ['unionWithNull', 'string|int'],
        'intersection' => ['intersection', 'Countable&Traversable'],
        'DNF with only an intersection left' => ['dnf', 'Countable&Traversable'],
        'DNF with an intersection and a class left' => ['dnfWithClass', '(Countable&Traversable)|'.Model::class],
    ]);
});
