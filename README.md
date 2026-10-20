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

A fluent, immutable API for PHP's native reflection.

> [!WARNING]
> Refract is in early development. There is no tagged release yet and the public API is still being designed.
> Expect breaking changes until `1.0.0`.

## Why Refract

PHP's `Reflection*` classes are powerful but verbose: filtering methods by attribute, resolving union types, or
reading promoted constructor parameters usually means loops, `instanceof` checks, and `ReflectionException`
handling. Refract wraps the native API in a small, chainable layer that keeps the same behavior while making
common inspection tasks read like a sentence.

Refract is framework-agnostic and has no runtime dependencies.

## Requirements

> **[PHP 8.3+](https://php.net/releases/)**

## Installation

Refract is not published on Packagist yet. Once it is:

```bash
composer require arielespinoza07/refract
```

## Usage

Documentation and examples will be added alongside the first public API.

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
| `composer test:coverage` | Enforces 100% type coverage                      |
| `composer check`         | Runs lint, analyse, and test                     |

## Versioning

Refract follows [Semantic Versioning](https://semver.org). See [CHANGELOG.md](CHANGELOG.md) for release notes.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## Security

Please report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## License

[MIT License](LICENSE)
