<?php

namespace App\Support\Docs;

/**
 * Metadata for a single documentation page, read from a Markdown file's
 * front matter. Rendered content is resolved separately by Documentation.
 */
final readonly class Document
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $description,
        public string $section,
        public int $order,
    ) {}
}
