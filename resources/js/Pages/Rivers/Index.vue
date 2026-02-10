<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import RiverModal from '@/Pages/Rivers/RiverModal.vue';

defineProps({
    rivers: {
        type: Array as () => Array<any>,
        default: () => [],
    },
});

const showingRiverModal = ref(false);
const riverToEdit = ref<any>(null);

const openCreateModal = () => {
    riverToEdit.value = null;
    showingRiverModal.value = true;
};

const openEditModal = (river: any) => {
    riverToEdit.value = river;
    showingRiverModal.value = true;
};

const closeRiverModal = () => {
    showingRiverModal.value = false;
    riverToEdit.value = null;
};

const deleteRiver = (river: any) => {
    if (confirm(`Are you sure you want to delete ${river.name}?`)) {
        router.delete(route('rivers.destroy', river.id));
    }
};
</script>

<template>
    <AppLayout title="Rivers Management">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Rivers Management
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Rivers List
                        </h3>
                        <PrimaryButton @click="openCreateModal">
                            Add River
                        </PrimaryButton>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-widest">
                                        Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-widest">
                                        Properties
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-widest">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="river in rivers" :key="river.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ river.name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        <pre class="text-xs overflow-auto max-w-xs max-h-20">{{ JSON.stringify(river.properties, null, 2) }}</pre>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <SecondaryButton @click="openEditModal(river)" class="mr-2">
                                            Edit
                                        </SecondaryButton>
                                        <DangerButton @click="deleteRiver(river)">
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                                <tr v-if="rivers.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No rivers found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <RiverModal
            :show="showingRiverModal"
            :river="riverToEdit"
            @close="closeRiverModal"
        />
    </AppLayout>
</template>
