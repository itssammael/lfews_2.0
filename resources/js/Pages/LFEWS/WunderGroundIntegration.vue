<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import axios from "axios";

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

const showConfirmModal = ref(false);
const isImporting = ref(false);
const importProgress = ref(0);
const importedCount = ref(0);
const totalRecords = ref(0);
const importSuccessMessage = ref<string | null>(null);
const importErrorMessage = ref<string | null>(null);

const selectedStationObj = computed(() => {
  return props.stations.find((st) => st.station_id === selectedStationId.value);
});

const fetchData = () => {
  if (!selectedStationId.value) return;
  isLoading.value = true;
  importSuccessMessage.value = null;
  importErrorMessage.value = null;
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

const confirmImport = () => {
  if (!selectedStationId.value || !props.tableData || props.tableData.length === 0) {
    return;
  }
  showConfirmModal.value = true;
};

const executeImport = async () => {
  showConfirmModal.value = false;
  await handleImport();
};

const handleImport = async () => {
  if (!selectedStationId.value || !props.tableData || props.tableData.length === 0) {
    return;
  }

  const allRows = props.tableData;
  const chunkSize = 50;
  const total = allRows.length;

  isImporting.value = true;
  importProgress.value = 0;
  importedCount.value = 0;
  totalRecords.value = total;
  importSuccessMessage.value = null;
  importErrorMessage.value = null;

  try {
    for (let i = 0; i < total; i += chunkSize) {
      const chunk = allRows.slice(i, i + chunkSize);

      await axios.post(route("wunderground-integration.import"), {
        station_id: selectedStationId.value,
        rows: chunk,
      });

      importedCount.value = Math.min(i + chunkSize, total);
      importProgress.value = (importedCount.value / total) * 100;

      await new Promise((resolve) => setTimeout(resolve, 10));
    }

    const stName = selectedStationObj.value
      ? `${selectedStationObj.value.name} (${selectedStationObj.value.station_id})`
      : selectedStationId.value;

    importSuccessMessage.value = `Successfully imported ${total} records for station ${stName} into weather_station_observation_data table.`;

    window.dispatchEvent(
      new CustomEvent("toast", {
        detail: {
          message: "Data imported successfully.",
          type: "success",
        },
      })
    );
  } catch (err: any) {
    console.error(err);
    importErrorMessage.value =
      err.response?.data?.message || "An error occurred during import. Please check console for details.";

    window.dispatchEvent(
      new CustomEvent("toast", {
        detail: {
          message: "An error occurred during import.",
          type: "error",
        },
      })
    );
  } finally {
    isImporting.value = false;
    importProgress.value = 0;
    importedCount.value = 0;
    totalRecords.value = 0;
  }
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

    <!-- Confirmation Modal -->
    <div
      v-if="showConfirmModal"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-2xl max-w-md w-full border border-gray-100 dark:border-gray-700 transform transition-all space-y-6"
      >
        <div class="flex items-center space-x-3 text-orange-600 dark:text-orange-500">
          <div class="p-3 bg-orange-100 dark:bg-orange-900/40 rounded-2xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
              Confirm Data Import
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Please review before proceeding
            </p>
          </div>
        </div>

        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
          Are you sure you want to import fetched observation data <span class="font-bold text-gray-900 dark:text-white">{{ startDate }}</span> to <span class="font-bold text-gray-900 dark:text-white">{{ endDate }}</span> for <span class="font-bold text-gray-900 dark:text-white">{{ selectedStationObj ? selectedStationObj.name + ' (' + selectedStationObj.station_id + ')' : selectedStationId }}</span>?
        </p>

        <div class="flex items-center justify-end space-x-3 pt-2">
          <button
            @click="showConfirmModal = false"
            class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold text-xs rounded-xl transition-all duration-200"
          >
            Cancel
          </button>
          <button
            @click="executeImport"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow transition-all duration-200 flex items-center space-x-1.5"
          >
            <span>Confirm Import</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Import Progress Loader Overlay -->
    <div
      v-if="isImporting"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-2xl max-w-sm w-full mx-4 border border-gray-100 dark:border-gray-700 transform transition-all"
      >
        <div class="flex items-center justify-between mb-6">
          <div class="flex flex-col">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
              Importing Data
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Processed Records: {{ importedCount }} / {{ totalRecords }}
            </p>
          </div>
          <div class="flex flex-col items-end">
            <span class="text-2xl font-black text-orange-600">
              {{ Math.round(importProgress) }}%
            </span>
          </div>
        </div>

        <div
          class="w-full bg-gray-100 dark:bg-gray-700/50 rounded-full h-3 mb-6 overflow-hidden p-0.5"
        >
          <div
            class="bg-gradient-to-r from-orange-500 to-orange-400 h-full rounded-full transition-all duration-300 ease-out shadow-[0_0_10px_rgba(249,115,22,0.4)]"
            :style="{ width: `${importProgress}%` }"
          ></div>
        </div>

        <div class="flex items-center justify-center space-x-2 text-gray-400">
          <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          <span class="text-[10px] font-bold uppercase tracking-widest">
            Please wait...
          </span>
        </div>
      </div>
    </div>

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

      <!-- Alerts -->
      <div v-if="error || importErrorMessage" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-300 text-sm">
        {{ error || importErrorMessage }}
      </div>

      <div v-if="importSuccessMessage" class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-300 text-sm flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ importSuccessMessage }}</span>
        </div>
      </div>

      <!-- Observations Data Table Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-4">
          <div>
            <h3 class="font-bold text-gray-900 dark:text-white text-base">
              Station Observations Data
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Showing observations for <span class="font-semibold text-gray-700 dark:text-gray-200">{{ selectedStationId }}</span> ({{ startDate }} to {{ endDate }})
            </p>
          </div>

          <div class="flex items-center space-x-3">
            <span class="text-xs px-3 py-1 bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300 font-bold rounded-full">
              {{ tableData ? tableData.length : 0 }} Records
            </span>

            <button
              v-if="$page.props.auth.can.manage"
              @click="confirmImport"
              :disabled="isImporting || !tableData || tableData.length === 0"
              class="py-1.5 px-4 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-semibold text-xs rounded-xl shadow transition-all duration-200 flex items-center space-x-1.5"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              <span>Import to DB</span>
            </button>
          </div>
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
