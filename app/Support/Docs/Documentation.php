<?php

namespace App\Support\Docs;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;

/**
 * Reads the Markdown documentation library from resources/docs, builds the
 * grouped navigation, and renders individual pages. Adding a new page is just
 * dropping another `.md` file with front matter into that directory.
 */
class Documentation
{
    /**
     * @var Collection<int, Document>|null
     */
    protected ?Collection $documents = null;

    public function __construct(protected MarkdownRenderer $renderer) {}

    /**
     * All documents, ordered by their front-matter `order`.
     *
     * @return Collection<int, Document>
     */
    public function all(): Collection
    {
        return $this->documents ??= $this->load();
    }

    /**
     * Find a single document by its slug (the file name without extension).
     */
    public function find(string $slug): ?Document
    {
        return $this->all()->firstWhere('slug', $slug);
    }

    /**
     * The navigation tree, grouped by section in document order.
     *
     * @return list<array{title: string, items: list<array{title: string, slug: string, description: string, href: string}>}>
     */
    public function navigation(): array
    {
        $sections = [];

        foreach ($this->all()->groupBy(fn (Document $document): string => $document->section) as $title => $documents) {
            $items = [];

            foreach ($documents as $document) {
                $items[] = [
                    'title' => $document->title,
                    'slug' => $document->slug,
                    'description' => $document->description,
                    'href' => route('docs.show', $document->slug),
                ];
            }

            $sections[] = [
                'title' => (string) $title,
                'items' => $items,
            ];
        }

        return $sections;
    }

    /**
     * Render a document to HTML with its heading outline.
     *
     * @return array{html: string, headings: list<array{id: string, text: string, level: int}>}
     */
    public function render(Document $document): array
    {
        $rendered = $this->renderer->render(File::get($this->pathFor($document->slug)));

        return [
            'html' => $rendered['html'],
            'headings' => $rendered['headings'],
        ];
    }

    /**
     * The previous/next documents surrounding the given one, for pager links.
     *
     * @return array{prev: ?array{title: string, href: string}, next: ?array{title: string, href: string}}
     */
    public function neighbors(Document $document): array
    {
        $documents = $this->all()->values();
        $index = $documents->search(fn (Document $candidate): bool => $candidate->slug === $document->slug);

        return [
            'prev' => is_int($index) && $index > 0
                ? $this->link($documents[$index - 1])
                : null,
            'next' => is_int($index) && $index < $documents->count() - 1
                ? $this->link($documents[$index + 1])
                : null,
        ];
    }

    /**
     * @return array{title: string, href: string}
     */
    protected function link(Document $document): array
    {
        return [
            'title' => $document->title,
            'href' => route('docs.show', $document->slug),
        ];
    }

    /**
     * Load and parse every Markdown file's front matter.
     *
     * @return Collection<int, Document>
     */
    protected function load(): Collection
    {
        $directory = $this->directory();

        if (! File::isDirectory($directory)) {
            return collect();
        }

        $parser = (new FrontMatterExtension)->getFrontMatterParser();

        return collect(File::files($directory))
            ->filter(fn ($file): bool => $file->getExtension() === 'md')
            ->map(function ($file) use ($parser): Document {
                $slug = $file->getFilenameWithoutExtension();
                $frontMatter = (array) ($parser->parse(File::get($file->getPathname()))->getFrontMatter() ?? []);

                return new Document(
                    slug: $slug,
                    title: (string) ($frontMatter['title'] ?? Str::headline($slug)),
                    description: (string) ($frontMatter['description'] ?? ''),
                    section: (string) ($frontMatter['section'] ?? 'Documentation'),
                    order: (int) ($frontMatter['order'] ?? 999),
                );
            })
            ->sortBy(fn (Document $document): int => $document->order)
            ->values();
    }

    protected function pathFor(string $slug): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.$slug.'.md';
    }

    protected function directory(): string
    {
        return resource_path('docs');
    }
}
