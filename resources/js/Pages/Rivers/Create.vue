<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';

const form = useForm({
    name: '',
    properties: '{}',
    geometry: '{}',
});

const submit = () => {
    try {
        // Validation check for JSON
        JSON.parse(form.properties);
        JSON.parse(form.geometry);
        
        form.transform((data) => ({
            ...data,
            properties: JSON.parse(data.properties),
            geometry: JSON.parse(data.geometry),
        })).post(route('rivers.store'));
    } catch (e) {
        alert('Invalid JSON format in Properties or Geometry field.');
    }
};
</script>

<template>
    <AppLayout title="Create River">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Add New River
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <InputLabel for="name" value="River Name" />
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

                        <div class="mb-4">
                            <InputLabel for="properties" value="Properties (JSON)" />
                            <TextArea
                                id="properties"
                                v-model="form.properties"
                                class="mt-1 block w-full font-mono text-sm"
                                rows="5"
                            />
                            <InputError :message="form.errors.properties" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="geometry" value="Geometry (GeoJSON Geometry Object)" />
                            <TextArea
                                id="geometry"
                                v-model="form.geometry"
                                class="mt-1 block w-full font-mono text-sm"
                                rows="10"
                                required
                            />
                            <InputError :message="form.errors.geometry" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-1">Paste the "geometry" object here (e.g., {"type": "Polygon", "coordinates": [...]})</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Create River
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
