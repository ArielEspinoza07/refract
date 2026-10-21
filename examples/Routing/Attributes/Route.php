<?php

declare(strict_types=1);

namespace Refract\Examples\Routing\Attributes;

use Attribute;
use Refract\Examples\Routing\HttpMethod;

/**
 * Maps a public controller method to an HTTP verb and path. Path segments like {id} are passed to the
 * method parameter with the same name. Repeat it to map one method to several routes.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Route
{
    public function __construct(
        public HttpMethod $verb,
        public string $path = '',
    ) {}
}
