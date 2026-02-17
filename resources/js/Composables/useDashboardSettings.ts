import { ref, watch } from 'vue';

const showWaterLevelSensors = ref(JSON.parse(localStorage.getItem('showWaterLevelSensors') || 'true'));
const showWeatherStations = ref(JSON.parse(localStorage.getItem('showWeatherStations') || 'true'));
const showEvacuationCenters = ref(JSON.parse(localStorage.getItem('showEvacuationCenters') || 'true'));

watch(showWaterLevelSensors, (newValue) => {
    localStorage.setItem('showWaterLevelSensors', JSON.stringify(newValue));
});

watch(showWeatherStations, (newValue) => {
    localStorage.setItem('showWeatherStations', JSON.stringify(newValue));
});

watch(showEvacuationCenters, (newValue) => {
    localStorage.setItem('showEvacuationCenters', JSON.stringify(newValue));
});

export function useDashboardSettings() {
    return {
        showWaterLevelSensors,
        showWeatherStations,
        showEvacuationCenters
    };
}
