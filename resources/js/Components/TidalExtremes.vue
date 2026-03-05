<script setup lang="ts">
import { computed } from 'vue';
import TideChart from './TideChart.vue';

interface TideRecord {
    id: number;
    dt: number;
    date: string;
    height: number;
    type: string;
}

const props = defineProps<{
    tides: Record<string, TideRecord[]>;
    tideHeights?: Array<{
        dt: number;
        date: string;
        height: number;
    }>;
}>();

const allExtremes = computed(() => {
    return Object.values(props.tides).flat();
});

const formatDate = (dateStr: string) => {
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short'
    }).format(new Date(dateStr));
};

const formatTime = (dateStr: string) => {
    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    }).format(new Date(dateStr));
};

const sortedDates = computed(() => {
    return Object.keys(props.tides).sort();
});
</script>

<template>
    <div v-if="props.tides && Object.keys(props.tides).length > 0" class="overflow-hidden bg-transparent mb-8">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 uppercase">
            Tidal Extremes
        </h3>

        <div v-if="props.tideHeights && props.tideHeights.length > 0" class="mb-8">
            <TideChart :heights="props.tideHeights" :extremes="allExtremes" />
        </div>

        <div class="overflow-x-auto shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        <th class="p-4 border border-blue-500 min-w-[120px] bg-slate-800"></th>
                        <th v-for="date in sortedDates" :key="date" class="p-4 border border-blue-500 text-center min-w-[150px] font-bold text-sm tracking-wide">
                            {{ formatDate(date) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- HIGH row -->
                    <tr class="bg-white dark:bg-gray-800">
                        <td class="p-4 border border-gray-200 dark:border-gray-700 font-bold text-blue-600 bg-gray-50 dark:bg-gray-900 text-center uppercase">
                            <div class="flex flex-col items-center">
                                <svg class="w-6 h-6 mb-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                <span>HIGH (PST)</span>
                            </div>
                        </td>
                        <td v-for="date in sortedDates" :key="date" class="p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col space-y-4">
                                <div v-for="tide in props.tides[date].filter(t => t.type === 'High')" :key="tide.id" class="text-center group">
                                    <div class="font-bold text-gray-800 dark:text-gray-100 text-lg tracking-tight group-hover:text-blue-600 transition-colors">{{ formatTime(tide.date) }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 font-semibold text-xs">{{ tide.height.toFixed(2) }}m</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <!-- LOW row -->
                    <tr class="bg-white dark:bg-gray-800">
                        <td class="p-4 border border-gray-200 dark:border-gray-700 font-bold text-teal-600 bg-gray-50 dark:bg-gray-900 text-center uppercase">
                            <div class="flex flex-col items-center">
                                <svg class="w-6 h-6 mb-1 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                <span>LOW (PST)</span>
                            </div>
                        </td>
                        <td v-for="date in sortedDates" :key="date" class="p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col space-y-4">
                                <div v-for="tide in props.tides[date].filter(t => t.type === 'Low')" :key="tide.id" class="text-center group">
                                    <div class="font-bold text-teal-700 dark:text-teal-400 text-lg tracking-tight group-hover:text-teal-500 transition-colors">{{ formatTime(tide.date) }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 font-semibold text-xs">{{ tide.height.toFixed(2) }}m</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
