<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref } from "vue";
import WaterLevelChart from "@/Components/WaterLevelChart.vue";
import WaterLevelCombinedChart from "@/Components/WaterLevelCombinedChart.vue";
import WeatherStationChart from "@/Components/WeatherStationChart.vue";
import WindCompass from "@/Components/WindCompass.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { useDashboardSettings } from "@/Composables/useDashboardSettings";

declare function route(name: string, params?: any, absolute?: boolean): string;

const props = defineProps<{
  latestData?: Record<
    number,
    {
      sensor_id: number;
      name: string;
      success: boolean;
      data?: any;
      error?: string;
      timestamp: string;
    }
  > | null;
  historyData?: Record<
    number,
    Array<{
      value: number;
      timestamp: string;
    }>
  > | null;
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
    name: string;
  }>;
  latestWeatherData?: Record<
    number,
    {
      id: number;
      station_id: string;
      name: string;
      success: boolean;
      data?: any;
      error?: string;
      timestamp: string;
    }
  > | null;
  historyWeatherData?: Record<
    number,
    Array<{
      data: any;
      timestamp: string;
    }>
  > | null;

  evacuationCenters?: Array<{
    id: number;
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    max_capacity: string | number;
    curren_resident: string | number;
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
  const directions = [
    "N",
    "NNE",
    "NE",
    "ENE",
    "E",
    "ESE",
    "SE",
    "SSE",
    "S",
    "SSW",
    "SW",
    "WSW",
    "W",
    "WNW",
    "NW",
    "NNW",
  ];
  const index = Math.round(degrees / 22.5) % 16;
  return directions[index];
};

const isAutoPulling = ref(true);
const isAutoWeatherPulling = ref(true);
const processingWater = ref(false);
const processingWeather = ref(false);
let interval: any = null;

const pullData = () => {
  if (processingWater.value) return;
  processingWater.value = true;
  
  const endpoint = page.props.auth.can.create 
    ? route("dashboard.pull-water-data") 
    : route("dashboard.refresh-water-data");

  router.post(
    endpoint,
    {},
    {
      preserveScroll: true,
      preserveState: true,
      only: ["latestData", "historyData"],
      onFinish: () => {
        processingWater.value = false;
      },
    }
  );
};

const pullWeatherData = () => {
  if (processingWeather.value) return;
  processingWeather.value = true;

  const endpoint = page.props.auth.can.create 
    ? route("dashboard.pull-weather-data") 
    : route("dashboard.refresh-weather-data");

  router.post(
    endpoint,
    {},
    {
      preserveScroll: true,
      preserveState: true,
      only: ["latestWeatherData", "historyWeatherData"],
      onFinish: () => {
        processingWeather.value = false;
      },
    }
  );
};

onMounted(() => {
  interval = setInterval(() => {
    // If auto-pulling is enabled for anything, just do a single partial reload of the cache data.
    // This is more efficient than separate POST requests and avoids network cancellations.
    if (isAutoPulling.value || isAutoWeatherPulling.value) {
      router.reload({
        only: [
          "latestData",
          "historyData",
          "latestWeatherData",
          "historyWeatherData",
        ] as any,
        preserveScroll: true,
        preserveState: true,
      });
    }
  }, 300000); 
});

onUnmounted(() => {
  if (interval) clearInterval(interval);
});
const sensorAlarmsEnabled = ref<Record<number, boolean>>({});
const dismissedAlerts = ref<Record<number, number>>({});

const getAlertLevel = (sensor: any, value: any) => {
    const val = Number(value);
    if (val >= Number(sensor.level_4)) return 4;
    if (val >= Number(sensor.level_3)) return 3;
    if (val >= Number(sensor.level_2)) return 2;
    return 0;
};

const dismissAlert = (sensorId: number, level: number) => {
    dismissedAlerts.value[sensorId] = level;
};

const isAlertDismissed = (sensorId: number, level: number) => {
    return dismissedAlerts.value[sensorId] === level;
};

const { showWaterLevelSensors, showWeatherStations, showEvacuationCenters } = useDashboardSettings();
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Dashboard
                </h2>
            </div>
            
        </template>

        <div class="pt-0 mb-12">
            <div class="w-full space-y-12 bg-gray-200/[0.25]">
                 <div v-if="showWaterLevelSensors" class="bg-transparent p-4 sm:p-8 pt-2"> <!--Water level sensors data -->
                    <div class="overflow-hidden">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 space-y-4 sm:space-y-0">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Water Level Sensors Data
                            </h3>
                            
                            <div v-if="$page.props.auth.can.read" class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <label for="auto-pull" class="cursor-pointer flex items-center" title="Toggle Auto Pull">
                                        <div class="relative inline-block w-10 h-5 transition duration-200 ease-in-out mr-2">
                                            <input 
                                                type="checkbox" 
                                                id="auto-pull"
                                                v-model="isAutoPulling"
                                                class="opacity-0 w-0 h-0 peer"
                                            />
                                            <div class="absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-300 dark:bg-gray-600 rounded-full transition-all duration-300 peer-checked:bg-orange-500"></div>
                                            <div class="absolute cursor-pointer h-4 w-4 left-0.5 bottom-0.5 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-5"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto Pull Data</span>
                                    </label>
                                </div>

                                <button
                                    @click="pullData"
                                    type="button"
                                    :disabled="processingWater"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    {{ processingWater ? 'Pulling...' : ($page.props.auth.can.create ? 'Pull Data Now' : 'Refresh Data') }}
                                </button>
                            </div>
                        </div>

                        <!-- Combined Trend Chart -->
                        <div class="mt-8">
                            <WaterLevelCombinedChart 
                                :sensors="sensors || []" 
                                :latestData="modbusResult" 
                                :historyData="historyData || undefined" 
                            />
                        </div>

                        <div v-if="sensors && sensors.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div v-for="sensor in sensors" :key="sensor.id" 
                                class="bg-white dark:bg-gray-800 border-2 border-orange-500 rounded-2xl overflow-hidden shadow-md"
                            >
                                <div class="px-4 py-3 border-b dark:border-gray-700 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <!-- Alarm Toggle Switch -->
                                        <div class="flex items-center mr-4">
                                            <label :for="'alarm-' + sensor.id" class="cursor-pointer flex items-center" title="Toggle Alarm">
                                                <svg class="w-5 h-5 mr-2 text-gray-500 hover:text-blue-600 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="13" r="8"></circle>
                                                    <path d="M12 9v4l2 2"></path>
                                                    <path d="m5 3 2 2"></path>
                                                    <path d="m19 3-2 2"></path>
                                                </svg>
                                                <div class="relative inline-block w-10 h-5 transition duration-200 ease-in-out">
                                                    <input 
                                                        type="checkbox" 
                                                        :id="'alarm-' + sensor.id"
                                                        :checked="sensorAlarmsEnabled[sensor.id] !== false"
                                                        @change="sensorAlarmsEnabled[sensor.id] = ($event.target as HTMLInputElement).checked"
                                                        class="opacity-0 w-0 h-0 peer"
                                                    />
                                                    <div class="absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-300 dark:bg-gray-600 rounded-full transition-all duration-300 peer-checked:bg-orange-500"></div>
                                                    <div class="absolute cursor-pointer h-4 w-4 left-0.5 bottom-0.5 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-5"></div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <h4 class="flex-grow text-center font-bold text-gray-700 dark:text-gray-200">
                                        {{ sensor.name }} <span v-if="modbusResult?.[sensor.id]?.success">- {{ modbusResult[sensor.id].data }}</span>
                                    </h4>
                                    <!-- Empty div for balancing the justify-between if needed -->
                                    <div class="w-16"></div> 
                                </div>
                                
                                <div class="p-4 relative">
                                    <!-- Alert Overlay -->
                                    <div v-if="(sensorAlarmsEnabled[sensor.id] !== false) && modbusResult?.[sensor.id]?.success && getAlertLevel(sensor, modbusResult[sensor.id].data) > 0 && !isAlertDismissed(sensor.id, getAlertLevel(sensor, modbusResult[sensor.id].data))"
                                        class="absolute inset-x-2 top-2 z-10 rounded-lg shadow-2xl p-4 flex flex-col border-2"
                                        :class="{
                                            'bg-yellow-500 border-yellow-400 text-black': getAlertLevel(sensor, modbusResult[sensor.id].data) === 2,
                                            'bg-orange-500 border-orange-400 text-white': getAlertLevel(sensor, modbusResult[sensor.id].data) === 3,
                                            'bg-red-600 border-red-500 text-white': getAlertLevel(sensor, modbusResult[sensor.id].data) === 4
                                        }"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center space-x-2">
                                                <svg v-if="getAlertLevel(sensor, modbusResult[sensor.id].data) === 4" class="w-8 h-8 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                <h5 class="text-xl font-black uppercase tracking-tighter italic">
                                                    {{ 
                                                        getAlertLevel(sensor, modbusResult[sensor.id].data) === 2 ? 'CAUTION' : 
                                                        getAlertLevel(sensor, modbusResult[sensor.id].data) === 3 ? 'WARNING' : 
                                                        'CRITICAL' 
                                                    }}
                                                </h5>
                                            </div>
                                            <button @click="dismissAlert(sensor.id, getAlertLevel(sensor, modbusResult[sensor.id].data))" 
                                                class="hover:opacity-75 transition-opacity"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <div class="mt-2 text-sm font-bold leading-tight uppercase">
                                            <template v-if="getAlertLevel(sensor, modbusResult[sensor.id].data) === 2">
                                                <p>Prepare for possible pre-emptive evacuation.</p>
                                                <p class="mt-1">No Classes for Kindergarten</p>
                                            </template>
                                            <template v-else-if="getAlertLevel(sensor, modbusResult[sensor.id].data) === 3">
                                                <p>PRE-EMPTIVE EVACUATION.</p>
                                                <p class="mt-1">No Classes in Kindergarten, Elementary & Secondary</p>
                                            </template>
                                            <template v-else-if="getAlertLevel(sensor, modbusResult[sensor.id].data) === 4">
                                                <p>Evacuate and proceed to designated Evacuation Center.</p>
                                                <p class="mt-1">No Classes in all levels. Work Suspension.</p>
                                            </template>
                                        </div>
                                        
                                        <div class="mt-4 py-1 px-2 bg-white/20 rounded text-[10px] font-mono self-start">
                                            CURRENT: {{ modbusResult[sensor.id].data }} cm
                                        </div>
                                    </div>

                                    <!-- Data Available and Success -->
                                    <div v-if="modbusResult?.[sensor.id]?.success">
                                        <WaterLevelChart 
                                            :sensorId="sensor.id" 
                                            :name="sensor.name" 
                                            :value="modbusResult[sensor.id].data" 
                                            :timestamp="modbusResult[sensor.id].timestamp" 
                                            :history="historyData?.[sensor.id] || []"
                                            :level2="Number(sensor.level_2)"
                                            :level3="Number(sensor.level_3)"
                                            :level4="Number(sensor.level_4)"
                                        />
                                    </div>
                                    <!-- Error State -->
                                    <div v-else-if="modbusResult?.[sensor.id] && !modbusResult?.[sensor.id]?.success" 
                                        class="bg-red-50 dark:bg-red-900/10 text-red-700 dark:text-red-300 rounded-lg p-6"
                                    >
                                        <p class="font-bold mb-2 text-sm text-center">Connection Issue</p>
                                        <p class="text-xs text-center line-clamp-2" :title="modbusResult[sensor.id].error">
                                            {{ modbusResult[sensor.id].error }}
                                        </p>
                                        <p class="text-[10px] text-center mt-2 opacity-50">{{ modbusResult[sensor.id].timestamp }}</p>
                                    </div>
                                    <!-- Waiting for Data -->
                                    <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400">
                                        <svg class="w-12 h-12 mb-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <p class="text-sm italic">Waiting for data pull...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="showWeatherStations" class="bg-transparent p-4 sm:p-8 pt-2"> <!--Weather Station data -->
                    <div class="overflow-hidden"> 
                 
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 space-y-4 sm:space-y-0">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Weather Stations Observation Data
                                </h3>
                                
                                <div v-if="$page.props.auth.can.read" class="flex items-center space-x-4">
                                    <div class="flex items-center">
                                        <label for="auto-weather-pull" class="cursor-pointer flex items-center" title="Toggle Auto Pull">
                                            <div class="relative inline-block w-10 h-5 transition duration-200 ease-in-out mr-2">
                                                <input 
                                                    type="checkbox" 
                                                    id="auto-weather-pull"
                                                    v-model="isAutoWeatherPulling"
                                                    class="opacity-0 w-0 h-0 peer"
                                                />
                                                <div class="absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-300 dark:bg-gray-600 rounded-full transition-all duration-300 peer-checked:bg-orange-500"></div>
                                                <div class="absolute cursor-pointer h-4 w-4 left-0.5 bottom-0.5 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-5"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto Pull Data</span>
                                        </label>
                                    </div>

                                    <button
                                        @click="pullWeatherData"
                                        type="button"
                                        :disabled="processingWeather"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        {{ processingWeather ? 'Pulling...' : ($page.props.auth.can.create ? 'Pull Data Now' : 'Refresh Data') }}
                                    </button>
                                </div>
                            </div>
                        
                            <div v-if="stations && stations.length > 0" class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
                            
                                <div v-for="station in stations" :key="station.id" 
                                    class="bg-white dark:bg-gray-800 border-2 border-orange-500 rounded-[2rem] overflow-hidden shadow-lg w-full flex flex-col md:flex-row">
                                    <template v-if="weatherResult?.[station.id]">
                                        <!-- Left Column: Chart -->
                                        <div class="w-full md:w-4/5 p-4 border-r dark:border-gray-700 flex flex-col">
                                            <div class="flex-grow">
                                                <WeatherStationChart 
                                                    :stationId="station.id" 
                                                    :name="station.name" 
                                                    :history="historyWeatherData?.[station.id] || []"
                                                />
                                            </div>
                                        </div>

                                        <!-- Right Column: Data Grid -->
                                        <div class="w-full md:w-2/5 p-6 flex flex-col">
                                            <!-- Header -->
                                            <div class="flex justify-between items-start mb-4">
                                                <div class="flex flex-col">
                                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-gray-100 uppercase leading-none">
                                                        {{ station.name }}
                                                    </h4>
                                                    <span class="text-xs text-gray-500 mt-1 italic">ID: {{ station.station_id }}</span>
                                                    <span v-if="weatherResult[station.id].success && weatherResult[station.id].data" class="text-[10px] font-light text-gray-400 italic mt-1">
                                                        last update: {{ weatherResult[station.id].data.date_time }}
                                                    </span>
                                                </div>
                                                <div :class="Number(weatherResult[station.id]?.data?.precipitation_rate || 0) > 0 ? 'text-blue-500' : 'text-gray-300'">
                                                    <!-- Rain Cloud -->
                                                    <svg v-if="Number(weatherResult[station.id]?.data?.precipitation_rate || 0) > 0" class="w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <path d="M17.5 19c2.5 0 4.5-2 4.5-4.5 0-2.3-1.7-4.2-3.9-4.5.3-.6.4-1.2.4-1.8 0-2.3-1.8-4.2-4.1-4.2-1.7 0-3.2 1-3.8 2.5C9.8 6.2 9 6 8.2 6c-2.3 0-4.2 1.8-4.2 4.1 0 .4.1.8.2 1.2C2.1 11.8 1 13.3 1 15c0 2.2 1.8 4 4 4h12.5z" />
                                                        <path class="animate-bounce" d="M8 20v2" />
                                                        <path class="animate-bounce" style="animation-delay: 0.2s" d="M12 20v2" />
                                                        <path class="animate-bounce" style="animation-delay: 0.4s" d="M16 20v2" />
                                                    </svg>
                                                    <!-- Regular Cloud -->
                                                    <svg v-else class="w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <path d="M17.5 19c2.5 0 4.5-2 4.5-4.5 0-2.3-1.7-4.2-3.9-4.5.3-.6.4-1.2.4-1.8 0-2.3-1.8-4.2-4.1-4.2-1.7 0-3.2 1-3.8 2.5C9.8 6.2 9 6 8.2 6c-2.3 0-4.2 1.8-4.2 4.1 0 .4.1.8.2 1.2C2.1 11.8 1 13.3 1 15c0 2.2 1.8 4 4 4h12.5z" />
                                                    </svg>
                                                </div>
                                            </div>

                                            <hr class="border-gray-100 dark:border-gray-700 mb-6" />

                                            <div v-if="weatherResult[station.id].success" class="flex flex-col space-y-6">
                                                <!-- Rain Row -->
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Rain Rate</span>
                                                        <div class="flex items-baseline mt-1">
                                                            <span class="text-3xl font-bold text-orange-500">{{ Number(weatherResult[station.id]?.data?.precipitation_rate || 0).toFixed(2) }}</span>
                                                            <span class="text-xs text-gray-500 ml-1">mm/h</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col pl-4 border-l border-gray-100 dark:border-gray-700">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Rain Total</span>
                                                        <div class="flex items-baseline mt-1">
                                                            <span class="text-3xl font-bold text-orange-600">{{ Number(weatherResult[station.id]?.data?.precipitation_total || 0).toFixed(2) }}</span>
                                                            <span class="text-xs text-gray-500 ml-1">mm</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr class="border-gray-100 dark:border-gray-700" />

                                                <!-- Secondary Grid -->
                                                <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Temperature</span>
                                                        <div class="flex items-baseline font-bold text-gray-700 dark:text-gray-200 text-lg">
                                                            <span>{{ Number(weatherResult[station.id].data.temperature).toFixed(1) }}</span>
                                                            <span class="ml-0.5 text-xs font-normal">°C</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Dewpoint</span>
                                                        <div class="flex items-baseline font-bold text-gray-700 dark:text-gray-200 text-lg">
                                                            <span>{{ Number(weatherResult[station.id].data.dewpoint).toFixed(2) }}</span>
                                                            <span class="ml-0.5 text-xs font-normal">°C</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Humidity</span>
                                                        <div class="flex items-baseline font-bold text-gray-700 dark:text-gray-200 text-lg">
                                                            <span>{{ Number(weatherResult[station.id].data.humidity).toFixed(2) }}</span>
                                                            <span class="ml-0.5 text-xs font-normal">%</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Heat Index</span>
                                                        <div class="flex items-baseline font-bold text-gray-700 dark:text-gray-200 text-lg">
                                                            <span>{{ Number(weatherResult[station.id].data.heat_index).toFixed(1) }}</span>
                                                            <span class="ml-0.5 text-xs font-normal">°C</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Pressure & Wind Section -->
                                                <div class="grid grid-cols-2 gap-4 items-center pt-2">
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Pressure</span>
                                                        <div class="flex items-baseline font-bold text-gray-700 dark:text-gray-200 text-lg mt-1">
                                                            <span>{{ Math.round(weatherResult[station.id].data.pressure) }}</span>
                                                            <span class="ml-1 text-xs font-normal">hPa</span>
                                                        </div>
                                                        
                                                        <div class="mt-4 flex flex-col">
                                                            <span class="text-[10px] font-bold text-gray-400 uppercase mb-1">Wind / Gust</span>
                                                            <div class="flex items-baseline text-gray-700 dark:text-gray-200 font-bold">
                                                                <span class="text-xl">{{ Number(weatherResult[station.id].data.wind_speed).toFixed(2) }}</span>
                                                                <span class="mx-1 text-gray-400 font-light">/</span>
                                                                <span class="text-xl">{{ Number(weatherResult[station.id].data.wind_gust).toFixed(0) }}</span>
                                                                <span class="ml-1 text-[10px] font-normal uppercase">km/h</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-col items-center justify-center">
                                                        <WindCompass 
                                                            :direction="weatherResult[station.id].data.wind_direction" 
                                                            :size="120"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="bg-red-50 dark:bg-red-900/10 text-red-700 dark:text-red-300 rounded-lg p-4">
                                                <p class="font-bold mb-2 text-xs text-center">Connection Issue</p>
                                                <p class="text-[10px] text-center line-clamp-2" :title="weatherResult[station.id].error">{{ weatherResult[station.id].error }}</p>
                                                <p class="text-[9px] text-center mt-2 opacity-50">{{ weatherResult[station.id].timestamp }}</p>
                                            </div>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="w-full p-12 flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-12 h-12 mb-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                            </svg>
                                            <p class="text-sm italic">Syncing weather data for {{ station.name }}...</p>
                                        </div>
                                    </template>
                                </div>

                            </div>
                       
                    </div>
                </div>
                <div v-if="showEvacuationCenters" class="bg-transparent p-4 sm:p-8 pt-2"> <!--Evacuation Center data -->
                    <div class="overflow-hidden mb-6"> 
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Evacuation Centers Status
                            </h3>
                        </div>

                        <div v-if="evacuationCenters && evacuationCenters.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="center in evacuationCenters" :key="center.id" class="bg-white dark:bg-gray-800 border-2 border-orange-500  rounded-xl shadow-md p-4 relative overflow-hidden group hover:shadow-md transition-shadow">
                                <div class="flex items-start space-x-4">
                                    <!-- Icon -->
                                    <div class="flex-shrink-0">
                                        <img src="/images/disaster.png" alt="Evacuation" class="w-16 h-16 object-contain opacity-80" />
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-grow min-w-0">
                                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-100 uppercase truncate border-b-2 border-gray-300 dark:border-gray-600 pb-1 mb-2">
                                            {{ center.name }}
                                        </h4>
                                        
                                        <div class="space-y-1">
                                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                                <span class="font-bold uppercase text-[10px] tracking-wider">Occupants:</span>
                                                <span class="font-bold ml-1 text-base">{{ center.curren_resident }}/{{ center.max_capacity }}</span>
                                            </div>
                                            
                                            <div class="text-sm font-bold flex items-center">
                                                <span class="uppercase text-[10px] tracking-wider text-gray-500 dark:text-gray-400 mr-1">Status:</span>
                                                <span :class="{
                                                    'text-red-600': (Number(center.curren_resident) / Number(center.max_capacity)) >= 1,
                                                    'text-gray-800 dark:text-gray-200': (Number(center.curren_resident) / Number(center.max_capacity)) < 1
                                                }">
                                                    {{ Math.round((Number(center.curren_resident) / Number(center.max_capacity)) * 100) }}%
                                                    <span v-if="(Number(center.curren_resident) / Number(center.max_capacity)) >= 1" class="text-red-600 ml-1">(FULL)</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Background decoration -->
                                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-gray-50 dark:bg-gray-700/50 rounded-full -z-0 pointer-events-none"></div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500 italic">
                            No evacuation center data available.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
