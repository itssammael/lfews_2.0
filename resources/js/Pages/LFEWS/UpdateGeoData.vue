<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import proj4 from 'proj4';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Toast from '@/Components/Toast.vue';
import ProgressLoader from '@/Components/ProgressLoader.vue';

// Define the CRS for Bayawan (UTM Zone 51N)
proj4.defs("EPSG:32651","+proj=utm +zone=51 +datum=WGS84 +units=m +no_defs");

const dataTypes = [
    { label: 'River', value: 'river' },
    { label: 'Contour Map', value: 'contour' },
    { label: 'Flood Hazard Map', value: 'flood_hazard' },
    { label: 'Barangay', value: 'barangay' },
    { label: 'Sitio', value: 'sitio' },
];

const selectedType = ref('river');
const currentGeoJson = ref<any>(null);
const uploadedGeoJson = ref<any>(null);
const isLoading = ref(false);
const isUpdating = ref(false);
const confirmingUpdate = ref(false);
const updateProgress = ref(0);
const fileInput = ref<HTMLInputElement | null>(null);

const mapLeftContainer = ref<HTMLElement | null>(null);
const mapRightContainer = ref<HTMLElement | null>(null);
let mapLeft: L.Map | null = null;
let mapRight: L.Map | null = null;
let layerLeft: L.GeoJSON | null = null;
let layerRight: L.GeoJSON | null = null;

const initMaps = () => {
    if (!mapLeftContainer.value || !mapRightContainer.value) return;

    const center: [number, number] = [9.3668, 122.8055];
    const zoom = 11;

    mapLeft = L.map(mapLeftContainer.value, {
        zoomControl: true,
        attributionControl: true
    }).setView(center, zoom);

    mapRight = L.map(mapRightContainer.value, {
        zoomControl: false, // Hide zoom control on right map to keep it clean
        attributionControl: true
    }).setView(center, zoom);

    const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    const osmAttrib = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

    L.tileLayer(osmUrl, { attribution: osmAttrib }).addTo(mapLeft);
    L.tileLayer(osmUrl, { attribution: osmAttrib }).addTo(mapRight);

    // Sync maps bi-directionally
    let isSyncing = false;

    const syncMaps = (source: L.Map, target: L.Map) => {
        if (isSyncing) return;
        isSyncing = true;
        target.setView(source.getCenter(), source.getZoom(), { animate: false });
        isSyncing = false;
    };

    mapLeft.on('move', () => syncMaps(mapLeft!, mapRight!));
    mapRight.on('move', () => syncMaps(mapRight!, mapLeft!));
    mapLeft.on('zoomend', () => syncMaps(mapLeft!, mapRight!));
    mapRight.on('zoomend', () => syncMaps(mapRight!, mapLeft!));
};

const fetchCurrentData = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('api.geo-data.fetch', { type: selectedType.value }));
        currentGeoJson.value = response.data;
        updateMapLayer('left');
    } catch (error) {
        console.error('Failed to fetch current data:', error);
    } finally {
        isLoading.value = false;
    }
};

const notify = (message: string, type: 'success' | 'error' = 'success') => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
};

const handleFileUpload = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const json = JSON.parse(e.target?.result as string);
            uploadedGeoJson.value = json;
            updateMapLayer('right');
        } catch (error) {
            notify('Invalid JSON file.', 'error');
            console.error(error);
        }
    };
    reader.readAsText(file);
};

const transformGeometry = (geometry: any) => {
    if (!geometry) return null;
    
    // Check if it's already in WGS84 (approximate check based on coordinate magnitude)
    // If UTM Zone 51, coordinates will be large (e.g., 400000, 1000000)
    const transformCoords = (coords: any): any => {
        if (typeof coords[0] === 'number') {
            // If coordinates are large, they likely need transformation from EPSG:32651 (UTM) to WGS84
            if (Math.abs(coords[0]) > 200 || Math.abs(coords[1]) > 200) {
                return proj4("EPSG:32651", "WGS84", coords);
            }
            return coords;
        }
        return coords.map(transformCoords);
    };

    return {
        ...geometry,
        coordinates: transformCoords(geometry.coordinates)
    };
};

const updateMapLayer = (side: 'left' | 'right') => {
    const map = side === 'left' ? mapLeft : mapRight;
    let layer = side === 'left' ? layerLeft : layerRight;
    const geojson = side === 'left' ? currentGeoJson.value : uploadedGeoJson.value;

    if (!map || !geojson) return;

    if (layer) {
        map.removeLayer(layer);
    }

    // Process features to ensure WGS84
    const processedGeojson = {
        ...geojson,
        features: geojson.features?.map((f: any) => ({
            ...f,
            geometry: transformGeometry(f.geometry)
        })) || []
    };

    const newLayer = L.geoJSON(processedGeojson as any, {
        style: {
            color: side === 'left' ? '#3b82f6' : '#ef4444',
            weight: 2,
            opacity: 0.8,
            fillOpacity: 0.2
        },
        coordsToLatLng: (coords) => L.latLng(coords[1], coords[0]),
        onEachFeature: (feature, layer) => {
            if (feature.properties) {
                let popupContent = '<div class="text-xs max-h-48 overflow-y-auto min-w-[200px]">';
                popupContent += '<h3 class="font-bold border-b mb-2 pb-1 uppercase">Properties</h3>';
                for (const key in feature.properties) {
                    const val = feature.properties[key];
                    if (typeof val !== 'object') {
                        popupContent += `<strong>${key}:</strong> ${val}<br/>`;
                    }
                }
                popupContent += '</div>';
                layer.bindPopup(popupContent);
            }
        }
    }).addTo(map);

    if (side === 'left') {
        layerLeft = newLayer;
    } else {
        layerRight = newLayer;
    }

    // Always center and fit based on the layer that has data
    if (newLayer.getBounds().isValid()) {
        // Apply fitBounds to BOTH maps to keep them identical
        if (mapLeft && mapRight) {
            const bounds = newLayer.getBounds();
            mapLeft.fitBounds(bounds, { padding: [20, 20] });
            mapRight.setView(mapLeft.getCenter(), mapLeft.getZoom(), { animate: false });
        }
    }
};

const proceedUpdate = () => {
    if (!uploadedGeoJson.value) {
        notify('Please upload a GeoJSON file first.', 'error');
        return;
    }
    confirmingUpdate.value = true;
};

const performUpdate = async () => {
    confirmingUpdate.value = false;
    isUpdating.value = true;
    updateProgress.value = 0;
    
    try {
        const response = await axios.post(route('api.geo-data.update'), {
            type: selectedType.value,
            geojson: uploadedGeoJson.value
        }, {
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    updateProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                }
            }
        });
        
        if (response.data.success) {
            notify(response.data.message, 'success');
            // Refresh current data after success
            await fetchCurrentData();
            uploadedGeoJson.value = null;
            if (layerRight && mapRight) {
                mapRight.removeLayer(layerRight);
                layerRight = null;
            }
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        } else {
            notify('Failed to update: ' + response.data.message, 'error');
        }
    } catch (error: any) {
        console.error('Update failed:', error);
        notify('An error occurred: ' + (error.response?.data?.message || error.message), 'error');
    } finally {
        isUpdating.value = false;
        updateProgress.value = 0;
    }
};

onMounted(async () => {
    await nextTick();
    initMaps();
    fetchCurrentData();
});

onUnmounted(() => {
    if (mapLeft) mapLeft.remove();
    if (mapRight) mapRight.remove();
});

watch(selectedType, () => {
    currentGeoJson.value = null;
    uploadedGeoJson.value = null;
    if (layerLeft && mapLeft) mapLeft.removeLayer(layerLeft);
    if (layerRight && mapRight) mapRight.removeLayer(layerRight);
    layerLeft = null;
    layerRight = null;
    if (fileInput.value) fileInput.value.value = '';
    fetchCurrentData();
});
</script>

<template>
    <AppLayout title="Update Geo Data">
        <ProgressLoader 
            :show="isUpdating" 
            title="Updating Geo Data" 
            :subtitle="'Uploading & Processing ' + selectedType.replace('_', ' ')"
            :progress="updateProgress" 
        />

        <Toast />

        <ConfirmationModal :show="confirmingUpdate" @close="confirmingUpdate = false">
            <template #title>
                Update {{ selectedType.replace('_', ' ') }} Data
            </template>

            <template #content>
                Are you sure you want to update <strong>{{ selectedType.replace('_', ' ') }}</strong> data? 
                This will <span class="text-red-600 font-bold underline leading-relaxed">permanently delete all existing records</span> for this type and replace them with the data from the uploaded GeoJSON file.
                
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 text-xs">
                    This action cannot be undone. Please ensure you have compared the data on the maps before proceeding.
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingUpdate = false">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': isUpdating }"
                    :disabled="isUpdating"
                    @click="performUpdate"
                >
                    Proceed with Update
                </DangerButton>
            </template>
        </ConfirmationModal>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Update Geo Data
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8 space-y-4">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex flex-col md:flex-row md:items-end gap-4 mb-6">
                        <div class="flex-1 max-w-xs">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Category</label>
                            <select 
                                v-model="selectedType"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="type in dataTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <div class="flex-1 max-w-xs">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select GeoJSON File</label>
                            <input 
                                ref="fileInput"
                                type="file" 
                                accept=".json,.geojson"
                                @change="handleFileUpload"
                                class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                        </div>

                        <div class="flex-none">
                            <button 
                                @click="proceedUpdate"
                                :disabled="!uploadedGeoJson || isUpdating"
                                class="inline-flex items-center px-6 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                <svg v-if="isUpdating" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Proceed with Update
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 h-[600px]">
                        <div class="relative flex flex-col border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <div class="absolute top-2 left-2 z-[1000] bg-white/90 dark:bg-gray-800/90 px-3 py-1.5 rounded-md shadow-sm border border-gray-200 dark:border-gray-700 text-sm font-bold uppercase pointer-events-none">
                                Current Data
                            </div>
                            <div v-if="isLoading" class="absolute inset-0 z-[1001] bg-white/80 dark:bg-gray-900/80 flex items-center justify-center backdrop-blur-sm">
                                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 flex flex-col items-center gap-4">
                                    <svg class="animate-spin h-8 w-8 text-orange-500" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-xs font-bold tracking-widest uppercase text-gray-500">Loading Data...</span>
                                </div>
                            </div>
                            <div ref="mapLeftContainer" class="w-full flex-1 z-0"></div>
                        </div>

                        <div class="relative flex flex-col border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <div class="absolute top-2 left-2 z-[1000] bg-white/90 dark:bg-gray-800/90 px-3 py-1.5 rounded-md shadow-sm border border-gray-200 dark:border-gray-700 text-sm font-bold uppercase pointer-events-none">
                                Recent Data / Uploaded
                            </div>
                            <div v-if="!uploadedGeoJson" class="absolute inset-0 z-[1001] bg-gray-50/80 dark:bg-gray-900/80 flex flex-col items-center justify-center text-gray-400">
                                <svg class="h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>Upload a GeoJSON file to see preview</span>
                            </div>
                            <div ref="mapRightContainer" class="w-full flex-1 z-0"></div>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-900/30 rounded-lg">
                        <div class="flex gap-3">
                            <svg class="h-5 w-5 text-orange-600 self-start mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="text-sm text-orange-800 dark:text-orange-300">
                                <p class="font-bold mb-1">Warning: Data Replacement</p>
                                <p>Proceeding with the update will <strong>permanently delete all existing records</strong> for the selected category from the database and replace them with the data from the uploaded GeoJSON file. This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.leaflet-container {
    z-index: 0 !important;
}
</style>
