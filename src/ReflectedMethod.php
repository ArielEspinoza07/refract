<?php

declare(strict_types=1);

namespace Refract;

use Closure;
use ReflectionException;
use ReflectionMethod;
use Refract\Collections\MethodCollection;
use Refract\Concerns\BelongsToClass;
use Refract\Concerns\InteractsWithAttributes;
use Refract\Concerns\InteractsWithFunctions;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\ClassMember;
use Refract\Contracts\FunctionLike;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\InstanceRequired;
use Refract\Exceptions\MethodNotFound;
use Refract\Exceptions\MethodNotInvocable;

/**
 * Fluent, immutable reflection of a class method.
 */
final readonly class ReflectedMethod implements ClassMember, FunctionLike
{
    use BelongsToClass;
    use InteractsWithAttributes;
    use InteractsWithFunctions;
    use PreventsSerialization;

    private function __construct(
        private ReflectionMethod $native,
    ) {}

    /**
     * Reflect a method, including inherited and trait methods, by class name or instance.
     *
     * @param  class-string|object  $class
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     * @throws MethodNotFound When the class has no such method.
     */
    public static function from(string|object $class, string $method): self
    {
        return ReflectedClass::from($class)->method($method);
    }

    /**
     * Like from(), but returns null instead of throwing when the class or the method does not exist.
     *
     * Accepts any string as class name, so it can be used directly on untrusted input.
     */
    public static function tryFrom(string|object $class, string $method): ?self
    {
        $reflected = ReflectedClass::tryFrom($class);

        if ($reflected === null || ! $reflected->hasMethod($method)) {
            return null;
        }

        return $reflected->method($method);
    }

    /**
     * Reflect all methods of a class, including inherited and trait methods.
     *
     * @param  class-string|object  $class
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     */
    public static function collectFrom(string|object $class): MethodCollection
    {
        return ReflectedClass::from($class)->methods();
    }

    public static function fromNative(ReflectionMethod $native): self
    {
        return new self($native);
    }

    public function native(): ReflectionMethod
    {
        return $this->native;
    }

    public function name(): string
    {
        return $this->native->getName();
    }

    /**
     * Whether the method has no body: declared abstract, or declared in an interface.
     */
    public function isAbstract(): bool
    {
        return $this->native->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->native->isFinal();
    }

    public function isConstructor(): bool
    {
        return $this->native->isConstructor();
    }

    public function isDestructor(): bool
    {
        return $this->native->isDestructor();
    }

    /**
     * The parent class or interface method this method overrides or implements, or null when there is none.
     */
    public function prototype(): ?self
    {
        return $this->native->hasPrototype() ? new self($this->native->getPrototype()) : null;
    }

    /**
     * Call the method, regardless of visibility. Static methods need no object. Named arguments are supported.
     *
     * @throws InstanceRequired When the method is not static and no object is given.
     * @throws MethodNotInvocable When the method has no body, like an abstract or interface method.
     */
    public function invoke(?object $object = null, mixed ...$arguments): mixed
    {
        try {
            return $this->native->invokeArgs($this->instanceFor($object), $arguments);
        } catch (ReflectionException $exception) {
            throw MethodNotInvocable::because($this->qualifiedName(), $exception);
        }
    }

    /**
     * The method as a Closure, bound to the given object. Static methods need no object.
     *
     * @throws InstanceRequired When the method is not static and no object is given.
     */
    public function closure(?object $object = null): Closure
    {
        return $this->native->getClosure($this->instanceFor($object));
    }

    /**
     * The fully qualified name of the declaration, e.g. "App\Models\User::save()".
     */
    public function qualifiedName(): string
    {
        return "{$this->native->class}::{$this->native->getName()}()";
    }

    private function instanceFor(?object $object): ?object
    {
        return $object ?? ($this->native->isStatic() ? null : throw InstanceRequired::forMethod($this->qualifiedName()));
    }

    private function typeScope(): string
    {
        return $this->native->class;
    }
}
