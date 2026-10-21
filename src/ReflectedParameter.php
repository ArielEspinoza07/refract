<?php

declare(strict_types=1);

namespace Refract;

use Closure;
use LogicException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Refract\Collections\ParameterCollection;
use Refract\Concerns\InteractsWithAttributes;
use Refract\Concerns\InteractsWithTypes;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\FunctionLike;
use Refract\Contracts\HasAttributes;
use Refract\Contracts\Named;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\DefaultValueNotAvailable;
use Refract\Exceptions\FunctionNotFound;
use Refract\Exceptions\InvalidFunctionReference;
use Refract\Exceptions\MethodNotFound;
use Refract\Exceptions\ParameterNotFound;

/**
 * Fluent, immutable reflection of a function or method parameter.
 *
 * The function can be given in any form ReflectedCallable accepts: a Closure, a function name, "Class::method",
 * [$classOrObject, 'method'], or an invokable object.
 *
 * @phpstan-import-type CallableReference from ReflectedCallable
 */
final readonly class ReflectedParameter implements HasAttributes, Named
{
    use InteractsWithAttributes;
    use InteractsWithTypes;
    use PreventsSerialization;

    private function __construct(
        private ReflectionParameter $native,
        private ?FunctionLike $declaringFunction = null,
    ) {}

    /**
     * @param  CallableReference  $function
     * @param  string|int  $parameter  The parameter name, or its zero-based position.
     *
     * @throws FunctionNotFound When no function has the given name.
     * @throws ClassNotFound When the given class does not exist.
     * @throws MethodNotFound When the class has no such method.
     * @throws InvalidFunctionReference When an array is not [class-or-object, method], or a string uses self::, parent:: or static::.
     * @throws ParameterNotFound When the function has no such parameter.
     */
    public static function from(object|string|array $function, string|int $parameter): self
    {
        return ReflectedCallable::from($function)->parameter($parameter);
    }

    /**
     * Like from(), but returns null instead of throwing when the function or the parameter does not exist.
     *
     * Accepts any value, including malformed arrays, so it can be used directly on untrusted input.
     *
     * @param  object|string|array<mixed>  $function
     * @param  string|int  $parameter  The parameter name, or its zero-based position.
     */
    public static function tryFrom(object|string|array $function, string|int $parameter): ?self
    {
        $resolved = ReflectedCallable::tryFrom($function);

        return $resolved !== null && $resolved->hasParameter($parameter) ? $resolved->parameter($parameter) : null;
    }

    /**
     * Reflect all parameters of a function, in declaration order.
     *
     * @param  CallableReference  $function
     *
     * @throws FunctionNotFound When no function has the given name.
     * @throws ClassNotFound When the given class does not exist.
     * @throws MethodNotFound When the class has no such method.
     * @throws InvalidFunctionReference When an array is not [class-or-object, method], or a string uses self::, parent:: or static::.
     */
    public static function collectFrom(object|string|array $function): ParameterCollection
    {
        return ReflectedCallable::from($function)->parameters();
    }

    /**
     * Wrap an existing native reflection.
     */
    public static function fromNative(ReflectionParameter $native): self
    {
        return new self($native);
    }

    /**
     * Wrap a native parameter of a function or method that is already reflected, so declaringFunction()
     * returns that reflection. Used by FunctionLike::parameters() and parameter().
     *
     * @internal
     */
    public static function ofFunction(ReflectionParameter $native, FunctionLike $function): self
    {
        return new self($native, $function);
    }

    public function native(): ReflectionParameter
    {
        return $this->native;
    }

    public function name(): string
    {
        return $this->native->getName();
    }

    /**
     * The zero-based position in the signature.
     */
    public function position(): int
    {
        return $this->native->getPosition();
    }

    public function allowsNull(): bool
    {
        return $this->native->allowsNull();
    }

    /**
     * Whether a caller may leave the parameter out: it has a default value, or it is variadic.
     */
    public function isOptional(): bool
    {
        return $this->native->isOptional();
    }

    public function isVariadic(): bool
    {
        return $this->native->isVariadic();
    }

    public function isPassedByReference(): bool
    {
        return $this->native->isPassedByReference();
    }

    /**
     * Whether this constructor parameter also declares a property.
     */
    public function isPromoted(): bool
    {
        return $this->native->isPromoted();
    }

    public function hasDefaultValue(): bool
    {
        return $this->native->isDefaultValueAvailable();
    }

    /**
     * @throws DefaultValueNotAvailable When the parameter has no default value.
     */
    public function defaultValue(): mixed
    {
        return $this->native->isDefaultValueAvailable()
            ? $this->native->getDefaultValue()
            : throw DefaultValueNotAvailable::for($this->qualifiedName());
    }

    /**
     * The method or function the parameter belongs to.
     *
     * A parameter wrapped with fromNative() reports its function the way PHP does, so a closure declared inside
     * a class comes back as a ReflectedMethod of that class. Reflect such parameters through from(), or through
     * ReflectedFunction::parameters(), to get the closure as a ReflectedFunction.
     */
    public function declaringFunction(): FunctionLike
    {
        if ($this->declaringFunction !== null) {
            return $this->declaringFunction;
        }

        $function = $this->native->getDeclaringFunction();

        if ($function instanceof ReflectionMethod) {
            return ReflectedMethod::fromNative($function);
        }

        // ReflectionFunctionAbstract has exactly two subclasses.
        return ReflectedFunction::fromNative($function instanceof ReflectionFunction ? $function : throw new LogicException('Unknown function reflection.'));
    }

    /**
     * The class of the method the parameter belongs to, or the scope of a closure; null for a plain function.
     *
     * @return ReflectedClass<object>|null
     */
    public function declaringClass(): ?ReflectedClass
    {
        $class = $this->native->getDeclaringClass();

        return $class === null ? null : ReflectedClass::fromNative($class);
    }

    /**
     * The fully qualified name of the declaration, e.g. "App\Models\User::save($data)" or "App\Support\format($value)".
     */
    public function qualifiedName(): string
    {
        $function = $this->native->getDeclaringFunction();
        $class = $this->native->getDeclaringClass();
        // A declaring class means a method, or a closure declared inside that class; only methods get the prefix.
        $prefix = $class === null || $function->isClosure() ? '' : "{$class->getName()}::";

        return "{$prefix}{$function->getName()}(\${$this->native->getName()})";
    }

    private function typeScope(): ?string
    {
        return $this->native->getDeclaringClass()?->getName();
    }
}
