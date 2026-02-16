<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import * as XLSX from 'xlsx';

const props = defineProps<{
    weatherStations: any[];
    waterLevelSensors: any[];
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const uploadedData = ref<any[]>([]);
const columns = ref<string[]>([]);
const targetType = ref('weather_station');
const targetId = ref<number | null>(null);

// Pagination State
const currentPage = ref(1);
const rowsPerPage = 10;

const form = useForm({
    target: 'weather_station',
    target_id: null as number | null,
    rows: [] as any[],
});

const formatDate = (date: any) => {
    if (!date) return '';
    
    let d: Date;
    if (date instanceof Date) {
        d = date;
    } else {
        // Handle Excel numeric dates if they come through as numbers
        if (typeof date === 'number') {
            d = new Date((date - 25569) * 86400 * 1000);
        } else {
            d = new Date(date);
        }
    }

    if (isNaN(d.getTime())) return date;

    // Use Intl.DateTimeFormat to force Asia/Manila (GMT+8)
    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: 'numeric',
        minute: 'numeric',
        second: 'numeric',
        hour12: false
    });

    const parts = formatter.formatToParts(d);
    const getPart = (type: string) => parts.find(p => p.type === type)?.value || '';

    const year = getPart('year');
    const month = getPart('month');
    const day = getPart('day');
    const hour = getPart('hour');
    const minute = getPart('minute');
    const second = getPart('second');

    return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
};

const fileName = ref<string>('No file chosen');

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) {
        fileName.value = 'No file chosen';
        return;
    }
    
    fileName.value = file.name;

    const reader = new FileReader();

    if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.name.endsWith('.csv')) {
        reader.onload = (e) => {
            const data = e.target?.result;
            const workbook = XLSX.read(data, { type: 'binary', cellDates: true });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const json = XLSX.utils.sheet_to_json(worksheet);
            
            if (json.length > 0) {
                columns.value = Object.keys(json[0] as object);
                uploadedData.value = json.map((row: any) => {
                    const newRow = { ...row };
                    Object.keys(newRow).forEach(key => {
                        const k = key.toLowerCase();
                        if (k === 'date' || k === 'date_time' || k === 'datetime' || k === 'timestamp' || k.includes('date')) {
                            if (newRow[key]) {
                                newRow[key] = formatDate(newRow[key]);
                            }
                        }
                    });
                    return newRow;
                });
            }
        };
        reader.readAsBinaryString(file);
    } else if (file.name.endsWith('.sql')) {
        // Basic SQL parsing logic (very simplified)
        reader.onload = (e) => {
            const content = e.target?.result as string;
            alert('SQL parsing is limited. Please use CSV/XLSX for better compatibility.');
        };
        reader.readAsText(file);
    }
};

const targetOptions = computed(() => {
    return targetType.value === 'weather_station' ? props.weatherStations : props.waterLevelSensors;
});

const importData = () => {
    if (!targetId.value) {
        alert('Please select a target station/sensor.');
        return;
    }

    form.target = targetType.value;
    form.target_id = targetId.value;
    form.rows = uploadedData.value;

    form.post(route('data-migration.import'), {
        onSuccess: () => {
            uploadedData.value = [];
            columns.value = [];
            fileName.value = 'No file chosen';
            currentPage.value = 1;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const totalPages = computed(() => Math.ceil(uploadedData.value.length / rowsPerPage));

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    return uploadedData.value.slice(start, end).map((item, index) => ({
        ...item,
        originalIndex: start + index
    }));
});

const removeRow = (index: number) => {
    uploadedData.value.splice(index, 1);
    // Adjust current page if the last row on the last page was removed
    if (currentPage.value > totalPages.value && totalPages.value > 0) {
        currentPage.value = totalPages.value;
    }
};

const updateCell = (index: number, key: string, event: Event) => {
    const value = (event.target as HTMLInputElement).value;
    uploadedData.value[index][key] = value;
};

const downloadTemplate = (type: 'weather_station' | 'water_level_sensor') => {
    let headers: string[] = [];
    let filename = '';

    if (type === 'weather_station') {
        headers = [
            'date_time', 'temperature', 'heat_index', 'dewpoint', 'humidity', 
            'wind_speed', 'wind_direction', 'wind_gust', 'pressure', 
            'precipitation_rate', 'precipitation_total', 'uv', 'solar_radiation'
        ];
        filename = 'weather_station_data_template.xlsx';
    } else {
        headers = ['date', 'sensor_data'];
        filename = 'water_level_sensor_data_template.xlsx';
    }

    const ws = XLSX.utils.aoa_to_sheet([headers]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template');
    XLSX.writeFile(wb, filename);
};
</script>

<template>
    <AppLayout title="Data Migration">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Data Migration
            </h2>
        </template>

        <div class="h-[calc(100vh-140px)] bg-gray-50/50 dark:bg-gray-900/50 py-4 overflow-hidden">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 h-full">
                <div class="grid grid-cols-12 gap-8 h-full overflow-hidden">
                    
                    <!-- Left Sidebar: Controls -->
                    <div class="col-span-12 lg:col-span-4 h-full flex flex-col space-y-6 overflow-y-auto pr-2 scrollbar-thin">
                        
                        <!-- Upload Section -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 shrink-0">
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6">Upload File</h3>
                            
                            <div class="space-y-6">
                                <div class="flex items-center space-x-4">
                                    <label class="cursor-pointer">
                                        <span class="inline-flex items-center px-6 py-2.5 bg-orange-50 hover:bg-orange-100 text-orange-600 font-medium rounded-full transition-all duration-200 border border-orange-100">
                                            Choose File
                                        </span>
                                        <input 
                                            type="file" 
                                            ref="fileInput"
                                            @change="handleFileUpload"
                                            accept=".xlsx, .xls, .csv, .sql"
                                            class="hidden"
                                        />
                                    </label>
                                    <span class="text-sm text-gray-400 truncate max-w-[200px]" :title="fileName">
                                        {{ fileName }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-400">Supported formats: XLSX, CSV, SQL</p>

                                <div class="pt-6 border-t border-gray-50 dark:border-gray-700">
                                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Download Templates</h4>
                                    <div class="grid grid-cols-1 gap-3">
                                        <button 
                                            @click="downloadTemplate('weather_station')"
                                            class="flex items-center px-4 py-2.5 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl transition-all duration-200 border border-transparent hover:border-gray-200 dark:hover:border-gray-600 group"
                                        >
                                            <svg class="size-4 mr-3 text-gray-400 group-hover:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span class="text-sm font-medium">Weather Station Template</span>
                                        </button>
                                        <button 
                                            @click="downloadTemplate('water_level_sensor')"
                                            class="flex items-center px-4 py-2.5 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl transition-all duration-200 border border-transparent hover:border-gray-200 dark:hover:border-gray-600 group"
                                        >
                                            <svg class="size-4 mr-3 text-gray-400 group-hover:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span class="text-sm font-medium">Water Level Sensor Template</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Section -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 shrink-0">
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6">Import Settings</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Target Type</label>
                                    <select v-model="targetType" @change="targetId = null" class="w-full bg-gray-50 dark:bg-gray-900 border-transparent focus:border-orange-500 focus:ring-0 rounded-xl text-gray-700 dark:text-gray-300 py-3 px-4">
                                        <option value="weather_station">Weather Station Observation Data</option>
                                        <option value="water_level_sensor">Water Level Sensor Data</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Select Target Entity</label>
                                    <select v-model="targetId" class="w-full bg-gray-50 dark:bg-gray-900 border-transparent focus:border-orange-500 focus:ring-0 rounded-xl text-gray-700 dark:text-gray-300 py-3 px-4">
                                        <option v-for="item in targetOptions" :key="item.id" :value="item.id">
                                            {{ item.name }} {{ item.station_id ? '(' + item.station_id + ')' : '' }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Data View -->
                    <div class="col-span-12 lg:col-span-8 h-full overflow-hidden">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 h-full flex flex-col overflow-hidden">
                            
                            <!-- Spreadsheet Header -->
                            <div v-if="uploadedData.length > 0" class="p-6 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 rounded-t-2xl sticky top-0 z-10">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Parsed Data</h3>
                                    <p class="text-sm text-gray-500 font-medium">{{ uploadedData.length }} records found</p>
                                </div>
                                <button 
                                    @click="importData"
                                    :disabled="form.processing || !targetId"
                                    class="inline-flex items-center px-8 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-full shadow-lg shadow-orange-500/20 transition-all duration-200 disabled:opacity-50 disabled:shadow-none"
                                >
                                    <span v-if="form.processing" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Importing...
                                    </span>
                                    <span v-else>Import Data</span>
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="flex-grow overflow-hidden flex flex-col">
                                <template v-if="uploadedData.length > 0">
                                    <div class="overflow-auto flex-grow scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">
                                        <table class="w-full text-left border-collapse">
                                            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0 z-10">
                                                <tr>
                                                    <th v-for="col in columns" :key="col" class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">
                                                        {{ col }}
                                                    </th>
                                                    <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 text-right">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                                <tr v-for="row in paginatedData" :key="row.originalIndex" class="group hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                                    <td v-for="col in columns" :key="col" class="px-4 py-3">
                                                        <input 
                                                            type="text" 
                                                            :value="row[col]" 
                                                            @input="updateCell(row.originalIndex, col, $event)"
                                                            class="w-full border-0 focus:ring-1 focus:ring-orange-500/50 bg-transparent rounded-lg text-sm text-gray-600 dark:text-gray-300 py-1.5 px-3 transition-all"
                                                        />
                                                    </td>
                                                    <td class="px-6 py-3 text-right">
                                                        <button @click="removeRow(row.originalIndex)" class="text-gray-300 hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400 transition-colors">
                                                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Controls -->
                                    <div class="p-6 border-t border-gray-50 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-b-2xl flex items-center justify-between">
                                        <div class="text-sm text-gray-500 font-medium">
                                            Showing <span class="text-gray-900 dark:text-gray-200">{{ Math.min((currentPage - 1) * rowsPerPage + 1, uploadedData.length) }}</span> 
                                            to <span class="text-gray-900 dark:text-gray-200">{{ Math.min(currentPage * rowsPerPage, uploadedData.length) }}</span> 
                                            of <span class="text-gray-900 dark:text-gray-200">{{ uploadedData.length }}</span> results
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button 
                                                @click="currentPage--" 
                                                :disabled="currentPage === 1"
                                                class="px-4 py-2 border border-gray-100 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
                                            >
                                                Previous
                                            </button>
                                            <div class="flex items-center space-x-1">
                                                <button 
                                                    v-for="page in totalPages" 
                                                    :key="page"
                                                    @click="currentPage = page"
                                                    :class="[
                                                        'size-9 rounded-lg text-sm font-bold transition-all duration-200',
                                                        currentPage === page 
                                                            ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' 
                                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                                                    ]"
                                                >
                                                    {{ page }}
                                                </button>
                                            </div>
                                            <button 
                                                @click="currentPage++" 
                                                :disabled="currentPage === totalPages"
                                                class="px-4 py-2 border border-gray-100 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
                                            >
                                                Next
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="flex-grow flex flex-col items-center justify-center p-12 text-center">
                                        <div class="relative mb-8">
                                            <div class="absolute inset-0 bg-orange-100 dark:bg-orange-500/10 rounded-full blur-3xl opacity-50 animate-pulse"></div>
                                            <svg class="size-24 text-gray-300 dark:text-gray-600 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">No data uploaded</h3>
                                        <p class="text-gray-500 dark:text-gray-400 max-w-sm">Select a file to start the migration process. We support XLSX, CSV, and SQL formats.</p>
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

<style scoped>
/* Custom scrollbar for better aesthetics */
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.dark .scrollbar-thin::-webkit-scrollbar-thumb {
    background: #374151;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

input:focus {
    box-shadow: none;
}
</style>
