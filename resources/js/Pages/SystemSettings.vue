<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
    });
};

const logoForm = useForm({
    name: 'system_logo',
    value: null as File | null,
});

const iconForm = useForm({
    name: 'system_icon',
    value: null as File | null,
});

const logoPreview = ref(props.settings?.system_logo || null);
const iconPreview = ref(props.settings?.system_icon || null);

const updateLogo = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        logoForm.value = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const updateIcon = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        iconForm.value = file;
        iconPreview.value = URL.createObjectURL(file);
    }
};

const submitLogo = () => {
    logoForm.post(route('system-settings.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const submitIcon = () => {
    iconForm.post(route('system-settings.update'), {
        preserveScroll: true,
        forceFormData: true,
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

                <FormSection @submitted="submitLogo">
                    <template #title>
                        System Logo
                    </template>

                    <template #description>
                        Upload a logo to be displayed in the sidebar.
                    </template>

                    <template #form>
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="logo" value="Logo" />
                            <div v-if="logoPreview" class="mt-2">
                                <img :src="logoPreview" class="h-20 w-auto object-contain mb-4 border rounded p-1" />
                            </div>
                            <input
                                id="logo"
                                type="file"
                                class="hidden"
                                ref="logoInput"
                                @change="updateLogo"
                            />
                            <PrimaryButton type="button" @click="$refs.logoInput.click()">
                                Select New Logo
                            </PrimaryButton>
                            <InputError :message="logoForm.errors.value" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <ActionMessage :on="logoForm.recentlySuccessful" class="me-3">
                            Saved.
                        </ActionMessage>

                        <PrimaryButton :class="{ 'opacity-25': logoForm.processing }" :disabled="logoForm.processing || !logoForm.value">
                            Save Logo
                        </PrimaryButton>
                    </template>
                </FormSection>

                <SectionBorder />

                <FormSection @submitted="submitIcon">
                    <template #title>
                        System Icon (Favicon)
                    </template>

                    <template #description>
                        Upload an icon to be used as the browser favicon.
                    </template>

                    <template #form>
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="icon" value="Icon" />
                            <div v-if="iconPreview" class="mt-2">
                                <img :src="iconPreview" class="h-10 w-10 object-contain mb-4 border rounded p-1" />
                            </div>
                            <input
                                id="icon"
                                type="file"
                                class="hidden"
                                ref="iconInput"
                                @change="updateIcon"
                            />
                            <PrimaryButton type="button" @click="$refs.iconInput.click()">
                                Select New Icon
                            </PrimaryButton>
                            <InputError :message="iconForm.errors.value" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <ActionMessage :on="iconForm.recentlySuccessful" class="me-3">
                            Saved.
                        </ActionMessage>

                        <PrimaryButton :class="{ 'opacity-25': iconForm.processing }" :disabled="iconForm.processing || !iconForm.value">
                            Save Icon
                        </PrimaryButton>
                    </template>
                </FormSection>

                <SectionBorder />
            </div>
        </div>
    </AppLayout>
</template>
