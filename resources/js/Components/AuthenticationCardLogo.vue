<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'">
        <template v-if="$page.props.system_settings?.system_logo">
            <img :src="$page.props.system_settings.system_logo" alt="Logo" class="size-16" />
        </template>
        <template v-else>
            <img src="/images/logo.png" alt="Logo" class="size-16" />
        </template>
    </Link>
</template>
