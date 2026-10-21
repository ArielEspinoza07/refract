<?php

declare(strict_types=1);

namespace Refract\Enums;

/**
 * The shape of a declared type.
 *
 * A nullable type like `?int` is Named; `int|null` and DNF types like `(A&B)|null` are Union.
 */
enum TypeKind
{
    case Named;
    case Union;
    case Intersection;
}
