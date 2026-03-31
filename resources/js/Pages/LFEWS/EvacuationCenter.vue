<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    evacuationCenters: {
        type: Array,
        default: () => [],
    },
});

const loading = ref(false);
const error = ref(null);
const evacuationCenters = ref(props.evacuationCenters);

const fetchEvacuationCenters = async () => {
    loading.value = true;
    try {
        const response = await fetch('https://swera.bayawancity.gov.ph/fetch/evacs', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        evacuationCenters.value = data.data; // Accessing the 'data' key from the response
        error.value = null;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (!props.evacuationCenters || props.evacuationCenters.length === 0) {
        fetchEvacuationCenters();
    }
});
</script>

<template>
    <AppLayout title="Evacuation Center">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Evacuation Center
            </h2>
        </template>

        <div class="pt-6 mb-16">
            <div class="w-full mx-auto px-8 h-full">
                <div class="bg-white border-2 border-orange-500 rounded-2xl shadow-md overflow-hidden sm:rounded-lg h-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 uppercase">
                            Evacuation Centers
                        </h3>
                        <PrimaryButton @click="fetchEvacuationCenters" :disabled="loading">
                            {{ loading ? 'Fetching...' : 'Fetch Data' }}
                        </PrimaryButton>
                    </div>

                    <div v-if="loading" class="text-center py-4">
                        Loading data...
                    </div>

                    <div v-else-if="error" class="text-red-500 text-center py-4">
                        Error: {{ error }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        ID
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Address
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Coordinates
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Capacity
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Current Residents
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="center in evacuationCenters" :key="center.id" class="odd:bg-gray-100/[0.6]">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ center.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ center.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ center.address }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ center.latitude }}, {{ center.longitude }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ center.max_capacity }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ center.curren_resident }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
