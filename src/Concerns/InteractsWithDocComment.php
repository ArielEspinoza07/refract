<?php

declare(strict_types=1);

namespace Refract\Concerns;

/**
 * The single implementation of docComment(), shared by every declaration that can have one.
 *
 * @internal
 */
trait InteractsWithDocComment
{
    /**
     * The doc comment above the declaration, including the comment markers, or null when there is none.
     */
    public function docComment(): ?string
    {
        $comment = $this->native()->getDocComment();

        return $comment === false ? null : $comment;
    }
}
