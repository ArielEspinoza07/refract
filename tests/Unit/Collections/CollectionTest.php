<?php

declare(strict_types=1);

use Refract\Collections\Collection;
use Refract\Collections\ConstantCollection;
use Refract\ReflectedConstant;
use Refract\ReflectedMethod;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Label;

/**
 * TYPE (public), FORMAT (public, final) and LIMIT (protected), in that order.
 *
 * @return Collection<ReflectedConstant>
 */
function constants(): Collection
{
    return new Collection([
        ReflectedConstant::from(Article::class, 'TYPE'),
        ReflectedConstant::from(Article::class, 'FORMAT'),
        ReflectedConstant::from(Article::class, 'LIMIT'),
    ]);
}

/**
 * @return Collection<ReflectedConstant>
 */
function noConstants(): Collection
{
    return new Collection();
}

describe('reading', function (): void {
    it('returns all items in order', function (): void {
        expect(constants()->all())->toHaveCount(3)
            ->and(constants()->all()[1]->name())->toBe('FORMAT');
    });

    it('is iterable in order', function (): void {
        expect(array_map(static fn (ReflectedConstant $constant): string => $constant->name(), iterator_to_array(constants())))
            ->toBe(['TYPE', 'FORMAT', 'LIMIT']);
    });

    it('counts its items', function (): void {
        expect(constants()->count())->toBe(3)
            ->and(count(constants()))->toBe(3);
    });

    it('tells whether it is empty', function (): void {
        expect(noConstants()->isEmpty())->toBeTrue()
            ->and(noConstants()->isNotEmpty())->toBeFalse()
            ->and(constants()->isEmpty())->toBeFalse()
            ->and(constants()->isNotEmpty())->toBeTrue();
    });
});

describe('first() and last()', function (): void {
    it('returns the first and last item', function (): void {
        expect(constants()->first()?->name())->toBe('TYPE')
            ->and(constants()->last()?->name())->toBe('LIMIT');
    });

    it('returns the first and last item the callback accepts', function (): void {
        $isPublic = static fn (ReflectedConstant $constant): bool => $constant->isPublic();

        expect(constants()->first($isPublic)?->name())->toBe('TYPE')
            ->and(constants()->last($isPublic)?->name())->toBe('FORMAT');
    });

    it('finds a match at either end of the collection', function (): void {
        $isType = static fn (ReflectedConstant $constant): bool => $constant->name() === 'TYPE';
        $isProtected = static fn (ReflectedConstant $constant): bool => $constant->isProtected();

        expect(constants()->last($isType)?->name())->toBe('TYPE')
            ->and(constants()->last($isProtected)?->name())->toBe('LIMIT')
            ->and(constants()->first($isProtected)?->name())->toBe('LIMIT');
    });

    it('returns null when no item is accepted', function (): void {
        $isPrivate = static fn (ReflectedConstant $constant): bool => $constant->isPrivate();

        expect(constants()->first($isPrivate))->toBeNull()
            ->and(constants()->last($isPrivate))->toBeNull();
    });

    it('returns null for an empty collection', function (): void {
        expect(noConstants()->first())->toBeNull()
            ->and(noConstants()->last())->toBeNull();
    });
});

describe('any() and every()', function (): void {
    it('tells whether at least one item is accepted', function (): void {
        expect(constants()->any(static fn (ReflectedConstant $constant): bool => $constant->isFinal()))->toBeTrue()
            ->and(constants()->any(static fn (ReflectedConstant $constant): bool => $constant->isPrivate()))->toBeFalse();
    });

    it('tells whether every item is accepted', function (): void {
        expect(constants()->every(static fn (ReflectedConstant $constant): bool => $constant->isDeclaredBy(Article::class)))->toBeTrue()
            ->and(constants()->every(static fn (ReflectedConstant $constant): bool => $constant->isPublic()))->toBeFalse();
    });

    it('accepts nothing and everything when empty', function (): void {
        $always = static fn (ReflectedConstant $constant): bool => true;

        expect(noConstants()->any($always))->toBeFalse()
            ->and(noConstants()->every($always))->toBeTrue();
    });
});

describe('filter() and reject()', function (): void {
    it('filters into a new, reindexed collection without modifying the original', function (): void {
        $collection = constants();

        $public = $collection->filter(static fn (ReflectedConstant $constant): bool => $constant->isPublic());

        expect($public->names())->toBe(['TYPE', 'FORMAT'])
            ->and(array_keys($public->all()))->toBe([0, 1])
            ->and($collection->count())->toBe(3);
    });

    it('rejects the items the callback accepts', function (): void {
        expect(constants()->reject(static fn (ReflectedConstant $constant): bool => $constant->isPublic())->names())
            ->toBe(['LIMIT']);
    });

    it('keeps the subclass type', function (): void {
        $constants = ReflectedConstant::collectFrom(Article::class);
        $always = static fn (ReflectedConstant $constant): bool => true;

        expect($constants->filter($always))->toBeInstanceOf(ConstantCollection::class)
            ->and($constants->reject($always))->toBeInstanceOf(ConstantCollection::class);
    });
});

describe('projections', function (): void {
    it('maps items to a list', function (): void {
        expect(constants()->map(static fn (ReflectedConstant $constant): mixed => $constant->value()))
            ->toBe(['article', 'html', 10]);
    });

    it('lists the names in order', function (): void {
        expect(constants()->names())->toBe(['TYPE', 'FORMAT', 'LIMIT'])
            ->and(noConstants()->names())->toBe([]);
    });

    it('keys the items by name', function (): void {
        $byName = constants()->keyByName();

        expect(array_keys($byName))->toBe(['TYPE', 'FORMAT', 'LIMIT'])
            ->and($byName['FORMAT']->isFinal())->toBeTrue();
    });

    it('keeps the last item when names repeat', function (): void {
        $labels = ReflectedMethod::from(Article::class, 'render')->attributes(Label::class)->keyByName();

        expect($labels)->toHaveCount(1)
            ->and($labels[Label::class]->arguments())->toBe(['value' => 'second']);
    });

    it('returns an empty map for an empty collection', function (): void {
        expect(noConstants()->keyByName())->toBe([]);
    });
});
