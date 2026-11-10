import { ref, watch } from 'vue';

const readStorage = (key: string, fallback: boolean): boolean => {
    if (typeof window === 'undefined' || !window.localStorage) {
        return fallback;
    }
    try {
        const raw = window.localStorage.getItem(key);
        return raw !== null ? JSON.parse(raw) : fallback;
    } catch {
        return fallback;
    }
};

const writeStorage = (key: string, value: boolean): void => {
    if (typeof window !== 'undefined' && window.localStorage) {
        window.localStorage.setItem(key, JSON.stringify(value));
    }
};

const showWaterLevelSensors = ref(readStorage('showWaterLevelSensors', true));
const showWeatherStations = ref(readStorage('showWeatherStations', true));
const showEvacuationCenters = ref(readStorage('showEvacuationCenters', true));
const showTidalExtremes = ref(readStorage('showTidalExtremes', true));
const showBarangays = ref(readStorage('showBarangays', true));
const showSitios = ref(readStorage('showSitios', true));

watch(showWaterLevelSensors, (newValue) => {
    writeStorage('showWaterLevelSensors', newValue);
});

watch(showWeatherStations, (newValue) => {
    writeStorage('showWeatherStations', newValue);
});

watch(showEvacuationCenters, (newValue) => {
    writeStorage('showEvacuationCenters', newValue);
});

watch(showTidalExtremes, (newValue) => {
    writeStorage('showTidalExtremes', newValue);
});

watch(showBarangays, (newValue) => {
    writeStorage('showBarangays', newValue);
});

watch(showSitios, (newValue) => {
    writeStorage('showSitios', newValue);
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
