import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Reveal elements marked with `data-reveal` as they scroll into view.
 *
 * Add `data-reveal` to any element inside the mounted tree; give it an optional
 * per-element stagger with an inline `--reveal-delay` custom property. Elements
 * already on screen at mount are revealed immediately so there is no flash of
 * empty page. Falls back to fully visible when IntersectionObserver is missing
 * or the user prefers reduced motion.
 */
export function useScrollReveal(): void {
    let observer: IntersectionObserver | null = null;

    onMounted(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const elements = Array.from(
            document.querySelectorAll<HTMLElement>('[data-reveal]'),
        );

        const prefersReducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (prefersReducedMotion || !('IntersectionObserver' in window)) {
            elements.forEach((element) => element.classList.add('is-revealed'));

            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        observer?.unobserve(entry.target);
                    }
                }
            },
            { rootMargin: '0px 0px -10% 0px', threshold: 0.08 },
        );

        elements.forEach((element) => observer?.observe(element));
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
        observer = null;
    });
}
