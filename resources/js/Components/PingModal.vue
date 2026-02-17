<script setup lang="ts">
import { ref, watch } from 'vue';
import DialogModal from './DialogModal.vue';
import SecondaryButton from './SecondaryButton.vue';
import axios from 'axios';

const props = defineProps<{
    show: boolean;
    ip: string | undefined;
    name: string | undefined;
}>();

const emit = defineEmits(['close']);

const pinging = ref(false);
const result = ref<{ success: boolean; output: string } | null>(null);
const error = ref<string | null>(null);

const performPing = async () => {
    if (!props.ip) return;
    
    pinging.value = true;
    result.value = null;
    error.value = null;

    try {
        const response = await axios.post(route('connectivity.ping'), {
            ip: props.ip
        });
        result.value = response.data;
    } catch (err: any) {
        error.value = err.response?.data?.message || 'An error occurred while pinging.';
        if (err.response?.data?.output) {
            result.value = { success: false, output: err.response.data.output };
        }
    } finally {
        pinging.value = false;
    }
};

watch(() => props.show, (showing) => {
    if (showing) {
        performPing();
    } else {
        result.value = null;
        error.value = null;
    }
});

const close = () => {
    emit('close');
};
</script>

<template>
    <DialogModal :show="show" @close="close">
        <template #title>
            Connectivity Check: {{ name }}
        </template>

        <template #content>
            <div class="mb-4">
                <p class="text-gray-600 dark:text-gray-400">
                    Pinging <span class="font-mono font-bold">{{ ip }}</span>...
                </p>
            </div>

            <div v-if="pinging" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
            </div>

            <div v-else-if="result" class="mt-4">
                <div :class="[
                    'p-4 rounded-lg mb-4',
                    result.success ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'
                ]">
                    <div class="flex items-center">
                        <svg v-if="result.success" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <span class="font-bold">{{ result.success ? 'Reachable' : 'Unreachable' }}</span>
                    </div>
                </div>

                <div class="bg-black text-green-400 p-4 rounded-lg font-mono text-xs overflow-x-auto shadow-inner">
                    <pre class="whitespace-pre-wrap">{{ result.output }}</pre>
                </div>
            </div>

            <div v-else-if="error" class="mt-4">
                <div class="p-4 bg-red-100 text-red-800 border border-red-200 rounded-lg">
                    <p class="font-bold">Error</p>
                    <p>{{ error }}</p>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="performPing" :disabled="pinging" class="mr-2">
                Retry
            </SecondaryButton>
            <SecondaryButton @click="close">
                Close
            </SecondaryButton>
        </template>
    </DialogModal>
</template>
