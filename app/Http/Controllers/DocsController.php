<?php

namespace App\Http\Controllers;

use App\Support\Docs\Documentation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    /**
     * Redirect the documentation root to the first page.
     */
    public function index(Documentation $docs): RedirectResponse
    {
        $first = $docs->all()->first();

        abort_if($first === null, 404);

        return redirect()->route('docs.show', $first->slug);
    }

    /**
     * Show a single documentation page with its navigation and outline.
     */
    public function show(string $page, Documentation $docs): Response
    {
        $document = $docs->find($page);

        abort_if($document === null, 404);

        $rendered = $docs->render($document);
        $neighbors = $docs->neighbors($document);

        return Inertia::render('docs/Show', [
            'navigation' => $docs->navigation(),
            'page' => [
                'slug' => $document->slug,
                'title' => $document->title,
                'description' => $document->description,
                'section' => $document->section,
                'html' => $rendered['html'],
                'headings' => $rendered['headings'],
            ],
            'prev' => $neighbors['prev'],
            'next' => $neighbors['next'],
        ]);
    }
}
