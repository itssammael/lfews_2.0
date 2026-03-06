<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
const markersLayer = L.layerGroup();

const getHeatIndexColor = (heatIndex: number) => {
    if (heatIndex >= 52) return '#990000'; // Extreme Danger - Dark Red
    if (heatIndex >= 39) return '#cc0000'; // Danger - Red
    if (heatIndex >= 32) return '#ff9900'; // Extreme Caution - Orange
    if (heatIndex >= 27) return '#ffcc00'; // Caution - Yellow
    return '#33cc33'; // Normal - Green
};

const updatePointsData = () => {
    if (!map) return;
    
    // Clear existing markers
    markersLayer.clearLayers();

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
                    width: 40px;
                    height: 40px;
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
                        width: 24px; height: 24px;
                        border-radius: 50%;
                        background-color: ${color};
                        border: 2px solid white;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-size: 10px;
                        font-weight: bold;
                        z-index: 10;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
                    ">
                        ${heatIndex.toFixed(1)}
                    </div>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -20]
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

    // Initial data load
    updatePointsData();

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
