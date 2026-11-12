<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import WaterLevelCombinedChart from '@/Components/WaterLevelCombinedChart.vue';
import WeatherStationChart from '@/Components/WeatherStationChart.vue';
import TideChart from '@/Components/TideChart.vue';

type Sensor = { id: number; name: string; level_2: number; level_3: number; level_4: number };
type Station = { id: number; name: string; station_id: string };
type Reading = { success?: boolean; data?: any; timestamp?: string };
type Tide = { id: number; dt: number; date: string; height: number; type: string };
const props = defineProps<{
  sensors: Sensor[];
  stations: Station[];
  waterReadings?: Record<number, Reading> | null;
  waterHistory?: Record<number, Array<{ value: number; timestamp: string }>> | null;
  weatherReadings?: Record<number, Reading> | null;
  weatherHistory?: Record<number, Array<{ data: any; timestamp: string }>> | null;
  tides?: Record<string, Tide[]> | null;
  tideHeights?: Array<{ dt: number; date: string; height: number }>;
  refreshingWater?: boolean;
  refreshingWeather?: boolean;
}>();
const emit = defineEmits<{ (e: 'refresh-water'): void; (e: 'refresh-weather'): void }>();
const now = ref(new Date());
const activeWeather = ref(0);
const wallMode = ref(false);
let timer: ReturnType<typeof setInterval> | undefined;
onMounted(() => {
  wallMode.value = window.innerWidth >= 3000 && window.innerHeight >= 1600;
  timer = setInterval(() => now.value = new Date(), 1000);
});
onUnmounted(() => { if (timer) clearInterval(timer); });
const selectedWeather = computed(() => props.stations?.[activeWeather.value] ?? props.stations?.[0]);
const selectWeather = (index: number) => { activeWeather.value = index; };
const selectedWeatherReading = computed(() => selectedWeather.value ? props.weatherReadings?.[selectedWeather.value.id] : undefined);
const extremes = computed(() => Object.values(props.tides || {}).flat().sort((a, b) => a.dt - b.dt));
const nextExtremes = computed(() => extremes.value.filter(t => t.dt * 1000 >= now.value.getTime()).slice(0, 4));
const waterStatus = computed(() => (props.sensors || []).map(s => {
  const entry = props.waterReadings?.[s.id];
  const value = entry?.success ? Number(entry.data) : NaN;
  const thresholdsValid = [s.level_2, s.level_3, s.level_4].every(x => Number.isFinite(Number(x))) && Number(s.level_2) < Number(s.level_3) && Number(s.level_3) < Number(s.level_4);
  const level = !Number.isFinite(value) || !thresholdsValid ? -1 : value >= s.level_4 ? 4 : value >= s.level_3 ? 3 : value >= s.level_2 ? 2 : 0;
  return { ...s, value, level };
}));
const highest = computed(() => Math.max(0, ...waterStatus.value.map(s => s.level)));
const formatTide = (t: Tide) => new Date(t.dt * 1000).toLocaleString('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', hour: '2-digit', minute: '2-digit' });
const formatNumber = (v: any, digits = 1) => v === null || v === undefined || v === '' || !Number.isFinite(Number(v)) ? '—' : Number(v).toFixed(digits);
</script>

<template>
  <section class="ops4k" :class="{ 'ops4k--wall': wallMode }" aria-label="4K single-screen hydrometeorological monitoring">
    <header class="ops4k__header">
      <div class="ops4k__brand"><div class="ops4k__symbol">L<span>F</span></div><div><div class="ops4k__eyebrow">LOCAL FLOOD EARLY WARNING SYSTEM · BAYAWAN CITY</div><h1>HYDROMETEOROLOGICAL <span>COMMAND DISPLAY</span></h1></div></div>
      <div class="ops4k__header-right"><button type="button" class="ops4k__wall-toggle" :aria-pressed="wallMode" @click="wallMode = !wallMode">{{ wallMode ? "Exit full screen" : "4K wall view" }}</button><span class="ops4k__indicator" :class="highest >= 4 ? 'ops4k__indicator--critical' : highest >= 2 ? 'ops4k__indicator--warning' : ''">{{ highest >= 4 ? 'CRITICAL WATER LEVEL' : highest >= 2 ? 'ELEVATED WATER LEVEL' : 'MONITORING ACTIVE' }}</span><div class="ops4k__clock"><strong>{{ now.toLocaleTimeString('en-PH', { timeZone: 'Asia/Manila', hour12: false }) }}</strong><small>{{ now.toLocaleDateString('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) }} · PHT</small></div></div>
    </header>
    <div class="ops4k__layout">
      <section class="ops4k__panel ops4k__water">
        <div class="ops4k__panel-title"><div><span>01 / HYDROLOGICAL TELEMETRY</span><h2>Water Level Sensor Data</h2></div><button :disabled="refreshingWater" @click="emit('refresh-water')">{{ refreshingWater ? 'Updating…' : '↻ Refresh' }}</button></div>
        <div class="ops4k__chart ops4k__water-chart"><WaterLevelCombinedChart :sensors="sensors || []" :latest-data="waterReadings as any" :history-data="waterHistory || undefined" /></div>
        <div class="ops4k__sensor-grid"><div v-for="sensor in waterStatus" :key="sensor.id" class="ops4k__sensor"><div class="ops4k__sensor-title"><span>{{ sensor.name }}</span><b :class="'ops4k__severity--' + sensor.level">{{ sensor.level === -1 ? 'NO DATA' : sensor.level === 4 ? 'CRITICAL' : sensor.level === 3 ? 'WARNING' : sensor.level === 2 ? 'CAUTION' : 'NORMAL' }}</b></div><strong>{{ Number.isFinite(sensor.value) ? sensor.value.toFixed(2) : '—' }}</strong><small>L2 {{ sensor.level_2 }} · L3 {{ sensor.level_3 }} · L4 {{ sensor.level_4 }}</small></div><p v-if="!waterStatus.length" class="ops4k__empty">No water sensors configured</p></div>
      </section>
      <section class="ops4k__panel ops4k__weather">
        <div class="ops4k__panel-title"><div><span>02 / METEOROLOGICAL TELEMETRY</span><h2>Weather Station Observation</h2></div><button :disabled="refreshingWeather" @click="emit('refresh-weather')">{{ refreshingWeather ? 'Updating…' : '↻ Refresh' }}</button></div>
        <div v-if="stations?.length" class="ops4k__station-tabs"><button v-for="(station,index) in stations" :key="station.id" :class="{ active: activeWeather === index }" @click="selectWeather(index)">{{ station.name }}</button></div>
        <div class="ops4k__weather-summary"><div><span>TEMPERATURE</span><strong>{{ formatNumber(selectedWeatherReading?.data?.temperature) }}<small>°C</small></strong></div><div><span>HEAT INDEX</span><strong>{{ formatNumber(selectedWeatherReading?.data?.heat_index) }}<small>°C</small></strong></div><div><span>HUMIDITY</span><strong>{{ formatNumber(selectedWeatherReading?.data?.humidity, 0) }}<small>%</small></strong></div><div><span>RAINFALL RATE</span><strong>{{ formatNumber(selectedWeatherReading?.data?.precipitation_rate) }}<small>mm/h</small></strong></div></div>
        <div class="ops4k__chart ops4k__weather-chart"><WeatherStationChart v-if="selectedWeather" :key="selectedWeather.id" :station-id="selectedWeather.id" :name="selectedWeather.name" :history="weatherHistory?.[selectedWeather.id] || []" /><p v-else class="ops4k__empty">No weather stations configured</p></div>
        <div class="ops4k__weather-bottom"><span>WIND SPEED <b>{{ formatNumber(selectedWeatherReading?.data?.wind_speed) }}</b></span><span>WIND GUST <b>{{ formatNumber(selectedWeatherReading?.data?.wind_gust) }}</b></span><span>PRESSURE <b>{{ formatNumber(selectedWeatherReading?.data?.pressure) }} hPa</b></span><span>STATIONS <b>{{ stations?.length || 0 }}</b></span></div>
      </section>
      <section class="ops4k__panel ops4k__tides">
        <div class="ops4k__panel-title"><div><span>03 / COASTAL OBSERVATION</span><h2>Tidal Extremes</h2></div><span class="ops4k__tag">PREDICTED TIDES</span></div>
        <div class="ops4k__chart ops4k__tide-chart"><TideChart v-if="tideHeights?.length" :heights="tideHeights" :extremes="extremes" /><p v-else class="ops4k__empty">Tide height forecast unavailable</p></div>
        <div class="ops4k__tide-list"><div v-for="tide in nextExtremes" :key="tide.id"><span :class="tide.type.toLowerCase() === 'high' ? 'ops4k__high' : 'ops4k__low'">{{ tide.type.toLowerCase() === 'high' ? '↑ HIGH' : '↓ LOW' }}</span><strong>{{ Number(tide.height).toFixed(2) }} <small>m</small></strong><time>{{ formatTide(tide) }}</time></div><p v-if="!nextExtremes.length" class="ops4k__empty">No upcoming tidal extremes available</p></div>
      </section>
    </div>
    <footer class="ops4k__footer"><span><i></i> AUTO-REFRESH · Dashboard polling every 5 minutes when enabled · Charts update with latest observations</span><span>Operational monitoring only · Verify observations and thresholds before public advisories</span></footer>
  </section>
</template>
