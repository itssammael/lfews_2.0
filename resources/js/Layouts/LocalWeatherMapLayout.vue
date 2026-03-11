<script setup>
import { Head, Link } from "@inertiajs/vue3";
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Main Content -->
        <main class="w-full h-screen">
            <slot />
        </main>
    </div>
</template>
