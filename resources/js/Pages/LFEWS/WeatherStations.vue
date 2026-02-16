<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WeatherStationModal from '@/Components/WeatherStationModal.vue';
import PingModal from '@/Components/PingModal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

interface Station {
    id: number;
    name: string;
    station_id: string;
    mode: string;
    ip?: string;
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
    stations: {
        data: Station[];
        links: any[];
    };
    filters?: {
        search: string;
    };
    locations?: any[]; // Allow locations prop
    showCreateModal?: boolean;
    editingStation?: Station;
    activeCount?: number;
    inactiveCount?: number;
    maintenanceCount?: number;
}>();

const search = ref(props.filters?.search || '');

watch(search, debounce((value) => {
    router.get(route('weather-stations'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

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

const showingPingModal = ref(false);
const stationToPing = ref<Station | null>(null);

const pingStation = (station: Station) => {
    stationToPing.value = station;
    showingPingModal.value = true;
};

const closePingModal = () => {
    showingPingModal.value = false;
    stationToPing.value = null;
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

        <div class="pt-6 mb-16 min-h-[calc(100vh-220px)]">
            <div class="w-full mx-auto px-8 h-full">
                <div class="bg-white border-2 border-blue-600 rounded-2xl shadow-md overflow-hidden sm:rounded-lg h-full min-h-[calc(100vh-280px)]">
                    <div class="p-6 h-full">
                        <div class="flex">
                            <div class="w-1/3 flex space-x-8 items-center py-2">
                                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight uppercase">
                                    Weather Station List
                                </h2>
                                <Link
                                    v-if="$page.props.auth.can.create"
                                    :href="route('weather-stations.create')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    Add Station
                                </Link>
                                
                            </div>
                            <div class="w-1/3 py-2 flex items-center justify-center">
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    placeholder="Search stations..."
                                    class="w-full"
                                />
                            </div>
                            <div class="w-1/3 py-2 flex space-x-6 items-center justify-center">
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
                        <div class="w-full grid grid-cols-7 gap-4 bg-gray-200 text-xl text-center font-bold">
                            <div>Name</div>
                            <div>Station ID</div>
                            <div>Mode</div>
                            <div>IP</div>
                            <div>Location</div>
                            <div>State</div>
                            <div>Action</div>
                        </div>
                        
                        <div v-for="station in props.stations.data" :key="station.id" class="w-full grid grid-cols-7 gap-4 border-b border-gray-200 dark:border-gray-700 py-3 text-lg text-center items-center odd:bg-gray-100/[0.6] hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ station.name }}</div>
                            <div class="text-gray-600 dark:text-gray-400 font-mono">{{ station.station_id }}</div>
                            <div class="text-gray-600 dark:text-gray-400">{{ station.mode }}</div>
                            <div class="text-gray-600 dark:text-gray-400 font-mono text-sm">{{ station.ip || 'N/A' }}</div>
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
                                    v-if="$page.props.auth.can.update"
                                    :href="route('weather-stations.edit', station.id)"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Edit
                                </Link>
                                <button
                                    v-if="$page.props.auth.can.delete"
                                    @click="confirmStationDeletion(station)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    Delete
                                </button>
                                <button
                                    v-if="$page.props.auth.can.read && (station.ip || station.mode === 'Davis')"
                                    @click="pingStation(station)"
                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                >
                                    Ping
                                </button>
                                <span v-if="!$page.props.auth.can.update && !$page.props.auth.can.delete" class="text-gray-400 text-sm">No Actions</span>
                            </div>
                        </div>

                        <div v-if="props.stations.data.length === 0" class="w-full py-10 text-center text-gray-500 dark:text-gray-400 text-xl font-medium">
                            No stations found. <Link v-if="$page.props.auth.can.create" :href="route('weather-stations.create')" class="text-indigo-600 hover:text-indigo-500 underline">Add one now</Link>.
                        </div>

                        <Pagination :links="props.stations.links" />
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

        <PingModal
            :show="showingPingModal"
            :ip="stationToPing?.ip"
            :name="stationToPing?.name"
            @close="closePingModal"
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
