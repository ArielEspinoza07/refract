<?php

declare(strict_types=1);

namespace Refract;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Refract\Collections\Collection;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\Named;
use Refract\Enums\TypeKind;
use Refract\Exceptions\TypeNotResolvable;

/**
 * Fluent, immutable reflection of a declared type: named, union, intersection or DNF.
 *
 * Types are reached through the declaration they belong to, such as a property or a parameter.
 */
final readonly class ReflectedType implements Named
{
    use PreventsSerialization;

    /**
     * @param  class-string|null  $scope
     */
    private function __construct(
        private ReflectionType $native,
        private ?string $scope,
    ) {}

    /**
     * Wrap an existing native reflection.
     *
     * @param  class-string|null  $scope  The class `self`, `static` and `parent` refer to; null outside a class.
     */
    public static function fromNative(ReflectionType $native, ?string $scope = null): self
    {
        return new self($native, $scope);
    }

    public function native(): ReflectionType
    {
        return $this->native;
    }

    public function kind(): TypeKind
    {
        return match (true) {
            $this->native instanceof ReflectionUnionType => TypeKind::Union,
            $this->native instanceof ReflectionIntersectionType => TypeKind::Intersection,
            default => TypeKind::Named,
        };
    }

    /**
     * The type as PHP prints it, e.g. "?int", "int|string" or "(A&B)|null".
     */
    public function name(): string
    {
        return (string) $this->native;
    }

    public function allowsNull(): bool
    {
        return $this->native->allowsNull();
    }

    /**
     * The type as PHP prints it, without the part that allows null: "int" for `?int`, "string|int" for
     * `string|int|null`, "A&B" for `(A&B)|null`. Types that cannot be split, like `mixed` and `null`, are
     * returned unchanged. Useful to decide how to convert a value before checking it with allows().
     */
    public function nonNullName(): string
    {
        if ($this->native instanceof ReflectionNamedType) {
            // Unlike the string form, getName() never includes the leading "?".
            return $this->native->getName();
        }

        if (! $this->native instanceof ReflectionUnionType) {
            return $this->name();
        }

        $members = $this->types()->reject(static fn (self $type): bool => $type->name() === 'null');

        // A lone member needs no grouping; inside a union, an intersection keeps its parentheses.
        return $members->count() === 1
            ? ($members->first()?->name() ?? '')
            : implode('|', $members->map(
                static fn (self $type): string => $type->kind() === TypeKind::Intersection ? "({$type->name()})" : $type->name(),
            ));
    }

    /**
     * Whether this is a single built-in type like `int` or `mixed`, as opposed to a class or a composite type.
     */
    public function isBuiltin(): bool
    {
        return $this->native instanceof ReflectionNamedType && $this->native->isBuiltin();
    }

    /**
     * The members of a union or intersection type. A named type contains only itself,
     * so the result can always be iterated.
     *
     * @return Collection<self>
     */
    public function types(): Collection
    {
        if (! $this->native instanceof ReflectionUnionType && ! $this->native instanceof ReflectionIntersectionType) {
            return new Collection([$this]);
        }

        return new Collection(array_values(array_map(
            fn (ReflectionType $type): self => new self($type, $this->scope),
            $this->native->getTypes(),
        )));
    }

    /**
     * The class and interface names the type accepts, for example ['App\User', 'App\Admin'] for `User|Admin|null`.
     *
     * Built-in types are skipped, nested DNF types are flattened, duplicates are removed, and self, static
     * and parent are resolved against the class scope. Names are returned as declared and not checked for
     * existence, so no class is autoloaded.
     *
     * @return list<string>
     *
     * @throws TypeNotResolvable When the type uses self, static or parent and has no class scope.
     */
    public function classNames(): array
    {
        if ($this->native instanceof ReflectionNamedType) {
            $class = $this->native->isBuiltin() ? null : $this->resolveClassName($this->native->getName());

            return $class === null ? [] : [$class];
        }

        return array_values(array_unique(array_merge(
            ...$this->types()->map(static fn (self $type): array => $type->classNames()),
        )));
    }

    /**
     * Whether the value satisfies the type as PHP checks it with strict_types=1: no coercion,
     * except that an int is accepted where a float is expected.
     *
     * @throws TypeNotResolvable When the type uses self, static or parent and has no class scope.
     */
    public function allows(mixed $value): bool
    {
        if ($this->native instanceof ReflectionNamedType) {
            return ($value === null && $this->native->allowsNull()) || $this->allowsNamed($this->native->getName(), $value);
        }

        $types = $this->types();
        $matching = $types->filter(static fn (self $type): bool => $type->allows($value))->count();

        return $this->kind() === TypeKind::Intersection ? $matching === $types->count() : $matching > 0;
    }

    /**
     * Only called for non-null values, or when the type does not allow null: allows() already accepts null
     * whenever the type does, so the `null` type itself has nothing left to accept.
     */
    private function allowsNamed(string $name, mixed $value): bool
    {
        return match ($name) {
            'mixed' => true,
            'null', 'void', 'never' => false,
            'int' => is_int($value),
            'float' => is_float($value) || is_int($value),
            'string' => is_string($value),
            'bool' => is_bool($value),
            'true' => $value === true,
            'false' => $value === false,
            'array' => is_array($value),
            'iterable' => is_iterable($value),
            'callable' => is_callable($value),
            'object' => is_object($value),
            default => $this->isInstanceOfNamed($value, $name),
        };
    }

    private function isInstanceOfNamed(mixed $value, string $name): bool
    {
        $class = $this->resolveClassName($name);

        return $class !== null && $value instanceof $class;
    }

    /**
     * The class a non-built-in name refers to: relative names resolve against the scope, and `parent`
     * gives null when the scope has no parent class. Other names are returned as declared.
     *
     * @throws TypeNotResolvable When the name is relative and there is no class scope.
     */
    private function resolveClassName(string $name): ?string
    {
        return match ($name) {
            'self', 'static' => $this->scope ?? throw TypeNotResolvable::withoutScope($name),
            'parent' => get_parent_class($this->scope ?? throw TypeNotResolvable::withoutScope($name)) ?: null,
            default => $name,
        };
    }
}
