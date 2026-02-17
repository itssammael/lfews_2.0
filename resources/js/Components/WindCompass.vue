<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    direction: number | string;
    size?: string | number;
}>();

const rotation = computed(() => {
    const dir = typeof props.direction === 'string' ? parseFloat(props.direction) : props.direction;
    return isNaN(dir) ? 0 : dir;
});

const compassSize = computed(() => props.size || 120);

// Generate ticks for the compass
const ticks = computed(() => {
    const items = [];
    for (let i = 0; i < 360; i += 2) {
        let length = 3;
        if (i % 10 === 0) length = 5;
        if (i % 90 === 0) length = 8;
        items.push({ deg: i, length });
    }
    return items;
});

// Degree labels (0, 20, 40, ..., 340)
const degreeLabels = computed(() => {
    const labels = [];
    for (let i = 0; i < 360; i += 20) {
        if (i % 90 !== 0) { // Don't show redundant degrees at N, E, S, W
            labels.push(i);
        }
    }
    return labels;
});

const getDirectionName = (deg: number) => {
    const directions = ['N', 'N|E', 'E', 'S|E', 'S', 'S|W', 'W', 'N|W'];
    const idx = Math.round(deg / 45) % 8;
    return directions[idx];
};

</script>

<template>
    <div class="relative flex items-center justify-center select-none" :style="{ width: `${compassSize}px`, height: `${compassSize}px` }">
        <svg :width="compassSize" :height="compassSize" viewBox="0 0 200 200" class="overflow-visible">
            <!-- Outer Ring Shadow/Base -->
            <circle cx="100" cy="100" r="95" fill="none" class="stroke-gray-100 dark:stroke-gray-800" stroke-width="1" />
            
            <!-- Degree Ticks -->
            <g v-for="tick in ticks" :key="tick.deg" :transform="`rotate(${tick.deg}, 100, 100)`">
                <line x1="100" y1="12" x2="100" :y2="12 + tick.length" class="stroke-gray-400 dark:stroke-gray-500" stroke-width="1" />
            </g>

            <!-- Major Cardinal Labels (N, E, S, W) -->
            <text x="100" y="8" text-anchor="middle" class="fill-gray-900 dark:fill-gray-100 font-bold text-[18px]">N</text>
            <text x="194" y="106" text-anchor="middle" class="fill-gray-900 dark:fill-gray-100 font-bold text-[18px]">E</text>
            <text x="100" y="198" text-anchor="middle" class="fill-gray-900 dark:fill-gray-100 font-bold text-[18px]">S</text>
            <text x="10" y="106" text-anchor="middle" class="fill-gray-900 dark:fill-gray-100 font-bold text-[18px]">W</text>

            <!-- Ordinal Labels (N|E, S|E, S|W, N|W) -->
            <g transform="rotate(45, 100, 100)">
                <text x="100" y="32" text-anchor="middle" class="fill-gray-600 dark:fill-gray-400 font-semibold text-[14px]">N|E</text>
            </g>
            <g transform="rotate(135, 100, 100)">
                <text x="100" y="32" text-anchor="middle" class="fill-gray-600 dark:fill-gray-400 font-semibold text-[14px]">S|E</text>
            </g>
            <g transform="rotate(225, 100, 100)">
                <text x="100" y="32" text-anchor="middle" class="fill-gray-600 dark:fill-gray-400 font-semibold text-[14px]">S|W</text>
            </g>
            <g transform="rotate(315, 100, 100)">
                <text x="100" y="32" text-anchor="middle" class="fill-gray-600 dark:fill-gray-400 font-semibold text-[14px]">N|W</text>
            </g>

            <!-- Degree Numbers -->
            <g v-for="deg in degreeLabels" :key="deg" :transform="`rotate(${deg}, 100, 100)`">
                <text x="100" y="27" text-anchor="middle" class="fill-gray-400 dark:fill-gray-600 text-[9px] font-medium" :transform="`rotate(${-deg}, 100, 24)`">{{ deg }}</text>
            </g>

            <!-- Center Wind Rose Star Decor -->
            <g class="fill-gray-800 dark:fill-gray-200 opacity-20">
                <!-- N-S large points -->
                <path d="M100 40 L106 94 L100 100 L94 94 Z" />
                <path d="M100 160 L106 106 L100 100 L94 106 Z" />
                <!-- E-W large points -->
                <path d="M160 100 L106 106 L100 100 L106 94 Z" />
                <path d="M40 100 L94 106 L100 100 L94 94 Z" />
                
                <!-- Shading for large points -->
                <path d="M100 40 L100 100 L106 94 Z" class="opacity-50" />
                <path d="M100 160 L100 100 L94 106 Z" class="opacity-50" />
                <path d="M160 100 L100 100 L106 106 Z" class="opacity-50" />
                <path d="M40 100 L100 100 L94 94 Z" class="opacity-50" />

                <!-- Small diagonal points -->
                <g transform="rotate(45, 100, 100)">
                    <path d="M100 65 L103 97 L100 100 L97 97 Z" />
                    <path d="M135 100 L103 103 L100 100 L103 97 Z" />
                    <path d="M100 135 L103 103 L100 100 L97 103 Z" />
                    <path d="M65 100 L97 103 L100 100 L97 97 Z" />
                </g>
            </g>
            
            <!-- Rotating Needle -->
            <g :style="{ transform: `rotate(${rotation}deg)`, transformOrigin: '100px 100px' }" class="transition-transform duration-1000 ease-in-out">
                <!-- Outer Triangle (Pointer) -->
                <path d="M100 15 L104 25 L96 25 Z" class="fill-gray-900 dark:fill-gray-100" />
                
                <!-- Main Needle Line -->
                <line x1="100" y1="20" x2="100" y2="100" class="stroke-gray-900 dark:stroke-gray-100" stroke-width="2" />
                
                <!-- Center Pivot -->
                <circle cx="100" cy="100" r="4" class="fill-gray-900 dark:fill-gray-100" />
            </g>
        </svg>

        <!-- Current Direction Label Overlay -->
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm px-2 py-0.5 rounded-md shadow-sm border border-gray-100 dark:border-gray-700">
                <span class="text-xs font-bold text-gray-800 dark:text-gray-100">{{ getDirectionName(rotation) }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
svg {
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.05));
}
</style>
