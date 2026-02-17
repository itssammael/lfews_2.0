<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import WaterLevelSensorModal from '@/Components/WaterLevelSensorModal.vue';
import PingModal from '@/Components/PingModal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import { router } from '@inertiajs/vue3';
import { Location } from '@/types';
import debounce from 'lodash/debounce';

interface Sensor {
    id: number;
    name: string;
    brand: string;
    mode: string;
    ip: string;
    location_id?: number | string;
    location?: Location;
    state: number;
    level_2: number;
    level_3: number;
    level_4: number;
    port: number;
    slave_id: number;
}

const props = defineProps<{
    sensors: {
        data: Sensor[];
        links: any[];
    };
    filters?: {
        search: string;
    };
    locations?: Location[];
    showCreateModal?: boolean;
    editingSensor?: Sensor;
    activeCount?: number;
    inactiveCount?: number;
    maintenanceCount?: number;
}>();

const search = ref(props.filters?.search || '');

watch(search, debounce((value) => {
    router.get(route('water-level-sensors'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

const showingModal = ref(false);
const activeSensor = ref<Sensor | null>(null);

watch(() => props.editingSensor, (newSensor: Sensor | undefined) => {
    console.log(newSensor)
    if (newSensor) {
        activeSensor.value = newSensor;
        showingModal.value = false;
    }
}, { immediate: true });

onMounted(() => {
    if (props.showCreateModal && !props.editingSensor) {
        activeSensor.value = null;
        showingModal.value = true;
    } else if (props.editingSensor) {
        activeSensor.value = props.editingSensor;
        showingModal.value = true;
    }
});

const closeModal = () => {
    showingModal.value = false;
    activeSensor.value = null;
};

const confirmingSensorDeletion = ref(false);
const sensorToDelete = ref<Sensor | null>(null);

const confirmSensorDeletion = (sensor: Sensor) => {
    sensorToDelete.value = sensor;
    confirmingSensorDeletion.value = true;
};

const deleteSensor = () => {
    if (sensorToDelete.value) {
        router.delete(route('water-level-sensors.destroy', sensorToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                confirmingSensorDeletion.value = false;
                sensorToDelete.value = null;
            },
        });
    }
};

const closeDeleteModal = () => {
    confirmingSensorDeletion.value = false;
    sensorToDelete.value = null;
};

const showingPingModal = ref(false);
const sensorToPing = ref<Sensor | null>(null);

const pingSensor = (sensor: Sensor) => {
    sensorToPing.value = sensor;
    showingPingModal.value = true;
};

const closePingModal = () => {
    showingPingModal.value = false;
    sensorToPing.value = null;
};
</script>

<template>
    <AppLayout title="Water Level Sensors">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Manage Water Level Sensors
                </h2>
            </div>
        </template>

        <div class="pt-6 mb-16 min-h-[calc(100vh-220px)]">
            <div class="w-full mx-auto px-8 h-full">
                <div class="bg-white border-2 border-orange-500 rounded-2xl shadow-md overflow-hidden sm:rounded-lg h-full min-h-[calc(100vh-280px)]">
                    <div class="p-6 h-full">
                        <div class="flex">
                            <div class="w-1/3 flex space-x-8 items-center py-2">
                                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight uppercase">
                                    Water Level Sensor List
                                </h2>
                                <Link
                                    v-if="$page.props.auth.can.create"
                                    :href="route('water-level-sensors.create')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    Add Sensor
                                </Link>
                            </div>
                            <div class="w-1/3 py-2 flex items-center justify-center">
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    placeholder="Search sensors..."
                                    class="w-full"
                                />
                            </div>
                            <div class="w-1/3 py-2 flex space-x-6 items-center justify-center ">
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
                        <div class="w-full grid grid-cols-7 gap-4 bg-gray-200 text-xl text-center font-bold ">
                            <div>Name</div>
                            <div>Brand</div>
                            <div>Mode</div>
                            <div>IP</div>
                            <div>Location</div>
                            <div>State</div>
                            <div>Action</div>
                        </div>
                        
                        <div v-for="sensor in props.sensors.data" :key="sensor.id" class="w-full grid grid-cols-7 gap-4 border-b border-gray-200 dark:border-gray-700 py-3 text-lg text-center items-center odd:bg-gray-100/[0.6] hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ sensor.name }}</div>
                            <div class="text-gray-600 dark:text-gray-400">{{ sensor.brand }}</div>
                            <div class="text-gray-600 dark:text-gray-400">{{ sensor.mode }}</div>
                            <div class="text-gray-600 dark:text-gray-400 font-mono text-sm">{{ sensor.ip }}</div>
                            <div class="text-gray-600 dark:text-gray-400">
                                {{ sensor.location?.location_type?.description || 'N/A' }} 
                                <span v-if="sensor.location" class="text-xs text-gray-500 block">({{ sensor.location.latitude }}, {{ sensor.location.longitude }})</span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                <span :class="{
                                    'px-2 py-1 text-xs font-semibold rounded-full': true,
                                    'bg-green-100 text-green-800': sensor.state === 1,
                                    'bg-red-100 text-red-800': sensor.state === 0,
                                    'bg-gray-100 text-gray-800': sensor.state !== 1 && sensor.state !== 0
                                }">
                                    {{ sensor.state === 1 ? 'Active' : (sensor.state === 0 ? 'Inactive' : sensor.state) }}
                                </span>
                            </div>
                            <div class="flex justify-center space-x-2">
                                <Link 
                                    v-if="$page.props.auth.can.update"
                                    :href="route('water-level-sensors.edit', sensor.id)"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Edit
                                </Link>
                                <button
                                    v-if="$page.props.auth.can.delete"
                                    @click="confirmSensorDeletion(sensor)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    Delete
                                </button>
                                <button
                                    v-if="$page.props.auth.can.read && sensor.ip"
                                    @click="pingSensor(sensor)"
                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                >
                                    Ping
                                </button>
                                <span v-if="!$page.props.auth.can.update && !$page.props.auth.can.delete" class="text-gray-400 text-sm">No Actions</span>
                            </div>
                        </div>

                        <div v-if="props.sensors.data.length === 0" class="w-full py-10 text-center text-gray-500 dark:text-gray-400 text-xl font-medium">
                            No sensors found. <Link v-if="$page.props.auth.can.create" :href="route('water-level-sensors.create')" class="text-indigo-600 hover:text-indigo-500 underline">Add one now</Link>.
                        </div>

                        <Pagination :links="props.sensors.links" />
                    </div>
                </div>
            </div>
        </div>

        <WaterLevelSensorModal
            :show="showingModal"
            :sensor="activeSensor"
            :locations="props.locations || []"
            @close="closeModal"
        />

        <PingModal
            :show="showingPingModal"
            :ip="sensorToPing?.ip"
            :name="sensorToPing?.name"
            @close="closePingModal"
        />

        <ConfirmationModal :show="confirmingSensorDeletion" @close="closeDeleteModal">
            <template #title>
                Delete Water Level Sensor
            </template>

            <template #content>
                Are you sure you want to delete this water level sensor? This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="closeDeleteModal">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="deleteSensor"
                >
                    Delete Sensor
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
