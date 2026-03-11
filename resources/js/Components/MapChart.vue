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
        };
    }> | null;
    barangays?: any[];
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
const markersLayer = L.layerGroup();
const barangaysGroup = L.featureGroup();

const getHeatIndexColor = (heatIndex: number) => {
    if (heatIndex >= 52) return '#990000'; // Extreme Danger - Dark Red
    if (heatIndex >= 39) return '#cc0000'; // Danger - Red
    if (heatIndex >= 32) return '#ff9900'; // Extreme Caution - Orange
    if (heatIndex >= 27) return '#ffcc00'; // Caution - Yellow
    return '#33cc33'; // Normal - Green
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

const updatePointsData = () => {
    if (!map) return;
    
    // Clear existing markers
    markersLayer.clearLayers();

    // Calculate scaling factor based on zoom, ensuring it does not shrink below base size
    const baseZoom = 11;
    const currentZoom = map.getZoom();
    const zoomScale = Math.max(1, Math.pow(1.3, currentZoom - baseZoom));

    // Base sizes (+30% from original 40/24/10)
    const baseOuter = 52;
    const baseInner = 31;
    const baseFont = 13;

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
                        z-index: 10;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
                    ">
                        ${heatIndex.toFixed(1)}
                    </div>
                </div>
            `,
            iconSize: [outerSize, outerSize],
            iconAnchor: [outerSize / 2, outerSize / 2],
            popupAnchor: [0, -outerSize / 2]
        });

        // Add marker to layer
        L.marker([Number(station.location.latitude), Number(station.location.longitude)], { icon: customIcon })
            .bindPopup(`<strong>${station.name}</strong><br/>Heat Index: ${heatIndex.toFixed(1)}°C`)
            .addTo(markersLayer);
    }
});
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
            
            const labels = [
                { color: '#33cc33', text: 'Normal (< 27°C)' },
                { color: '#ffcc00', text: 'Caution (27°C - 32°C)' },
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

    new LegendControl().addTo(map);
});

onUnmounted(() => {
    if (map) {
        map.remove();
    }
});

// Watch for changes in weatherData to dynamically update markers
watch(() => props.weatherData, () => {
    updatePointsData();
}, { deep: true });

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
