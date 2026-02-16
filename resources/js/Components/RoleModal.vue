<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    role: {
        type: Object as () => any | null,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    description: '',
    can_create: true,
    can_read: true,
    can_update: true,
    can_delete: true,
});

watch(() => props.role, (newRole) => {
    if (newRole) {
        form.name = newRole.name;
        form.description = newRole.description || '';
        form.can_create = Boolean(newRole.can_create);
        form.can_read = Boolean(newRole.can_read);
        form.can_update = Boolean(newRole.can_update);
        form.can_delete = Boolean(newRole.can_delete);
    } else {
        form.reset();
        // Defaults for new role
        form.can_create = true;
        form.can_read = true;
        form.can_update = true;
        form.can_delete = true;
    }
}, { immediate: true });

const submit = () => {
    if (props.role?.id) {
        form.put(route('roles.update', props.role.id), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                form.reset();
            },
        });
    } else {
        form.post(route('roles.store'), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                form.reset();
            },
        });
    }
};

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <DialogModal :show="show" @close="close">
        <template #title>
            {{ role?.id ? 'Edit Role' : 'Add Role' }}
        </template>

        <template #content>
            <div v-if="role?.name === 'admin'" class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <svg class="inline-block w-4 h-4 me-2 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    The <strong>admin</strong> role is a system requirement. Its name and permissions are protected and cannot be modified.
                </p>
            </div>

            <div class="space-y-4">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        :disabled="role?.name === 'admin'"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <!-- Description -->
                <div>
                    <InputLabel for="description" value="Description" />
                    <TextInput
                        id="description"
                        v-model="form.description"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <!-- Permissions -->
                <div class="mt-4">
                    <InputLabel value="Permissions" />
                    <div class="grid grid-cols-2 gap-4 mt-2">
                        <label class="flex items-center">
                            <Checkbox 
                                v-model:checked="form.can_create" 
                                name="can_create" 
                                :disabled="role?.name === 'admin'"
                            />
                            <span class="ms-2 text-sm" :class="role?.name === 'admin' ? 'text-gray-400' : 'text-gray-600 dark:text-gray-400'">Can Create</span>
                        </label>
                        <label class="flex items-center">
                            <Checkbox 
                                v-model:checked="form.can_read" 
                                name="can_read" 
                                :disabled="role?.name === 'admin'"
                            />
                            <span class="ms-2 text-sm" :class="role?.name === 'admin' ? 'text-gray-400' : 'text-gray-600 dark:text-gray-400'">Can Read</span>
                        </label>
                        <label class="flex items-center">
                            <Checkbox 
                                v-model:checked="form.can_update" 
                                name="can_update" 
                                :disabled="role?.name === 'admin'"
                            />
                            <span class="ms-2 text-sm" :class="role?.name === 'admin' ? 'text-gray-400' : 'text-gray-600 dark:text-gray-400'">Can Update</span>
                        </label>
                        <label class="flex items-center">
                            <Checkbox 
                                v-model:checked="form.can_delete" 
                                name="can_delete" 
                                :disabled="role?.name === 'admin'"
                            />
                            <span class="ms-2 text-sm" :class="role?.name === 'admin' ? 'text-gray-400' : 'text-gray-600 dark:text-gray-400'">Can Delete</span>
                        </label>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="close">
                {{ role?.name === 'admin' ? 'Close' : 'Cancel' }}
            </SecondaryButton>

            <PrimaryButton
                v-if="role?.name !== 'admin'"
                class="ms-3"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
                @click="submit"
            >
                Save
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
