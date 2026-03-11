<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    barangays: {
        type: Object as () => any,
        default: () => ({ data: [], links: [] }),
    },
    sitios: {
        type: Object as () => any,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object as () => any,
        default: () => ({ search: '' }),
    },
});

const search = ref(props.filters.search);
const activeTab = ref('barangays');

watch(search, debounce((value) => {
    router.get(route('barangays-sitios.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

const deleteBarangay = (barangay: any) => {
    if (confirm(`Are you sure you want to delete Barangay ${barangay.name}? This will NOT delete associated geometry from GeoJSON file but will remove it from the database.`)) {
        // router.delete(route('barangays.destroy', barangay.id));
        alert('Delete functionality not implemented yet for this new module.');
    }
};

const deleteSitio = (sitio: any) => {
    if (confirm(`Are you sure you want to delete Sitio ${sitio.name}?`)) {
        // router.delete(route('sitios.destroy', sitio.id));
        alert('Delete functionality not implemented yet for this new module.');
    }
};
</script>

<template>
    <AppLayout title="Barangays & Sitios Management">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Barangays & Sitios Management
            </h2>
        </template>

        <div class="pt-6 mb-16 px-4 sm:px-0">
            <div class="w-full mx-auto px-0 sm:px-8 h-full">
                <div class="bg-white border-2 border-orange-500 rounded-2xl shadow-md overflow-hidden sm:rounded-lg h-full p-4 sm:p-6">
                    
                    <!-- Tabs -->
                    <div class="flex border-b border-gray-200 mb-6">
                        <button 
                            @click="activeTab = 'barangays'"
                            :class="activeTab === 'barangays' ? 'border-orange-500 text-orange-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-2 px-4 border-b-2 font-medium text-sm transition-all duration-200"
                        >
                            Barangays
                        </button>
                        <button 
                            @click="activeTab = 'sitios'"
                            :class="activeTab === 'sitios' ? 'border-orange-500 text-orange-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-2 px-4 border-b-2 font-medium text-sm transition-all duration-200"
                        >
                            Sitios
                        </button>
                    </div>

                    <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-8 items-start sm:items-center mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 uppercase">
                            {{ activeTab === 'barangays' ? 'Barangay List' : 'Sitio List' }}
                        </h3>
                        <div class="hidden sm:block flex-grow"></div>
                        <div class="w-full sm:w-64">
                            <TextInput
                                v-model="search"
                                type="text"
                                placeholder="Search..."
                                class="w-full"
                            />
                        </div>
                    </div>

                    <!-- Barangays Table -->
                    <div v-if="activeTab === 'barangays'" class="overflow-x-auto">
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
                                <tr v-for="barangay in barangays.data" :key="barangay.id" class="odd:bg-gray-100/[0.6]">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ barangay.name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        <pre class="text-xs overflow-auto max-w-xs max-h-20">{{ JSON.stringify(barangay.properties, null, 2) }}</pre>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <SecondaryButton disabled class="opacity-50 cursor-not-allowed mr-2">
                                            Edit
                                        </SecondaryButton>
                                        <DangerButton @click="deleteBarangay(barangay)">
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                                <tr v-if="barangays.data.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No barangays found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination class="mt-4" :links="barangays.links" />
                    </div>

                    <!-- Sitios Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-200 ">
                                <tr>
                                    <th scope="col" class="px-6 py-1.5 text-left text-md font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">
                                        Name
                                    </th>
                                    <th scope="col" class="px-6 py-1.5 text-left text-md font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">
                                        Barangay
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
                                <tr v-for="sitio in sitios.data" :key="sitio.id" class="odd:bg-gray-100/[0.6]">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ sitio.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ sitio.barangay_name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        <pre class="text-xs overflow-auto max-w-xs max-h-20">{{ JSON.stringify(sitio.properties, null, 2) }}</pre>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <SecondaryButton disabled class="opacity-50 cursor-not-allowed mr-2">
                                            Edit
                                        </SecondaryButton>
                                        <DangerButton @click="deleteSitio(sitio)">
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                                <tr v-if="sitios.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No sitios found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination class="mt-4" :links="sitios.links" />
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
