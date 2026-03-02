<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    },
    roles: {
        type: Array as () => Array<{ id: number; name: string }>,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '',
});

watch(() => props.user, (newUser) => {
    if (newUser) {
        form.name = newUser.name;
        form.username = newUser.username || '';
        form.email = newUser.email;
        form.role_id = newUser.roles?.[0]?.id || '';
        form.password = '';
        form.password_confirmation = '';
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (props.user?.id) {
        form.put(route('users.update', props.user.id), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                form.reset();
            },
        });
    } else {
        form.post(route('users.store'), {
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
            {{ user?.id ? 'Edit User' : 'Add User' }}
        </template>

        <template #content>
            <div class="space-y-4">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <!-- Username -->
                <div>
                    <InputLabel for="username" value="Username" />
                    <TextInput
                        id="username"
                        v-model="form.username"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.username" class="mt-2" />
                </div>

                <!-- Email -->
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <!-- Role -->
                <div>
                    <InputLabel for="role" value="Role" />
                    <select
                        id="role"
                        v-model="form.role_id"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        required
                    >
                        <option value="" disabled>Select a role</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">
                            {{ role.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.role_id" class="mt-2" />
                </div>

                <!-- Password -->
                <div>
                    <InputLabel for="password" :value="user?.id ? 'Password (leave blank to keep current)' : 'Password'" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        :required="!user?.id"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        :required="!user?.id"
                    />
                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="close">
                Cancel
            </SecondaryButton>

            <PrimaryButton
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
