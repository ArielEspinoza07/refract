<?php

declare(strict_types=1);

namespace Refract\Concerns;

/**
 * The single implementation of where a declaration comes from, shared by classes and functions.
 *
 * Internal declarations, defined by PHP itself or an extension, have no file or lines.
 *
 * @internal
 */
trait InteractsWithSource
{
    /**
     * Whether the declaration is defined by PHP itself or an extension, like stdClass or strlen().
     */
    public function isInternal(): bool
    {
        return $this->native()->isInternal();
    }

    /**
     * Whether the declaration is defined in PHP code, as opposed to PHP itself or an extension.
     */
    public function isUserDefined(): bool
    {
        return $this->native()->isUserDefined();
    }

    /**
     * The file the declaration is in, or null for internal declarations.
     */
    public function fileName(): ?string
    {
        $fileName = $this->native()->getFileName();

        return $fileName === false ? null : $fileName;
    }

    /**
     * The line the declaration starts on, or null for internal declarations.
     */
    public function startLine(): ?int
    {
        $line = $this->native()->getStartLine();

        return $line === false ? null : $line;
    }

    /**
     * The line the declaration ends on, or null for internal declarations.
     */
    public function endLine(): ?int
    {
        $line = $this->native()->getEndLine();

        return $line === false ? null : $line;
    }
}
