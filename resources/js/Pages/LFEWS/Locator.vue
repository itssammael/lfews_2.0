<script setup lang="ts">
// @ts-ignore
import AppLayout from '@/Layouts/AppLayout.vue';
import { onMounted, ref } from 'vue';
import L from 'leaflet';

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
    iconRetinaUrl,
    iconUrl,
    shadowUrl,
  });
} catch (e) {
  console.warn('Leaflet icon fix failed:', e);
}

const props = defineProps<{
    weatherStations: any[];
    waterLevelSensors: any[];
    locations: any[];
    rivers: any[];
}>();

const mapContainer = ref<HTMLElement | null>(null);
const map = ref<any>(null);
const selectedCategory = ref('all'); // Default to showing everything

// Layer Groups
const weatherStationsGroup = L.layerGroup();
const waterLevelSensorsGroup = L.layerGroup();
const evacuationCentersGroup = L.layerGroup();
const riversGroup = L.layerGroup();
const floodRiskGroup = L.layerGroup();

const categories = [
    { id: 'weather_stations', label: 'Weather Stations' },
    { id: 'water_level_sensors', label: 'Water Level Sensors' },
    { id: 'all_devices', label: 'All Devices' },
    { id: 'evacuation_centers', label: 'Evacuation Centers' },
    { id: 'rivers', label: 'All Rivers in Bayawan City' },
    { id: 'flood_risk', label: 'Flood Risk' },
];

const selectCategory = (id: string) => {
    selectedCategory.value = id;
    updateVisibleLayers();
};

const updateVisibleLayers = () => {
    if (!map.value) return;

    // Clear all layers first
    weatherStationsGroup.removeFrom(map.value);
    waterLevelSensorsGroup.removeFrom(map.value);
    evacuationCentersGroup.removeFrom(map.value);
    riversGroup.removeFrom(map.value);
    floodRiskGroup.removeFrom(map.value);

    // Add layers based on selection
    if (selectedCategory.value === 'all') {
         weatherStationsGroup.addTo(map.value);
         waterLevelSensorsGroup.addTo(map.value);
         evacuationCentersGroup.addTo(map.value);
         riversGroup.addTo(map.value);
         floodRiskGroup.addTo(map.value);
    } else if (selectedCategory.value === 'weather_stations') {
        weatherStationsGroup.addTo(map.value);
    } else if (selectedCategory.value === 'water_level_sensors') {
        waterLevelSensorsGroup.addTo(map.value);
    } else if (selectedCategory.value === 'all_devices') {
        weatherStationsGroup.addTo(map.value);
        waterLevelSensorsGroup.addTo(map.value);
    } else if (selectedCategory.value === 'evacuation_centers') {
        evacuationCentersGroup.addTo(map.value);
    } else if (selectedCategory.value === 'rivers') {
        riversGroup.addTo(map.value);
    } else if (selectedCategory.value === 'flood_risk') {
        floodRiskGroup.addTo(map.value);
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

        // Add Weather Stations (Blue Marker)
        props.weatherStations.forEach(station => {
            if (station.location) {
                const marker = L.marker([station.location.latitude, station.location.longitude], {
                    title: station.name
                });
                
                
                marker.bindTooltip(`<b>${station.name}</b>`, {
                    permanent: true,
                    direction: 'top',
                    className: 'map-label'
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
                    title: sensor.name
                });
                
                
                marker.bindTooltip(`<b>${sensor.name}</b>`, {
                    permanent: true,
                    direction: 'top',
                    className: 'map-label'
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
                    permanent: true,
                    direction: 'top',
                    className: 'map-label'
                });
                
                if (loc.location_type?.description === 'River') {
                    riversGroup.addLayer(marker);
                } else if (loc.location_type?.description === 'Evacuation Centers') {
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
                            permanent: true,
                            direction: 'center',
                            className: 'river-label'
                        });
                    }
                }
            }).addTo(riversGroup);
             hasPoints = true; // Assume rivers exist and should be fitted to
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
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Locator
            </h2>
        </template>

        <div class="pt-0 mb-12">
            <div class="w-full ">
                <div class=" bg-gray-200/[0.25] overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-transparent border-b border-gray-200 dark:border-gray-700">
                        
                        <!-- Filtering Menu -->
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-red-400 mb-4 tracking-wider uppercase">Select an item to locate</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                                <button 
                                    v-for="category in categories" 
                                    :key="category.id"
                                    @click="selectCategory(category.id)"
                                    class="text-left py-1 transition-colors duration-200"
                                    :class="selectedCategory === category.id ? 'text-gray-900 dark:text-gray-100 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                >
                                    {{ category.label }}
                                </button>
                            </div>
                        </div>

                        <div ref="mapContainer" class="w-full h-[600px] z-0  border-2 border-orange-500 rounded-2xl shadow-md"></div>
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

:deep(.map-label), :deep(.river-label) {
    background: transparent;
    border: none;
    box-shadow: none;
    font-weight: bold;
    font-size: 12px;
    text-shadow: 
        -1px -1px 0 #fff,  
         1px -1px 0 #fff,
        -1px  1px 0 #fff,
         1px  1px 0 #fff;
}
</style>
