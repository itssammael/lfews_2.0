import { ref, watch } from 'vue';

const showWaterLevelSensors = ref(JSON.parse(localStorage.getItem('showWaterLevelSensors') || 'true'));
const showWeatherStations = ref(JSON.parse(localStorage.getItem('showWeatherStations') || 'true'));
const showEvacuationCenters = ref(JSON.parse(localStorage.getItem('showEvacuationCenters') || 'true'));
const showTidalExtremes = ref(JSON.parse(localStorage.getItem('showTidalExtremes') || 'true'));
const showBarangays = ref(JSON.parse(localStorage.getItem('showBarangays') || 'true'));
const showSitios = ref(JSON.parse(localStorage.getItem('showSitios') || 'true'));

watch(showWaterLevelSensors, (newValue) => {
    localStorage.setItem('showWaterLevelSensors', JSON.stringify(newValue));
});

watch(showWeatherStations, (newValue) => {
    localStorage.setItem('showWeatherStations', JSON.stringify(newValue));
});

watch(showEvacuationCenters, (newValue) => {
    localStorage.setItem('showEvacuationCenters', JSON.stringify(newValue));
});

watch(showTidalExtremes, (newValue) => {
    localStorage.setItem('showTidalExtremes', JSON.stringify(newValue));
});

watch(showBarangays, (newValue) => {
    localStorage.setItem('showBarangays', JSON.stringify(newValue));
});

watch(showSitios, (newValue) => {
    localStorage.setItem('showSitios', JSON.stringify(newValue));
});

export function useDashboardSettings() {
    return {
        showWaterLevelSensors,
        showWeatherStations,
        showEvacuationCenters,
        showTidalExtremes,
        showBarangays,
        showSitios
    };
}
