<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

use Countable;
use LogicException;
use Traversable;

/**
 * Declares one member per kind of type, for the ReflectedType tests.
 */
final class TypeSamples extends Model
{
    use ReturnsRelativeTypes;

    public int $int = 0;

    public float $float = 0.0;

    public string $string = '';

    public bool $bool = false;

    public true $true = true;

    public false $false = false;

    public null $null = null;

    /** @var array<mixed> */
    public array $array = [];

    /** @var iterable<mixed> */
    public iterable $iterable = [];

    public object $object;

    public mixed $mixed = null;

    public ?string $nullable = null;

    public int|string $union = 0;

    /** @var Countable&Traversable<mixed> */
    public Countable&Traversable $intersection;

    /** @var (Countable&Traversable<mixed>)|null */
    public (Countable&Traversable)|null $dnf = null;

    public self $self;

    public Model $class;

    public ?Model $nullableClass = null;

    public function callable(callable $callback): void {}

    public function never(): never
    {
        throw new LogicException('Never returns.');
    }

    public function static(): static
    {
        return $this;
    }

    /**
     * Resolves to the same class twice, once through self and once through static.
     */
    public function staticOrSelf(): static|self
    {
        return $this;
    }
}
