<?php

declare(strict_types=1);

use Refract\Collections\ConstantCollection;
use Refract\ReflectedClass;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Model;
use Refract\Tests\Fixtures\Tag;

function articleConstants(): ConstantCollection
{
    return ReflectedClass::from(Article::class)->constants();
}

it('filters public constants', function (): void {
    expect(articleConstants()->public()->names())->toEqualCanonicalizing(['TYPE', 'FORMAT', 'CONNECTION']);
});

it('filters protected constants', function (): void {
    expect(articleConstants()->protected()->names())->toBe(['LIMIT']);
});

it('filters private constants', function (): void {
    expect(articleConstants()->private()->names())->toBe(['STEP']);
});

it('filters final constants', function (): void {
    expect(articleConstants()->final()->names())->toBe(['FORMAT']);
});

it('filters constants declared by a given class', function (): void {
    expect(articleConstants()->declaredBy(Model::class)->names())->toBe(['CONNECTION']);
});

it('filters constants with a given attribute', function (): void {
    expect(articleConstants()->withAttribute(Tag::class)->names())->toBe(['TYPE']);
});

it('chains filters', function (): void {
    expect(articleConstants()->public()->declaredBy(Article::class)->names())->toEqualCanonicalizing(['TYPE', 'FORMAT']);
});
