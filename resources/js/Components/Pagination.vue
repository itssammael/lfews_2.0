<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array as () => Array<{ active: boolean; label: string; url: string | null }>,
});
</script>

<template>
    <div v-if="links && links.length > 3">
        <div class="flex flex-wrap -mb-1 mt-6">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" 
                     class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded cursor-default" 
                     v-html="link.label" 
                />
                <Link v-else 
                      class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" 
                      :class="{ 'bg-blue-600 text-white border-orange-500 hover:bg-blue-700': link.active }" 
                      :href="link.url" 
                      v-html="link.label" 
                />
            </template>
        </div>
    </div>
</template>
