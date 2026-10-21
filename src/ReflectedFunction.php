<?php

declare(strict_types=1);

namespace Refract;

use Closure;
use ReflectionException;
use ReflectionFunction;
use Refract\Concerns\InteractsWithAttributes;
use Refract\Concerns\InteractsWithFunctions;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\FunctionLike;
use Refract\Exceptions\FunctionNotFound;

/**
 * Fluent, immutable reflection of a named function or a closure.
 */
final readonly class ReflectedFunction implements FunctionLike
{
    use InteractsWithAttributes;
    use InteractsWithFunctions;
    use PreventsSerialization;

    private function __construct(
        private ReflectionFunction $native,
    ) {}

    /**
     * Reflect a function by name, or a closure.
     *
     * @throws FunctionNotFound When no function has the given name.
     */
    public static function from(Closure|string $function): self
    {
        try {
            return new self(new ReflectionFunction($function));
        } catch (ReflectionException $exception) {
            throw FunctionNotFound::named(is_string($function) ? $function : '{closure}', $exception);
        }
    }

    /**
     * Like from(), but returns null instead of throwing when no function has the given name.
     *
     * Accepts any string, so it can be used directly on untrusted input.
     */
    public static function tryFrom(Closure|string $function): ?self
    {
        return $function instanceof Closure || function_exists($function)
            ? new self(new ReflectionFunction($function))
            : null;
    }

    /**
     * Wrap an existing native reflection.
     */
    public static function fromNative(ReflectionFunction $native): self
    {
        return new self($native);
    }

    public function native(): ReflectionFunction
    {
        return $this->native;
    }

    /**
     * The fully qualified name. Closures have a generated name like "{closure}".
     */
    public function name(): string
    {
        return $this->native->getName();
    }

    /**
     * Whether this is a closure declared with `function () {}` or `fn () =>`, as opposed to
     * a named function or a first-class callable created from one, like `strlen(...)`.
     */
    public function isAnonymous(): bool
    {
        return $this->native->isAnonymous();
    }

    /**
     * Whether this was reflected from a Closure object, anonymous or not.
     */
    public function isClosure(): bool
    {
        return $this->native->isClosure();
    }

    /**
     * The object a closure is bound to as `$this`, or null when it is unbound or static.
     */
    public function closureThis(): ?object
    {
        return $this->native->getClosureThis();
    }

    /**
     * The class a closure is scoped to, which decides what `self`, `static` and private members refer to.
     *
     * @return ReflectedClass<object>|null
     */
    public function closureScopeClass(): ?ReflectedClass
    {
        $scope = $this->native->getClosureScopeClass();

        return $scope === null ? null : ReflectedClass::fromNative($scope);
    }

    /**
     * The function's `static` variables, and the variables a closure captured with `use`,
     * keyed by variable name.
     *
     * @return array<mixed>
     */
    public function staticVariables(): array
    {
        return $this->native->getStaticVariables();
    }

    /**
     * Call the function. Named arguments are supported.
     */
    public function invoke(mixed ...$arguments): mixed
    {
        return $this->native->invokeArgs($arguments);
    }

    /**
     * The function as a Closure.
     */
    public function closure(): Closure
    {
        return $this->native->getClosure();
    }

    /**
     * The fully qualified name of the declaration, e.g. "App\Support\format()"; closures use the name PHP gives them.
     */
    public function qualifiedName(): string
    {
        return "{$this->native->getName()}()";
    }

    private function typeScope(): ?string
    {
        return $this->native->getClosureScopeClass()?->getName();
    }
}
