# Class members

Methods, properties and constants share the `ClassMember` contract:

| Method | Returns | Description |
|---|---|---|
| `name()` | `string` | The member name |
| `qualifiedName()` | `string` | `App\User::save()`, `App\User::$email` or `App\User::TABLE` |
| `isPublic()` / `isProtected()` / `isPrivate()` | `bool` | |
| `declaringClass()` | `ReflectedClass` | The class that declares it: a parent class for inherited members |
| `isDeclaredBy($class)` | `bool` | Whether it is declared by that class rather than inherited |

They also have attributes, see [Attributes](attributes.md). An inherited member is named after its declaring class:
`ReflectedMethod::from(User::class, 'id')->qualifiedName()` is `App\Model::id()` when `id()` is declared in `Model`.

## ReflectedMethod

```php
use Refract\ReflectedMethod;

$method = ReflectedMethod::from(User::class, 'save');
```

| Method | Returns | Description |
|---|---|---|
| `isStatic()` / `isAbstract()` / `isFinal()` | `bool` | Interface methods are abstract |
| `isConstructor()` / `isDestructor()` | `bool` | |
| `prototype()` | `?ReflectedMethod` | The parent or interface method it overrides or implements |
| `invoke($object, ...$arguments)` | `mixed` | Calls the method regardless of visibility; named arguments are supported. Static methods take `null` as object |
| `closure($object)` | `Closure` | The method as a closure bound to the object |

`invoke()` and `closure()` throw `InstanceRequired` for an instance method without an object, and `invoke()` throws
`MethodNotInvocable` for a method without a body. Methods also have the signature methods of every function: see
[Functions and parameters](functions.md#signature).

## ReflectedProperty

```php
use Refract\ReflectedProperty;

$property = ReflectedProperty::from(User::class, 'email');
$property->value($user);
```

| Method | Returns | Description |
|---|---|---|
| `type()` | `?ReflectedType` | `null` when untyped |
| `isStatic()` / `isReadonly()` / `isPromoted()` | `bool` | |
| `isDynamic()` | `bool` | Added to an object at runtime; only possible when reflected from an instance |
| `hasDefaultValue()` / `defaultValue()` | `bool` / `mixed` | `defaultValue()` throws `DefaultValueNotAvailable` |
| `value($object)` | `mixed` | Regardless of visibility. Static properties need no object |
| `isInitialized($object)` | `bool` | False for typed properties not assigned yet |
| `docComment()` | `?string` | |

`value()` and `isInitialized()` throw `InstanceRequired` for an instance property without an object. Reading a typed
property before it is initialized throws PHP's own `Error`, as direct access would.

PHP 8.4 adds hooks, asymmetric visibility and final or abstract properties: see [PHP 8.4 features](php-8.4.md).

## ReflectedConstant

Reflects a class constant or an enum case.

```php
use Refract\ReflectedConstant;

ReflectedConstant::from(User::class, 'TABLE')->value();             // 'users'
ReflectedConstant::from(Status::class, 'Active')->backingValue();   // 'active'
```

| Method | Returns | Description |
|---|---|---|
| `value()` | `mixed` | For an enum case, the case instance |
| `backingValue()` | `int\|string\|null` | The value of a backed enum case; `null` otherwise |
| `type()` | `?ReflectedType` | Typed constants, PHP 8.3+; `null` when untyped |
| `isFinal()` | `bool` | |
| `isEnumCase()` | `bool` | |
| `docComment()` | `?string` | |
