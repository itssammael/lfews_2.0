<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';

const props = defineProps<{
    show: boolean;
    river?: any;
}>();

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    properties: '{}',
    geometry: '{}',
});

const isEditing = computed(() => !!props.river);

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.river) {
            form.name = props.river.name;
            form.properties = JSON.stringify(props.river.properties, null, 2);
            form.geometry = JSON.stringify(props.river.geometry, null, 2);
        } else {
            form.reset();
            form.properties = '{}';
            form.geometry = '{}';
        }
        form.clearErrors();
    }
});

const submit = () => {
    try {
        JSON.parse(form.properties);
        JSON.parse(form.geometry);

        const transformData = (data: any) => ({
            ...data,
            properties: JSON.parse(data.properties),
            geometry: JSON.parse(data.geometry),
        });

        if (isEditing.value) {
            form.transform(transformData).put(route('rivers.update', props.river.id), {
                onSuccess: () => emit('close'),
            });
        } else {
            form.transform(transformData).post(route('rivers.store'), {
                onSuccess: () => emit('close'),
            });
        }
    } catch (e) {
        alert('Invalid JSON format in Properties or Geometry field.');
    }
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ isEditing ? 'Edit River' : 'Add New River' }}
            </h2>

            <div class="mt-6">
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
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="close">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ isEditing ? 'Update River' : 'Create River' }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
