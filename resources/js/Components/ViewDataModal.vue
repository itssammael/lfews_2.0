<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps<{
    show: boolean;
    data: any;
    title: string;
}>();

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                {{ title }}
            </h2>

            <div class="mt-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-md overflow-auto max-h-96">
                <pre class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ JSON.stringify(data, null, 2) }}</pre>
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="close">
                    Close
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
