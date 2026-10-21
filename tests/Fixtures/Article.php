<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

/**
 * Covers every member modifier the collection filters can select on.
 *
 * Kept non-final so Pint does not turn its protected members into private ones.
 *
 * @extensible
 */
class Article extends Model
{
    #[Tag]
    public const string TYPE = 'article';

    /** The rendered output format. */
    final public const string FORMAT = 'html';

    protected const int LIMIT = 10;

    private const int STEP = 1;

    public static int $published = 0;

    public readonly string $title;

    #[Tag]
    protected ?string $summary = null;

    private int $views = 0;

    public function __construct(
        public readonly string $slug,
        #[Tag] string $title,
    ) {
        $this->title = $title;
    }

    public static function published(): int
    {
        return self::$published;
    }

    #[Tag]
    public function show(): void
    {
        $this->track();
    }

    final public function publish(): void
    {
        self::$published++;
    }

    #[Label('first'), Label(value: 'second')]
    protected function render(): string
    {
        return $this->summary ?? $this->title;
    }

    private function track(): void
    {
        $this->views += self::STEP;
    }
}
