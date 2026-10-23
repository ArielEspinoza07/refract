# Classes and enums

## ReflectedClass

Reflects a class, interface, trait or enum.

```php
use Refract\ReflectedClass;

$class = ReflectedClass::from(User::class);
```

### Identity and kind

| Method | Returns | Description |
|---|---|---|
| `name()` | `class-string` | The fully qualified name |
| `shortName()` | `string` | The name without namespace |
| `namespace()` | `string` | The namespace, or `''` for the global namespace |
| `qualifiedName()` | `string` | The same as `name()`; present on every declaration |
| `kind()` | `ClassKind` | `Standard`, `Interface`, `Trait` or `Enum`. Abstract and anonymous classes are `Standard` |
| `asEnum()` | `ReflectedEnum` | The enum this class is; throws `EnumNotFound` for any other kind |

### Modifiers

| Method | Returns | Description |
|---|---|---|
| `isAbstract()` | `bool` | |
| `isFinal()` | `bool` | |
| `isReadonly()` | `bool` | Declared `readonly class` |
| `isAnonymous()` | `bool` | `new class { … }` |
| `isInstantiable()` | `bool` | False for interfaces, traits, enums, abstract classes and classes without a public constructor |
| `isCloneable()` | `bool` | |
| `isIterable()` | `bool` | Implements `Traversable` |
| `isInternal()` / `isUserDefined()` | `bool` | Defined by PHP or an extension, or in PHP code |

### Hierarchy

| Method | Returns | Description |
|---|---|---|
| `parent()` | `?ReflectedClass` | `null` when the class extends nothing |
| `interfaces()` | `Collection<ReflectedClass>` | All implemented interfaces, including inherited ones |
| `traits()` | `Collection<ReflectedClass>` | Traits used directly by this class |
| `isSubclassOf($class)` | `bool` | Throws `ClassNotFound` for an unknown class |
| `implements($interface)` | `bool` | False for a name that is not an interface; throws `ClassNotFound` for an unknown name |
| `isInstance($object)` | `bool` | Includes subclasses and implementations; narrows `$object` for PHPStan |

### Members

| Method | Returns | Description |
|---|---|---|
| `methods()` | `MethodCollection` | All methods, including inherited and trait methods |
| `method($name)` / `hasMethod($name)` | `ReflectedMethod` / `bool` | `method()` throws `MethodNotFound` |
| `constructor()` | `?ReflectedMethod` | `null` when there is none |
| `properties()` | `PropertyCollection` | All properties, including inherited non-private ones |
| `property($name)` / `hasProperty($name)` | `ReflectedProperty` / `bool` | `property()` throws `PropertyNotFound` |
| `constants()` | `ConstantCollection` | All constants and enum cases |
| `constant($name)` / `hasConstant($name)` | `ReflectedConstant` / `bool` | `constant()` throws `ConstantNotFound` |
| `defaultProperties()` | `array<string, mixed>` | Default values of declared properties, static ones included |
| `staticPropertyValue($name)` | `mixed` | Throws `PropertyNotFound` when there is no such static property |

Attributes work as on every declaration: see [Attributes](attributes.md).

### Source

| Method | Returns | Description |
|---|---|---|
| `fileName()` | `?string` | `null` for internal classes |
| `startLine()` / `endLine()` | `?int` | `null` for internal classes |
| `docComment()` | `?string` | Including the comment markers; `null` when there is none |

### Instantiation

| Method | Returns | Description |
|---|---|---|
| `newInstance(...$arguments)` | `T` | Runs the constructor; named arguments are supported. Throws `ClassNotInstantiable` |
| `newInstanceWithoutConstructor()` | `T` | Works with non-public constructors. Throws `ClassNotInstantiable` for abstract and non-standard classes |
| `newLazyGhost($initializer)` / `newLazyProxy($factory)` | `T` | PHP 8.4+, see [PHP 8.4 features](php-8.4.md) |
| `isUninitializedLazyObject($object)` | `bool` | PHP 8.4+; always false before |

```php
$user = ReflectedClass::from(User::class)->newInstance(email: 'ada@example.com');   // typed as User
```

## ReflectedEnum

Reflects an enum and its cases. Cases are `ReflectedConstant`s, the same objects `ReflectedClass::constants()`
returns. For everything an enum shares with classes, such as methods, interfaces or its source location, use
`asClass()`.

```php
use Refract\ReflectedEnum;

$enum = ReflectedEnum::from(Status::class);       // or ReflectedEnum::from(Status::Active)
$enum->case('Active')->backingValue();            // 'active'
```

| Method | Returns | Description |
|---|---|---|
| `name()` / `shortName()` / `qualifiedName()` | `string` | As on `ReflectedClass` |
| `isBacked()` | `bool` | Whether cases have `int` or `string` values |
| `backingType()` | `?ReflectedType` | `int` or `string`; `null` for a pure enum |
| `cases()` | `ConstantCollection` | All cases, in declaration order |
| `case($name)` / `hasCase($name)` | `ReflectedConstant` / `bool` | `case()` throws `ConstantNotFound` |
| `asClass()` | `ReflectedClass` | The enum as a class |

`ReflectedEnum::from()` throws `EnumNotFound` for any name that is not an enum, including unknown names.
