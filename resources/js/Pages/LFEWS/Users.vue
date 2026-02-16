<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import UserModal from '@/Components/UserModal.vue';
import RoleModal from '@/Components/RoleModal.vue';
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
        type: Object as () => any | null,
        default: null,
    },
});

// Tabs
const activeTab = ref('System Users');
const tabs = ['System Users', 'System Roles'];

// User Modal
const showingUserModal = ref(props.showCreateModal || props.showEditModal);
const userToEdit = ref<any>(props.editingUser);

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

// Role Modal
const showingRoleModal = ref(false);
const roleToEdit = ref<any>(null);

const openCreateRoleModal = () => {
    roleToEdit.value = null;
    showingRoleModal.value = true;
};

const openEditRoleModal = (role: any) => {
    roleToEdit.value = role;
    showingRoleModal.value = true;
};

const closeRoleModal = () => {
    showingRoleModal.value = false;
    roleToEdit.value = null;
};

const deleteRole = (role: any) => {
    if (confirm(`Are you sure you want to delete the role ${role.name}?`)) {
        router.delete(route('roles.destroy', role.id), {
            preserveScroll: true,
            onError: (errors: any) => {
               // Only show valid error messages
               if (errors && errors.error) {
                   alert(errors.error);
               } else if (errors && typeof errors === 'object') {
                    // Sometimes Inertia returns errors in a bag
                    const keys = Object.keys(errors);
                    if (keys.length > 0) alert(errors[keys[0]]);
               }
            }
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
                    
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab"
                                @click="activeTab = tab"
                                :class="[
                                    activeTab === tab
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                {{ tab }}
                            </button>
                        </nav>
                    </div>

                    <!-- System Users Tab -->
                    <div v-show="activeTab === 'System Users'">
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

                    <!-- System Roles Tab -->
                    <div v-show="activeTab === 'System Roles'">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                System Roles
                            </h3>
                            <PrimaryButton @click="openCreateRoleModal">
                                Add Role
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
                                            Permissions
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-widest">
                                            Description
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-widest">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="role in roles" :key="role.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ role.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <div class="flex gap-1">
                                                <span v-if="role.can_create" class="px-1 text-xs bg-green-100 text-green-800 rounded">C</span>
                                                <span v-if="role.can_read" class="px-1 text-xs bg-blue-100 text-blue-800 rounded">R</span>
                                                <span v-if="role.can_update" class="px-1 text-xs bg-yellow-100 text-yellow-800 rounded">U</span>
                                                <span v-if="role.can_delete" class="px-1 text-xs bg-red-100 text-red-800 rounded">D</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ role.description }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <SecondaryButton @click="openEditRoleModal(role)" class="mr-2">
                                                {{ role.name === 'admin' ? 'View' : 'Edit' }}
                                            </SecondaryButton>
                                            <DangerButton 
                                                v-if="role.name !== 'admin'"
                                                @click="deleteRole(role)"
                                            >
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
        </div>

        <UserModal
            :show="showingUserModal"
            :user="userToEdit"
            :roles="roles"
            @close="closeUserModal"
        />

        <RoleModal
            :show="showingRoleModal"
            :role="roleToEdit"
            @close="closeRoleModal"
        />
    </AppLayout>
</template>
