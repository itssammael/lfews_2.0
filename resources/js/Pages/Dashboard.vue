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
    stations?: Array<{
        id: number;
        station_id: string;
    }>;
    latestWeatherData?: Record<number, {
        id: number;
        station_id: string;
        name: string;
        success: boolean;
        data?: any;
        error?: string;
        timestamp: string;
    }> | null;
    historyWeatherData?: Record<number, Array<{
        data: any;
        timestamp: string;
    }>> | null; 
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
const flashWeatherResult = computed(() => page.props.flash.weatherResult);

// Use latestData prop if available, otherwise fallback to flash
// Normalize to always be a Record/mapped object
const weatherResult = computed(() => {
    const data: any = props.latestWeatherData || flashWeatherResult.value;
    if (!data) return null;
    
    // If it has 'success' but no keyed sensors, it's likely the old flat format
    if (data.success !== undefined && !data[Object.keys(data)[0]]?.timestamp) {
        return { system: data };
    }
    
    return data;
});

const getWindDirection = (degrees: number) => {
    const directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
    const index = Math.round(degrees / 22.5) % 16;
    return directions[index];
};


const isAutoPulling = ref(true);
const isAutoWeatherPulling = ref(true);
let interval: any = null;

const pullData = () => {
    router.post(route('dashboard.pull-water-data'), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['latestData', 'historyData'],
    });
};

const pullWeatherData = () => {
    router.post(route('dashboard.pull-weather-data'), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['latestWeatherData', 'historyWeatherData'],
    });
};

onMounted(() => {
    // Initial pull if needed can be handled by controller, 
    // but we'll set up the periodic pull here.
    interval = setInterval(() => {
        if (isAutoPulling.value) {
            pullData();
        }
        
        if (isAutoWeatherPulling.value) {
            pullWeatherData();
        }

        if (!isAutoPulling.value && !isAutoWeatherPulling.value) {
            // Just refresh data from cache if not pulling from sensors
            router.reload({ 
                only: ['latestData', 'historyData', 'latestWeatherData', 'historyWeatherData'] as any,
                preserveScroll: true,
                preserveState: true,
            });
        }
    }, 10000); // Pull every 10 seconds to avoid overloading
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

        <div class="pt-6 pb-12">
            <div class="max-w-9xl mx-auto sm:px-6 lg:px-8">
                 <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-3"> <!--Water level sensors data -->
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
                                    :href="route('dashboard.pull-water-data')"
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
                                    <!-- <div v-if="result.success"> -->
                                        <div>
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
                                    <!-- <div v-else class="bg-red-50 dark:bg-red-900/10 text-red-700 dark:text-red-300 rounded-lg p-4">
                                        <p class="font-bold mb-2 text-sm text-center">Error Pulling Data ({{ result.timestamp }})</p>
                                        <p class="text-xs text-center">{{ result.error }}</p>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 my-6"> <!--Weather Station data -->
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Weather Stations Observation Data
                            </h3>
                            
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <Checkbox 
                                        id="auto-weather-pull" 
                                        v-model:checked="isAutoWeatherPulling"
                                        class="mr-2"
                                    />
                                    <InputLabel for="auto-weather-pull" value="Auto Pull Data" class="cursor-pointer" />
                                </div>

                                <Link
                                    :href="route('dashboard.pull-weather-data')"
                                    method="post"
                                    as="button"
                                    type="button"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Pull Data Now
                                </Link>
                            </div>
                        </div>
                      
                        <div v-if="weatherResult" class="flex space-x-6 mt-6">
                           
                              <div v-for="(observation, stationId) in weatherResult" :key="stationId" 
                                class="bg-white dark:bg-gray-800 border-2 border-blue-600 rounded-2xl overflow-hidden shadow-md w-1/3">
                                <div class="px-4 py-3 border-b dark:border-gray-700">
                                    <h4 class="text-center font-bold text-xl text-gray-700 dark:text-gray-200 uppercase flex items-center justify-between">
                                        <span>{{ observation.name }}  <span class="text-[12px] font-light text-gray-500 text-sm italic capitalize">data as of {{ observation.data.date_time }}</span> </span><span class="text-gray-500 text-sm italic">PWS ID: {{ observation.station_id }}</span>
                                    </h4>
                                </div>
                                <div class="p-4">
                                    <div v-if="observation.success" class="flex flex-col space-y-6">
                                        <!-- Main Conditions Row -->
                                        <div class="flex items-center justify-between">
                                            <!-- Temperature -->
                                            <div class="flex flex-col">
                                                <div class="flex items-baseline">
                                                    <span class="text-6xl font-normal text-orange-500">{{ Number(observation.data.temperature) }}</span>
                                                    <span class="text-2xl text-gray-500 ml-1">°C</span>
                                                </div>
                                                <div class="text-gray-500 text-sm mt-1">
                                                    Feels Like {{ Number(observation.data.heat_index) }}° <span class="text-[10px] italic uppercase">(Heat Index)</span>
                                                </div>
                                            </div>

                                            <!-- Wind Compass (Simple implementation) -->
                                            <div class="relative w-20 h-20 flex items-center justify-center">
                                                <div class="absolute inset-0 border-2 border-dashed border-gray-300 rounded-full"></div>
                                                <span class="font-bold text-gray-800">{{ getWindDirection(observation.data.wind_direction) }}</span>
                                                <!-- Compass Needle -->
                                                <div 
                                                    class="absolute w-1 h-8 bg-gray-400 rounded-full"
                                                    :style="{ transform: `rotate(${observation.data.wind_direction}deg)`, transformOrigin: 'center bottom', bottom: '50%' }"
                                                ></div>
                                            </div>

                                            <!-- Wind & Gust -->
                                            <div class="flex flex-col items-end">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Wind & Gust</span>
                                                <div class="flex items-baseline text-gray-800 font-bold mt-1">
                                                    <span class="text-lg">{{ Number(observation.data.wind_speed) }}</span>
                                                    <span class="mx-1 text-gray-400">/</span>
                                                    <span class="text-lg">{{ Number(observation.data.wind_gust) }}</span>
                                                    <span class="ml-1 text-sm font-normal">km/h</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Secondary Grid -->
                                        <div class="grid grid-cols-3 gap-y-4 gap-x-8 pt-4">
                                            <!-- Dewpoint -->
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Dewpoint</span>
                                                <div class="flex items-baseline font-bold text-gray-800 mt-1">
                                                    <span>{{ observation.data.dewpoint }}</span>
                                                    <span class="ml-1 text-xs font-normal">°C</span>
                                                </div>
                                            </div>
                                            <!-- Precip Rate -->
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Precip Rate</span>
                                                <div class="flex items-baseline font-bold text-gray-800 mt-1">
                                                    <span>{{ observation.data.precipitation_rate }}</span>
                                                    <span class="ml-1 text-xs font-normal">mm/hr</span>
                                                </div>
                                            </div>
                                            <!-- Pressure -->
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Pressure</span>
                                                <div class="flex items-baseline font-bold text-gray-800 mt-1">
                                                    <span>{{ Number(observation.data.pressure) }}</span>
                                                    <span class="ml-1 text-xs font-normal">hPa</span>
                                                </div>
                                            </div>
                                            <!-- Humidity -->
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Humidity</span>
                                                <div class="flex items-baseline font-bold text-gray-800 mt-1">
                                                    <span>{{ observation.data.humidity }}</span>
                                                    <span class="ml-1 text-xs font-normal">%</span>
                                                </div>
                                            </div>
                                            <!-- Precip Accum -->
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Precip Accum</span>
                                                <div class="flex items-baseline font-bold text-gray-800 mt-1">
                                                    <span>{{ observation.data.precipitation_total }}</span>
                                                    <span class="ml-1 text-xs font-normal">mm</span>
                                                </div>
                                            </div>
                                            <!-- UV -->
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-gray-400 uppercase">UV</span>
                                                <div class="flex items-baseline font-bold text-gray-800 mt-1">
                                                    <span>{{ observation.data.uv ?? '--' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="bg-red-50 dark:bg-red-900/10 text-red-700 dark:text-red-300 rounded-lg p-4">
                                        <p class="font-bold mb-2 text-sm text-center">Error Pulling Data ({{ observation.timestamp }})</p>
                                        <p class="text-xs text-center">{{ observation.error }}</p>
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
