<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";

const props = defineProps<{
  stations: Array<{
    id: number;
    name: string;
    station_id: string;
    key?: string;
  }>;
  filters: {
    station_id: string;
    start_date: string;
    end_date: string;
  };
  tableData: Array<Record<string, any>>;
  apiKeyUsed?: string;
  error?: string | null;
}>();

const selectedStationId = ref(props.filters.station_id || (props.stations[0]?.station_id ?? ""));
const startDate = ref(props.filters.start_date || new Date().toISOString().split("T")[0]);
const endDate = ref(props.filters.end_date || new Date().toISOString().split("T")[0]);
const isLoading = ref(false);

const fetchData = () => {
  if (!selectedStationId.value) return;
  isLoading.value = true;
  router.get(
    route("wunderground-integration.index"),
    {
      station_id: selectedStationId.value,
      start_date: startDate.value,
      end_date: endDate.value,
      fetch: 1,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        isLoading.value = false;
      },
    }
  );
};

const formatPressure = (val: any) => {
  if (val === null || val === undefined || val === "") return "—";
  const num = parseFloat(String(val).replace(/,/g, ""));
  if (isNaN(num)) return `${val}`;
  return num.toFixed(2);
};

const formatPrecipRate = (val: any) => {
  if (val === null || val === undefined || val === "") return "0.00";
  const num = parseFloat(String(val).replace(/,/g, ""));
  if (isNaN(num)) return `${val}`;
  return num.toFixed(2);
};

const formatPrecipAccum = (val: any) => {
  if (val === null || val === undefined || val === "") return "0.00";
  const num = parseFloat(String(val).replace(/,/g, ""));
  if (isNaN(num)) return `${val}`;
  return num.toFixed(2);
};
</script>

<template>
  <AppLayout title="WunderGround Integration">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        WunderGround Integration
      </h2>
    </template>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
      <!-- Filter Controls Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <form @submit.prevent="fetchData" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <!-- Station ID Dropdown -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
              Select Station ID
            </label>
            <select
              v-model="selectedStationId"
              class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
            >
              <option v-for="st in stations" :key="st.id" :value="st.station_id">
                {{ st.name }} ({{ st.station_id }})
              </option>
            </select>
          </div>

          <!-- Start Date -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
              Start Date
            </label>
            <input
              type="date"
              v-model="startDate"
              class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
            />
          </div>

          <!-- End Date -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
              End Date
            </label>
            <input
              type="date"
              v-model="endDate"
              class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
            />
          </div>

          <!-- Fetch Button -->
          <div>
            <button
              type="submit"
              :disabled="isLoading"
              class="w-full py-2.5 px-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl shadow-md transition-all duration-200 flex items-center justify-center space-x-2 disabled:opacity-50"
            >
              <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isLoading ? 'Fetching...' : 'Fetch Weather Data' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Error Alert -->
      <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-300 text-sm">
        {{ error }}
      </div>

      <!-- Observations Data Table Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <div>
            <h3 class="font-bold text-gray-900 dark:text-white text-base">
              Station Observations Data
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Showing observations for <span class="font-semibold text-gray-700 dark:text-gray-200">{{ selectedStationId }}</span> ({{ startDate }} to {{ endDate }})
            </p>
          </div>
          <span class="text-xs px-3 py-1 bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300 font-bold rounded-full">
            {{ tableData ? tableData.length : 0 }} Records
          </span>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-gray-200/70 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold uppercase tracking-tight border-b border-gray-300 dark:border-gray-600">
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">date_time</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">temperature</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">heat_index</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">dewpoint</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">humidity</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">wind_speed</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">wind_direction</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">wind_gust</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">pressure</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">precipitation_rate</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">precipitation_total</th>
                <th class="py-3 px-3 border-r border-gray-300 dark:border-gray-600">uv</th>
                <th class="py-3 px-3">solar_radiation</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 font-medium">
              <tr
                v-for="(row, idx) in tableData"
                :key="idx"
                class="hover:bg-orange-50/50 dark:hover:bg-gray-750 transition-colors border-b border-gray-200 dark:border-gray-700"
                :class="idx % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/40 dark:bg-gray-800/60'"
              >
                <!-- date_time -->
                <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['date_time'] || '—' }}
                </td>
                <!-- temperature -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['temperature'] !== null && row['temperature'] !== undefined && row['temperature'] !== '' ? row['temperature'] : '—' }}
                </td>
                <!-- heat_index -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['heat_index'] !== null && row['heat_index'] !== undefined && row['heat_index'] !== '' ? row['heat_index'] : '—' }}
                </td>
                <!-- dewpoint -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['dewpoint'] !== null && row['dewpoint'] !== undefined && row['dewpoint'] !== '' ? row['dewpoint'] : '—' }}
                </td>
                <!-- humidity -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['humidity'] !== null && row['humidity'] !== undefined && row['humidity'] !== '' ? row['humidity'] : '—' }}
                </td>
                <!-- wind_speed -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['wind_speed'] !== null && row['wind_speed'] !== undefined && row['wind_speed'] !== '' ? row['wind_speed'] : '—' }}
                </td>
                <!-- wind_direction -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['wind_direction'] || '—' }}
                </td>
                <!-- wind_gust -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['wind_gust'] !== null && row['wind_gust'] !== undefined && row['wind_gust'] !== '' ? row['wind_gust'] : '—' }}
                </td>
                <!-- pressure -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ formatPressure(row['pressure']) }}
                </td>
                <!-- precipitation_rate -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ formatPrecipRate(row['precipitation_rate']) }}
                </td>
                <!-- precipitation_total -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ formatPrecipAccum(row['precipitation_total']) }}
                </td>
                <!-- uv -->
                <td class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                  {{ row['uv'] !== null && row['uv'] !== undefined ? row['uv'] : '' }}
                </td>
                <!-- solar_radiation -->
                <td class="py-2.5 px-3 whitespace-nowrap">
                  {{ row['solar_radiation'] !== null && row['solar_radiation'] !== undefined && row['solar_radiation'] !== '' ? row['solar_radiation'] : '' }}
                </td>
              </tr>
              <tr v-if="!tableData || tableData.length === 0">
                <td colspan="13" class="py-8 text-center text-gray-500 dark:text-gray-400">
                  No weather observation data available for the selected range. Click "Fetch Weather Data" to retrieve observations.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
