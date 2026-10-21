<?php

declare(strict_types=1);

use Refract\Collections\Collection;
use Refract\Enums\AttributeTarget;
use Refract\Exceptions\AttributeNotFound;
use Refract\Exceptions\AttributeNotInstantiable;
use Refract\Exceptions\ClassNotFound;
use Refract\ReflectedAttribute;
use Refract\ReflectedConstant;
use Refract\ReflectedMethod;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Label;
use Refract\Tests\Fixtures\MisusedAttribute;
use Refract\Tests\Fixtures\Money;
use Refract\Tests\Fixtures\Table;
use Refract\Tests\Fixtures\Tag;
use Refract\Tests\Fixtures\User;

describe('from()', function (): void {
    it('reflects a class attribute by class name', function (): void {
        expect(ReflectedAttribute::from(User::class, Table::class)->name())->toBe(Table::class);
    });

    it('reflects a class attribute by instance', function (): void {
        expect(ReflectedAttribute::from(new User('ada@example.com'), Table::class)->name())->toBe(Table::class);
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedAttribute::from('Refract\Tests\Fixtures\Missing', Table::class);
    })->throws(ClassNotFound::class);

    it('throws AttributeNotFound when the class does not declare the attribute', function (): void {
        ReflectedAttribute::from(User::class, Tag::class);
    })->throws(AttributeNotFound::class, Tag::class);
});

describe('tryFrom()', function (): void {
    it('reflects a declared attribute', function (): void {
        expect(ReflectedAttribute::tryFrom(User::class, Table::class)?->name())->toBe(Table::class);
    });

    it('returns null for an unknown class name', function (): void {
        expect(ReflectedAttribute::tryFrom('Refract\Tests\Fixtures\Missing', Table::class))->toBeNull();
    });

    it('returns null when the class does not declare the attribute', function (): void {
        expect(ReflectedAttribute::tryFrom(User::class, Tag::class))->toBeNull();
    });
});

describe('collectFrom()', function (): void {
    it('reflects all attributes declared on a class', function (): void {
        $attributes = ReflectedAttribute::collectFrom(User::class);

        expect($attributes)->toBeInstanceOf(Collection::class)
            ->and($attributes->names())
            ->toBe([Table::class]);
    });

    it('reflects only attributes of the given type', function (): void {
        expect(ReflectedAttribute::collectFrom(new User('ada@example.com'), Table::class)->count())->toBe(1)
            ->and(ReflectedAttribute::collectFrom(User::class, Tag::class)->isEmpty())->toBeTrue();
    });

    it('returns an empty collection for a class without attributes', function (): void {
        expect(ReflectedAttribute::collectFrom(Money::class)->isEmpty())->toBeTrue();
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedAttribute::collectFrom('Refract\Tests\Fixtures\Missing');
    })->throws(ClassNotFound::class);
});

describe('fromNative()', function (): void {
    it('wraps the given native reflection', function (): void {
        $native = (new ReflectionClass(User::class))->getAttributes(Table::class)[0];

        $attribute = ReflectedAttribute::fromNative($native);

        expect($attribute->name())->toBe(Table::class)
            ->and($attribute->native())->toBe($native);
    });
});

describe('arguments()', function (): void {
    it('returns positional arguments under integer keys', function (): void {
        expect(ReflectedAttribute::from(User::class, Table::class)->arguments())->toBe([0 => 'users']);
    });

    it('returns named arguments under their name', function (): void {
        $labels = ReflectedMethod::from(Article::class, 'render')->attributes(Label::class);

        expect($labels->map(static fn (ReflectedAttribute $label): array => $label->arguments()))
            ->toBe([[0 => 'first'], ['value' => 'second']]);
    });

    it('returns an empty array when the attribute has no arguments', function (): void {
        expect(ReflectedMethod::from(Article::class, 'show')->attribute(Tag::class)->arguments())->toBe([]);
    });
});

describe('target()', function (): void {
    it('tells what kind of declaration the attribute is placed on', function (ReflectedAttribute $attribute, AttributeTarget $target): void {
        expect($attribute->target())->toBe($target);
    })->with([
        'class' => [ReflectedAttribute::from(User::class, Table::class), AttributeTarget::ClassLike],
        'method' => [ReflectedMethod::from(Article::class, 'show')->attribute(Tag::class), AttributeTarget::Method],
        'property' => [ReflectedProperty::from(Article::class, 'summary')->attribute(Tag::class), AttributeTarget::Property],
        'constant' => [ReflectedConstant::from(Article::class, 'TYPE')->attribute(Tag::class), AttributeTarget::ClassConstant],
        'parameter' => [
            ReflectedAttribute::fromNative((new ReflectionMethod(Article::class, '__construct'))->getParameters()[1]->getAttributes()[0]),
            AttributeTarget::Parameter,
        ],
        'function' => [
            ReflectedAttribute::fromNative((new ReflectionFunction(#[Tag] static fn (): null => null))->getAttributes()[0]),
            AttributeTarget::Function,
        ],
    ]);
});

describe('isRepeated()', function (): void {
    it('tells whether the attribute appears more than once on the declaration', function (): void {
        expect(ReflectedMethod::from(Article::class, 'render')->attribute(Label::class)->isRepeated())->toBeTrue()
            ->and(ReflectedAttribute::from(User::class, Table::class)->isRepeated())->toBeFalse();
    });
});

describe('newInstance()', function (): void {
    it('creates the attribute with its declared arguments', function (): void {
        $table = ReflectedAttribute::from(User::class, Table::class)->newInstance();

        expect($table)->toBeInstanceOf(Table::class)
            ->and($table->name)->toBe('users');
    });

    it('passes named arguments to the constructor', function (): void {
        $labels = ReflectedMethod::from(Article::class, 'render')->attributes(Label::class);

        expect($labels->map(static fn (ReflectedAttribute $label): string => $label->newInstance()->value))
            ->toBe(['first', 'second']);
    });

    it('throws AttributeNotInstantiable when PHP refuses to create the attribute', function (): void {
        ReflectedMethod::from(MisusedAttribute::class, 'handle')->attribute(Table::class)->newInstance();
    })->throws(AttributeNotInstantiable::class, 'cannot target method');
});
