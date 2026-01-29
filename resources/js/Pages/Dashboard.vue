<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import WaterLevelChart from '@/Components/WaterLevelChart.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';

declare function route(name: string, params?: any, absolute?: boolean): string;

const props = defineProps<{
    latestData?: Record<number, {
        sensor_id: number;
        name: string;
        success: boolean;
        data?: any;
        error?: string;
        timestamp: string;
    }> | null;
    historyData?: Record<number, Array<{
        value: number;
        timestamp: string;
    }>> | null;
    sensors?: Array<{
        id: number;
        name: string;
        level_2: number;
        level_3: number;
        level_4: number;
    }>;
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

const isAutoPulling = ref(true);
let interval: any = null;

const pullData = () => {
    router.post(route('dashboard.pull-data'), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['latestData', 'historyData'],
    });
};

onMounted(() => {
    // Initial pull if needed can be handled by controller, 
    // but we'll set up the periodic pull here.
    interval = setInterval(() => {
        if (isAutoPulling.value) {
            pullData();
        } else {
            // Just refresh data from cache if not pulling from sensors
            router.reload({ 
                only: ['latestData', 'historyData'] as any,
                preserveScroll: true,
                preserveState: true,
            });
        }
    }, 10000); // Pull every 10 seconds to avoid overloading Modbus
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
            <div class="max-w-9xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div>
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Water Level Sensors Data
                        </h3>
                        
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <Checkbox 
                                    id="auto-pull" 
                                    v-model:checked="isAutoPulling" 
                                    class="mr-2"
                                />
                                <InputLabel for="auto-pull" value="Auto Pull Data" class="cursor-pointer" />
                            </div>

                            <Link
                                :href="route('dashboard.pull-data')"
                                method="post"
                                as="button"
                                type="button"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Pull Data Now
                            </Link>
                        </div>
                    </div>

                    <div v-if="modbusResult" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div v-for="(result, sensorId) in modbusResult" :key="sensorId" 
                            class="bg-white dark:bg-gray-800 border-2 border-blue-600 rounded-2xl overflow-hidden shadow-md"
                        >
                            <div class="px-4 py-3 border-b dark:border-gray-700">
                                <h4 class="text-center font-bold text-gray-700 dark:text-gray-200">
                                    {{ result.name }}
                                </h4>
                            </div>
                            
                            <div class="p-4">
                                <div v-if="result.success">
                                    <WaterLevelChart 
                                        :sensorId="Number(sensorId)" 
                                        :name="result.name" 
                                        :value="result.data / 10" 
                                        :timestamp="result.timestamp" 
                                        :history="historyData?.[Number(sensorId)] || []"
                                        :level2="sensors?.find(s => s.id === Number(sensorId))?.level_2"
                                        :level3="sensors?.find(s => s.id === Number(sensorId))?.level_3"
                                        :level4="sensors?.find(s => s.id === Number(sensorId))?.level_4"
                                    />
                                </div>
                                <div v-else class="bg-red-50 dark:bg-red-900/10 text-red-700 dark:text-red-300 rounded-lg p-4">
                                    <p class="font-bold mb-2 text-sm text-center">Error Pulling Data ({{ result.timestamp }})</p>
                                    <p class="text-xs text-center">{{ result.error }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
