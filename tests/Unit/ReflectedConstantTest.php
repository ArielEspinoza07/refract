<?php

declare(strict_types=1);

use Refract\Collections\ConstantCollection;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\ConstantNotFound;
use Refract\ReflectedConstant;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Priority;
use Refract\Tests\Fixtures\Status;
use Refract\Tests\Fixtures\User;

describe('from()', function (): void {
    it('reflects a constant by class name', function (): void {
        expect(ReflectedConstant::from(User::class, 'TABLE')->name())->toBe('TABLE');
    });

    it('reflects a constant by instance', function (): void {
        expect(ReflectedConstant::from(new User('ada@example.com'), 'TABLE')->name())->toBe('TABLE');
    });

    it('reflects an enum case', function (): void {
        expect(ReflectedConstant::from(Status::class, 'Active')->name())->toBe('Active');
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedConstant::from('Refract\Tests\Fixtures\Missing', 'TABLE');
    })->throws(ClassNotFound::class);

    it('throws ConstantNotFound for an unknown constant', function (): void {
        ReflectedConstant::from(User::class, 'MISSING');
    })->throws(ConstantNotFound::class, 'MISSING');
});

describe('tryFrom()', function (): void {
    it('reflects a known constant', function (): void {
        expect(ReflectedConstant::tryFrom(User::class, 'TABLE')?->name())->toBe('TABLE');
    });

    it('returns null for an unknown class name', function (): void {
        expect(ReflectedConstant::tryFrom('Refract\Tests\Fixtures\Missing', 'TABLE'))->toBeNull();
    });

    it('returns null for an unknown constant', function (): void {
        expect(ReflectedConstant::tryFrom(User::class, 'MISSING'))->toBeNull();
    });
});

describe('collectFrom()', function (): void {
    it('reflects all constants of a class, including inherited ones', function (): void {
        $constants = ReflectedConstant::collectFrom(User::class);

        expect($constants)->toBeInstanceOf(ConstantCollection::class)
            ->and($constants->names())
            ->toEqualCanonicalizing(['TABLE', 'CONNECTION']);
    });

    it('reflects enum cases', function (): void {
        expect(ReflectedConstant::collectFrom(Status::class)->names())
            ->toEqualCanonicalizing(['Active', 'Inactive']);
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedConstant::collectFrom('Refract\Tests\Fixtures\Missing');
    })->throws(ClassNotFound::class);
});

describe('value()', function (): void {
    it('returns the value of a constant', function (): void {
        expect(ReflectedConstant::from(User::class, 'TABLE')->value())->toBe('users');
    });

    it('returns the case instance for an enum case', function (): void {
        expect(ReflectedConstant::from(Status::class, 'Active')->value())->toBe(Status::Active);
    });
});

describe('backingValue()', function (): void {
    it('returns the scalar value of a backed enum case', function (): void {
        expect(ReflectedConstant::from(Status::class, 'Active')->backingValue())->toBe('active');
    });

    it('returns null for a pure enum case and for a regular constant', function (): void {
        expect(ReflectedConstant::from(Priority::class, 'Low')->backingValue())->toBeNull()
            ->and(ReflectedConstant::from(User::class, 'TABLE')->backingValue())->toBeNull();
    });
});

describe('modifiers', function (): void {
    it('detects final constants', function (): void {
        expect(ReflectedConstant::from(Article::class, 'FORMAT')->isFinal())->toBeTrue()
            ->and(ReflectedConstant::from(Article::class, 'TYPE')->isFinal())->toBeFalse();
    });

    it('detects enum cases', function (): void {
        expect(ReflectedConstant::from(Status::class, 'Active')->isEnumCase())->toBeTrue()
            ->and(ReflectedConstant::from(Priority::class, 'High')->isEnumCase())->toBeTrue()
            ->and(ReflectedConstant::from(User::class, 'TABLE')->isEnumCase())->toBeFalse();
    });
});

describe('docComment()', function (): void {
    it('returns the doc comment, or null when there is none', function (): void {
        expect(ReflectedConstant::from(Article::class, 'FORMAT')->docComment())->toBe('/** The rendered output format. */')
            ->and(ReflectedConstant::from(Article::class, 'TYPE')->docComment())->toBeNull();
    });
});

describe('type()', function (): void {
    it('returns the declared type of a typed constant', function (): void {
        expect(ReflectedConstant::from(Article::class, 'LIMIT')->type()?->name())->toBe('int');
    });

    it('returns null when the constant has no type', function (): void {
        expect(ReflectedConstant::from(Status::class, 'Active')->type())->toBeNull();
    });
});
