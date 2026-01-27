<script setup lang="ts">
  import { ref } from 'vue';
import { Link } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";

defineProps({
  //
});
const showSidebar = ref(false);
</script>

<template>
  <aside
  :class="{'w-64 lg:w-48': showSidebar, 'w-16 lg:w-12': ! showSidebar }"
    class="flex flex-col bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 min-h-screen"
  >
      <div class="flex items-center justify-center">
          <div class="font-bold text-xl w-3/4 text-center" :class="{'inline-block': showSidebar, 'hidden': ! showSidebar }">LFEWS</div>
          <div>
            <button class="w-fit inline-flex items-right justify-center p-2 rounded-md text-gray-800 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-800 hover:bg-orange-100 dark:hover:bg-orange-900 focus:outline-none dark:focus:bg-orange-900 focus:text-gray-800 dark:focus:text-gray-800 transition duration-150 ease-in-out" @click="showSidebar = ! showSidebar">
              <svg
                  class="size-6"
                  stroke="currentColor"
                  fill="none"
                  viewBox="0 0 24 24"
              >
                  <path
                      :class="{'hidden': showSidebar, 'inline-flex': ! showSidebar }"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"
                  />
                  <path
                      :class="{'hidden': ! showSidebar, 'inline-flex': showSidebar }"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                  />
              </svg>
          </button>
        </div>
      </div>
    <!-- Navigation Links -->
    <div class="flex-1 overflow-y-auto py-4 space-y-1 uppercase">
      <ResponsiveNavLink
        :href="route('dashboard')"
        :active="route().current('dashboard')"
      >
        <span :class="{'inline-block': showSidebar, 'hidden': ! showSidebar}">Dashboard</span><span :class="{'hidden': showSidebar, 'inline-block': ! showSidebar}">  <img src="/images/dashboard.png" alt="Logo" class="w-8" /></span>
      </ResponsiveNavLink>

      <ResponsiveNavLink
        :href="route('water-level-sensors')"
        :active="route().current('water-level-sensors')"
      >
        <span :class="{'inline-block': showSidebar, 'hidden': ! showSidebar}">Water Level Sensors</span><span :class="{'hidden': showSidebar, 'inline-block': ! showSidebar}"><img src="/images/water-level.png" alt="Logo" class="w-8" /></span>
      </ResponsiveNavLink>

      <ResponsiveNavLink
        :href="route('weather-stations')"
        :active="route().current('weather-stations')"
      >
        <span :class="{'inline-block': showSidebar, 'hidden': ! showSidebar}">Weather Stations</span><span :class="{'hidden': showSidebar, 'inline-block': ! showSidebar}"><img src="/images/weather-station.png" alt="Logo" class="w-8" /></span>
      </ResponsiveNavLink>
      <!-- Add more sidebar links here -->
    </div>
  </aside>
</template>
