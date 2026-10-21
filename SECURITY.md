# Security Policy

## Supported versions

Refract is pre-1.0. Only the latest released minor version receives security fixes.

| Version         | Supported |
|-----------------|-----------|
| Latest `0.x`    | Yes       |
| Older `0.x`     | No        |

## Reporting a vulnerability

**Please do not report security vulnerabilities through public GitHub issues, discussions, or pull requests.**

Report them privately using one of these channels:

1. **Preferred:** GitHub's [private vulnerability reporting](https://github.com/arielespinoza07/refract/security/advisories/new).
2. **Alternative:** email [arielespinoza46@gmail.com](mailto:arielespinoza46@gmail.com) with the subject
   `[Refract security]`.

Please include:

- The affected Refract version and PHP version.
- A description of the issue and its potential impact.
- A minimal proof of concept or steps to reproduce.
- Any known workaround.

## What to expect

- **Acknowledgement** within 3 business days.
- **Initial assessment** within 7 days, including whether the report is accepted as a vulnerability.
- **Fix and release** as soon as practical, depending on severity. You will be kept informed of progress.
- **Disclosure** through a GitHub Security Advisory once a fixed version is available. Reporters are credited
  unless they prefer to remain anonymous.

## Scope

Refract wraps PHP's native reflection API. Most of its API only inspects declarations. The members below are the
exceptions: they run code or read private state **by design**, only when you call them, and are documented as such.

| Member | What it does beyond inspecting |
|---|---|
| `ReflectedClass::newInstance()` | Runs the constructor |
| `ReflectedClass::newInstanceWithoutConstructor()` | Creates an object without its constructor; its destructor still runs later |
| `ReflectedClass::newLazyGhost()`, `newLazyProxy()` | Run the given initializer or factory on first access to the object |
| `ReflectedClass::staticPropertyValue()` | Reads a static property, regardless of visibility |
| `ReflectedMethod::invoke()`, `closure()` | Call the method, or return it as a callable, regardless of visibility |
| `ReflectedFunction::invoke()`, `closure()` | Call the function, or return it as a callable |
| `ReflectedProperty::value()` | Reads the property regardless of visibility; on PHP 8.4, this runs its `get` hook |
| `ReflectedAttribute::newInstance()` | Runs the attribute's constructor |
| `ReflectedAttribute::arguments()`, `ReflectedParameter::defaultValue()` | Evaluate the declared expressions, which can contain `new` (PHP 8.1+) and so run constructors |

Looking up a class by name, through `from()`, `tryFrom()`, `isSubclassOf()` or `implements()`, triggers autoloading,
which loads that class's file.

Issues in scope include, for example:

- Any member not listed above running code or reading private state.
- Refract bypassing visibility or `readonly` guarantees in ways that are not documented above.
- `tryFrom()` throwing, emitting warnings, or crashing on malformed input instead of returning `null`.
- Denial of service through unbounded recursion or memory use when inspecting crafted classes or types.

Out of scope:

- Vulnerabilities in PHP itself or in its native `Reflection*` classes. Report those to the
  [PHP project](https://github.com/php/php-src/security).
- Vulnerabilities in development dependencies (Pest, PHPStan, Pint) that do not affect the published package.
- Applications that let untrusted input choose which classes, methods or properties to reflect. `tryFrom()` handles
  such input safely, returning `null` instead of failing, but loading an attacker-chosen class still runs the
  autoloader, and the members listed above run code. Restricting names to a known set is the application's
  responsibility.
