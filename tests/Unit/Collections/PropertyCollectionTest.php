<?php

declare(strict_types=1);

use Refract\Collections\PropertyCollection;
use Refract\ReflectedClass;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Model;
use Refract\Tests\Fixtures\Tag;

function articleProperties(): PropertyCollection
{
    return ReflectedClass::from(Article::class)->properties();
}

it('filters public properties', function (): void {
    expect(articleProperties()->public()->names())->toEqualCanonicalizing(['published', 'title', 'slug']);
});

it('filters protected properties', function (): void {
    expect(articleProperties()->protected()->names())->toEqualCanonicalizing(['summary', 'key']);
});

it('filters private properties', function (): void {
    expect(articleProperties()->private()->names())->toBe(['views']);
});

it('filters static properties', function (): void {
    expect(articleProperties()->static()->names())->toBe(['published']);
});

it('filters readonly properties', function (): void {
    expect(articleProperties()->readonly()->names())->toEqualCanonicalizing(['title', 'slug']);
});

it('filters promoted properties', function (): void {
    expect(articleProperties()->promoted()->names())->toBe(['slug']);
});

it('filters properties declared by a given class', function (): void {
    expect(articleProperties()->declaredBy(Model::class)->names())->toBe(['key']);
});

it('filters properties with a given attribute', function (): void {
    expect(articleProperties()->withAttribute(Tag::class)->names())->toBe(['summary']);
});

it('chains filters', function (): void {
    expect(articleProperties()->public()->readonly()->promoted()->names())->toBe(['slug']);
});
