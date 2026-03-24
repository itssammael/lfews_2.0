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

const heatIndexForm = useForm({
    name: 'heat_index_advisory_gauge',
    value: props.settings?.heat_index_advisory_gauge || [
        { color: '#33cc33', advice: "Heat Index within bearable parameters.", label: "Normal", temprange: '< 27°C' },
        { color: '#ffcc00', advice: "HEAT ALERT. Fatigue is possible with prolonged exposure and activity. Continuing activity could result in heat cramps.", label: "Caution", temprange: '28°C - 32°C' },
        { color: '#ff9900', advice: "HEAT ALERT. Heat cramps and heat exhaustion are possible. Continuing activity could result in heatstroke.", label: "Ext. Caution", temprange: '33°C - 41°C' },
        { color: '#cc0000', advice: "EXTREME HEAT ALERT. Heat cramps and heat exhaustion are likely. Heatstroke is probable with continued exposure.", label: "Danger", temprange: '42°C - 51°C' },
        { color: '#990000', advice: "EXTREME HEAT ALERT. Heatstroke is highly likely with continued exposure.", label: "Extreme Danger", temprange: '>= 52°C' }
    ],
});

const submitHeatIndex = () => {
    heatIndexForm.post(route('system-settings.update'), {
        preserveScroll: true,
    });
};

const apiKeyForm = useForm({
    name: 'api_key',
    value: props.settings?.api_key || {
        name: 'API/wunderground',
        key: 'cb0c2dc0f7e84bdd8c2dc0f7e8ebdd4d'
    },
});

const submitApiKey = () => {
    apiKeyForm.post(route('system-settings.update'), {
        preserveScroll: true,
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

                <FormSection @submitted="submitHeatIndex">
                    <template #title>
                        Heat Index Advisory Gauge
                    </template>

                    <template #description>
                        Configuration for Heat Index Advisory Gauge (labels, colors, advice, and brackets).
                    </template>

                    <template #form>
                        <div class="col-span-12 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Range</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Advice</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-transparent divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(item, index) in heatIndexForm.value" :key="index">
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-2">
                                                <input
                                                    type="color"
                                                    v-model="item.color"
                                                    class="h-8 w-8 rounded border border-gray-300 dark:border-gray-600 cursor-pointer p-0"
                                                />
                                                <span class="text-xs font-mono text-gray-500">{{ item.color }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <TextInput
                                                v-model="item.label"
                                                type="text"
                                                class="mt-1 block w-24 sm:w-32"
                                                autocomplete="off"
                                            />
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <TextInput
                                                v-model="item.temprange"
                                                type="text"
                                                class="mt-1 block w-24 sm:w-32"
                                                autocomplete="off"
                                            />
                                        </td>
                                        <td class="px-3 py-4">
                                            <textarea
                                                v-model="item.advice"
                                                rows="2"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                            ></textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <InputError :message="heatIndexForm.errors.value" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <ActionMessage :on="heatIndexForm.recentlySuccessful" class="me-3">
                            Saved.
                        </ActionMessage>

                        <PrimaryButton :class="{ 'opacity-25': heatIndexForm.processing }" :disabled="heatIndexForm.processing">
                            Save Configuration
                        </PrimaryButton>
                    </template>
                </FormSection>

                <SectionBorder />

                <FormSection @submitted="submitApiKey">
                    <template #title>
                        Weather Station Setup
                    </template>

                    <template #description>
                        Configuration for API connection and integration keys.
                    </template>

                    <template #form>
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="api_name" value="API Name" />
                            <TextInput
                                id="api_name"
                                v-model="apiKeyForm.value.name"
                                type="text"
                                class="mt-1 block w-full"
                                autocomplete="off"
                            />
                            <InputError :message="apiKeyForm.errors.value" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="api_key_val" value="API Key" />
                            <TextInput
                                id="api_key_val"
                                v-model="apiKeyForm.value.key"
                                type="text"
                                class="mt-1 block w-full"
                                autocomplete="off"
                            />
                            <InputError :message="apiKeyForm.errors.value" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <ActionMessage :on="apiKeyForm.recentlySuccessful" class="me-3">
                            Saved.
                        </ActionMessage>

                        <PrimaryButton :class="{ 'opacity-25': apiKeyForm.processing }" :disabled="apiKeyForm.processing">
                            Save API Key
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
