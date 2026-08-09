---
paths:
  - 'resources/docs/**'
---

# Docs

## Public docs are markdown files in resources/docs
Public product docs live at /docs (DocsController + App\Support\Docs\Documentation). Each page is a resources/docs/<slug>.md file with front matter: title, description, section, order. To add a page, drop a new .md file — nav (grouped by `section`), ordering, prev/next, and the on-this-page outline are all derived automatically; no code or route changes needed. Rendering (CommonMark + GFM + front matter) and h2/h3 anchor ids are in App\Support\Docs\MarkdownRenderer. The Vue page is resources/js/pages/docs/Show.vue — after adding it (or any new Inertia page) run `npm run build` or tests 500 with a Vite manifest error.
