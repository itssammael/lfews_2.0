<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Data Pull Configuration</h3>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="water_level_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Water Level Sensor Timeout (seconds)
                            </label>
                            <input 
                                id="water_level_timeout"
                                type="number" 
                                v-model="form.value.water_level_sensor"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-orange-500 dark:focus:border-orange-600 focus:ring-orange-500 dark:focus:ring-orange-600 rounded-md shadow-sm"
                            />
                        </div>

                        <div>
                            <label for="weather_station_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Weather Station Timeout (seconds)
                            </label>
                            <input 
                                id="weather_station_timeout"
                                type="number" 
                                v-model="form.value.weather_station"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-orange-500 dark:focus:border-orange-600 focus:ring-orange-500 dark:focus:ring-orange-600 rounded-md shadow-sm"
                            />
                        </div>

                        <div class="flex items-center justify-end">
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-500 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                :class="{ 'opacity-25': form.processing }"
                            >
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
