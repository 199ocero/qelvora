<script setup lang="ts">
import {
    ChartColumn,
    CircleCheck,
    Send,
    TriangleAlert,
    Undo2,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * A faithful recreation of Xelqun's Email overview dashboard, used as the hero
 * visual. Card, icon-badge, and stat styling mirror `pages/mail/Dashboard.vue`.
 * The numbers count up on mount; the trend chart streams like a live monitor,
 * scrolling left while each new bar grows up from the baseline as it enters.
 * The stream is driven by requestAnimationFrame so it can freeze on hover,
 * letting a tooltip show each bar's send count.
 */
const tiles = [
    { icon: Send, label: 'Sent (30d)', target: 24318, decimals: 0, suffix: '' },
    {
        icon: CircleCheck,
        label: 'Delivery rate',
        target: 99.2,
        decimals: 1,
        suffix: '%',
    },
    {
        icon: Undo2,
        label: 'Bounce rate',
        target: 0.3,
        decimals: 1,
        suffix: '%',
    },
    {
        icon: TriangleAlert,
        label: 'Complaint rate',
        target: 0.0,
        decimals: 1,
        suffix: '%',
    },
];

const VISIBLE_BARS = 14;
const SLIDE_MS = 1400;
const DWELL_MS = 650;

interface Bar {
    id: number;
    height: number;
    sent: number;
    /** Once true, the bar animates up from the baseline to its height. */
    grown: boolean;
}

let nextId = 0;

/** A bar with a plausible daily-send count and a matching height (28%–98%). */
function makeBar(grown: boolean): Bar {
    const height = 28 + Math.round(Math.random() * 70);

    return { id: nextId++, height, sent: 40 + Math.round(height * 21), grown };
}

const bars = ref<Bar[]>(
    Array.from({ length: VISIBLE_BARS + 1 }, () => makeBar(false)),
);

const rowWidth = computed(
    () => `calc(100% * ${bars.value.length} / ${VISIBLE_BARS})`,
);
const slotBasis = computed(() => `calc(100% / ${bars.value.length})`);

const numberFormat = new Intl.NumberFormat();
const displayed = ref<number[]>(tiles.map(() => 0));

const chartRef = ref<HTMLElement | null>(null);
const rowRef = ref<HTMLElement | null>(null);
const paused = ref(false);
const tooltip = ref<{ sent: number; x: number; y: number } | null>(null);

let countFrame = 0;
let streamFrame = 0;

function formatValue(value: number, decimals: number, suffix: string): string {
    const text =
        decimals > 0
            ? value.toFixed(decimals)
            : numberFormat.format(Math.round(value));

    return `${text}${suffix}`;
}

/** Position the tooltip above a bar, using its final height (growth-safe). */
function showTooltip(event: MouseEvent, bar: Bar): void {
    const slot = event.currentTarget as HTMLElement;
    const host = chartRef.value;

    if (!host) {
        return;
    }

    const slotRect = slot.getBoundingClientRect();
    const hostRect = host.getBoundingClientRect();

    tooltip.value = {
        sent: bar.sent,
        x: slotRect.left - hostRect.left + slotRect.width / 2,
        y:
            slotRect.top -
            hostRect.top +
            slotRect.height * (1 - bar.height / 100),
    };
}

function hideTooltip(): void {
    tooltip.value = null;
}

onMounted(() => {
    const reduceMotion = window.matchMedia?.(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    // Count the stat tiles up regardless of motion preference.
    if (reduceMotion) {
        displayed.value = tiles.map((tile) => tile.target);
        bars.value = bars.value
            .slice(0, VISIBLE_BARS)
            .map((bar) => ({ ...bar, grown: true }));

        return;
    }

    countFrame = requestAnimationFrame(() => {
        bars.value.forEach((bar, index) => {
            if (index < VISIBLE_BARS) {
                bar.grown = true;
            }
        });
    });

    const countStart = performance.now();
    const countUp = (now: number): void => {
        const progress = Math.min(1, (now - countStart) / 1200);
        const eased = 1 - Math.pow(1 - progress, 3);

        displayed.value = tiles.map((tile) => tile.target * eased);

        if (progress < 1) {
            countFrame = requestAnimationFrame(countUp);
        } else {
            displayed.value = tiles.map((tile) => tile.target);
        }
    };
    countFrame = requestAnimationFrame(countUp);

    // The streaming loop. `phaseElapsed` only advances while not paused, so
    // hovering the chart freezes it wherever it is.
    let phase: 'dwell' | 'slide' = 'dwell';
    let phaseElapsed = 0;
    let last = performance.now();

    const stream = (now: number): void => {
        const delta = now - last;
        last = now;

        if (!paused.value) {
            phaseElapsed += delta;

            if (phase === 'dwell') {
                if (phaseElapsed >= DWELL_MS) {
                    // The incoming off-screen bar begins growing as it enters.
                    const incoming = bars.value[bars.value.length - 1];

                    if (incoming) {
                        incoming.grown = true;
                    }

                    phase = 'slide';
                    phaseElapsed = 0;
                }
            } else {
                const t = Math.min(1, phaseElapsed / SLIDE_MS);
                const slotPercent = (t / bars.value.length) * 100;

                if (rowRef.value) {
                    rowRef.value.style.transform = `translateX(-${slotPercent}%)`;
                }

                if (t >= 1) {
                    // Drop the bar that left the frame, append a fresh low one,
                    // and reset the offset. The +1 shift cancels the reset, so
                    // it is seamless.
                    bars.value = [...bars.value.slice(1), makeBar(false)];

                    if (rowRef.value) {
                        rowRef.value.style.transform = 'translateX(0)';
                    }

                    phase = 'dwell';
                    phaseElapsed = 0;
                }
            }
        }

        streamFrame = requestAnimationFrame(stream);
    };

    streamFrame = requestAnimationFrame(stream);
});

onBeforeUnmount(() => {
    cancelAnimationFrame(countFrame);
    cancelAnimationFrame(streamFrame);
});
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-sidebar-border bg-card shadow-2xl shadow-black/40"
    >
        <!-- Window chrome -->
        <div
            class="flex items-center gap-3 border-b border-sidebar-border bg-secondary/40 px-4 py-3"
        >
            <div class="flex gap-1.5">
                <span class="size-2.5 rounded-full bg-border" />
                <span class="size-2.5 rounded-full bg-border" />
                <span class="size-2.5 rounded-full bg-border" />
            </div>
            <span class="ml-1 text-sm font-medium text-foreground"
                >Email overview</span
            >
            <span
                class="ml-auto inline-flex items-center gap-1.5 rounded-full border border-primary/30 bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
            >
                <span class="size-1.5 animate-pulse rounded-full bg-primary" />
                Live
            </span>
        </div>

        <div class="space-y-4 p-4 sm:p-5">
            <!-- Stat tiles -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    v-for="(tile, index) in tiles"
                    :key="tile.label"
                    class="rounded-xl border border-sidebar-border bg-card p-3.5"
                >
                    <div
                        class="flex items-center gap-2.5 text-xs text-muted-foreground"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                        >
                            <component
                                :is="tile.icon"
                                class="size-4 text-muted-foreground"
                            />
                        </span>
                        {{ tile.label }}
                    </div>
                    <p
                        class="mt-2 ml-[42px] text-xl font-bold text-foreground tabular-nums"
                    >
                        {{
                            formatValue(
                                displayed[index],
                                tile.decimals,
                                tile.suffix,
                            )
                        }}
                    </p>
                </div>
            </div>

            <!-- Live trend chart -->
            <div
                ref="chartRef"
                class="relative rounded-xl border border-sidebar-border bg-card p-4"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full border border-border bg-linear-to-br from-muted/70 to-muted/20"
                        >
                            <ChartColumn class="size-4 text-muted-foreground" />
                        </span>
                        <span class="text-sm font-medium text-foreground"
                            >Last 14 days</span
                        >
                    </div>
                    <div
                        class="flex items-center gap-1.5 text-[11px] text-muted-foreground"
                    >
                        <span class="size-2 rounded-full bg-primary" /> Sent
                    </div>
                </div>

                <div
                    class="mt-4 h-28 overflow-hidden"
                    @mouseenter="paused = true"
                    @mouseleave="
                        paused = false;
                        hideTooltip();
                    "
                >
                    <div
                        ref="rowRef"
                        class="flex h-full items-end"
                        :style="{ width: rowWidth }"
                    >
                        <div
                            v-for="bar in bars"
                            :key="bar.id"
                            class="flex h-full items-end justify-center"
                            :style="{ flex: `0 0 ${slotBasis}` }"
                            @mouseenter="showTooltip($event, bar)"
                        >
                            <div
                                class="w-[68%] rounded-t-sm bg-primary"
                                :style="{
                                    height: bar.grown ? `${bar.height}%` : '0%',
                                    transition: `height ${SLIDE_MS}ms ease-out`,
                                }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Hover tooltip -->
                <div
                    v-if="tooltip"
                    class="pointer-events-none absolute z-20 -translate-x-1/2 -translate-y-full"
                    :style="{
                        left: `${tooltip.x}px`,
                        top: `${tooltip.y - 8}px`,
                    }"
                >
                    <div
                        class="rounded-md border border-border bg-popover px-2.5 py-1.5 text-xs whitespace-nowrap shadow-lg shadow-black/40"
                    >
                        <span
                            class="font-semibold text-foreground tabular-nums"
                        >
                            {{ numberFormat.format(tooltip.sent) }}
                        </span>
                        <span class="text-muted-foreground"> sent</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
