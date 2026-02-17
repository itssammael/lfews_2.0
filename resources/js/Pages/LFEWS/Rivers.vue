<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import RiverModal from '@/Components/RiverModal.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    rivers: {
        type: Object as () => any,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object as () => any,
        default: () => ({ search: '' }),
    },
});

const search = ref(props.filters.search);

const showingRiverModal = ref(false);
const riverToEdit = ref<any>(null);

watch(search, debounce((value) => {
    router.get(route('rivers.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

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

        <div class="pt-6 mb-16">
            <div class="w-full mx-auto px-8 h-full">
                <div class="bg-white border-2 border-orange-500 rounded-2xl shadow-md overflow-hidden sm:rounded-lg h-full p-6">
                    <div class="flex space-x-8 items-center mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 uppercase">
                            Rivers List
                        </h3>
                        <PrimaryButton 
                            v-if="$page.props.auth.can.create"
                            @click="openCreateModal"
                        >
                            Add River
                        </PrimaryButton>
                        <div class="flex-grow"></div>
                        <div class="w-64">
                            <TextInput
                                v-model="search"
                                type="text"
                                placeholder="Search rivers..."
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
                                <tr v-for="river in rivers.data" :key="river.id" class="odd:bg-gray-100/[0.6]">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ river.name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        <pre class="text-xs overflow-auto max-w-xs max-h-20">{{ JSON.stringify(river.properties, null, 2) }}</pre>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <SecondaryButton 
                                            v-if="$page.props.auth.can.update"
                                            @click="openEditModal(river)" 
                                            class="mr-2"
                                        >
                                            Edit
                                        </SecondaryButton>
                                        <DangerButton 
                                            v-if="$page.props.auth.can.delete"
                                            @click="deleteRiver(river)"
                                        >
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                                <tr v-if="rivers.data.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No rivers found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination :links="rivers.links" />
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
