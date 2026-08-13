<script setup lang="ts">
import { ref, computed } from 'vue';

const props = withDefaults(
  defineProps<{
    apiKey?: string;
    lat?: number;
    lon?: number;
    zoom?: number;
    title?: string;
  }>(),
  {
    apiKey: 'ftgRewCaEDuirgiq7pA9dBXm9dP3qOoi',
    lat: 9.347,
    lon: 122.805,
    zoom: 13,
    title: 'Windy Forecast Map',
  }
);

const activeOverlay = ref<'wind' | 'rain' | 'temp' | 'clouds' | 'pressure'>('rain');

const overlays = [
  { id: 'rain', name: 'Rain', icon: '🌧️' },
  { id: 'wind', name: 'Wind', icon: '💨' },
  { id: 'temp', name: 'Temp', icon: '🌡️' },
  { id: 'clouds', name: 'Clouds', icon: '☁️' },
  { id: 'pressure', name: 'Pressure', icon: '🌀' },
];

const setOverlay = (id: string) => {
  activeOverlay.value = id as any;
};

const embedUrl = computed(() => {
  const key = encodeURIComponent(props.apiKey || 'ftgRewCaEDuirgiq7pA9dBXm9dP3qOoi');
  const lat = props.lat;
  const lon = props.lon;
  const zoom = props.zoom;
  const overlay = activeOverlay.value;

  return `https://embed.windy.com/embed.html?key=${key}&type=map&location=coordinates&metricRain=mm&metricTemp=%C2%B0C&metricWind=km%2Fh&zoom=${zoom}&overlay=${overlay}&product=ecmwf&level=surface&lat=${lat}&lon=${lon}&detailLat=${lat}&detailLon=${lon}&marker=true&message=true`;
});
</script>

<template>
  <div class="bg-white rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full">
    <!-- Card Header -->
    <div class="p-4 bg-transparent from-slate-900 to-slate-800 text-black flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-700">
      <div class="flex items-center space-x-2">
        <h3 class="font-semibold text-base tracking-wide">{{ title }}</h3>
      </div>

      <!-- Overlay Selector Pills -->
      <div class="flex items-center space-x-1 bg-slate-950/60 p-1 rounded-lg border border-slate-700/50">
        <button
          v-for="item in overlays"
          :key="item.id"
          @click="setOverlay(item.id)"
          :class="[
            'px-2.5 py-1 text-xs font-medium rounded-md transition-all duration-200 flex items-center space-x-1',
            activeOverlay === item.id
              ? 'bg-cyan-500 text-white shadow-md shadow-cyan-500/20'
              : 'text-slate-300 hover:text-white hover:bg-slate-800/80'
          ]"
          :title="`Switch overlay to ${item.name}`"
        >
          <span>{{ item.icon }}</span>
          <span class="hidden sm:inline">{{ item.name }}</span>
        </button>
      </div>
    </div>

    <!-- Map Container -->
    <div class="relative w-full flex-1 min-h-[420px] bg-slate-900">
      <iframe
        :src="embedUrl"
        class="w-full h-full min-h-[420px] border-0 rounded-b-xl"
        loading="lazy"
        allowfullscreen
        title="Windy Weather Forecast Map"
      ></iframe>
    </div>
  </div>
</template>
