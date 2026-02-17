<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import SectionBorder from '@/Components/SectionBorder.vue';

const props = defineProps({
    settings: Object,
});

const timeoutSettings = ref(props.settings?.data_pull_timeout || {
    water_level_sensor: 300,
    weather_station: 300,
});

const form = useForm({
    name: 'data_pull_timeout',
    value: timeoutSettings.value,
});

const submit = () => {
    form.post(route('system-settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: show a toast or notification
        },
    });
};
</script>

<template>
    <AppLayout title="System Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                System Settings
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <FormSection @submitted="submit">
                    <template #title>
                        Data Pull Configuration
                    </template>

                    <template #description>
                        Controls the frequency and timeout of data pulling operations from Water Level Sensor and Weather Station.
                    </template>

                    <template #form>
                        <!-- Water Level Sensor Timeout -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="water_level_timeout" value="Water Level Sensor Timeout (seconds)" />
                            <TextInput
                                id="water_level_timeout"
                                v-model="form.value.water_level_sensor"
                                type="number"
                                class="mt-1 block w-full"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.value" class="mt-2" />
                        </div>

                        <!-- Weather Station Timeout -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="weather_station_timeout" value="Weather Station Timeout (seconds)" />
                            <TextInput
                                id="weather_station_timeout"
                                v-model="form.value.weather_station"
                                type="number"
                                class="mt-1 block w-full"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.value" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <ActionMessage :on="form.recentlySuccessful" class="me-3">
                            Saved.
                        </ActionMessage>

                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Save
                        </PrimaryButton>
                    </template>
                </FormSection>

                <SectionBorder />
            </div>
        </div>
    </AppLayout>
</template>
