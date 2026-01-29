<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WeatherStationModal from '@/Components/WeatherStationModal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { router } from '@inertiajs/vue3';

interface Station {
    id: number;
    name: string;
    station_id: string;
    mode: string;
    state: number; // Changed to number
    location_id: number;
    location?: {
        id: number;
        latitude: number;
        longitude: number;
        location_type?: {
            description: string;
        }
    };
}

const props = defineProps<{
    stations: Station[];
    locations?: any[]; // Allow locations prop
    showCreateModal?: boolean;
    editingStation?: Station;
    activeCount?: number;
    inactiveCount?: number;
    maintenanceCount?: number;
}>();

const showingModal = ref(false);
const activeStation = ref<Station | null>(null);

watch(() => props.editingStation, (newStation: Station | undefined) => {
    console.log(newStation)
    if (newStation) {
        activeStation.value = newStation;
        showingModal.value = false;
    }
}, { immediate: true });

onMounted(() => {
    if (props.showCreateModal && !props.editingStation) {
        activeStation.value = null;
        showingModal.value = true;
    } else if (props.editingStation) {
        activeStation.value = props.editingStation;
        showingModal.value = true;
    }
});

const closeModal = () => {
    showingModal.value = false;
    activeStation.value = null;
};

const confirmingStationDeletion = ref(false);
const stationToDelete = ref<Station | null>(null);

const confirmStationDeletion = (station: Station) => {
    stationToDelete.value = station;
    confirmingStationDeletion.value = true;
};

const deleteStation = () => {
    if (stationToDelete.value) {
        router.delete(route('weather-stations.destroy', stationToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                confirmingStationDeletion.value = false;
                stationToDelete.value = null;
            },
        });
    }
};

const closeDeleteModal = () => {
    confirmingStationDeletion.value = false;
    stationToDelete.value = null;
};
</script>

<template>
    <AppLayout title="Weather Stations">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Manage Weather Stations
                </h2>
            </div>
        </template>

        <div class="py-6 h-[85%]">
            <div class="max-w-9xl mx-auto px-8 h-full">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg h-full">
                    <div class="p-6 h-full">
                        <div class="flex">
                            <div class="w-1/3 flex space-x-8 items-center py-2">
                                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight uppercase">
                                    Weather Station List
                                </h2>
                                <Link
                                    :href="route('weather-stations.create')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    Add Station
                                </Link>
                            </div>
                            <div class="w-2/3 py-2 flex space-x-6 items-center justify-center">
                                <div class="flex items-center justify-center w-fit space-x-1.5">
                                    <div class="w-[20px] h-[20px] bg-green-500 rounded-full">&nbsp;</div>
                                    <span class="text-lg uppercase">Active (<span class="font-bold">{{ activeCount }}</span>)</span>
                                </div>
                                <div class="flex items-center justify-center w-fit space-x-1.5">
                                    <div class="w-[20px] h-[20px] bg-red-500 rounded-full">&nbsp;</div>
                                    <span class="text-lg uppercase">inactive (<span class="font-bold">{{ inactiveCount }}</span>)</span>
                                </div>
                                <div class="flex items-center justify-center w-fit space-x-1.5">
                                    <div class="w-[20px] h-[20px] bg-gray-500 rounded-full">&nbsp;</div>
                                    <span class="text-lg uppercase">maintenance (<span class="font-bold">{{ maintenanceCount }}</span>)</span>
                                </div>
                            </div>
                        </div>
                        <div class="w-full grid grid-cols-6 gap-4 bg-gray-200 text-xl text-center font-bold">
                            <div>Name</div>
                            <div>Station ID</div>
                            <div>Mode</div>
                            <div>Location</div>
                            <div>State</div>
                            <div>Action</div>
                        </div>
                        
                        <div v-for="station in props.stations" :key="station.id" class="w-full grid grid-cols-6 gap-4 border-b border-gray-200 dark:border-gray-700 py-3 text-lg text-center items-center odd:bg-gray-100/[0.6] hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ station.name }}</div>
                            <div class="text-gray-600 dark:text-gray-400 font-mono">{{ station.station_id }}</div>
                            <div class="text-gray-600 dark:text-gray-400">{{ station.mode }}</div>
                            <div class="text-gray-600 dark:text-gray-400">
                                {{ station.location?.location_type?.description || 'N/A' }} 
                                <span v-if="station.location" class="text-xs text-gray-500 block">({{ station.location.latitude }}, {{ station.location.longitude }})</span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                <span :class="{
                                    'px-2 py-1 text-xs font-semibold rounded-full': true,
                                    'bg-green-100 text-green-800': station.state === 1,
                                    'bg-red-100 text-red-800': station.state === 0,
                                    'bg-gray-100 text-gray-800': station.state !== 1 && station.state !== 0
                                }">
                                    {{ station.state === 1 ? 'Active' : (station.state === 0 ? 'Inactive' : station.state) }}
                                </span>
                            </div>
                            <div class="flex justify-center space-x-2">
                                <Link 
                                    :href="route('weather-stations.edit', station.id)"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="confirmStationDeletion(station)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div v-if="props.stations.length === 0" class="w-full py-10 text-center text-gray-500 dark:text-gray-400 text-xl font-medium">
                            No stations found. <Link :href="route('weather-stations.create')" class="text-indigo-600 hover:text-indigo-500 underline">Add one now</Link>.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <WeatherStationModal
            :show="showingModal"
            :station="activeStation"
            :locations="props.locations || []" 
            @close="closeModal"
        />

        <ConfirmationModal :show="confirmingStationDeletion" @close="closeDeleteModal">
            <template #title>
                Delete Weather Station
            </template>

            <template #content>
                Are you sure you want to delete this weather station? This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="closeDeleteModal">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="deleteStation"
                >
                    Delete Station
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
