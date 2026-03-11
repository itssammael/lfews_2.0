<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import MapChart from "@/Components/MapChart.vue";
import { ref, computed, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import LocalWeatherMapLayout from "@/Layouts/LocalWeatherMapLayout.vue";

const props = defineProps<{
  stations?: Array<{
    id: number;
    station_id: string;
    name: string;
    location?: {
        latitude: string | number;
        longitude: string | number;
    };
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
  barangays?: any[];
}>();

const page = usePage();
const flashWeatherResult = computed(() => page.props.flash?.weatherResult);

const weatherResult = computed(() => {
  const data: any = props.latestWeatherData || flashWeatherResult.value;
  if (!data) return null;

  if (data.success !== undefined && !data[Object.keys(data)[0]]?.timestamp) {
    return { system: data };
  }

  return data;
});

const REFRESH_INTERVAL_SECONDS = 300; // 5 minutes
const countdownRemaining = ref(REFRESH_INTERVAL_SECONDS);
let refreshInterval: ReturnType<typeof setInterval>;

const startCountdown = () => {
    countdownRemaining.value = REFRESH_INTERVAL_SECONDS;
    if (refreshInterval) clearInterval(refreshInterval);
    
    refreshInterval = setInterval(() => {
        countdownRemaining.value--;
        
        if (countdownRemaining.value <= 0) {
            router.reload({ only: ['latestWeatherData'] });
            countdownRemaining.value = REFRESH_INTERVAL_SECONDS;
        }
    }, 1000); // 1 second
};

onMounted(() => {
  startCountdown();
});

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});
</script>

<template>
  <LocalWeatherMapLayout>
    <Head title="Local Weather Map" />
    <div class="w-full h-screen p-2 relative bg-gray-50 dark:bg-gray-900 flex justify-center items-center">
        <!-- Countdown overlay -->
        <div class="absolute top-4 right-4 z-[1000] bg-white dark:bg-gray-800 bg-opacity-90 dark:bg-opacity-90 px-4 py-2 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 pointer-events-none">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Refreshing data in {{ countdownRemaining }} seconds
            </span>
        </div>
        
        <MapChart :stations="stations || []" :weatherData="weatherResult" :barangays="barangays || []" heightClass="h-full" />
    </div>
  </LocalWeatherMapLayout>
</template>
