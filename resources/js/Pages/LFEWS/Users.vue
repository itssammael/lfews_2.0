<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import UserModal from '@/Components/UserModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    users: {
        type: Array as () => Array<any>,
        default: () => [],
    },
    roles: {
        type: Array as () => Array<any>,
        default: () => [],
    },
    showCreateModal: {
        type: Boolean,
        default: false,
    },
    showEditModal: {
        type: Boolean,
        default: false,
    },
    editingUser: {
        type: Object,
        default: null,
    },
});

const showingUserModal = ref(props.showCreateModal || props.showEditModal);
const userToEdit = ref(props.editingUser);

const openCreateModal = () => {
    userToEdit.value = null;
    showingUserModal.value = true;
};

const openEditModal = (user: any) => {
    userToEdit.value = user;
    showingUserModal.value = true;
};

const closeUserModal = () => {
    showingUserModal.value = false;
    userToEdit.value = null;
};

const deleteUser = (user: any) => {
    if (confirm(`Are you sure you want to delete ${user.name}?`)) {
        router.delete(route('users.destroy', user.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout title="User Management">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                User Management
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            System Users
                        </h3>
                        <PrimaryButton @click="openCreateModal">
                            Add User
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
                                        Email
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-widest">
                                        Roles
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-widest">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in users" :key="user.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ user.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ user.email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mr-1">
                                            {{ role.name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <SecondaryButton @click="openEditModal(user)" class="mr-2">
                                            Edit
                                        </SecondaryButton>
                                        <DangerButton @click="deleteUser(user)" v-if="user.id !== $page.props.auth.user.id">
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <UserModal
            :show="showingUserModal"
            :user="userToEdit"
            :roles="roles"
            @close="closeUserModal"
        />
    </AppLayout>
</template>
