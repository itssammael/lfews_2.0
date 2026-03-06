<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import MapChart from "@/Components/MapChart.vue";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import HeatIndexMapLayout from "@/Layouts/HeatIndexMapLayout.vue";

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
</script>

<template>
  <HeatIndexMapLayout>
    <Head title="Heat Index Map" />
    <div class="w-full h-screen p-2 relative bg-gray-50 dark:bg-gray-900 flex justify-center items-center">
        <MapChart :stations="stations || []" :weatherData="weatherResult" heightClass="h-full" />
    </div>
  </HeatIndexMapLayout>
</template>
