<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref } from "vue";
import WaterLevelChart from "@/Components/WaterLevelChart.vue";
import WaterLevelCombinedChart from "@/Components/WaterLevelCombinedChart.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputLabel from "@/Components/InputLabel.vue";

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
  router.post(
    route("dashboard.pull-water-data"),
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
  router.post(
    route("dashboard.pull-weather-data"),
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

                                <button
                                    @click="pullData"
                                    type="button"
                                    :disabled="processingWater"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    {{ processingWater ? 'Pulling...' : 'Pull Data Now' }}
                                </button>
                            </div>
                        </div>

                        <!-- Combined Trend Chart -->
                        <div class="mt-8">
                            <WaterLevelCombinedChart 
                                :sensors="sensors" 
                                :latestData="modbusResult" 
                                :historyData="historyData" 
                            />
                        </div>

                        <div v-if="sensors && sensors.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div v-for="sensor in sensors" :key="sensor.id" 
                                class="bg-white dark:bg-gray-800 border-2 border-blue-600 rounded-2xl overflow-hidden shadow-md"
                            >
                                <div class="px-4 py-3 border-b dark:border-gray-700">
                                    <h4 class="text-center font-bold text-gray-700 dark:text-gray-200">
                                        {{ sensor.name }} <span v-if="modbusResult?.[sensor.id]">- {{ modbusResult[sensor.id].data }}</span>
                                    </h4>
                                </div>
                                
                                <div class="p-4">
                                    <div v-if="modbusResult?.[sensor.id]">
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

                                <button
                                    @click="pullWeatherData"
                                    type="button"
                                    :disabled="processingWeather"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    {{ processingWeather ? 'Pulling...' : 'Pull Data Now' }}
                                </button>
                            </div>
                        </div>
                      
                        <div v-if="stations && stations.length > 0" class="flex flex-wrap gap-6 mt-6">
                           
                              <div v-for="station in stations" :key="station.id" 
                                class="bg-white dark:bg-gray-800 border-2 border-blue-600 rounded-2xl overflow-hidden shadow-md w-full md:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)]">
                                <template v-if="weatherResult?.[station.id]">
                                    <div class="px-4 py-3 border-b dark:border-gray-700">
                                        <h4 class="text-center font-bold text-lg text-gray-700 dark:text-gray-200 uppercase flex flex-col md:flex-row items-center justify-between">
                                            <span class="flex flex-col items-start">
                                                <span>{{ station.name }}</span>
                                                <span v-if="weatherResult[station.id].success && weatherResult[station.id].data" class="text-[10px] font-light text-gray-500 italic normal-case">
                                                    last update: {{ weatherResult[station.id].data.date_time }}
                                                </span>
                                            </span>
                                            <span class="text-gray-500 text-xs italic mt-1 md:mt-0">ID: {{ station.station_id }}</span>
                                        </h4>
                                    </div>
                                    <div class="p-4">
                                        <div v-if="weatherResult[station.id].success" class="flex flex-col space-y-6">
                                            <!-- Main Conditions Row -->
                                            <div class="flex items-center justify-between pt-2">
                                                <!-- Rain Rate -->
                                                <div class="flex flex-col">
                                                    <span class="text-[10px] font-bold text-gray-400 uppercase leading-none">Rain Rate</span>
                                                    <div class="flex items-baseline mt-1">
                                                        <span class="text-4xl font-semibold text-orange-500">{{weatherResult[station.id].data.precipitation_rate }}</span>
                                                        <span class="text-lg text-gray-500 ml-1">mm/h</span>
                                                    </div>
                                                </div>

                                                <!-- Rain Total -->
                                                <div class="flex flex-col pl-4 border-l dark:border-gray-700">
                                                    <span class="text-[10px] font-bold text-gray-400 uppercase leading-none">Rain Total</span>
                                                    <div class="flex items-baseline mt-1">
                                                        <span class="text-4xl font-semibold text-orange-500">{{ weatherResult[station.id].data.precipitation_total }}</span>
                                                        <span class="text-lg text-gray-500 ml-1">mm</span>
                                                    </div>
                                                </div>

                                                <!-- Animated Icon -->
                                                <div class="flex items-center justify-center text-gray-400 ml-4">
                                                    <svg class="w-12 h-12 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                                    </svg>
                                                </div>
                                            </div>

                                            <!-- Secondary Grid -->
                                            <div class="grid grid-cols-4 gap-y-4 gap-x-4 pt-4 border-t dark:border-gray-700">
                                                <!-- Wind Compass -->
                                                <div class="relative w-16 h-16 flex items-center justify-center">
                                                    <div class="absolute inset-0 border-2 border-dashed border-gray-300 rounded-full"></div>
                                                    <span class="font-bold text-gray-800 text-sm">{{ getWindDirection(weatherResult[station.id].data.wind_direction) }}</span>
                                                    <div 
                                                        class="absolute w-1 h-6 bg-gray-400 rounded-full"
                                                        :style="{ transform: `rotate(${weatherResult[station.id].data.wind_direction}deg)`, transformOrigin: 'center bottom', bottom: '50%' }"
                                                    ></div>
                                                </div>
                                                
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">Dewpoint</span>
                                                    <div class="flex items-baseline font-bold text-gray-800 text-sm mt-0.5">
                                                        <span>{{ weatherResult[station.id].data.dewpoint }}</span>
                                                        <span class="ml-0.5 text-[10px] font-normal">°C</span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">Temperature</span>
                                                    <div class="flex items-baseline font-bold text-gray-800 text-sm mt-0.5">
                                                        <span>{{ Number(weatherResult[station.id].data.temperature) }}</span>
                                                        <span class="ml-0.5 text-[10px] font-normal">°C</span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">Pressure</span>
                                                    <div class="flex items-baseline font-bold text-gray-800 text-sm mt-0.5">
                                                        <span>{{ Math.round(weatherResult[station.id].data.pressure) }}</span>
                                                        <span class="ml-0.5 text-[10px] font-normal">hPa</span>
                                                    </div>
                                                </div>
                                                <!-- Wind & Gust -->
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">Wind / Gust</span>
                                                    <div class="flex items-baseline text-gray-800 font-bold mt-1">
                                                        <span class="text-base">{{ Number(weatherResult[station.id].data.wind_speed) }}</span>
                                                        <span class="mx-1 text-gray-400">/</span>
                                                        <span class="text-base">{{ Number(weatherResult[station.id].data.wind_gust) }}</span>
                                                        <span class="ml-1 text-[10px] font-normal uppercase">km/h</span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">Humidity</span>
                                                    <div class="flex items-baseline font-bold text-gray-800 text-sm mt-0.5">
                                                        <span>{{ weatherResult[station.id].data.humidity }}</span>
                                                        <span class="ml-0.5 text-[10px] font-normal">%</span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">Heat Index</span>
                                                    <div class="flex items-baseline font-bold text-gray-800 text-sm mt-0.5">
                                                        <span>{{ Number(weatherResult[station.id].data.heat_index) }}</span>
                                                        <span class="ml-0.5 text-[10px] font-normal">°C</span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">UV</span>
                                                    <div class="flex items-baseline font-bold text-gray-800 text-sm mt-0.5">
                                                        <span>{{ weatherResult[station.id].data.uv ?? '--' }}</span>
                                                    </div>
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
                                    <div class="px-4 py-3 border-b dark:border-gray-700">
                                        <h4 class="text-center font-bold text-gray-700 dark:text-gray-200">
                                            {{ station.name }}
                                        </h4>
                                    </div>
                                    <div class="p-8 flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-10 h-10 mb-2 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                        </svg>
                                        <p class="text-xs italic">Syncing weather data...</p>
                                    </div>
                                </template>
                              </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
