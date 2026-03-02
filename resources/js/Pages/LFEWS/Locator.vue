<script setup lang="ts">
// @ts-ignore
import AppLayout from '@/Layouts/AppLayout.vue';
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
// @ts-ignore
import Checkbox from '@/Components/Checkbox.vue';
import axios from 'axios';

import 'leaflet/dist/leaflet.css';
import proj4 from 'proj4';

// Define the CRS for Bayawan Rivers (UTM Zone 51N)
// Source: https://epsg.io/32651
proj4.defs("EPSG:32651","+proj=utm +zone=51 +datum=WGS84 +units=m +no_defs");

// Fix for default marker icons in Leaflet with Webpack/Vite
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

try {
  // @ts-ignore
  delete L.Icon.Default.prototype._getIconUrl;
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: iconRetinaUrl,
    iconUrl: iconUrl,
    shadowUrl: shadowUrl,
  });
} catch (e) {
  console.warn('Leaflet icon fix failed:', e);
}

// Custom Icons
const waterLevelIcon = L.icon({
    iconUrl: '/images/map_marker/water-level.png',
    iconSize: [40, 52],
    iconAnchor: [20, 52],
    popupAnchor: [0, -52],
    tooltipAnchor: [0, -52]
});

const weatherStationIcon = L.icon({
    iconUrl: '/images/map_marker/weather-station.png',
    iconSize: [40, 52],
    iconAnchor: [20, 52],
    popupAnchor: [0, -52],
    tooltipAnchor: [0, -52]
});

const evacuationCenterIcon = L.icon({
    iconUrl: '/images/map_marker/disaster.png',
    iconSize: [40, 60],
    iconAnchor: [20, 60],
    popupAnchor: [0, -60],
    tooltipAnchor: [0, -60]
});

const props = defineProps<{
    weatherStations: any[];
    waterLevelSensors: any[];
    locations: any[];
    rivers: any[];
    contours?: any[];
    floodRisks: any[];
}>();

const localContours = ref<any[]>([]);

const mapContainer = ref<HTMLElement | null>(null);
const map = ref<any>(null);
const selectedCategories = ref<string[]>(['weather_stations', 'water_level_sensors', 'evacuation_centers', 'rivers']); // Default visible categories

// Contour loading state
const isLoadingContours = ref(false);
const contourLoadingProgress = ref(0);
const isContoursProcessed = ref(false);
const totalContours = ref(0);
const processedContoursCount = ref(0);

// Flood Risk loading state
const isLoadingFloodRisks = ref(false);
const floodRiskLoadingProgress = ref(0);
const isFloodRisksProcessed = ref(false);

// Layer Groups
const weatherStationsGroup = L.layerGroup();
const waterLevelSensorsGroup = L.layerGroup();
const evacuationCentersGroup = L.layerGroup();
const riversGroup = L.layerGroup();
const contoursGroup = L.layerGroup();
const floodRiskGroup = L.layerGroup();

const categories = [
    { id: 'weather_stations', label: 'Weather Stations' },
    { id: 'water_level_sensors', label: 'Water Level Sensors' },
    { id: 'all_devices', label: 'All Devices' },
    { id: 'evacuation_centers', label: 'Evacuation Centers' },
    { id: 'rivers', label: 'All Rivers in Bayawan City' },
    { id: 'contours', label: 'Contour lines' },
    { id: 'flood_risk', label: 'Flood Risk Map' },
];

const toggleCategory = (id: string) => {
    if (id === 'all_devices') {
        const isAllDevicesSelected = selectedCategories.value.includes('weather_stations') && selectedCategories.value.includes('water_level_sensors');
        if (isAllDevicesSelected) {
            selectedCategories.value = selectedCategories.value.filter(item => item !== 'weather_stations' && item !== 'water_level_sensors' && item !== 'all_devices');
        } else {
            if (!selectedCategories.value.includes('weather_stations')) selectedCategories.value.push('weather_stations');
            if (!selectedCategories.value.includes('water_level_sensors')) selectedCategories.value.push('water_level_sensors');
            if (!selectedCategories.value.includes('all_devices')) selectedCategories.value.push('all_devices');
        }
    } else {
        const index = selectedCategories.value.indexOf(id);
        if (index > -1) {
            selectedCategories.value.splice(index, 1);
            if (id === 'weather_stations' || id === 'water_level_sensors') {
                selectedCategories.value = selectedCategories.value.filter(item => item !== 'all_devices');
            }
        } else {
            selectedCategories.value.push(id);
            if (id === 'weather_stations' || id === 'water_level_sensors') {
                 const hasBoth = selectedCategories.value.includes('weather_stations') && selectedCategories.value.includes('water_level_sensors');
                 if (hasBoth && !selectedCategories.value.includes('all_devices')) {
                     selectedCategories.value.push('all_devices');
                 }
            }
        }
    }

    if (selectedCategories.value.includes('contours') && !isContoursProcessed.value) {
        processContours();
    }
    
    if (selectedCategories.value.includes('flood_risk') && !isFloodRisksProcessed.value && props.floodRisks?.length > 0) {
        processFloodRisks();
    }

    updateVisibleLayers();
};

watch(selectedCategories, () => {
    updateVisibleLayers();
}, { deep: true });

const processContours = async () => {
    if (isLoadingContours.value || isContoursProcessed.value) return;
    
    isLoadingContours.value = true;
    contourLoadingProgress.value = 0;
    processedContoursCount.value = 0;

    // Fetch data if not already loaded locally
    if (localContours.value.length === 0) {
        try {
            // @ts-ignore
            const response = await axios.get(route('locator.api.contours'));
            localContours.value = response.data;
        } catch (error) {
            console.error('Failed to load contours:', error);
            isLoadingContours.value = false;
            return;
        }
    }

    totalContours.value = localContours.value.length;

    const chunkSize = 50;
    const features = localContours.value.map(contour => ({
        type: "Feature",
        properties: contour.properties,
        geometry: contour.geometry
    }));

    for (let i = 0; i < features.length; i += chunkSize) {
        const chunk = features.slice(i, i + chunkSize);
        
        L.geoJSON({ type: "FeatureCollection", features: chunk } as any, {
            style: (feature) => getContourStyle(feature),
            coordsToLatLng: (coords) => L.latLng(coords[1], coords[0]),
            onEachFeature: (feature, layer) => {
                if (feature.properties) {
                    const style = getContourStyle(feature);
                    const label = `<b>${feature.properties.name || 'Contour'}</b><br/>Height: ${style.height}m<br/><b>${style.riskLevel}</b>`;
                    layer.bindTooltip(label, {
                        sticky: true,
                        className: 'contour-label'
                    });
                }
            }
        }).addTo(contoursGroup);

        processedContoursCount.value += chunk.length;
        contourLoadingProgress.value = Math.min(100, Math.round((processedContoursCount.value / totalContours.value) * 100));
        
        // Yield to browser for UI updates
        await new Promise(resolve => setTimeout(resolve, 10));
    }

    isContoursProcessed.value = true;
    isLoadingContours.value = false;
    updateVisibleLayers();
};

const processFloodRisks = async () => {
    if (isLoadingFloodRisks.value || isFloodRisksProcessed.value) return;
    
    isLoadingFloodRisks.value = true;
    floodRiskLoadingProgress.value = 0;
    const total = props.floodRisks.length;
    let processed = 0;

    const chunkSize = 50;
    const features = props.floodRisks.map(risk => ({
        type: "Feature",
        properties: risk.properties,
        geometry: risk.geometry
    }));

    for (let i = 0; i < features.length; i += chunkSize) {
        const chunk = features.slice(i, i + chunkSize);
        
        L.geoJSON({ type: "FeatureCollection", features: chunk } as any, {
            style: (feature) => getFloodRiskStyle(feature),
            coordsToLatLng: (coords) => L.latLng(coords[1], coords[0]),
            onEachFeature: (feature, layer) => {
                if (feature.properties) {
                    const label = `<b>Flood Hazard Area</b><br/>Risk Level: ${feature.properties.FS_VH || 'Unknown'}`;
                    layer.bindTooltip(label, {
                        sticky: true,
                        className: 'contour-label'
                    });
                }
            }
        }).addTo(floodRiskGroup);

        processed += chunk.length;
        floodRiskLoadingProgress.value = Math.min(100, Math.round((processed / total) * 100));
        
        await new Promise(resolve => setTimeout(resolve, 10));
    }

    isFloodRisksProcessed.value = true;
    isLoadingFloodRisks.value = false;
    updateVisibleLayers();
};

const getFloodRiskStyle = (feature: any) => {
    const risk = feature.properties?.FS_VH || '';
    
    let color = '#9eb753'; // Low (Light Green)
    let fillOpacity = 0.4;

    if (risk === 'Very High') {
        color = '#9a5f97'; // Purple
        fillOpacity = 0.6;
    } else if (risk === 'High') {
        color = '#526ab1'; // Blue
        fillOpacity = 0.5;
    } else if (risk === 'Moderate') {
        color = '#6fc0b0'; // Teal
        fillOpacity = 0.4;
    }

    return {
        color: color,
        weight: 1.5,
        opacity: 0.8,
        fillOpacity: fillOpacity,
    };
};

const getContourStyle = (feature: any) => {
    const height = feature.properties?.height || 0;
    
    let color = '#9eb753'; // Low Risk
    let riskLevel = 'Low Risk';

    if (height <= 10) {
        color = '#9a5f97'; // Very High Risk
        riskLevel = 'Very High Risk';
    } else if (height <= 20) {
        color = '#526ab1'; // High Risk
        riskLevel = 'High Risk';
    } else if (height <= 40) {
        color = '#6fc0b0'; // Moderate Risk
        riskLevel = 'Moderate Risk';
    }

    return {
        color: color,
        weight: 1.5,
        opacity: 0.7,
        fillOpacity: 0.2,
        riskLevel: riskLevel, // Store for use in tooltip
        height: height
    };
};

const updateVisibleLayers = () => {
    if (!map.value) return;

    // weather_stations
    if (selectedCategories.value.includes('weather_stations')) {
        weatherStationsGroup.addTo(map.value);
    } else {
        weatherStationsGroup.removeFrom(map.value);
    }

    // water_level_sensors
    if (selectedCategories.value.includes('water_level_sensors')) {
        waterLevelSensorsGroup.addTo(map.value);
    } else {
        waterLevelSensorsGroup.removeFrom(map.value);
    }

    // evacuation_centers
    if (selectedCategories.value.includes('evacuation_centers')) {
        evacuationCentersGroup.addTo(map.value);
    } else {
        evacuationCentersGroup.removeFrom(map.value);
    }

    // rivers
    if (selectedCategories.value.includes('rivers')) {
        riversGroup.addTo(map.value);
    } else {
        riversGroup.removeFrom(map.value);
    }

    // flood_risk
    if (selectedCategories.value.includes('flood_risk')) {
        floodRiskGroup.addTo(map.value);
    } else {
        floodRiskGroup.removeFrom(map.value);
    }

    // contours
    if (selectedCategories.value.includes('contours')) {
        contoursGroup.addTo(map.value);
    } else {
        contoursGroup.removeFrom(map.value);
    }
};

onMounted(() => {
    if (mapContainer.value) {
        // Initialize map centered on a default location (e.g., Bayawan City or average of points)
        // Default to Bayawan City approx coords if no data, or calculating bounds
        map.value = L.map(mapContainer.value).setView([9.37, 122.8], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map.value);

        const bounds = L.latLngBounds([]);
        let hasPoints = false;

        // Add Weather Stations
        props.weatherStations.forEach(station => {
            if (station.location) {
                const marker = L.marker([station.location.latitude, station.location.longitude], {
                    title: station.name,
                    icon: weatherStationIcon
                });
                
                
                marker.bindTooltip(`<b>${station.name}</b>`, {
                    permanent: false,
                    direction: 'top',
                    className: 'map-tooltip'
                });
                weatherStationsGroup.addLayer(marker);
                bounds.extend([station.location.latitude, station.location.longitude]);
                hasPoints = true;
            }
        });

        // Add Water Level Sensors
        props.waterLevelSensors.forEach(sensor => {
             if (sensor.location) {
                const marker = L.marker([sensor.location.latitude, sensor.location.longitude], {
                    title: sensor.name,
                    icon: waterLevelIcon
                });
                
                
                marker.bindTooltip(`<b>${sensor.name}</b>`, {
                    permanent: false,
                    direction: 'top',
                    className: 'map-tooltip'
                });
                 waterLevelSensorsGroup.addLayer(marker);
                 bounds.extend([sensor.location.latitude, sensor.location.longitude]);
                 hasPoints = true;
            }
        });

        // Add Other Locations
        props.locations.forEach(loc => {
             const title = loc.location_type ? loc.location_type.description : 'Location';
             const marker = L.marker([loc.latitude, loc.longitude], {
                    title: title
                });
                
                
                marker.bindTooltip(`<b>${title}</b>`, {
                    permanent: false,
                    direction: 'top',
                    className: 'map-tooltip'
                });
                                if (loc.location_type?.description === 'River') {
                    riversGroup.addLayer(marker);
                } else if (loc.location_type?.description === 'Evacuation Centers') {
                    marker.setIcon(evacuationCenterIcon);
                    evacuationCentersGroup.addLayer(marker);
                } else {
                    // Default to evacuation centers group or maybe needs a 'General' group? 
                    // For now, if it doesn't match, we might not show it or add to a misc group.
                    // Given the prompt, let's treat others as Evacuation Centers or just generic locations
                    // If strictly following the menu, items not in these categories might be hidden. 
                    // Let's add them to evacuation centers for safety or just keep them in a generic group attached to 'all'.
                    // Actually, let's just add to evacuationCentersGroup if it's not a river, assuming most 'Locations' are relevant.
                    // Or better, creating a 'Others' group but the menu doesn't have 'Others'.
                    // I will leave them out of specific groups for now unless they match 'Evacuation Centers' to be precise.
                     if (title.includes('Evacuation') || loc.location_type?.description.includes('Evacuation')) {
                         marker.setIcon(evacuationCenterIcon);
                         evacuationCentersGroup.addLayer(marker);
                     }
                }
                
                bounds.extend([loc.latitude, loc.longitude]);
                hasPoints = true;
        });

        // Add all groups initially (handled by updateVisibleLayers called with 'all')
        
        // Add River Polygons
        if (props.rivers && props.rivers.length > 0) {
            // Convert array of river objects to GeoJSON FeatureCollection
            const features = props.rivers.map(river => ({
                type: "Feature",
                properties: {
                    ...river.properties,
                    seg_name: river.name
                },
                geometry: river.geometry
            }));

             L.geoJSON({ type: "FeatureCollection", features: features } as any, {
                style: {
                    color: '#3b82f6', // Blue-500
                    weight: 2,
                    opacity: 0.8,
                    fillOpacity: 0.3
                },
                coordsToLatLng: (coords) => {
                    // Convert UTM Zone 51N (EPSG:32651) to WGS84 (EPSG:4326)
                    // Proj4 takes [x, y] and returns [lng, lat]
                    // Leaflet expects L.latLng(lat, lng)
                    const [lng, lat] = proj4('EPSG:32651', 'EPSG:4326', coords as [number, number]);
                    return L.latLng(lat, lng);
                },
                onEachFeature: (feature, layer) => {
                    if (feature.properties && feature.properties.seg_name) {
                        layer.bindTooltip(`<b>${feature.properties.seg_name}</b>`, {
                            permanent: false,
                            direction: 'center',
                            className: 'map-tooltip'
                        });
                    }
                }
            }).addTo(riversGroup);
             hasPoints = true; // Assume rivers exist and should be fitted to
        }


        if (selectedCategories.value.includes('contours')) {
            if (!isContoursProcessed.value) {
                 processContours();
            }
        }

        updateVisibleLayers();

        if (hasPoints) {
            map.value.fitBounds(bounds, { padding: [50, 50] });
        }
    }
});
</script>

<template>
    <AppLayout title="Locator">
        <Teleport to="body">
            <div v-if="isLoadingContours" class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-[2rem] p-8 shadow-2xl w-full max-w-md mx-4 transform transition-all">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Loading Map Data</h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Plotting Coordinates</p>
                        </div>
                        <div class="text-3xl font-bold text-orange-500">
                            {{ contourLoadingProgress }}%
                        </div>
                    </div>

                    <!-- Progress Bar Container -->
                    <div class="w-full bg-gray-100 dark:bg-gray-700 h-4 rounded-full overflow-hidden mb-8 relative">
                        <!-- Orange Progress Fill -->
                        <div 
                            class="h-full bg-orange-500 transition-all duration-300 ease-out" 
                            :style="{ width: contourLoadingProgress + '%' }"
                        ></div>
                        <!-- Progress Knob/Circle -->
                        <div 
                            class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-orange-500 border-2 border-white rounded-full transition-all duration-300 ease-out"
                            :style="{ left: `calc(${contourLoadingProgress}% - 8px)` }"
                        ></div>
                    </div>

                    <div class="flex items-center justify-center gap-3 text-gray-400 dark:text-gray-500">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs font-bold tracking-widest uppercase">PLEASE WAIT...</span>
                    </div>
                </div>
            </div>
        </Teleport>

        <div class="h-[calc(100vh-82px)] overflow-hidden">
            <div class="h-full w-full">
                <div class="h-full bg-gray-200/[0.25] overflow-hidden">
                    <div class="flex h-full bg-transparent border-b border-gray-200 dark:border-gray-700">
                        <div class="w-[300px] flex flex-col h-full overflow-y-auto no-scrollbar">
                             <div class="mb-4 bg-white p-4">
                                    <h2 class="font-semibold uppercase text-xl text-gray-800 dark:text-gray-200 leading-tight">
                                        Locator
                                    </h2>
                                </div>
                        <!-- Filtering Menu -->
                        <div class="mb-6 px-2">
                             
                            <h3 class="text-xs font-bold text-red-500 mb-4 tracking-wider uppercase opacity-80">MAP LAYERS</h3>
                            <div class="space-y-3">
                                <div 
                                    v-for="category in categories" 
                                    :key="category.id"
                                    class="flex items-center gap-3 bg-white/50 dark:bg-gray-800/50 p-2 rounded-lg border border-transparent hover:border-orange-200 transition-all duration-200"
                                >
                                    <Checkbox 
                                        :id="category.id"
                                        :value="category.id"
                                        :checked="selectedCategories.includes(category.id)"
                                        @update:checked="toggleCategory(category.id)"
                                    />
                                    <label 
                                        :for="category.id" 
                                        class="cursor-pointer text-sm transition-colors duration-200"
                                        :class="selectedCategories.includes(category.id) ? 'text-gray-900 dark:text-gray-100 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    >
                                        {{ category.label }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Hazard Risk Legend -->
                        <div v-if="selectedCategories.includes('flood_risk') || selectedCategories.includes('contours')" class="mt-auto bg-white dark:bg-gray-800 p-4  rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Hazard risk levels</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-3 rounded-sm bg-[#9eb753] border border-gray-400"></div>
                                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">Low</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-3 rounded-sm bg-[#6fc0b0] border border-gray-400"></div>
                                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">Moderate</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-3 rounded-sm bg-[#526ab1] border border-gray-400"></div>
                                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">High</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-3 rounded-sm bg-[#9a5f97] border border-gray-400"></div>
                                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">Very High</span>
                                </div>
                            </div>
                        </div>
                        </div>
                        <div ref="mapContainer" class="flex-1 h-full z-0 border-l border-orange-500/20 shadow-inner"></div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Ensure map has z-index lower than potential overlays/modals */
.leaflet-container {
    z-index: 0;
}

:deep(.leaflet-marker-icon) {
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
    transition: transform 0.2s ease-in-out;
}

:deep(.leaflet-marker-icon:hover) {
    transform: scale(1.1);
    z-index: 1000 !important;
}

:deep(.map-tooltip), :deep(.river-label), :deep(.contour-label) {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 4px 8px;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    font-weight: bold;
    font-size: 12px;
    color: #1e293b;
}

:deep(.river-label), :deep(.contour-label) {
    background: transparent;
    border: none;
    box-shadow: none;
    text-shadow: 
        -1px -1px 0 #fff,  
         1px -1px 0 #fff,
        -1px  1px 0 #fff,
         1px  1px 0 #fff;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
