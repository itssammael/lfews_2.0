<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    station: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    brand: '',
    model: '',
    lat: '',
    long: '',
    location: '',
    level_2: '',
    level_3: '',
    level_4: '',
    state: '',
    ip: '',
    port: '100',
    slave_id: '',
});

watch(() => props.station, (newStation) => {
    console.log(newStation)
    if (newStation) {
        form.name = newStation.name || '';
        form.brand = newStation.brand || '';
        form.model = newStation.model || '';
        form.lat = newStation.lat !== null && newStation.lat !== undefined ? String(newStation.lat) : '';
        form.long = newStation.long !== null && newStation.long !== undefined ? String(newStation.long) : '';
        form.location = newStation.location || '';
        form.level_2 = newStation.level_2 !== null && newStation.level_2 !== undefined ? String(newStation.level_2) : '';
        form.level_3 = newStation.level_3 !== null && newStation.level_3 !== undefined ? String(newStation.level_3) : '';
        form.level_4 = newStation.level_4 !== null && newStation.level_4 !== undefined ? String(newStation.level_4) : '';
        form.state = newStation.state || '';
        form.ip = newStation.ip || '';
        form.port = newStation.port !== null && newStation.port !== undefined ? String(newStation.port) : '';
        form.slave_id = newStation.slave_id !== null && newStation.slave_id !== undefined ? String(newStation.slave_id) : '';
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (props.station?.id) {
        form.put(route('weather-stations.update', props.station.id), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                form.reset();
            },
        });
    } else {
        form.post(route('weather-stations.store'), {
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
            {{ station?.id ? 'Edit Weather Station' : 'Add Weather Station' }}
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

                <!-- Model -->
                <div>
                    <InputLabel for="model" value="Model" />
                    <TextInput
                        id="model"
                        v-model="form.model"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.model" class="mt-2" />
                </div>

                <!-- Location -->
                <div>
                    <InputLabel for="location" value="Location" />
                    <TextInput
                        id="location"
                        v-model="form.location"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.location" class="mt-2" />
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
                    <TextInput
                        id="state"
                        v-model="form.state"
                        type="text"
                        class="mt-1 block w-full"
                    />
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
                        disabled="true"
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
                        v-model="form.slave_id"
                        type="number"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.slave_id" class="mt-2" />
                </div>
            </div>
        </template>

        <template #footer>
        
             <Link @click="close" :href="route('weather-stations')" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 cursor-pointer">
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
