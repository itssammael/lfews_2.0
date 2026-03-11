<script setup lang="ts">
// @ts-ignore
import AppLayout from '@/Layouts/AppLayout.vue';
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
// @ts-ignore
import Checkbox from '@/Components/Checkbox.vue';
import axios from 'axios';
import { useDashboardSettings } from "@/Composables/useDashboardSettings";
import ProgressLoader from '@/Components/ProgressLoader.vue';

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
    barangays: any[];
    sitios?: any[];
}>();

const localContours = ref<any[]>([]);
const localSitios = ref<any[]>([]);

const mapContainer = ref<HTMLElement | null>(null);
const map = ref<any>(null);

const { showBarangays, showSitios } = useDashboardSettings();

const selectedCategories = ref<string[]>(['weather_stations', 'water_level_sensors', 'evacuation_centers', 'rivers']); // Default visible categories

// Sync global settings to selectedCategories
if (showBarangays.value && !selectedCategories.value.includes('barangays')) selectedCategories.value.push('barangays');
if (showSitios.value && !selectedCategories.value.includes('sitios')) selectedCategories.value.push('sitios');

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

// Barangay & Sitio loading state
const isLoadingBarangays = ref(false);
const barangayLoadingProgress = ref(0);
const isBarangaysProcessed = ref(false);

const isLoadingSitios = ref(false);
const sitioLoadingProgress = ref(0);
const isSitiosProcessed = ref(false);

// Layer Groups
const weatherStationsGroup = L.layerGroup();
const waterLevelSensorsGroup = L.layerGroup();
const evacuationCentersGroup = L.layerGroup();
const riversGroup = L.layerGroup();
const contoursGroup = L.layerGroup();
const floodRiskGroup = L.layerGroup();
const barangaysGroup = L.layerGroup();
const sitiosGroup = L.layerGroup();

const categories = [
    { id: 'weather_stations', label: 'Weather Stations' },
    { id: 'water_level_sensors', label: 'Water Level Sensors' },
    { id: 'evacuation_centers', label: 'Evacuation Centers' },
    { id: 'rivers', label: 'Rivers' },
    { id: 'contours', label: 'Contour lines' },
    { id: 'flood_risk', label: 'Flood Risk Map' },
    { id: 'sitios', label: 'Sitios' },
    { id: 'barangays', label: 'Barangays' },
];

const toggleCategory = (id: string) => {
    const index = selectedCategories.value.indexOf(id);
    if (index > -1) {
        selectedCategories.value.splice(index, 1);
    } else {
        selectedCategories.value.push(id);
    }

    if (selectedCategories.value.includes('contours') && !isContoursProcessed.value) {
        processContours();
    }
    
    if (selectedCategories.value.includes('flood_risk') && !isFloodRisksProcessed.value && props.floodRisks?.length > 0) {
        processFloodRisks();
    }

    if (selectedCategories.value.includes('barangays') && !isBarangaysProcessed.value && props.barangays?.length > 0) {
        processBarangays();
    }

    if (selectedCategories.value.includes('sitios') && !isSitiosProcessed.value) {
        processSitios();
    }

    updateVisibleLayers();
};

const transformGeometry = (geometry: any) => {
    if (!geometry) return null;
    
    const transformCoords = (coords: any): any => {
        if (typeof coords[0] === 'number') {
            // [x, y] -> [lng, lat]
            return proj4("EPSG:32651", "WGS84", coords);
        }
        return coords.map(transformCoords);
    };

    return {
        ...geometry,
        coordinates: transformCoords(geometry.coordinates)
    };
};

// Sync local selectedCategories changes back to global showBarangays/showSitios
watch(selectedCategories, (newCats) => {
    showBarangays.value = newCats.includes('barangays');
    showSitios.value = newCats.includes('sitios');
    updateVisibleLayers();
}, { deep: true });

// Sync global showBarangays/showSitios changes back to selectedCategories
watch(showBarangays, (newVal) => {
    const has = selectedCategories.value.includes('barangays');
    if (newVal && !has) {
        selectedCategories.value.push('barangays');
        if (!isBarangaysProcessed.value && props.barangays?.length > 0) processBarangays();
    } else if (!newVal && has) {
        selectedCategories.value = selectedCategories.value.filter(c => c !== 'barangays');
    }
});

watch(showSitios, (newVal) => {
    const has = selectedCategories.value.includes('sitios');
    if (newVal && !has) {
        selectedCategories.value.push('sitios');
        if (!isSitiosProcessed.value) processSitios();
    } else if (!newVal && has) {
        selectedCategories.value = selectedCategories.value.filter(c => c !== 'sitios');
    }
});

const processContours = async () => {
    if (isLoadingContours.value || isContoursProcessed.value) return;
    
    isLoadingContours.value = true;
    contourLoadingProgress.value = 0;
    processedContoursCount.value = 0;

    if (localContours.value.length === 0) {
        try {
            let page = 1;
            let hasMorePages = true;

            while (hasMorePages) {
                // @ts-ignore
                const response = await axios.get(route('locator.api.contours', { page }));
                const payload = response.data;
                
                if (page === 1) {
                    totalContours.value = payload.total;
                }

                const chunkData = payload.data;
                localContours.value.push(...chunkData);

                const features = chunkData.map((contour: any) => ({
                    type: "Feature",
                    properties: contour.properties,
                    geometry: contour.geometry
                }));

                const chunkSize = 50;
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

                if (page >= payload.last_page) {
                    hasMorePages = false;
                } else {
                    page++;
                }
            }
            
            isContoursProcessed.value = true;
            isLoadingContours.value = false;
            updateVisibleLayers();
        } catch (error) {
            console.error('Failed to load contours:', error);
            isLoadingContours.value = false;
            return;
        }
    }
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

const processBarangays = async () => {
    if (isLoadingBarangays.value || isBarangaysProcessed.value) return;
    
    isLoadingBarangays.value = true;
    barangayLoadingProgress.value = 0;
    const total = props.barangays.length;
    let processed = 0;

    const chunkSize = 20;
    const features = props.barangays.map(b => ({
        type: "Feature",
        properties: { ...b.properties, db_name: b.name },
        geometry: transformGeometry(b.geometry)
    }));

    for (let i = 0; i < features.length; i += chunkSize) {
        const chunk = features.slice(i, i + chunkSize);
        
        L.geoJSON({ type: "FeatureCollection", features: chunk } as any, {
            style: {
                color: '#f97316', // Orange-500
                weight: 2,
                opacity: 0.8,
                fillOpacity: 0.1
            },
            coordsToLatLng: (coords) => L.latLng(coords[1], coords[0]),
            onEachFeature: (feature, layer) => {
                if (feature.properties) {
                    const label = `<b style="text-transform: capitalize;">${feature.properties.db_name || feature.properties.barangay || feature.properties.Bgy_Name || feature.properties.name || 'Unknown'}</b>`;
                    layer.bindTooltip(label, {
                        sticky: true,
                        className: 'map-tooltip'
                    });
                }
            }
        }).addTo(barangaysGroup);

        processed += chunk.length;
        barangayLoadingProgress.value = Math.min(100, Math.round((processed / total) * 100));
        await new Promise(resolve => setTimeout(resolve, 10));
    }

    isBarangaysProcessed.value = true;
    isLoadingBarangays.value = false;
    updateVisibleLayers();
};

const processSitios = async () => {
    if (isLoadingSitios.value || isSitiosProcessed.value) return;
    
    isLoadingSitios.value = true;
    sitioLoadingProgress.value = 0;
    
    let processed = 0;
    let total = 0;

    if (localSitios.value.length === 0) {
        try {
            let page = 1;
            let hasMorePages = true;

            while (hasMorePages) {
                // @ts-ignore
                const response = await axios.get(route('locator.api.sitios', { page }));
                const payload = response.data;
                
                if (page === 1) {
                    total = payload.total;
                }

                const chunkData = payload.data;
                localSitios.value.push(...chunkData);

                const chunkSize = 50;
                const features = chunkData.map((s: any) => ({
                    type: "Feature",
                    properties: { ...s.properties, db_name: s.name },
                    geometry: transformGeometry(s.geometry)
                }));

                for (let i = 0; i < features.length; i += chunkSize) {
                    const chunk = features.slice(i, i + chunkSize);
                    
                    L.geoJSON({ type: "FeatureCollection", features: chunk } as any, {
                        style: {
                            color: '#06b6d4', // Cyan-500
                            weight: 1,
                            opacity: 0.6,
                            fillOpacity: 0.2
                        },
                        coordsToLatLng: (coords) => L.latLng(coords[1], coords[0]),
                        onEachFeature: (feature, layer) => {
                            if (feature.properties) {
                                const label = `<b>Sitio: ${feature.properties.db_name || feature.properties.sitioname || feature.properties.Sitio_Name || feature.properties.name || 'Unknown'}</b><br/>Barangay: ${feature.properties.barangayname || feature.properties.Bgy_Name || 'Unknown'}`;
                                layer.bindTooltip(label, {
                                    sticky: true,
                                    className: 'map-tooltip'
                                });
                            }
                        }
                    }).addTo(sitiosGroup);

                    processed += chunk.length;
                    sitioLoadingProgress.value = Math.min(100, Math.round((processed / total) * 100));
                    await new Promise(resolve => setTimeout(resolve, 10));
                }

                if (page >= payload.last_page) {
                    hasMorePages = false;
                } else {
                    page++;
                }
            }

            isSitiosProcessed.value = true;
            isLoadingSitios.value = false;
            updateVisibleLayers();
        } catch (error) {
            console.error('Failed to load sitios:', error);
            isLoadingSitios.value = false;
            return;
        }
    }
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

    // barangays
    if (selectedCategories.value.includes('barangays')) {
        barangaysGroup.addTo(map.value);
    } else {
        barangaysGroup.removeFrom(map.value);
    }

    // sitios
    if (selectedCategories.value.includes('sitios')) {
        sitiosGroup.addTo(map.value);
    } else {
        sitiosGroup.removeFrom(map.value);
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

                const latest = station.latest_observation;
                const status = station.state == 1 ? '<span class="text-green-500">Online</span>' : '<span class="text-red-500">Offline</span>';
                let dataHtml = '<div class="text-xs text-gray-500 mt-1">No recent data</div>';

                if (latest) {
                    dataHtml = `
                        <div class="mt-2 space-y-1">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Temperature:</span>
                                <span class="font-bold">${Number(latest.temperature).toFixed(2)}°C</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Heat Index:</span>
                                <span class="font-bold">${Number(latest.heat_index).toFixed(2)}°C</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Rain Total:</span>
                                <span class="font-bold">${Number(latest.precipitation_total).toFixed(2)} mm</span>
                            </div>
                            <div class="text-[10px] text-gray-400 mt-1">As of: ${latest.date_time}</div>
                        </div>
                    `;
                }

                marker.bindPopup(`
                    <div class="p-1 min-w-[150px]">
                        <div class="font-bold text-sm border-b pb-1 mb-1">${station.name}</div>
                        <div class="text-xs">Status: ${status}</div>
                        ${dataHtml}
                    </div>
                `);

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

                const latest = sensor.latest_data;
                const status = sensor.state == 1 ? '<span class="text-green-500">Online</span>' : '<span class="text-red-500">Offline</span>';
                let dataHtml = '<div class="text-xs text-gray-500 mt-1">No recent data</div>';

                if (latest) {
                    dataHtml = `
                        <div class="mt-2 space-y-1">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Water Level:</span>
                                <span class="font-bold">${latest.sensor_data} m</span>
                            </div>
                            <div class="text-[10px] text-gray-400 mt-1">As of: ${latest.date}</div>
                        </div>
                    `;
                }

                marker.bindPopup(`
                    <div class="p-1 min-w-[150px]">
                        <div class="font-bold text-sm border-b pb-1 mb-1">${sensor.name}</div>
                        <div class="text-xs">Status: ${status}</div>
                        ${dataHtml}
                    </div>
                `);

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
                    // Coordinates in BayawanRivers.json are already in WGS84 [lng, lat]
                    // Leaflet expects L.latLng(lat, lng)
                    return L.latLng(coords[1], coords[0]);
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


        if (selectedCategories.value.includes('barangays')) {
            if (!isBarangaysProcessed.value && props.barangays?.length > 0) {
                 processBarangays();
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
        <ProgressLoader 
            :show="isLoadingContours" 
            title="Loading Map Data" 
            subtitle="Plotting Coordinates"
            :progress="contourLoadingProgress" 
        />

        <ProgressLoader 
            :show="isLoadingBarangays" 
            title="Loading Barangays" 
            subtitle="Processing Geographic Data"
            :progress="barangayLoadingProgress" 
        />

        <ProgressLoader 
            :show="isLoadingSitios" 
            title="Loading Sitios" 
            subtitle="Processing Geographic Data"
            :progress="sitioLoadingProgress" 
        />

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
                                        :disabled="category.id === 'barangays'"
                                        :class="category.id === 'barangays' ? 'opacity-50 cursor-not-allowed' : ''"
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
