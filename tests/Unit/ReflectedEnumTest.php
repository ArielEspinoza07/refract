<?php

declare(strict_types=1);

use Refract\Contracts\HasAttributes;
use Refract\Enums\ClassKind;
use Refract\Exceptions\AttributeNotFound;
use Refract\Exceptions\ConstantNotFound;
use Refract\Exceptions\EnumNotFound;
use Refract\ReflectedEnum;
use Refract\Tests\Fixtures\Priority;
use Refract\Tests\Fixtures\Status;
use Refract\Tests\Fixtures\Tag;
use Refract\Tests\Fixtures\User;

describe('from()', function (): void {
    it('reflects an enum by name', function (): void {
        expect(ReflectedEnum::from(Status::class)->name())->toBe(Status::class);
    });

    it('reflects the enum of a case', function (): void {
        expect(ReflectedEnum::from(Priority::High)->name())->toBe(Priority::class);
    });

    it('throws EnumNotFound for an unknown name', function (): void {
        /** @phpstan-ignore argument.type, argument.templateType (deliberately passing a name that does not exist) */
        ReflectedEnum::from('Refract\Tests\Fixtures\Missing');
    })->throws(EnumNotFound::class, 'Refract\Tests\Fixtures\Missing');

    it('throws EnumNotFound for a class that is not an enum', function (): void {
        /** @phpstan-ignore argument.type, argument.templateType (deliberately passing a class that is not an enum) */
        ReflectedEnum::from(User::class);
    })->throws(EnumNotFound::class, User::class);
});

describe('tryFrom()', function (): void {
    it('reflects an enum by name or case', function (): void {
        expect(ReflectedEnum::tryFrom(Status::class)?->name())->toBe(Status::class)
            ->and(ReflectedEnum::tryFrom(Status::Active)->name())->toBe(Status::class);
    });

    it('returns null for a name that is not an enum', function (string $name): void {
        expect(ReflectedEnum::tryFrom($name))->toBeNull();
    })->with(['Refract\Tests\Fixtures\Missing', User::class]);
});

describe('fromNative()', function (): void {
    it('wraps the given native reflection', function (): void {
        $native = new ReflectionEnum(Status::class);

        expect(ReflectedEnum::fromNative($native)->native())->toBe($native);
    });
});

describe('identity', function (): void {
    it('exposes the fully qualified and short name', function (): void {
        $enum = ReflectedEnum::from(Status::class);

        expect($enum->name())->toBe(Status::class)
            ->and($enum->shortName())->toBe('Status');
    });
});

describe('backing', function (): void {
    it('tells whether the enum is backed', function (): void {
        expect(ReflectedEnum::from(Status::class)->isBacked())->toBeTrue()
            ->and(ReflectedEnum::from(Priority::class)->isBacked())->toBeFalse();
    });

    it('returns the backing type, or null for a pure enum', function (): void {
        expect(ReflectedEnum::from(Status::class)->backingType()?->name())->toBe('string')
            ->and(ReflectedEnum::from(Priority::class)->backingType())->toBeNull();
    });
});

describe('cases', function (): void {
    it('lists the cases in declaration order', function (): void {
        expect(ReflectedEnum::from(Status::class)->cases()->names())
            ->toBe(['Active', 'Inactive']);
    });

    it('returns a case by name', function (): void {
        $case = ReflectedEnum::from(Status::class)->case('Inactive');

        expect($case->value())->toBe(Status::Inactive)
            ->and($case->backingValue())->toBe('inactive');
    });

    it('tells whether a case exists', function (): void {
        $enum = ReflectedEnum::from(Status::class);

        expect($enum->hasCase('Active'))->toBeTrue()
            ->and($enum->hasCase('Missing'))->toBeFalse();
    });

    it('throws ConstantNotFound for an unknown case', function (): void {
        ReflectedEnum::from(Status::class)->case('Missing');
    })->throws(ConstantNotFound::class, 'Missing');
});

describe('asClass()', function (): void {
    it('reflects the enum as a class', function (): void {
        $class = ReflectedEnum::from(Status::class)->asClass();

        expect($class->name())->toBe(Status::class)
            ->and($class->kind())->toBe(ClassKind::Enum);
    });
});

describe('attributes', function (): void {
    it('reads attributes declared on the enum', function (): void {
        $enum = ReflectedEnum::from(Priority::class);

        expect($enum)->toBeInstanceOf(HasAttributes::class)
            ->and($enum->hasAttribute(Tag::class))->toBeTrue()
            ->and(ReflectedEnum::from(Status::class)->hasAttribute(Tag::class))->toBeFalse();
    });
});

it('names the enum when an attribute is missing', function (): void {
    ReflectedEnum::from(Status::class)->attribute(Tag::class);
})->throws(AttributeNotFound::class, 'is not declared on ['.Status::class.']');
