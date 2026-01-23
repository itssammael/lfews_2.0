<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    latestData?: Record<number, {
        sensor_id: number;
        name: string;
        success: boolean;
        data?: any;
        error?: string;
        timestamp: string;
    }> | null;
}>();

const page = usePage();
const flashResult = computed(() => page.props.flash.modbusResult);

// Use latestData prop if available, otherwise fallback to flash
// Normalize to always be a Record/mapped object
const modbusResult = computed(() => {
    const data: any = props.latestData || flashResult.value;
    if (!data) return null;
    
    // If it has 'success' but no keyed sensors, it's likely the old flat format
    if (data.success !== undefined && !data[Object.keys(data)[0]]?.timestamp) {
        return { system: data };
    }
    
    return data;
});

let interval: any = null;

onMounted(() => {
    interval = setInterval(() => {
        router.reload({ 
            only: ['latestData'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 1000);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Water Level Sensors Data
                        </h3>
                        
                        <Link
                            :href="route('dashboard.pull-data')"
                            method="post"
                            as="button"
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Pull Water Data
                        </Link>
                    </div>

                    <div v-if="modbusResult" class="flex mt-6 space-x-6">
                        <div v-for="(result, sensorId) in modbusResult" :key="sensorId" class="border dark:border-gray-700 rounded-lg overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b dark:border-gray-700 flex justify-between items-center">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ result.name }}</h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">ID: {{ sensorId }}</span>
                            </div>
                            
                            <div class="p-4">
                                <div v-if="result.success" class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg p-4">
                                    <p class="font-bold mb-2 text-sm">Data Pulled Successfully ({{ result.timestamp }})</p>
                                    <pre class="text-xs overflow-auto">{{ JSON.stringify(result.data, null, 2) }}</pre>
                                </div>
                                <div v-else class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg p-4">
                                    <p class="font-bold mb-2 text-sm">Error Pulling Data ({{ result.timestamp }})</p>
                                    <p class="text-sm">{{ result.error }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
