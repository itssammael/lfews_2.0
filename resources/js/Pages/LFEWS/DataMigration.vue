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
const rawUploadedData = ref<any[]>([]);
const rawColumns = ref<string[]>([]);
const targetType = ref('weather_station');
const targetId = ref<number | null>(null);

// Pagination State
const currentPage = ref(1);
const rowsPerPage = 18;

// Temperature Conversion State
const convertTemperature = ref(false);

// Unit Conversion State (in to mm/hPa)
const convertInToMm = ref(false);

const fahrenheitToCelsius = (f: number) => {
    return ((f - 32) * 5) / 9;
};

const celsiusToFahrenheit = (c: number) => {
    return (c * 9) / 5 + 32;
};

const inToMm = (inches: number) => {
    return inches * 25.4;
};

const mmToIn = (mm: number) => {
    return mm / 25.4;
};

const inHgToHpa = (inHg: number) => {
    return inHg * 33.8639;
};

const hpaToInHg = (hpa: number) => {
    return hpa / 33.8639;
};

const toggleTemperatureUnit = () => {
    const targetCols = ['temperature', 'heat_index', 'dewpoint'];
    
    uploadedData.value = uploadedData.value.map(row => {
        const newRow = { ...row };
        targetCols.forEach(col => {
            const val = parseFloat(newRow[col]);
            if (!isNaN(val)) {
                if (convertTemperature.value) {
                    // Converting F to C
                    newRow[col] = fahrenheitToCelsius(val).toFixed(1);
                } else {
                    // Converting C to F
                    newRow[col] = celsiusToFahrenheit(val).toFixed(1);
                }
            }
        });
        return newRow;
    });
};

const toggleUnitConversion = () => {
    const precipCols = ['precipitation_rate', 'precipitation_total'];
    const pressureCols = ['pressure'];
    
    uploadedData.value = uploadedData.value.map(row => {
        const newRow = { ...row };
        
        // Convert Precipitation
        precipCols.forEach(col => {
            const val = parseFloat(newRow[col]);
            if (!isNaN(val)) {
                if (convertInToMm.value) {
                    newRow[col] = inToMm(val).toFixed(2);
                } else {
                    newRow[col] = mmToIn(val).toFixed(2);
                }
            }
        });
        
        // Convert Pressure
        pressureCols.forEach(col => {
            const val = parseFloat(newRow[col]);
            if (!isNaN(val)) {
                if (convertInToMm.value) {
                    newRow[col] = inHgToHpa(val).toFixed(2);
                } else {
                    newRow[col] = hpaToInHg(val).toFixed(2);
                }
            }
        });
        
        return newRow;
    });
};

const cleanData = () => {
    // Regex to match common unit symbols and suffixes
    const unitRegex = /\s*(?:°|%|°in|°F|°C|in|%RH|mph|km\/h|Pa|hPa|mb|mbar|mm)\s*/gi;
    
    uploadedData.value = uploadedData.value.map(row => {
        const newRow = { ...row };
        Object.keys(newRow).forEach(key => {
            if (typeof newRow[key] === 'string') {
                newRow[key] = newRow[key].replace(unitRegex, '').trim();
            }
        });
        return newRow;
    });
};

const removeColumn = (colName: string) => {
    columns.value = columns.value.filter(col => col !== colName);
    uploadedData.value = uploadedData.value.map(row => {
        const newRow = { ...row };
        delete newRow[colName];
        return newRow;
    });
};

const resetData = () => {
    uploadedData.value = JSON.parse(JSON.stringify(rawUploadedData.value));
    columns.value = [...rawColumns.value];
    convertTemperature.value = false;
    convertInToMm.value = false;
};

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
    convertTemperature.value = false;
    convertInToMm.value = false;

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
                rawUploadedData.value = JSON.parse(JSON.stringify(uploadedData.value));
                rawColumns.value = [...columns.value];
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
            convertTemperature.value = false;
            convertInToMm.value = false;
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
            <div class="max-w-[1800px] mx-auto px-4  h-full">
                <div class="grid grid-cols-12 gap-8 h-full overflow-hidden">
                    
                    <!-- Left Sidebar: Controls -->
                    <div class="col-span-12 lg:col-span-2 h-full flex flex-col space-y-6 overflow-y-auto pr-2 scrollbar-thin">
                         <!-- Settings Section -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 shrink-0">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Import Settings</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Target Type</label>
                                    <select v-model="targetType" @change="targetId = null" class="w-full bg-gray-50 dark:bg-gray-900 border-transparent focus:border-orange-500 focus:ring-0 rounded-xl text-xs text-gray-700 dark:text-gray-300 py-2.5 px-3">
                                        <option value="weather_station">Weather Station Observation Data</option>
                                        <option value="water_level_sensor">Water Level Sensor Data</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Select Target Entity</label>
                                    <select v-model="targetId" class="w-full bg-gray-50 dark:bg-gray-900 border-transparent focus:border-orange-500 focus:ring-0 rounded-xl text-xs text-gray-700 dark:text-gray-300 py-2.5 px-3">
                                        <option v-for="item in targetOptions" :key="item.id" :value="item.id">
                                            {{ item.name }} {{ item.station_id ? '(' + item.station_id + ')' : '' }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Section -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 shrink-0">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Upload File</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <label class="cursor-pointer">
                                        <span class="inline-flex items-center px-4 py-2 bg-orange-50 hover:bg-orange-100 text-orange-600 text-sm font-medium rounded-full transition-all duration-200 border border-orange-100">
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
                                    <span class="text-xs text-gray-400 truncate max-w-[120px]" :title="fileName">
                                        {{ fileName }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Supported formats: XLSX, CSV, SQL</p>

                                <div class="pt-4 border-t border-gray-50 dark:border-gray-700">
                                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Download Templates</h4>
                                    <div class="space-y-2">
                                        <button 
                                            @click="downloadTemplate('weather_station')"
                                            class="w-full flex items-center p-2.5 bg-gray-50 hover:bg-orange-50 dark:bg-gray-900/50 dark:hover:bg-orange-900/20 text-gray-600 dark:text-gray-300 rounded-xl transition-all duration-200 border border-transparent hover:border-orange-100 dark:hover:border-orange-900/50 group"
                                        >
                                            <svg class="size-3.5 mr-2 text-gray-400 group-hover:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span class="text-xs font-medium">Weather Station Template</span>
                                        </button>
                                        <button 
                                            @click="downloadTemplate('water_level_sensor')"
                                            class="w-full flex items-center p-2.5 bg-gray-50 hover:bg-orange-50 dark:bg-gray-900/50 dark:hover:bg-orange-900/20 text-gray-600 dark:text-gray-300 rounded-xl transition-all duration-200 border border-transparent hover:border-orange-100 dark:hover:border-orange-900/50 group"
                                        >
                                            <svg class="size-3.5 mr-2 text-gray-400 group-hover:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span class="text-xs font-medium">Water Level Sensor Template</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Data View -->
                    <div class="col-span-12 lg:col-span-10 h-full overflow-hidden">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 h-full flex flex-col overflow-hidden">
                            
                            <!-- Spreadsheet Header -->
                            <div v-if="uploadedData.length > 0" class="px-6 py-4 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 rounded-t-2xl sticky top-0 z-10">
                                <div class="flex items-center space-x-6">
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Parsed Data</h3>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ uploadedData.length }} records found</p>
                                    </div>
                                    <div v-if="targetType === 'weather_station'" class="flex flex-col space-y-2 border-l border-gray-100 dark:border-gray-700 pl-6">
                                        <label class="relative inline-flex items-center cursor-pointer group">
                                            <input 
                                                type="checkbox" 
                                                v-model="convertTemperature" 
                                                @change="toggleTemperatureUnit"
                                                class="sr-only peer"
                                            >
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-500 rounded-full"></div>
                                            <span class="ml-3 text-[10px] font-bold text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-all uppercase tracking-widest whitespace-nowrap">
                                                Convert °F to °C
                                            </span>
                                        </label>
                                        <label class="relative inline-flex items-center cursor-pointer group">
                                            <input 
                                                type="checkbox" 
                                                v-model="convertInToMm" 
                                                @change="toggleUnitConversion"
                                                class="sr-only peer"
                                            >
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-500 rounded-full"></div>
                                            <span class="ml-3 text-[10px] font-bold text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-all uppercase tracking-widest whitespace-nowrap">
                                                Convert in to mm
                                            </span>
                                        </label>
                                    </div>
                                    <div class="flex items-center space-x-2 border-l border-gray-100 dark:border-gray-700 pl-6">
                                        <button 
                                            @click="resetData"
                                            class="inline-flex items-center px-4 py-2 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-200 text-xs font-bold rounded-full transition-all duration-200 border border-gray-200 dark:border-gray-500"
                                        >
                                            <svg class="size-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Reset
                                        </button>
                                        <button 
                                            @click="cleanData"
                                            class="inline-flex items-center px-4 py-2 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-200 text-xs font-bold rounded-full transition-all duration-200 border border-gray-200 dark:border-gray-500"
                                        >
                                            <svg class="size-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Clean Data
                                        </button>
                                    </div>
                                </div>
                                <button 
                                    @click="importData"
                                    :disabled="form.processing || !targetId"
                                    class="inline-flex items-center px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-full shadow-lg shadow-orange-500/20 transition-all duration-200 disabled:opacity-50 disabled:shadow-none"
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
                                                    <th 
                                                        v-for="col in columns" 
                                                        :key="col" 
                                                        :class="[
                                                            'px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 group/header',
                                                            col.toLowerCase().includes('date') ? 'min-w-[160px]' : 'min-w-[120px]'
                                                        ]"
                                                    >
                                                        <div class="flex items-center justify-between">
                                                            <span>{{ col }}</span>
                                                            <button 
                                                                @click="removeColumn(col)"
                                                                class="opacity-0 group-hover/header:opacity-100 text-gray-300 hover:text-red-500 transition-all ml-2 p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                                                                title="Remove Column"
                                                            >
                                                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </th>
                                                    <th class="px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 text-right">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                                <tr v-for="row in paginatedData" :key="row.originalIndex" class="group hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                                    <td 
                                                        v-for="col in columns" 
                                                        :key="col" 
                                                        :class="[
                                                            'px-3 py-1.5',
                                                            col.toLowerCase().includes('date') ? 'min-w-[160px]' : 'min-w-[120px]'
                                                        ]"
                                                    >
                                                        <input 
                                                            type="text" 
                                                            :value="row[col]" 
                                                            @input="updateCell(row.originalIndex, col, $event)"
                                                            class="w-full border-0 focus:ring-1 focus:ring-orange-500/30 bg-transparent rounded-lg text-xs text-gray-600 dark:text-gray-300 py-1 px-2 transition-all"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-1.5 text-right">
                                                        <button @click="removeRow(row.originalIndex)" class="text-gray-300 hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400 transition-colors">
                                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Controls -->
                                    <div class="px-6 py-4 border-t border-gray-50 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-b-2xl flex items-center justify-between">
                                        <div class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">
                                            Showing <span class="text-gray-900 dark:text-gray-200">{{ Math.min((currentPage - 1) * rowsPerPage + 1, uploadedData.length) }}</span> 
                                            to <span class="text-gray-900 dark:text-gray-200">{{ Math.min(currentPage * rowsPerPage, uploadedData.length) }}</span> 
                                            of <span class="text-gray-900 dark:text-gray-200">{{ uploadedData.length }}</span> results
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button 
                                                @click="currentPage--" 
                                                :disabled="currentPage === 1"
                                                class="px-3 py-1.5 border border-gray-100 dark:border-gray-700 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
                                            >
                                                Previous
                                            </button>
                                            <div class="flex items-center space-x-1">
                                                <button 
                                                    v-for="page in totalPages" 
                                                    :key="page"
                                                    @click="currentPage = page"
                                                    :class="[
                                                        'size-7 rounded-lg text-xs font-bold transition-all duration-200',
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
                                                class="px-3 py-1.5 border border-gray-100 dark:border-gray-700 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
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
