<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import WindyMap from '@/Components/WindyMap.vue';

declare function route(name: string, params?: any): string;

type Sensor = { id: number; name: string; level_2: number; level_3: number; level_4: number };
type Station = { id: number; name: string; station_id?: string };
type Center = { id: number; name: string; address?: string; curren_resident: string | number; max_capacity: string | number };
type Reading = { success?: boolean; data?: any; timestamp?: string; error?: string };

const props = defineProps<{
  sensors: Sensor[];
  stations: Station[];
  waterReadings?: Record<number, Reading> | null;
  weatherReadings?: Record<number, Reading> | null;
  evacuationCenters?: Center[];
  windyApiKey?: string;
  refreshingWater?: boolean;
  refreshingWeather?: boolean;
}>();

const emit = defineEmits<{ (event: 'refresh-water'): void; (event: 'refresh-weather'): void }>();
const now = ref(new Date());
const wallMode = ref(false);
const showMap = ref(true);
let tick: ReturnType<typeof setInterval> | undefined;

onMounted(() => { tick = setInterval(() => { now.value = new Date(); }, 1000); });
onUnmounted(() => { if (tick) clearInterval(tick); });

const formatTime = (value?: string) => {
  if (!value) return 'No report';
  const parsed = new Date(value.replace(' ', 'T'));
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
const ageMinutes = (value?: string) => {
  if (!value) return Number.POSITIVE_INFINITY;
  const parsed = new Date(value.replace(' ', 'T'));
  return Number.isNaN(parsed.getTime()) ? Number.POSITIVE_INFINITY : Math.max(0, (now.value.getTime() - parsed.getTime()) / 60000);
};
const water = computed(() => (props.sensors || []).map(s => {
  const record = props.waterReadings?.[s.id];
  const measured = record?.success ? Number(record.data) : NaN;
  const valid = Number.isFinite(measured);
  const thresholdsValid = [s.level_2, s.level_3, s.level_4].every(n => Number.isFinite(Number(n))) &&
    Number(s.level_2) < Number(s.level_3) && Number(s.level_3) < Number(s.level_4);
  const level = valid && thresholdsValid
    ? measured >= Number(s.level_4) ? 4 : measured >= Number(s.level_3) ? 3 : measured >= Number(s.level_2) ? 2 : 0
    : null;
  const updatedAt = record?.timestamp;
  return { ...s, measured, valid, level, thresholdsValid, updatedAt, stale: ageMinutes(updatedAt) > 15 };
}));
const weather = computed(() => (props.stations || []).map(s => {
  const record = props.weatherReadings?.[s.id];
  const data = record?.success ? record.data : null;
  const heat = Number(data?.heat_index);
  const rain = Number(data?.precipitation_rate);
  return { ...s, data, updatedAt: data?.date_time || record?.timestamp, heat: data?.heat_index == null ? null : (Number.isFinite(heat) ? heat : null), rain: data?.precipitation_rate == null ? null : (Number.isFinite(rain) ? rain : null) };
}));
const activeWater = computed(() => water.value.filter(x => x.valid && !x.stale).length);
const elevated = computed(() => water.value.filter(x => !x.stale && x.level !== null && x.level >= 2));
const critical = computed(() => elevated.value.filter(x => x.level === 4));
const missingWater = computed(() => water.value.filter(x => !x.valid || x.stale || !x.thresholdsValid));
const activeWeather = computed(() => weather.value.filter(x => x.data && ageMinutes(x.updatedAt) <= 15).length);
const staleWeather = computed(() => weather.value.filter(x => !x.data || ageMinutes(x.updatedAt) > 15));
const occupancy = computed(() => (props.evacuationCenters || []).reduce((sum, center) => sum + Math.max(0, Number(center.curren_resident) || 0), 0));
const capacity = computed(() => (props.evacuationCenters || []).reduce((sum, center) => sum + Math.max(0, Number(center.max_capacity) || 0), 0));
const percent = computed(() => capacity.value ? Math.round(occupancy.value / capacity.value * 100) : null);
const highHeat = computed(() => weather.value.filter(x => x.heat !== null && x.heat >= 42 && ageMinutes(x.updatedAt) <= 15));
const alerts = computed(() => [
  ...critical.value.map(x => ({ id: 'water-' + x.id, kind: 'CRITICAL', title: x.name, detail: 'Water level exceeded configured critical threshold', time: x.updatedAt, order: 0 })),
  ...elevated.value.filter(x => x.level !== 4).map(x => ({ id: 'water-' + x.id, kind: x.level === 3 ? 'WARNING' : 'CAUTION', title: x.name, detail: 'Water level at alert level ' + x.level, time: x.updatedAt, order: x.level === 3 ? 1 : 2 })),
  ...highHeat.value.map(x => ({ id: 'heat-' + x.id, kind: 'HEAT', title: x.name, detail: 'Elevated heat index: ' + x.heat?.toFixed(1) + ' °C', time: x.updatedAt, order: 2 })),
  ...missingWater.value.map(x => ({ id: 'offline-water-' + x.id, kind: 'DATA', title: x.name, detail: !x.thresholdsValid ? 'Review sensor alert thresholds' : 'Missing or stale water level observation', time: x.updatedAt, order: 3 })),
  ...staleWeather.value.map(x => ({ id: 'offline-weather-' + x.id, kind: 'DATA', title: x.name, detail: 'Missing or stale weather observation', time: x.updatedAt, order: 3 }))
].sort((a, b) => a.order - b.order));
const currentState = computed(() => critical.value.length ? 'CRITICAL CONDITIONS' : elevated.value.length ? 'ELEVATED WATER LEVELS' : (missingWater.value.length || staleWeather.value.length) ? 'DATA INCOMPLETE' : 'NO THRESHOLD BREACHES');
const stateColor = computed(() => critical.value.length ? 'critical' : elevated.value.length ? 'warning' : (missingWater.value.length || staleWeather.value.length) ? 'unknown' : 'normal');
const waterLabel = (level: number | null, stale: boolean) => stale || level === null ? 'UNVERIFIED' : level === 4 ? 'CRITICAL' : level === 3 ? 'WARNING' : level === 2 ? 'CAUTION' : 'NORMAL';
const waterTone = (level: number | null, stale: boolean) => stale || level === null ? 'unknown' : level === 4 ? 'critical' : level === 3 ? 'warning' : level === 2 ? 'caution' : 'normal';
</script>

<template>
  <section class="command-center" :class="{ 'command-center--wall': wallMode }" aria-label="Emergency operations monitoring overview">
    <header class="command-center__top">
      <div class="command-center__identity">
        <span class="command-center__eyebrow"><span class="command-center__live-dot"></span> LOCAL FLOOD EARLY WARNING SYSTEM / OPERATIONS</span>
        <h1>Emergency Operations <span>Command Center</span></h1>
        <p>Real-time situational awareness · Bayawan City, Negros Oriental</p>
      </div>
      <div class="command-center__actions">
        <div class="command-center__clock" aria-label="Local Philippine time">{{ now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'Asia/Manila' }) }}<strong>{{ now.toLocaleTimeString('en-PH', { hour12: false, timeZone: 'Asia/Manila' }) }}</strong><span>PHILIPPINE STANDARD TIME</span></div>
        <button class="command-center__button" type="button" @click="showMap = !showMap" :aria-pressed="showMap">{{ showMap ? 'Hide map' : 'Show map' }}</button>
        <button class="command-center__button" type="button" @click="wallMode = !wallMode" :aria-pressed="wallMode">{{ wallMode ? 'Exit wall view' : 'Wall display' }}</button>
      </div>
    </header>

    <div class="command-center__status" :class="'command-center__status--' + stateColor" role="status">
      <div><span class="command-center__status-label">CURRENT MONITORING STATUS</span><strong>{{ currentState }}</strong><small>Based on configured water thresholds and observation freshness; not an official emergency declaration.</small></div>
      <span class="command-center__status-count">{{ alerts.length }} <small>ITEMS TO REVIEW</small></span>
    </div>

    <div class="command-center__metrics">
      <div class="command-center__metric"><span>WATER LEVEL SENSORS</span><strong>{{ activeWater }}<small> / {{ water.length }}</small></strong><p>Reporting in last 15 minutes</p></div>
      <div class="command-center__metric"><span>WEATHER OBSERVATIONS</span><strong>{{ activeWeather }}<small> / {{ weather.length }}</small></strong><p>Current station reports</p></div>
      <div class="command-center__metric"><span>ELEVATED WATER LEVELS</span><strong :class="{ 'command-center__metric-alert': elevated.length }">{{ elevated.length }}</strong><p>{{ critical.length }} critical threshold breaches</p></div>
      <div class="command-center__metric"><span>EVACUATION OCCUPANCY</span><strong>{{ occupancy.toLocaleString() }}<small v-if="capacity"> / {{ capacity.toLocaleString() }}</small></strong><p>{{ percent === null ? 'Capacity data unavailable' : percent + '% of recorded capacity' }}</p></div>
    </div>

    <div class="command-center__grid">
      <div class="command-center__primary">
        <div v-if="showMap" class="command-center__panel command-center__map">
          <div class="command-center__panel-heading"><div><span>01 / REGIONAL OVERVIEW</span><h2>Live Weather Intelligence</h2></div><span class="command-center__pill">FORECAST OVERLAY</span></div>
          <WindyMap :api-key="windyApiKey" title="Regional weather overlay · forecast, not station observation" />
        </div>

        <div class="command-center__panel">
          <div class="command-center__panel-heading"><div><span>02 / WATER LEVEL NETWORK</span><h2>River Monitoring Stations</h2></div><button class="command-center__button" type="button" :disabled="refreshingWater" @click="emit('refresh-water')">{{ refreshingWater ? 'Refreshing…' : 'Refresh readings' }}</button></div>
          <div v-if="water.length" class="command-center__station-grid">
            <article v-for="sensor in water" :key="sensor.id" class="command-center__station" :class="'command-center__station--' + waterTone(sensor.level, sensor.stale)">
              <div class="command-center__station-head"><strong>{{ sensor.name }}</strong><span class="command-center__pill" :class="'command-center__pill--' + waterTone(sensor.level, sensor.stale)">{{ waterLabel(sensor.level, sensor.stale) }}</span></div>
              <div class="command-center__reading">{{ sensor.valid ? sensor.measured.toFixed(2) : '—' }} <small>{{ sensor.valid ? 'sensor units' : 'no data' }}</small></div>
              <div class="command-center__thresholds"><span>L2 {{ sensor.level_2 }}</span><span>L3 {{ sensor.level_3 }}</span><span>L4 {{ sensor.level_4 }}</span></div>
              <small class="command-center__updated">Last observation: {{ formatTime(sensor.updatedAt) }}</small>
            </article>
          </div>
          <p v-else class="command-center__empty">No water level sensors configured.</p>
        </div>

        <div class="command-center__panel">
          <div class="command-center__panel-heading"><div><span>03 / METEOROLOGICAL NETWORK</span><h2>Weather Station Telemetry</h2></div><button class="command-center__button" type="button" :disabled="refreshingWeather" @click="emit('refresh-weather')">{{ refreshingWeather ? 'Refreshing…' : 'Refresh readings' }}</button></div>
          <div v-if="weather.length" class="command-center__weather-grid">
            <article v-for="station in weather" :key="station.id" class="command-center__weather-card">
              <div class="command-center__station-head"><strong>{{ station.name }}</strong><span class="command-center__pill" :class="station.data && ageMinutes(station.updatedAt) <= 15 ? 'command-center__pill--normal' : 'command-center__pill--unknown'">{{ station.data && ageMinutes(station.updatedAt) <= 15 ? 'CURRENT' : 'STALE / NO DATA' }}</span></div>
              <div class="command-center__weather-main"><div><span>TEMPERATURE</span><strong>{{ station.data?.temperature == null ? '—' : Number(station.data.temperature).toFixed(1) + '°' }}</strong></div><div><span>HEAT INDEX</span><strong>{{ station.heat === null ? '—' : station.heat.toFixed(1) + '°' }}</strong></div></div>
              <div class="command-center__weather-facts"><span>Humidity <b>{{ station.data?.humidity == null ? '—' : Number(station.data.humidity).toFixed(0) + '%' }}</b></span><span>Rain rate <b>{{ station.rain === null ? '—' : station.rain.toFixed(1) + ' mm/h' }}</b></span><span>Wind <b>{{ station.data?.wind_speed == null ? '—' : Number(station.data.wind_speed).toFixed(1) }}</b></span></div>
              <small class="command-center__updated">Last observation: {{ formatTime(station.updatedAt) }}</small>
            </article>
          </div>
          <p v-else class="command-center__empty">No weather stations configured.</p>
        </div>
      </div>

      <aside class="command-center__secondary">
        <section class="command-center__panel">
          <div class="command-center__panel-heading"><div><span>EVENT / ATTENTION QUEUE</span><h2>Conditions to Review</h2></div><span class="command-center__pill">{{ alerts.length }} ITEMS</span></div>
          <div v-if="alerts.length" class="command-center__alerts" aria-live="polite">
            <div v-for="alert in alerts.slice(0, 12)" :key="alert.id" class="command-center__alert" :class="'command-center__alert--' + alert.kind.toLowerCase()">
              <span class="command-center__pill" :class="'command-center__pill--' + (alert.kind === 'CRITICAL' ? 'critical' : alert.kind === 'WARNING' ? 'warning' : alert.kind === 'DATA' ? 'unknown' : 'caution')">{{ alert.kind }}</span>
              <strong>{{ alert.title }}</strong><p>{{ alert.detail }}</p><small>{{ formatTime(alert.time) }}</small>
            </div>
            <p v-if="alerts.length > 12" class="command-center__empty">+ {{ alerts.length - 12 }} additional items. Review monitoring details below.</p>
          </div>
          <p v-else class="command-center__empty">No water threshold breaches or missing station observations detected. Continue routine monitoring.</p>
        </section>

        <section class="command-center__panel">
          <div class="command-center__panel-heading"><div><span>SHELTER OPERATIONS</span><h2>Evacuation Facilities</h2></div><Link :href="route('evacuation-center.index')" class="command-center__text-link">View all</Link></div>
          <div v-if="evacuationCenters?.length" class="command-center__centers">
            <div v-for="center in evacuationCenters.slice(0, 8)" :key="center.id" class="command-center__center">
              <div><strong>{{ center.name }}</strong><small>{{ center.curren_resident }} / {{ center.max_capacity }} occupants</small></div>
              <span :class="Number(center.max_capacity) > 0 && Number(center.curren_resident) >= Number(center.max_capacity) ? 'command-center__metric-alert' : ''">{{ Number(center.max_capacity) > 0 ? Math.round(Number(center.curren_resident) / Number(center.max_capacity) * 100) + '%' : 'N/A' }}</span>
              <div class="command-center__bar"><span :style="{ width: Math.min(100, Number(center.max_capacity) > 0 ? Math.max(0, Number(center.curren_resident) / Number(center.max_capacity) * 100) : 0) + '%' }"></span></div>
            </div>
          </div>
          <p v-else class="command-center__empty">No evacuation center data available.</p>
        </section>
        <section class="command-center__panel command-center__quicklinks">
          <div class="command-center__panel-heading"><div><span>OPERATOR SHORTCUTS</span><h2>Quick Access</h2></div></div>
          <Link :href="route('locator')">Station locator <span>↗</span></Link>
          <Link :href="route('flood_risks.index')">Flood hazard map <span>↗</span></Link>
          <Link :href="route('reports')">Monitoring reports <span>↗</span></Link>
        </section>
      </aside>
    </div>
    <footer class="command-center__footer">LFEWS / COMMAND OVERVIEW · Readings are informational. Confirm observations, timestamps and agency protocols before issuing public warnings.</footer>
  </section>
</template>
