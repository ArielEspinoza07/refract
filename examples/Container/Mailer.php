<?php

declare(strict_types=1);

namespace Refract\Examples\Container;

final readonly class Mailer
{
    public function __construct(
        public Logger $logger,
        public string $from = 'noreply@example.com',
    ) {}
}
