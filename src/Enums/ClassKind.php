<?php

declare(strict_types=1);

namespace Refract\Enums;

/**
 * The kind of class-like structure being reflected.
 *
 * Abstract and anonymous classes are both `Standard`; use the dedicated
 * modifiers on ReflectedClass to tell them apart.
 */
enum ClassKind
{
    case Standard;
    case Interface;
    case Trait;
    case Enum;
}
