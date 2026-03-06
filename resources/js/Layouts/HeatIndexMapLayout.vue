<script setup>
import { Head, Link } from "@inertiajs/vue3";
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 border-t-4 border-orange-500">
        <!-- Main Content -->
        <main class="w-full h-screen">
            <slot />
        </main>
    </div>
</template>
