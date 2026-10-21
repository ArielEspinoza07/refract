<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use Throwable;

/**
 * Implemented by every exception Refract throws, so all of them can be caught at once.
 */
interface RefractException extends Throwable {}
