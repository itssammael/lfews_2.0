<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    sensor: {
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
    port: '',
    slave_id: '',
});

watch(() => props.sensor, (newSensor) => {
    console.log(newSensor)
    if (newSensor) {
        form.name = newSensor.name || '';
        form.brand = newSensor.brand || '';
        form.model = newSensor.model || '';
        form.lat = newSensor.lat !== null && newSensor.lat !== undefined ? String(newSensor.lat) : '';
        form.long = newSensor.long !== null && newSensor.long !== undefined ? String(newSensor.long) : '';
        form.location = newSensor.location || '';
        form.level_2 = newSensor.level_2 !== null && newSensor.level_2 !== undefined ? String(newSensor.level_2) : '';
        form.level_3 = newSensor.level_3 !== null && newSensor.level_3 !== undefined ? String(newSensor.level_3) : '';
        form.level_4 = newSensor.level_4 !== null && newSensor.level_4 !== undefined ? String(newSensor.level_4) : '';
        form.state = newSensor.state || '';
        form.ip = newSensor.ip || '';
        form.port = newSensor.port !== null && newSensor.port !== undefined ? String(newSensor.port) : '';
        form.slave_id = newSensor.slave_id !== null && newSensor.slave_id !== undefined ? String(newSensor.slave_id) : '';
    } else {
        form.reset();
    }
}, { immediate: true });

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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            <SecondaryButton @click="close">
                Cancel
            </SecondaryButton>

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
