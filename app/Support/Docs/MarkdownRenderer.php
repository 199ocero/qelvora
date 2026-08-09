<?php

namespace App\Support\Docs;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Renders a documentation Markdown file to HTML, injecting stable anchor ids on
 * its section headings and returning the heading outline used for the "on this
 * page" table of contents.
 */
class MarkdownRenderer
{
    protected MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            // Docs are authored by the team, so raw HTML in a page is trusted.
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'renderer' => [
                'soft_break' => "\n",
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new FrontMatterExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Render a Markdown string.
     *
     * @return array{frontMatter: array<string, mixed>, html: string, headings: list<array{id: string, text: string, level: int}>}
     */
    public function render(string $markdown): array
    {
        $result = $this->converter->convert($markdown);

        $frontMatter = $result instanceof RenderedContentWithFrontMatter
            ? (array) $result->getFrontMatter()
            : [];

        [$html, $headings] = $this->anchorHeadings($result->getContent());

        return [
            'frontMatter' => $frontMatter,
            'html' => $html,
            'headings' => $headings,
        ];
    }

    /**
     * Add unique `id` attributes to every h2/h3 and collect them as an outline.
     *
     * @return array{0: string, 1: list<array{id: string, text: string, level: int}>}
     */
    protected function anchorHeadings(string $html): array
    {
        if (trim($html) === '') {
            return ['', []];
        }

        $dom = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        // The XML prolog forces UTF-8 so multibyte heading text survives the round-trip.
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//h2 | //h3');

        /** @var list<array{id: string, text: string, level: int}> $headings */
        $headings = [];
        $used = [];

        foreach ($nodes ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $text = trim($node->textContent);
            $slug = Str::slug($text) ?: 'section';
            $candidate = $slug;
            $suffix = 1;

            while (in_array($candidate, $used, true)) {
                $candidate = $slug.'-'.(++$suffix);
            }

            $used[] = $candidate;
            $node->setAttribute('id', $candidate);

            $headings[] = [
                'id' => $candidate,
                'text' => $text,
                'level' => (int) substr($node->nodeName, 1),
            ];
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $rendered = '';

        if ($body !== null) {
            foreach ($body->childNodes as $child) {
                $rendered .= (string) $dom->saveHTML($child);
            }
        }

        return [$rendered, $headings];
    }
}
