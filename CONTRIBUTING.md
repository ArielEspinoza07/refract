# Contributing to Refract

Thanks for considering a contribution. Refract aims to be a small, predictable, fluent layer over PHP's native
reflection, so the bar for new public API is deliberately high. Bug fixes, tests, and documentation improvements
are always welcome.

## Before you start

- **Bugs:** open an issue using the bug report template, with a minimal reproduction snippet.
- **Features:** open a feature request first and wait for feedback before writing code. Public API is a long-term
  contract, and a PR that adds API without prior discussion may be declined even if the code is good.
- **Security issues:** do **not** open a public issue. Follow [SECURITY.md](SECURITY.md).

## Development setup

Requirements: PHP 8.3+ and Composer.

```bash
git clone https://github.com/arielespinoza07/refract
cd refract
composer install
```

## Quality checks

Every PR must pass the same checks CI runs:

| Command                 | What it does                                    |
|-------------------------|-------------------------------------------------|
| `composer lint`         | Checks code style with Pint (no changes made)   |
| `composer lint:fix`     | Fixes code style with Pint                      |
| `composer analyse`      | Runs PHPStan at level `max` on `src` and `tests` |
| `composer test`         | Runs the Pest test suite                        |
| `composer test:types`   | Enforces 100% type coverage                     |
| `composer test:coverage`| Enforces 100% line coverage (needs Xdebug or PCOV; with Xdebug, set `XDEBUG_MODE=coverage`) |
| `composer test:mutate`  | Runs mutation testing and enforces a 95% score (needs Xdebug or PCOV; takes a few minutes) |
| `composer bench`        | Compares Refract with native reflection using PHPBench; Xdebug is switched off for the measured runs |
| `composer check`        | Runs lint, analyse, type coverage, and test in sequence |

Run `composer bench` before and after a change that could affect performance, and include both results in the
pull request.

Run `composer check` before pushing. CI also runs the test suite on PHP 8.3, 8.4, and 8.5 with both lowest and
stable dependencies, so avoid language features or functions that don't exist on 8.3.

### Supporting features newer than PHP 8.3

PHPStan analyses against PHP 8.3 to 8.5 (see `phpVersion` in `phpstan.neon`), whichever PHP runs it.

- Guard every call to a newer native method with `method_exists()`, written as one expression with its
  fallback, e.g. `method_exists($this->native, 'isVirtual') && $this->native->isVirtual() === true`. This keeps
  PHPStan happy on every version and keeps line coverage meaningful.
- Methods that only inspect return a safe value on older versions (`false`, an empty collection). Methods
  that create something throw `FeatureNotSupported`.
- Fixtures using newer syntax go in `tests/Fixtures/Php84`, which PHPStan and Pint skip. Reference them by
  class name string in tests, and skip those tests on older versions with `->skip(PHP_VERSION_ID < 80400, ...)`.

## Coding standards

Pint enforces most of these automatically; the rest are reviewed by hand.

- `declare(strict_types=1);` in every PHP file.
- Classes are `final` by default. Opening a class for extension needs a concrete reason.
- Prefer immutable, `readonly` objects. Fluent methods return new instances rather than mutating state.
- No `@phpstan-ignore` or baseline entries without a comment explaining why the error is a false positive.
- Throw dedicated exception types from the `Refract` namespace instead of generic `\Exception` or
  `\RuntimeException`, and do not leak raw `\ReflectionException` through the public API.
- Public methods get an English docblock when the signature alone doesn't explain the behavior, edge cases, or
  thrown exceptions. Don't add docblocks that only repeat the type hints.
- Name things after what they do. Avoid `Helper`, `Util`, `Manager`, and similar catch-all names.

## Tests

- Tests are written with [Pest](https://pestphp.com) and live in `tests/`.
- Every bug fix starts with a failing regression test.
- Every new public method needs tests for the success path, failure paths, and relevant edge cases (e.g. enums,
  readonly properties, promoted constructor parameters, union/intersection/DNF types, attributes, anonymous
  classes, closures).
- Test behavior through the public API, not internal implementation details.
- Put fixture classes in `tests/Fixtures` instead of defining them inline in test files.

## Commits and pull requests

1. Fork the repository and create a branch from `main` (e.g. `fix/nullable-union-types`, `feat/attribute-filter`).
2. Keep each PR focused on one change. Separate refactors from behavior changes.
3. Write commit messages in the imperative mood (`Add method filter by attribute`, not `Added…`).
4. Add an entry under `## [Unreleased]` in [CHANGELOG.md](CHANGELOG.md) for any user-facing change.
5. Fill in the pull request template and make sure `composer check` passes.

## Backward compatibility

Refract follows [Semantic Versioning](https://semver.org). Until `1.0.0`, minor versions may contain breaking
changes, but they must be called out in the changelog. From `1.0.0` on, anything not marked `@internal` is public
API and can only break in a major release.

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
