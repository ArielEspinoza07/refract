# Functions and parameters

## ReflectedCallable

Reflects any callable as the function or method it refers to, when you do not know its form in advance. It only
creates reflections; it has no instances of its own.

```php
use Refract\ReflectedCallable;

ReflectedCallable::from([UserController::class, 'show']);   // ReflectedMethod
ReflectedCallable::from('App\Jobs\Send::handle');           // ReflectedMethod
ReflectedCallable::from(new SendInvoice());                 // ReflectedMethod: its __invoke()
ReflectedCallable::from($user->save(...));                  // ReflectedMethod: first-class callables are unwrapped
ReflectedCallable::from(strlen(...));                       // ReflectedFunction
ReflectedCallable::from(fn (int $id) => $id);               // ReflectedFunction
```

| Method | Returns | Description |
|---|---|---|
| `from($callable)` | `FunctionLike` | The concrete `ReflectedFunction` or `ReflectedMethod` |
| `tryFrom($callable)` | `?FunctionLike` | Accepts any value; `null` when it does not resolve |

`from()` throws `FunctionNotFound`, `ClassNotFound` or `MethodNotFound` when the target does not exist, and
`InvalidFunctionReference` for an array that is not `[class-or-object, method]` or a string using `self::`,
`parent::` or `static::`.

## ReflectedFunction

Reflects a named function or a closure.

```php
use Refract\ReflectedFunction;

$function = ReflectedFunction::from('App\Support\format');
$closure = ReflectedFunction::from(fn (int $id): User => User::find($id));
```

| Method | Returns | Description |
|---|---|---|
| `isAnonymous()` | `bool` | Written with `function` or `fn`, as opposed to a first-class callable such as `strlen(...)` |
| `isClosure()` | `bool` | Reflected from a `Closure` object |
| `closureThis()` | `?object` | The object a closure is bound to |
| `closureScopeClass()` | `?ReflectedClass` | The class a closure is scoped to |
| `staticVariables()` | `array` | `static` variables, and the variables a closure captured with `use` |
| `invoke(...$arguments)` | `mixed` | Calls the function; named arguments are supported |
| `closure()` | `Closure` | The function as a closure |

## Signature

`ReflectedFunction` and `ReflectedMethod` share the `FunctionLike` contract, so code can accept either:

| Method | Returns | Description |
|---|---|---|
| `name()` / `shortName()` / `qualifiedName()` | `string` | `shortName()` drops the namespace; for methods it equals `name()` |
| `parameters()` | `ParameterCollection` | In declaration order |
| `parameter($nameOrPosition)` / `hasParameter($nameOrPosition)` | `ReflectedParameter` / `bool` | `parameter()` throws `ParameterNotFound` |
| `returnType()` | `?ReflectedType` | `null` when none is declared |
| `isStatic()` | `bool` | A static method, or a closure declared with `static` |
| `isVariadic()` / `isGenerator()` / `returnsReference()` | `bool` | |
| `isDeprecated()` / `isInternal()` / `isUserDefined()` | `bool` | |
| `fileName()` / `startLine()` / `endLine()` | `?string` / `?int` | `null` for internal functions and methods |
| `docComment()` | `?string` | |
| `native()` | `ReflectionFunctionAbstract` | |

## ReflectedParameter

```php
use Refract\ReflectedParameter;

ReflectedParameter::from([UserController::class, 'show'], 'id');   // any callable form, by name
ReflectedParameter::from('App\Support\format', 0);                 // or by zero-based position
ReflectedParameter::collectFrom($handler)->required();
```

| Method | Returns | Description |
|---|---|---|
| `name()` / `position()` | `string` / `int` | |
| `qualifiedName()` | `string` | `App\User::save($data)` or `App\Support\format($value)` |
| `type()` / `allowsNull()` | `?ReflectedType` / `bool` | |
| `isOptional()` | `bool` | Can be left out by a caller: variadic, or has a default value and no required parameter after it |
| `isVariadic()` / `isPassedByReference()` / `isPromoted()` | `bool` | |
| `hasDefaultValue()` / `defaultValue()` | `bool` / `mixed` | `defaultValue()` throws `DefaultValueNotAvailable` |
| `declaringFunction()` | `FunctionLike` | The method or function it belongs to |
| `declaringClass()` | `?ReflectedClass` | The method's class or the closure's scope; `null` for a plain function |

A parameter reached through `from()` or a `FunctionLike` remembers its function, so a closure declared inside a class
is a `ReflectedFunction`. Wrapped directly with `fromNative()`, it is reported the way PHP reports it: as a method of
that class.

Parameters have attributes too, see [Attributes](attributes.md).
