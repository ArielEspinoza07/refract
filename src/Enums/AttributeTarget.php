<?php

declare(strict_types=1);

namespace Refract\Enums;

use Attribute;

/**
 * The kind of declaration an attribute is placed on.
 *
 * Backed by the matching Attribute::TARGET_* value. `ClassLike` covers classes, interfaces,
 * traits and enums; PHP does not allow an enum case named `Class`.
 */
enum AttributeTarget: int
{
    case ClassLike = Attribute::TARGET_CLASS;
    case Function = Attribute::TARGET_FUNCTION;
    case Method = Attribute::TARGET_METHOD;
    case Property = Attribute::TARGET_PROPERTY;
    case ClassConstant = Attribute::TARGET_CLASS_CONSTANT;
    case Parameter = Attribute::TARGET_PARAMETER;
}
