<script setup lang="ts"></script>

<template>
    <img v-if="$page.props.system_settings?.system_logo" :src="$page.props.system_settings.system_logo" alt="Logo" class="h-12" />
    <img v-else src="/images/logo.png" alt="Logo" class="h-12" />
</template>
