<?php

declare(strict_types=1);

namespace Refract\Concerns;

use ReflectionParameter;
use Refract\Collections\ParameterCollection;
use Refract\Contracts\FunctionLike;
use Refract\Exceptions\ParameterNotFound;
use Refract\ReflectedParameter;
use Refract\ReflectedType;

/**
 * The single implementation of FunctionLike, shared by methods and functions.
 *
 * @internal
 *
 * @see FunctionLike
 */
trait InteractsWithFunctions
{
    use InteractsWithDocComment;
    use InteractsWithSource;

    public function shortName(): string
    {
        return $this->native()->getShortName();
    }

    public function parameters(): ParameterCollection
    {
        return new ParameterCollection(array_map(
            fn (ReflectionParameter $native): ReflectedParameter => ReflectedParameter::ofFunction($native, $this),
            $this->native()->getParameters(),
        ));
    }

    public function hasParameter(string|int $parameter): bool
    {
        return $this->findParameter($parameter) !== null;
    }

    /**
     * @throws ParameterNotFound
     */
    public function parameter(string|int $parameter): ReflectedParameter
    {
        $native = $this->findParameter($parameter) ?? throw ParameterNotFound::in($this->qualifiedName(), $parameter);

        return ReflectedParameter::ofFunction($native, $this);
    }

    public function returnType(): ?ReflectedType
    {
        $type = $this->native()->getReturnType();

        return $type === null ? null : ReflectedType::fromNative($type, $this->typeScope());
    }

    public function isStatic(): bool
    {
        return $this->native()->isStatic();
    }

    public function isVariadic(): bool
    {
        return $this->native()->isVariadic();
    }

    public function isGenerator(): bool
    {
        return $this->native()->isGenerator();
    }

    public function returnsReference(): bool
    {
        return $this->native()->returnsReference();
    }

    public function isDeprecated(): bool
    {
        return $this->native()->isDeprecated();
    }

    /**
     * The fully qualified name of the declaration, also used in error messages.
     */
    abstract public function qualifiedName(): string;

    private function findParameter(string|int $parameter): ?ReflectionParameter
    {
        $matches = array_filter(
            $this->native()->getParameters(),
            static fn (ReflectionParameter $native): bool => $native->getName() === $parameter || $native->getPosition() === $parameter,
        );

        return $matches === [] ? null : reset($matches);
    }

    /**
     * The class that self, static and parent refer to in the signature, or null outside a class.
     *
     * @return class-string|null
     */
    abstract private function typeScope(): ?string;
}
