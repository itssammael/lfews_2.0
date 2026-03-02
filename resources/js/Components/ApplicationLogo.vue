<template>
    <img v-if="$page.props.system_settings?.system_logo" :src="$page.props.system_settings.system_logo" alt="Logo" class="w-auto h-16" />
    <img v-else src="/images/logo.png" alt="Logo" class="w-auto h-16" />
</template>
