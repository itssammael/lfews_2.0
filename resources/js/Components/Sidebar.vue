<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";

defineProps({
  //
});
</script>

<template>
  <aside
    class="hidden md:flex flex-col w-64 bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 min-h-screen"
  >
    <!-- Navigation Links -->
    <div class="flex-1 overflow-y-auto py-4 space-y-1 uppercase">
      <ResponsiveNavLink
        :href="route('dashboard')"
        :active="route().current('dashboard')"
      >
        Dashboard
      </ResponsiveNavLink>

      <ResponsiveNavLink
        :href="route('water-level-sensors')"
        :active="route().current('water-level-sensors')"
      >
        Water Level Sensors
      </ResponsiveNavLink>

      <ResponsiveNavLink
        :href="route('weather-stations')"
        :active="route().current('weather-stations')"
      >
        Weather Stations
      </ResponsiveNavLink>
      <!-- Add more sidebar links here -->
    </div>
  </aside>
</template>
