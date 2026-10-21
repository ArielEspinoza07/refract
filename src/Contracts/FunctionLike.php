<?php

declare(strict_types=1);

namespace Refract\Contracts;

use ReflectionFunctionAbstract;
use Refract\Collections\ParameterCollection;
use Refract\Exceptions\ParameterNotFound;
use Refract\ReflectedParameter;
use Refract\ReflectedType;

/**
 * The signature and metadata shared by methods, functions and closures.
 *
 * Not meant to be implemented outside Refract. New methods may be added in minor releases.
 */
interface FunctionLike extends HasAttributes, Named
{
    public function native(): ReflectionFunctionAbstract;

    /**
     * The name without namespace. Equal to name() for methods.
     */
    public function shortName(): string;

    public function parameters(): ParameterCollection;

    /**
     * @param  string|int  $parameter  The parameter name, or its zero-based position.
     */
    public function hasParameter(string|int $parameter): bool;

    /**
     * @param  string|int  $parameter  The parameter name, or its zero-based position.
     *
     * @throws ParameterNotFound
     */
    public function parameter(string|int $parameter): ReflectedParameter;

    /**
     * The declared return type, or null when there is none.
     */
    public function returnType(): ?ReflectedType;

    /**
     * Whether the method is static, or the closure was declared with `static fn` / `static function`.
     */
    public function isStatic(): bool;

    public function isVariadic(): bool;

    public function isGenerator(): bool;

    public function returnsReference(): bool;

    public function isDeprecated(): bool;

    public function isInternal(): bool;

    public function isUserDefined(): bool;

    /**
     * The file it is declared in, or null for internal functions and methods.
     */
    public function fileName(): ?string;

    public function startLine(): ?int;

    public function endLine(): ?int;

    /**
     * The doc comment above the declaration, including the comment markers, or null when there is none.
     */
    public function docComment(): ?string;
}
