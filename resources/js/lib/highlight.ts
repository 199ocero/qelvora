import hljs from 'highlight.js/lib/core';
import bash from 'highlight.js/lib/languages/bash';
import javascript from 'highlight.js/lib/languages/javascript';
import php from 'highlight.js/lib/languages/php';

/**
 * Syntax highlighting for the landing page code sample, backed by highlight.js.
 * Only the three languages the sample uses are registered, so the marketing
 * bundle stays small. The token colors themselves live in `app.css` as `.hljs`
 * rules mapped onto the project palette.
 */
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('php', php);

export type HighlightLanguage = 'bash' | 'javascript' | 'php';

/** Return highlight.js token markup for `code`. Output is HTML-escaped. */
export function highlight(code: string, language: HighlightLanguage): string {
    return hljs.highlight(code, { language }).value;
}
