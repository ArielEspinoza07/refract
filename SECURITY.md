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

Refract wraps PHP's native reflection API and does not execute code on its own. Issues in scope include, for
example:

- Refract invoking methods, constructors, or property hooks, or reading private state, when the API contract says
  it only inspects.
- Refract bypassing visibility or `readonly` guarantees in ways that are not explicitly documented and opted into.
- Denial of service through unbounded recursion or memory use when inspecting crafted classes or types.

Out of scope:

- Vulnerabilities in PHP itself or in its native `Reflection*` classes. Report those to the
  [PHP project](https://github.com/php/php-src/security).
- Vulnerabilities in development dependencies (Pest, PHPStan, Pint) that do not affect the published package.
- Applications that pass untrusted input as class, method, or property names into Refract. Validating that input is
  the application's responsibility.
