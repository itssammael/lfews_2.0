<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet marker icon issue
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIconRetina from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

let DefaultIcon = L.icon({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIconRetina,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

L.Marker.prototype.options.icon = DefaultIcon;

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    sensor: {
        type: Object,
        default: null,
    },
    locations: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    brand: '',
    mode: '',
    lat: '',
    long: '',
    location_id: '',
    level_2: '',
    level_3: '',
    level_4: '',
    state: 1, // Default to 1 (active)
    ip: '',
    port: '100',
    slave_id: '1',
});

let map: L.Map | null = null;
let marker: L.Marker | null = null;

const initMap = async () => {
    await nextTick();
    
    const defaultLat = 9.374062;
    const defaultLong = 122.7992699;
    
    const lat = form.lat ? parseFloat(form.lat) : defaultLat;
    const lng = form.long ? parseFloat(form.long) : defaultLong;
    
    // Clean up if already exists
    if (map) {
        map.remove();
    }
    
    map = L.map('map').setView([lat, lng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    
    marker.on('dragend', () => {
        const position = marker!.getLatLng();
        form.lat = String(position.lat);
        form.long = String(position.lng);
    });
    
    map.on('click', (e) => {
        const { lat, lng } = e.latlng;
        marker!.setLatLng([lat, lng]);
        form.lat = String(lat);
        form.long = String(lng);
    });

    // Invalidate size in case of rendering issues in modal
    setTimeout(() => {
        map?.invalidateSize();
    }, 100);
};

watch(() => props.show, (showing: boolean) => {
    if (showing) {
        initMap();
    } else {
        if (map) {
            map.remove();
            map = null;
            marker = null;
        }
    }
});

watch([() => form.lat, () => form.long], ([newLat, newLong]: [string, string]) => {
    if (marker && newLat && newLong) {
        const lat = parseFloat(newLat);
        const lng = parseFloat(newLong);
        if (!isNaN(lat) && !isNaN(lng)) {
            const pos = L.latLng(lat, lng);
            if (!marker.getLatLng().equals(pos)) {
                marker.setLatLng(pos);
                map?.panTo(pos);
            }
        }
    }
});

watch(() => props.sensor, (newSensor) => {
    console.log(newSensor)
    if (newSensor) {
        form.name = newSensor.name || '';
        form.brand = newSensor.brand || '';
        form.mode = newSensor.mode || '';
        form.lat = newSensor.location ? String(newSensor.location.latitude) : '';
        form.long = newSensor.location ? String(newSensor.location.longitude) : '';
        form.location_id = newSensor.location_id || '';
        form.level_2 = newSensor.level_2 !== null && newSensor.level_2 !== undefined ? String(newSensor.level_2) : '';
        form.level_3 = newSensor.level_3 !== null && newSensor.level_3 !== undefined ? String(newSensor.level_3) : '';
        form.level_4 = newSensor.level_4 !== null && newSensor.level_4 !== undefined ? String(newSensor.level_4) : '';
        form.state = newSensor.state !== undefined ? newSensor.state : 1;
        form.ip = newSensor.ip || '';
        form.port = newSensor.port !== null && newSensor.port !== undefined ? String(newSensor.port) : '100';
        form.slave_id = newSensor.slave_id !== null && newSensor.slave_id !== undefined ? String(newSensor.slave_id) : '1';
        
        if (props.show) {
            initMap();
        }
    } else {
        form.reset();
    }
}, { immediate: true });

onUnmounted(() => {
    if (map) {
        map.remove();
    }
});

const submit = () => {
    if (props.sensor?.id) {
        form.put(route('water-level-sensors.update', props.sensor.id), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                form.reset();
            },
        });
    } else {
        form.post(route('water-level-sensors.store'), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                form.reset();
            },
        });
    }
};

const close = () => {
    emit('close');    
};
</script>

<template>
    <DialogModal :show="show" @close="close">
        <template #title>
            {{ sensor?.id ? 'Edit Water Level Sensor' : 'Add Water Level Sensor' }}
        </template>

        <template #content>
            <div class="grid grid-cols-3 gap-4">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <!-- Brand -->
                <div>
                    <InputLabel for="brand" value="Brand" />
                    <TextInput
                        id="brand"
                        v-model="form.brand"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.brand" class="mt-2" />
                </div>

                <!-- Mode -->
                <div>
                    <InputLabel for="mode" value="Mode" />
                    <TextInput
                        id="mode"
                        v-model="form.mode"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.mode" class="mt-2" />
                </div>


                <!-- Lat -->
                <div>
                    <InputLabel for="lat" value="Latitude" />
                    <TextInput
                        id="lat"
                        v-model="form.lat"
                        type="number"
                        step="any"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.lat" class="mt-2" />
                </div>

                <!-- Long -->
                <div>
                    <InputLabel for="long" value="Longitude" />
                    <TextInput
                        id="long"
                        v-model="form.long"
                        type="number"
                        step="any"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.long" class="mt-2" />
                </div>

                 <!-- Level 2 -->
                <div>
                    <InputLabel for="level_2" value="Level 2" />
                    <TextInput
                        id="level_2"
                        v-model="form.level_2"
                        type="number"
                        step="any"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.level_2" class="mt-2" />
                </div>

                 <!-- Level 3 -->
                <div>
                    <InputLabel for="level_3" value="Level 3" />
                    <TextInput
                        id="level_3"
                        v-model="form.level_3"
                        type="number"
                        step="any"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.level_3" class="mt-2" />
                </div>

                 <!-- Level 4 -->
                <div>
                    <InputLabel for="level_4" value="Level 4" />
                    <TextInput
                        id="level_4"
                        v-model="form.level_4"
                        type="number"
                        step="any"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.level_4" class="mt-2" />
                </div>

                <!-- State -->
                <div>
                    <InputLabel for="state" value="State" />
                    <select
                        id="state"
                        v-model="form.state"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        required
                    >
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                        <option :value="2">Maintenance</option>
                    </select>
                    <InputError :message="form.errors.state" class="mt-2" />
                </div>

                <!-- IP -->
                <div>
                    <InputLabel for="ip" value="IP Address" />
                    <TextInput
                        id="ip"
                        v-model="form.ip"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.ip" class="mt-2" />
                </div>

                <!-- Port -->
                <div>
                    <InputLabel for="port" value="Port" />
                    <TextInput
                        id="port"
                        v-model="form.port"
                        type="number"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.port" class="mt-2" />
                </div>

                <!-- Slave ID -->
                <div>
                    <InputLabel for="slave_id" value="Slave ID" />
                    <TextInput
                        id="slave_id"
                        disabled="true"
                        v-model="form.slave_id"
                        type="number"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.slave_id" class="mt-2" />
                </div>
            </div>
                <!-- Map -->
                <div class="col-span-full mt-4">
                    <InputLabel value="Location Map" />
                    <div id="map" class="mt-1 h-64 w-full rounded-md border border-gray-300 dark:border-gray-700 shadow-sm z-0"></div>
                </div>
        </template>

        <template #footer>
        
             <Link @click="close" :href="route('water-level-sensors')" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 cursor-pointer">
                Cancel
            </Link>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
                @click="submit"
            >
                Save
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
