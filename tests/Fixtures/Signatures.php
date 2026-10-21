<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

use Closure;
use Generator;

/**
 * Covers every parameter and method feature the function reflections can inspect.
 */
final class Signatures
{
    /** @var list<string> */
    private array $sent = [];

    public function __construct(
        public readonly int $id = 0,
    ) {}

    public function __destruct() {}

    public function __invoke(int $value): int
    {
        return $value;
    }

    public static function make(): self
    {
        return new self();
    }

    public function merge(self $other): self
    {
        return $other;
    }

    /** Sends a message. */
    public function send(#[Tag] string $to, ?string $subject = null, int &$attempts = 0, string ...$cc): bool
    {
        $attempts++;
        $this->sent[] = $to;

        return $subject !== null || $cc !== [];
    }

    /**
     * @return list<string>
     */
    public function &sent(): array
    {
        return $this->sent;
    }

    /**
     * @return Generator<int, int>
     */
    public function numbers(): Generator
    {
        yield 1;
    }

    /**
     * A closure bound to this instance and scoped to this class.
     */
    public function boundClosure(): Closure
    {
        return fn (): int => $this->id;
    }
}
