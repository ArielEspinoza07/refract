<?php

declare(strict_types=1);

use Refract\Collections\MethodCollection;
use Refract\ReflectedClass;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Identifiable;
use Refract\Tests\Fixtures\Model;
use Refract\Tests\Fixtures\Tag;

function articleMethods(): MethodCollection
{
    return ReflectedClass::from(Article::class)->methods();
}

it('filters public methods', function (): void {
    expect(articleMethods()->public()->names())
        ->toEqualCanonicalizing(['__construct', 'published', 'show', 'publish', 'id']);
});

it('filters protected methods', function (): void {
    expect(articleMethods()->protected()->names())->toBe(['render']);
});

it('filters private methods', function (): void {
    expect(articleMethods()->private()->names())->toBe(['track']);
});

it('filters static methods', function (): void {
    expect(articleMethods()->static()->names())->toBe(['published']);
});

it('filters abstract methods', function (): void {
    expect(articleMethods()->abstract()->names())->toBe([])
        ->and(ReflectedClass::from(Identifiable::class)->methods()->abstract()->names())->toBe(['id']);
});

it('filters final methods', function (): void {
    expect(articleMethods()->final()->names())->toEqualCanonicalizing(['publish', 'id']);
});

it('filters methods declared by a given class', function (): void {
    expect(articleMethods()->declaredBy(Model::class)->names())->toBe(['id'])
        ->and(articleMethods()->declaredBy(Article::class)->names())->not->toContain('id');
});

it('filters methods with a given attribute', function (): void {
    expect(articleMethods()->withAttribute(Tag::class)->names())->toBe(['show']);
});

it('chains filters', function (): void {
    expect(articleMethods()->public()->final()->declaredBy(Article::class)->names())->toBe(['publish']);
});
