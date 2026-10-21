# Refract

<p>
  <a href="https://github.com/ArielEspinoza07/refract/actions">
    <img src="https://github.com/ArielEspinoza07/refract/actions/workflows/ci.yml/badge.svg"  alt="Build Status">
  </a>
  <a href="https://packagist.org/packages/arielespinoza07/refract">
    <img src="https://img.shields.io/packagist/dt/arielespinoza07/refract.svg?style=flat-square" alt="Total Downloads">
  </a>
  <a href="https://packagist.org/packages/arielespinoza07/refract">
    <img src="https://img.shields.io/packagist/v/arielespinoza07/refract.svg?style=flat-square" alt="Latest Stable Version">
  </a>
  <a href="https://github.com/ArielEspinoza07/refract/blob/main/LICENSE">
    <img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License">
  </a>
</p>

A fluent, immutable and fully typed API for PHP's native reflection: classes, enums, methods, functions,
properties, parameters, attributes and types.

> [!WARNING]
> Refract is in early development. There is no tagged release yet and the public API is still being designed.
> Expect breaking changes until `1.0.0`.

## Why Refract

PHP's `Reflection*` classes are powerful but verbose: filtering methods by attribute, resolving union types, or
reading promoted constructor parameters usually means loops, `instanceof` checks, and `ReflectionException`
handling. Refract wraps the native API in a small, chainable layer that keeps the same behavior while making
common inspection tasks read like a sentence:

- **Every declaration has its own entry point.** Reflect a method, property, parameter or attribute directly,
  without going through its class first.
- **Filterable collections.** Methods, properties, constants and parameters come back as immutable collections
  with filters like `public()`, `declaredBy()`, `promoted()` or `withAttribute()`.
- **Types you can query.** Inspect named, union, intersection and DNF types, and check whether a value satisfies
  one, the way PHP does with `strict_types=1`.
- **Precise for static analysis.** Generic types flow through, so PHPStan knows that
  `ReflectedClass::from(User::class)->newInstance()` returns a `User`.
- **Explicit errors.** Every failure throws its own exception, such as `MethodNotFound`, and all of them implement
  `RefractException`. Native `ReflectionException`s never reach your code.
- **PHP 8.4 ready.** Property hooks, asymmetric visibility and lazy objects are supported, with safe fallbacks on 8.3.

Refract is framework-agnostic and has no runtime dependencies.

## Requirements

> **[PHP 8.3+](https://php.net/releases/)**

## Installation

Refract is not published on Packagist yet. Once it is:

```bash
composer require arielespinoza07/refract
```

## Usage

Every type can be used on its own, and offers the same entry points:

| Method | Returns | When the target does not exist |
|---|---|---|
| `from(...)` | one reflection | throws a specific exception, e.g. `ClassNotFound` |
| `tryFrom(...)` | one reflection or `null` | returns `null`, safe for untrusted input |
| `collectFrom(...)` | a typed collection (members and parameters) | throws for an unknown class or function |
| `fromNative(...)` | wraps a native `Reflection*` object | — |

`native()` always returns the underlying native object.

### Classes

```php
use Refract\ReflectedClass;

$class = ReflectedClass::from(User::class);   // or ReflectedClass::from($user)

$class->name();                                // 'App\Models\User'
$class->kind();                                // ClassKind::Standard
$class->isFinal();
$class->parent()?->name();
$class->implements(Arrayable::class);

$class->methods()->public()->declaredBy(User::class)->names();   // ['save', 'delete', ...]
$class->properties()->promoted()->readonly();
$class->attribute(Table::class)->newInstance()->name;

$user = $class->newInstance(email: 'ada@example.com');   // typed as User for PHPStan
```

### Methods, properties and constants

```php
use Refract\ReflectedConstant;
use Refract\ReflectedMethod;
use Refract\ReflectedProperty;

$method = ReflectedMethod::from(User::class, 'save');
$method->parameters()->required();
$method->returnType()?->name();                // 'bool'
$method->invoke($user, force: true);           // works on private methods too

ReflectedMethod::collectFrom(User::class)->static()->withAttribute(Command::class);

$property = ReflectedProperty::from(User::class, 'email');
$property->type()?->allows('ada@example.com'); // true
$property->value($user);
$property->isInitialized($user);

ReflectedConstant::from(Status::class, 'Active')->backingValue();   // 'active'
```

### Functions, closures and parameters

```php
use Refract\ReflectedFunction;
use Refract\ReflectedParameter;

$function = ReflectedFunction::from(fn (int $id): User => User::find($id));
$function->parameter('id')->type()?->name();   // 'int'

// Parameters accept every callable form:
ReflectedParameter::from([UserController::class, 'show'], 'id');
ReflectedParameter::from('App\Support\format', 0);
ReflectedParameter::collectFrom(new SendInvoice())->withAttribute(Inject::class);
```

### Types

```php
$type = ReflectedProperty::from(Order::class, 'total')->type();

$type->kind();        // TypeKind::Union
$type->name();        // 'int|float'
$type->types();       // a collection of the member types
$type->allows(9.99);  // true: checked the way PHP does with strict_types=1

// The classes a type accepts, e.g. for dependency injection: ?Logger, (A&B)|C, static...
ReflectedParameter::from([UserController::class, '__construct'], 'logger')->type()?->classNames();   // ['Psr\Log\LoggerInterface']
```

### Enums and attributes

```php
use Refract\ReflectedAttribute;
use Refract\ReflectedEnum;

$enum = ReflectedEnum::from(Status::class);
$enum->backingType()?->name();                 // 'string'
$enum->case('Active')->value();                // Status::Active

ReflectedClass::from(Status::class)->asEnum(); // from a class reflection to an enum reflection

$routes = ReflectedAttribute::collectFrom(UserController::class, Route::class);
$routes->first()?->arguments();                // [0 => '/users'] or ['path' => '/users']
$routes->first()?->target();                   // AttributeTarget::ClassLike
```

### Errors

Every exception implements `Refract\Exceptions\RefractException`, so all of them can be caught at once. Each failure has
its own exception, such as `ClassNotFound`, `MethodNotFound` or `InstanceRequired`; native `ReflectionException`s never
reach your code.

Reflections cannot be serialized, so don't store them in a cache, a session or a queued job: `serialize()` throws
`NotSerializable`. Store the name you reflected instead, and call `from()` again; reflecting is cheap.

### PHP 8.4 features

Property hooks, virtual properties, asymmetric visibility and final or abstract properties are available through
`ReflectedProperty`, and lazy ghosts and proxies through `ReflectedClass::newLazyGhost()` and `newLazyProxy()`.
On PHP 8.3, the inspection methods return `false`, and creating a lazy object throws `FeatureNotSupported`.

## Development

```bash
composer install
composer check
```

| Command                  | What it does                                     |
|--------------------------|--------------------------------------------------|
| `composer lint`          | Checks code style with Pint                      |
| `composer lint:fix`      | Fixes code style with Pint                       |
| `composer analyse`       | Runs PHPStan at level `max`                      |
| `composer test`          | Runs the Pest test suite                         |
| `composer test:types`    | Enforces 100% type coverage                      |
| `composer test:coverage` | Enforces 100% line coverage (needs Xdebug or PCOV) |
| `composer check`         | Runs lint, analyse, type coverage, and test      |

## Versioning

Refract follows [Semantic Versioning](https://semver.org). See [CHANGELOG.md](CHANGELOG.md) for release notes.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## Security

Please report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## License

[MIT License](LICENSE)
