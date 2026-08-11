<script setup lang="ts">
/**
 * Small animated line-art glyphs for the feature grid, one per feature. Each
 * carries a subtle looping animation that hints at what it does: a live pulse,
 * a rotating clock hand, a travelling webhook signal, and so on. Drawn on a
 * 48x48 canvas in the same stroke language as the pillar illustrations.
 */
defineProps<{
    variant:
        | 'tracking'
        | 'templates'
        | 'suppression'
        | 'domains'
        | 'schedule'
        | 'webhooks';
}>();
</script>

<template>
    <svg
        viewBox="0 0 48 48"
        class="text-foreground"
        fill="none"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <!-- ===================== LIVE TRACKING ==================== -->
        <template v-if="variant === 'tracking'">
            <path d="M6,24 H42" stroke="currentColor" stroke-opacity="0.18" />
            <path
                class="glyph-pulse"
                d="M6,24 H15 L19,13 L24,35 L28,21 L32,24 H42"
                stroke="var(--primary)"
                stroke-width="2"
            />
        </template>

        <!-- ====================== TEMPLATES ====================== -->
        <template v-else-if="variant === 'templates'">
            <rect
                x="13"
                y="8"
                width="22"
                height="32"
                rx="3"
                stroke="currentColor"
                stroke-opacity="0.55"
            />
            <path
                d="M18,16 H30 M18,21 H30"
                stroke="currentColor"
                stroke-opacity="0.3"
            />
            <rect
                class="glyph-pulse"
                x="17"
                y="27"
                width="14"
                height="6"
                rx="2"
                fill="var(--primary)"
                fill-opacity="0.2"
                stroke="var(--primary)"
            />
        </template>

        <!-- ===================== SUPPRESSION ===================== -->
        <template v-else-if="variant === 'suppression'">
            <g class="glyph-pulse">
                <circle
                    cx="24"
                    cy="24"
                    r="15"
                    stroke="currentColor"
                    stroke-opacity="0.55"
                />
                <path
                    d="M14,14 L34,34"
                    stroke="var(--primary)"
                    stroke-width="2.2"
                />
            </g>
        </template>

        <!-- ======================= DOMAINS ======================= -->
        <template v-else-if="variant === 'domains'">
            <g class="glyph-float">
                <circle
                    cx="21"
                    cy="21"
                    r="14"
                    stroke="currentColor"
                    stroke-opacity="0.55"
                />
                <path
                    d="M7,21 H35"
                    stroke="currentColor"
                    stroke-opacity="0.3"
                />
                <ellipse
                    cx="21"
                    cy="21"
                    rx="6.5"
                    ry="14"
                    stroke="currentColor"
                    stroke-opacity="0.3"
                />
                <circle cx="35" cy="35" r="8" fill="var(--card)" />
                <circle
                    cx="35"
                    cy="35"
                    r="8"
                    stroke="var(--primary)"
                    stroke-opacity="0.9"
                />
                <path
                    d="M31,35 L34,38 L39,32"
                    stroke="var(--primary)"
                    stroke-width="2"
                />
            </g>
        </template>

        <!-- ======================= SCHEDULE ====================== -->
        <template v-else-if="variant === 'schedule'">
            <circle
                cx="24"
                cy="24"
                r="15"
                stroke="currentColor"
                stroke-opacity="0.55"
            />
            <path
                d="M24,24 L24,15"
                stroke="currentColor"
                stroke-opacity="0.7"
            />
            <g class="glyph-spin">
                <path
                    d="M24,24 L32,24"
                    stroke="var(--primary)"
                    stroke-width="2"
                />
            </g>
            <circle cx="24" cy="24" r="1.8" fill="var(--primary)" />
        </template>

        <!-- ======================= WEBHOOKS ====================== -->
        <template v-else>
            <path
                class="glyph-dash"
                d="M24,30 L12,16 M24,30 L36,16"
                stroke="var(--primary)"
                stroke-opacity="0.8"
            />
            <circle cx="24" cy="31" r="4.5" fill="var(--primary)" />
            <circle
                cx="12"
                cy="15"
                r="4"
                fill="var(--card)"
                stroke="currentColor"
                stroke-opacity="0.6"
            />
            <circle
                cx="36"
                cy="15"
                r="4"
                fill="var(--card)"
                stroke="currentColor"
                stroke-opacity="0.6"
            />
        </template>
    </svg>
</template>
