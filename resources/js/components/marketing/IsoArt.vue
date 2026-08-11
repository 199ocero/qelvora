<script setup lang="ts">
/**
 * Line-art illustrations for the pillar section. Each one is drawn to match its
 * copy rather than being decorative:
 *   - `server`: a stack of units with a highlighted control panel on top
 *     ("runs on your own account").
 *   - `timeline`: an event history of connected nodes with rows
 *     ("track every message").
 *   - `shield`: a guard emblem with a check ("protect your reputation").
 * They float on an infinite loop; `phase` offsets neighbours so a row reads
 * up / down / up.
 */
defineProps<{
    variant: 'server' | 'timeline' | 'shield';
    /** Float phase: `a` rises first, `b` sinks first, for an offset loop. */
    phase?: 'a' | 'b';
}>();

// Shared shield outline, reused for the front face and the depth shadow.
const shieldPath =
    'M58,62 L142,62 L140,104 C138,128 118,143 100,151 C82,143 62,128 60,104 Z';

// Timeline rows: y position and delivery state, oldest at the top.
const timelineRows: Array<{ y: number; state: 'done' | 'active' | 'pending' }> =
    [
        { y: 52, state: 'done' },
        { y: 88, state: 'done' },
        { y: 124, state: 'active' },
        { y: 160, state: 'pending' },
    ];
</script>

<template>
    <div class="relative flex h-52 items-center justify-center">
        <svg
            viewBox="0 0 200 200"
            class="relative h-full w-full text-foreground"
            :class="phase === 'b' ? 'iso-float-b' : 'iso-float-a'"
            fill="none"
            stroke-linejoin="round"
            stroke-linecap="round"
            aria-hidden="true"
        >
            <!-- ===================== SERVER STACK ===================== -->
            <template v-if="variant === 'server'">
                <!-- body: two front faces + unit dividers -->
                <path
                    d="M54,72 L100,95 L100,155 L54,132 Z"
                    fill="currentColor"
                    fill-opacity="0.02"
                    stroke="currentColor"
                    stroke-opacity="0.4"
                />
                <path
                    d="M146,72 L100,95 L100,155 L146,132 Z"
                    fill="currentColor"
                    fill-opacity="0.05"
                    stroke="currentColor"
                    stroke-opacity="0.4"
                />
                <path
                    d="M54,92 L100,115 M146,92 L100,115 M54,112 L100,135 M146,112 L100,135"
                    stroke="currentColor"
                    stroke-opacity="0.25"
                />
                <!-- control panel on top -->
                <path
                    d="M100,49 L146,72 L100,95 L54,72 Z"
                    fill="var(--primary)"
                    fill-opacity="0.14"
                    stroke="var(--primary)"
                    stroke-opacity="0.75"
                />
                <circle cx="86" cy="72" r="2.4" fill="var(--primary)" />
                <circle cx="100" cy="72" r="2.4" fill="var(--primary)" />
                <circle cx="114" cy="72" r="2.4" fill="var(--primary)" />
            </template>

            <!-- ===================== EVENT TIMELINE =================== -->
            <template v-else-if="variant === 'timeline'">
                <!-- spine: solid through the completed events, faint after -->
                <path
                    d="M62,52 L62,124"
                    stroke="var(--primary)"
                    stroke-opacity="0.55"
                    stroke-width="1.5"
                />
                <path
                    d="M62,124 L62,160"
                    stroke="currentColor"
                    stroke-opacity="0.25"
                    stroke-width="1.5"
                />

                <template v-for="row in timelineRows" :key="row.y">
                    <!-- active node gets an outer ring -->
                    <circle
                        v-if="row.state === 'active'"
                        cx="62"
                        :cy="row.y"
                        r="8"
                        stroke="var(--primary)"
                        stroke-opacity="0.35"
                    />
                    <circle
                        cx="62"
                        :cy="row.y"
                        r="4.5"
                        :fill="
                            row.state === 'pending'
                                ? 'var(--card)'
                                : 'var(--primary)'
                        "
                        :stroke="
                            row.state === 'pending'
                                ? 'currentColor'
                                : 'var(--primary)'
                        "
                        :stroke-opacity="row.state === 'pending' ? 0.4 : 1"
                    />

                    <!-- event label + timestamp rows -->
                    <rect
                        x="84"
                        :y="row.y - 8"
                        width="72"
                        height="6"
                        rx="3"
                        fill="currentColor"
                        :fill-opacity="
                            row.state === 'active'
                                ? 0.7
                                : row.state === 'pending'
                                  ? 0.28
                                  : 0.45
                        "
                    />
                    <rect
                        x="84"
                        :y="row.y + 2"
                        width="36"
                        height="4"
                        rx="2"
                        fill="currentColor"
                        fill-opacity="0.22"
                    />
                </template>
            </template>

            <!-- ======================= SHIELD ======================== -->
            <template v-else>
                <!-- depth shadow -->
                <path
                    :d="shieldPath"
                    transform="translate(5 6)"
                    stroke="currentColor"
                    stroke-opacity="0.14"
                />
                <!-- front face -->
                <path
                    :d="shieldPath"
                    fill="currentColor"
                    fill-opacity="0.04"
                    stroke="currentColor"
                    stroke-opacity="0.5"
                />
                <!-- check -->
                <path
                    d="M82,98 L96,113 L122,80"
                    stroke="var(--primary)"
                    stroke-opacity="0.9"
                    stroke-width="3"
                />
            </template>
        </svg>
    </div>
</template>
