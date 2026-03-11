<script setup lang="ts">
import { Teleport } from 'vue';

interface Props {
    show: boolean;
    title?: string;
    subtitle?: string;
    progress?: number;
}

withDefaults(defineProps<Props>(), {
    show: false,
    title: 'Loading Data',
    subtitle: 'Please wait while we process your request',
    progress: 0,
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-[2rem] p-8 shadow-2xl w-full max-w-md mx-4 transform transition-all">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h2>
                        <p class="text-gray-500 dark:text-gray-400 text-sm" v-if="subtitle">{{ subtitle }}</p>
                    </div>
                    <div class="text-3xl font-bold text-orange-500">
                        {{ Math.round(progress) }}%
                    </div>
                </div>

                <!-- Progress Bar Container -->
                <div class="w-full bg-gray-100 dark:bg-gray-700 h-4 rounded-full overflow-hidden mb-8 relative">
                    <!-- Orange Progress Fill -->
                    <div 
                        class="h-full bg-orange-500 transition-all duration-300 ease-out" 
                        :style="{ width: progress + '%' }"
                    ></div>
                    <!-- Progress Knob/Circle -->
                    <div 
                        class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-orange-500 border-2 border-white rounded-full transition-all duration-300 ease-out"
                        :style="{ left: `calc(${progress}% - 8px)` }"
                    ></div>
                </div>

                <div class="flex items-center justify-center gap-3 text-gray-400 dark:text-gray-500">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs font-bold tracking-widest uppercase">PLEASE WAIT...</span>
                </div>
            </div>
        </div>
    </Teleport>
</template>
