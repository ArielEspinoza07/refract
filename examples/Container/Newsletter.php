<?php

declare(strict_types=1);

namespace Refract\Examples\Container;

final readonly class Newsletter
{
    public function __construct(
        public Mailer $mailer,
        public ?Clock $clock = null,
    ) {}
}
