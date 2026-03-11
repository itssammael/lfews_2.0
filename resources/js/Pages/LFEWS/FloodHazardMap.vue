<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ViewDataModal from '@/Components/ViewDataModal.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    floodRisks: {
        type: Object as () => any,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object as () => any,
        default: () => ({ search: '' }),
    },
});

const search = ref(props.filters.search);

const showingViewModal = ref(false);
const dataToView = ref<any>(null);

watch(search, debounce((value) => {
    router.get(route('flood_risks.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

const openViewModal = (floodRisk: any) => {
    dataToView.value = floodRisk;
    showingViewModal.value = true;
};

const closeViewModal = () => {
    showingViewModal.value = false;
    dataToView.value = null;
};
</script>

<template>
    <AppLayout title="Flood Hazard Map Management">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Flood Hazard Map Management
            </h2>
        </template>

        <div class="pt-6 mb-16 px-4 sm:px-0">
            <div class="w-full mx-auto px-0 sm:px-8 h-full">
                <div class="bg-white border-2 border-orange-500 rounded-2xl shadow-md overflow-hidden sm:rounded-lg h-full p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-8 items-start sm:items-center mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 uppercase">
                            Flood Hazard List
                        </h3>

                        <div class="hidden sm:block flex-grow"></div>
                        <div class="w-full sm:w-64">
                            <TextInput
                                v-model="search"
                                type="text"
                                placeholder="Search flood risks..."
                                class="w-full"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-200 ">
                                <tr>
                                    <th scope="col" class="px-6 py-1.5 text-left text-md font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">
                                        Name
                                    </th>
                                    <th scope="col" class="px-6 py-1.5 text-left text-md font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">
                                        Properties
                                    </th>
                                    <th scope="col" class="px-6 py-1.5 text-right text-md font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="floodRisk in floodRisks.data" :key="floodRisk.id" class="odd:bg-gray-100/[0.6]">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ floodRisk.name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        <pre class="text-xs overflow-auto max-w-xs max-h-20">{{ JSON.stringify(floodRisk.properties, null, 2) }}</pre>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <SecondaryButton 
                                            @click="openViewModal(floodRisk)"
                                        >
                                            View
                                        </SecondaryButton>
                                    </td>
                                </tr>
                                <tr v-if="floodRisks.data.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No flood hazards found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination :links="floodRisks.links" />
                </div>
            </div>
        </div>

        <ViewDataModal
            :show="showingViewModal"
            :data="dataToView"
            title="Flood Risk Details"
            @close="closeViewModal"
        />
    </AppLayout>
</template>
