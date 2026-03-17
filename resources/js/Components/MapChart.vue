<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import proj4 from 'proj4';

// Define the CRS for Bayawan (UTM Zone 51N)
proj4.defs("EPSG:32651","+proj=utm +zone=51 +datum=WGS84 +units=m +no_defs");

const props = defineProps<{
    stations: Array<{
        id: number;
        name: string;
        location?: {
            latitude: string | number;
            longitude: string | number;
        };
    }>;
    weatherData: Record<number, {
        success: boolean;
        data?: {
            heat_index: number | string;
            temperature?: number | string;
            humidity?: number | string;
            wind_speed?: number | string;
            precipitation_rate?: number | string;
            solar_radiation?: number | string;
            uv?: number | string;
            date_time?: string;
        };
        timestamp?: string;
    }> | null;
    barangays?: any[];
    hiSettings?: Array<{
        color: string;
        advice: string;
        label: string;
        temprange: string;
    }>;
    viewMode?: 'heat_index' | 'full';
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
const markersLayer = L.layerGroup();
const barangaysGroup = L.featureGroup();

const getHeatIndexColor = (heatIndex: number) => {
    if (!props.hiSettings || props.hiSettings.length === 0) {
        if (heatIndex >= 52) return '#990000'; // Extreme Danger - Dark Red
        if (heatIndex >= 42) return '#cc0000'; // Danger - Red
        if (heatIndex >= 33) return '#ff9900'; // Extreme Caution - Orange
        if (heatIndex >= 28) return '#ffcc00'; // Caution - Yellow
        return '#33cc33'; // Normal - Green
    }

    // Iterate through settings to find the matching range
    // We reverse to check higher ranges first (e.g., >= 52 before >= 42)
    for (const setting of [...props.hiSettings].reverse()) {
        const range = setting.temprange;
        try {
            if (range.includes('>=')) {
                const val = parseFloat(range.replace(/>=/g, '').trim());
                if (heatIndex >= val) return setting.color;
            } else if (range.includes('<=')) {
                const val = parseFloat(range.replace(/<=/g, '').trim());
                if (heatIndex <= val) return setting.color;
            } else if (range.includes('>')) {
                const val = parseFloat(range.replace(/>/g, '').trim());
                if (heatIndex > val) return setting.color;
            } else if (range.includes('<')) {
                const val = parseFloat(range.replace(/</g, '').trim());
                // Handle gap if heat index is slightly below the warning bounds (e.g. 27.5 for < 27 condition if needed, but < works fine alone)
                if (heatIndex < val) return setting.color;
            } else if (range.includes('-')) {
                const parts = range.split('-').map((p: string) => parseFloat(p.trim()));
                // Add 1 to the upper bound to effectively cover float values like 41.5 in a 33-41 range (as [33, 42))
                if (heatIndex >= parts[0] && heatIndex < (parts[1] + 1)) return setting.color;
            }
        } catch (e) {
            console.error("Error parsing temprange:", range, e);
        }
    }

    // Default to the first setting's color or normal green if no match found
    // also gracefully covers gap values like 27.5 lying between < 27 and 28-32
    return props.hiSettings?.[0]?.color || '#33cc33';
};

const transformGeometry = (geometry: any) => {
    if (!geometry) return null;
    
    // Check if it's already in WGS84 (approximate check based on coordinate magnitude)
    // If UTM Zone 51, coordinates will be large (e.g., 400000, 1000000)
    // We'll use proj4 to transform if necessary
        const transformCoords = (coords: any): any => {
            if (typeof coords[0] === 'number') {
                // If coordinates are large, they likely need transformation from EPSG:32651 to WGS84
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

const processBarangays = () => {
    if (!props.barangays || props.barangays.length === 0) return;

    barangaysGroup.clearLayers();

    const features = props.barangays.map(b => ({
        type: "Feature",
        properties: { ...b.properties, db_name: b.name },
        geometry: transformGeometry(b.geometry)
    }));

    L.geoJSON({ type: "FeatureCollection", features: features } as any, {
        style: {
            color: '#0d4dad',
            weight: 2,
            opacity: 0.8,
            fillOpacity: 0.1
        },
        coordsToLatLng: (coords) => L.latLng(coords[1], coords[0]),
        onEachFeature: (feature: any, layer: any) => {
            if (feature.properties) {
                const label = `<b style="text-transform: capitalize;">${feature.properties.db_name || feature.properties.barangay || feature.properties.Bgy_Name || feature.properties.name || 'Unknown'}</b>`;
                layer.bindTooltip(label, {
                    sticky: true,
                    className: 'map-tooltip'
                });
            }
        }
    }).addTo(barangaysGroup);

    if (map && barangaysGroup.getLayers().length > 0) {
        map.fitBounds(barangaysGroup.getBounds(), { padding: [5, 5] });
    }
};

const formatValue = (value: any, decimals: number = 0) => {
    if (value === undefined || value === null || isNaN(Number(value))) return '-';
    return Number(value).toFixed(decimals);
};

const updatePointsData = () => {
    if (!map) return;
    
    // Clear existing markers
    markersLayer.clearLayers();

    if (props.viewMode === 'full') {
        props.stations.forEach(station => {
            if (station.location?.latitude && station.location?.longitude) {
                const stationData = props.weatherData?.[station.id];
                const data = (stationData?.data || {}) as any;

                const customIcon = L.divIcon({
                    className: 'custom-full-weather-marker',
                    html: `
                        <div class="grid grid-cols-2 gap-0.5 w-[76px] h-[76px] bg-white dark:bg-gray-800 border-2 border-gray-400 rounded-lg shadow-xl overflow-hidden p-[2px]">
                            <!-- Top Left: Temp -->
                            <div class="bg-blue-300/30 flex flex-col items-center justify-center rounded-sm">
                                 <span class="text-[7px] font-bold text-blue-600 leading-none">TEMP</span>
                                 <span class="text-[13px] font-black text-blue-700 leading-none">${formatValue(data.temperature)}°</span>
                            </div>
                            <!-- Top Right: Humidity -->
                            <div class="bg-yellow-200/30 flex flex-col items-center justify-center rounded-sm">
                                 <span class="text-[7px] font-bold text-yellow-600 leading-none">HUMI</span>
                                 <span class="text-[13px] font-black text-yellow-700 leading-none">${formatValue(data.humidity)}%</span>
                            </div>
                            <!-- Bottom Left: Wind -->
                            <div class="bg-white/50 flex flex-col items-center justify-center rounded-sm">
                                 <span class="text-[7px] font-bold text-gray-500 leading-none">WIND</span>
                                 <span class="text-[11px] font-black text-gray-800 leading-none italic">${formatValue(data.wind_speed)}</span>
                            </div>
                            <!-- Bottom Right: Rain -->
                            <div class="bg-red-200/30 flex flex-col items-center justify-center rounded-sm">
                                 <span class="text-[7px] font-bold text-red-600 leading-none">RAIN</span>
                                 <span class="text-[11px] font-black text-red-700 leading-none">${formatValue(data.precipitation_rate, 1)}</span>
                            </div>
                        </div>
                    `,
                    iconSize: [76, 76],
                    iconAnchor: [38, 38],
                    popupAnchor: [0, -38]
                });

                const marker = L.marker([Number(station.location.latitude), Number(station.location.longitude)], { icon: customIcon })
                    .bindPopup(`
                        <div class="p-2 min-w-[200px] dark:bg-gray-800 dark:text-gray-100">
                            <div class="font-black text-lg border-b border-gray-200 dark:border-gray-700 mb-2 uppercase italic text-blue-600">${station.name}</div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="flex flex-col"><span class="font-bold opacity-60">Temperature</span> <span class="text-lg font-black">${formatValue(data.temperature)}°C</span></div>
                                <div class="flex flex-col"><span class="font-bold opacity-60">Humidity</span> <span class="text-lg font-black">${formatValue(data.humidity)}%</span></div>
                                <div class="flex flex-col"><span class="font-bold opacity-60">Wind Speed</span> <span class="text-lg font-black">${formatValue(data.wind_speed)} kph</span></div>
                                <div class="flex flex-col"><span class="font-bold opacity-60">Rain Rate</span> <span class="text-lg font-black">${formatValue(data.precipitation_rate, 1)} mm/h</span></div>
                                <div class="flex flex-col"><span class="font-bold opacity-60">Solar Radiation</span> <span class="text-lg font-black">${formatValue(data.solar_radiation)} W/m²</span></div>
                                <div class="flex flex-col"><span class="font-bold opacity-60">UV Index</span> <span class="text-lg font-black">${formatValue(data.uv, 1)}</span></div>
                            </div>
                            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 text-[10px] opacity-40 uppercase font-bold italic">Latest Observation: ${stationData?.data?.date_time || '-'}</div>
                        </div>
                    `, {
                        autoClose: false,
                        closeOnClick: false
                    })
                    .addTo(markersLayer);
                marker.openPopup();
            }
        });
    } else {
        // Calculate scaling factor based on zoom, ensuring it does not shrink below base size
        const baseZoom = 11;
        const currentZoom = map.getZoom();
        const zoomScale = Math.max(1, Math.pow(1.3, currentZoom - baseZoom));

        // Base sizes
        const baseOuter = 58;
        const baseInner = 38;
        const baseFont = 12;

        const outerSize = Math.round(baseOuter * zoomScale);
        const innerSize = Math.round(baseInner * zoomScale);
        const fontSize = Math.round(baseFont * zoomScale);

        props.stations.forEach(station => {
            if (station.location?.latitude && station.location?.longitude) {
                const stationData = props.weatherData?.[station.id];
                
                let heatIndex = 0;
                if (stationData && stationData.success && stationData.data?.heat_index !== undefined) {
                     heatIndex = Number(stationData.data.heat_index);
                }

                const color = getHeatIndexColor(heatIndex);
                
                // Determine if the marker should blink (Danger or Extreme Danger)
                const isDanger = heatIndex >= 42;
                const animationClass = isDanger ? 'pulse-danger' : '';

                // Create a custom DivIcon
                const customIcon = L.divIcon({
                    className: 'custom-heat-index-marker',
                    html: `
                        <div class="${animationClass}" style="
                            position: relative;
                            width: ${outerSize}px;
                            height: ${outerSize}px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <!-- Outer Glow -->
                            <div style="
                                position: absolute;
                                top: 0; left: 0;
                                width: 100%; height: 100%;
                                border-radius: 50%;
                                background-color: ${color};
                                opacity: 0.3;
                            "></div>
                            <!-- Inner Solid Circle -->
                            <div style="
                                position: relative;
                                width: ${innerSize}px; height: ${innerSize}px;
                                border-radius: 50%;
                                background-color: ${color};
                                border: 2px solid white;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                font-size: ${fontSize}px;
                                font-weight: bold;
                                letter-spacing: -0.5px;
                                z-index: 10;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.3);
                            ">
                                ${heatIndex.toFixed(1)}°
                            </div>
                        </div>
                    `,
                    iconSize: [outerSize, outerSize],
                    iconAnchor: [outerSize / 2, outerSize / 2],
                    popupAnchor: [0, -outerSize / 2]
                });

                // Add marker to layer
                const marker = L.marker([Number(station.location.latitude), Number(station.location.longitude)], { icon: customIcon })
                    .bindPopup(`<strong>${station.name}</strong>`, {
                        autoClose: false,
                        closeOnClick: false
                    })
                    .addTo(markersLayer);
                marker.openPopup();
            }
        });
    }
};

onMounted(() => {
    if (!mapContainer.value) return;

    // Initialize map
    map = L.map(mapContainer.value).setView([9.3668, 122.8055], 11);

    // Add OSM TileLayer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Add markers layer
    markersLayer.addTo(map);

    // Add barangays layer
    barangaysGroup.addTo(map);

    // Initial data load
    updatePointsData();
    processBarangays();

    // Redraw markers when zoom changes to maintain scaling ratio
    map.on('zoomend', () => {
        updatePointsData();
    });

    // Create custom Legend Control
    const LegendControl = L.Control.extend({
        options: {
            position: 'bottomleft'
        },
        onAdd: function () {
            const div = L.DomUtil.create('div', 'info legend');
            div.style.backgroundColor = 'white';
            div.style.padding = '10px';
            div.style.borderRadius = '5px';
            div.style.boxShadow = '0 1px 5px rgba(0,0,0,0.4)';
            div.style.fontSize = '12px';
            div.style.lineHeight = '1.5';
            
            if (props.viewMode !== 'heat_index') {
                div.style.display = 'none';
                return div;
            }
            div.style.display = 'block';
            
            const labels = props.hiSettings && props.hiSettings.length > 0
                ? props.hiSettings.map(s => ({ color: s.color, text: `${s.label} (${s.temprange})` }))
                : [
                    { color: '#33cc33', text: 'Normal (< 27°C)' },
                    { color: '#ffcc00', text: 'Caution (28°C - 32°C)' },
                    { color: '#ff9900', text: 'Ext. Caution (33°C - 41°C)' },
                    { color: '#cc0000', text: 'Danger (42°C - 51°C)' },
                    { color: '#990000', text: 'Ext. Danger (>= 52°C)' }
                ];
            
            let html = '<div style="font-weight:bold;margin-bottom:5px;">Heat Index</div>';
            labels.forEach(label => {
                html += `
                    <div style="display:flex;align-items:center;">
                        <span style="display:inline-block;width:12px;height:12px;background-color:${label.color};border-radius:50%;margin-right:5px;"></span>
                        ${label.text}
                    </div>
                `;
            });
            div.innerHTML = html;
            return div;
        }
    });

    legendInstance = new LegendControl();
    legendInstance.addTo(map);
});

let legendInstance: L.Control | null = null;

onUnmounted(() => {
    if (map) {
        map.remove();
    }
});

// Watch for changes in weatherData to dynamically update markers
watch(() => props.weatherData, () => {
    updatePointsData();
}, { deep: true });

watch(() => props.hiSettings, () => {
    updatePointsData();
    // Re-add legend if it changes
    if (map && legendInstance) {
        map.removeControl(legendInstance);
        legendInstance.addTo(map);
    }
}, { deep: true });

watch(() => props.viewMode, () => {
    updatePointsData();
    // Re-add legend to apply visibility check
    if (map && legendInstance) {
        map.removeControl(legendInstance);
        legendInstance.addTo(map);
    }
});

</script>

<template>
    <div class="w-full h-full z-0" ref="mapContainer"></div>
</template>

<style>
/* Leaflet uses high z-indexes which can interfere with fixed navbars, lowering it for dashboard integration */
.leaflet-container {
    z-index: 0 !important;
}

/* Blinking animation for danger markers */
@keyframes dangerPulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}

.pulse-danger {
    animation: dangerPulse 1s infinite;
}
</style>
