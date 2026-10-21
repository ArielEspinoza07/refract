<?php

declare(strict_types=1);

namespace Refract\Collections;

use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use Refract\Contracts\Named;

/**
 * Immutable, ordered list of reflected items.
 *
 * Every operation returns a new collection of the same type; the original is never modified.
 * Extend it only to add type-specific filters; the core behavior is final.
 *
 * For anything beyond these operations, all() returns a plain list to use with PHP's array functions.
 *
 * @template-covariant T of Named
 *
 * @implements IteratorAggregate<int, T>
 *
 * @extensible
 */
readonly class Collection implements Countable, IteratorAggregate
{
    /**
     * @param  list<T>  $items
     */
    final public function __construct(
        private array $items = [],
    ) {}

    /**
     * @return list<T>
     */
    final public function all(): array
    {
        return $this->items;
    }

    /**
     * The first item, or the first one the callback accepts; null when there is none.
     *
     * @param  (callable(T): bool)|null  $callback
     * @return T|null
     */
    final public function first(?callable $callback = null): ?Named
    {
        if ($callback === null) {
            return $this->items[0] ?? null;
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * The last item, or the last one the callback accepts; null when there is none.
     *
     * @param  (callable(T): bool)|null  $callback
     * @return T|null
     */
    final public function last(?callable $callback = null): ?Named
    {
        if ($callback === null) {
            return $this->items[count($this->items) - 1] ?? null;
        }

        for ($index = count($this->items) - 1; $index >= 0; $index--) {
            if ($callback($this->items[$index])) {
                return $this->items[$index];
            }
        }

        return null;
    }

    final public function isEmpty(): bool
    {
        return $this->items === [];
    }

    final public function isNotEmpty(): bool
    {
        return $this->items !== [];
    }

    final public function count(): int
    {
        return count($this->items);
    }

    /**
     * Whether the callback accepts at least one item. Always false for an empty collection.
     *
     * @param  callable(T): bool  $callback
     */
    final public function any(callable $callback): bool
    {
        return $this->first($callback) !== null;
    }

    /**
     * Whether the callback accepts every item. Always true for an empty collection.
     *
     * @param  callable(T): bool  $callback
     */
    final public function every(callable $callback): bool
    {
        return $this->first(static fn (Named $item): bool => ! $callback($item)) === null;
    }

    /**
     * Keep only the items the callback accepts.
     *
     * @param  callable(T): bool  $callback
     */
    final public function filter(callable $callback): static
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Keep only the items the callback rejects; the inverse of filter().
     *
     * @param  callable(T): bool  $callback
     */
    final public function reject(callable $callback): static
    {
        return $this->filter(static fn (Named $item): bool => ! $callback($item));
    }

    /**
     * @template TResult
     *
     * @param  callable(T): TResult  $callback
     * @return list<TResult>
     */
    final public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * The names of all items, in order.
     *
     * @return list<string>
     */
    final public function names(): array
    {
        return array_map(static fn (Named $item): string => $item->name(), $this->items);
    }

    /**
     * The items keyed by name. When names repeat, like a repeated attribute, the last item wins.
     *
     * @return array<string, T>
     */
    final public function keyByName(): array
    {
        return array_combine($this->names(), $this->items);
    }

    /**
     * @return Iterator<int, T>
     */
    final public function getIterator(): Iterator
    {
        return new ArrayIterator($this->items);
    }
}
