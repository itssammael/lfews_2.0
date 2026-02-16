<script setup lang="ts">
  import { ref } from 'vue';
import { Link } from "@inertiajs/vue3";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";

defineProps({
  //
});
const showSidebar = ref(false);
</script>

<template>
  <aside
  :class="{'w-64 lg:w-48': showSidebar, 'w-24 lg:w-16': ! showSidebar }"
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
        <div class="flex items-center">
             <img src="/images/dashboard.png" alt="Dashboard" class="w-8 h-8 mr-2" />
             <span :class="{'block': showSidebar, 'hidden': ! showSidebar}">Dashboard</span>
        </div>
      </ResponsiveNavLink>

     
      <ResponsiveNavLink
        :href="route('reports')"
        :active="route().current('reports')"
      >
        <div class="flex items-center">
             <img src="/images/report.png" alt="Reports" class="w-8 h-8 mr-2" />
             <span :class="{'block': showSidebar, 'hidden': ! showSidebar}">Reports</span>
        </div>
      </ResponsiveNavLink>
      <ResponsiveNavLink
        :href="route('locator')"
        :active="route().current('locator')"
      >
        <div class="flex items-center">
             <img src="/images/maps.png" alt="Locator" class="w-8 h-8 mr-2" />
             <span :class="{'block': showSidebar, 'hidden': ! showSidebar}">Locator</span>
        </div>
      </ResponsiveNavLink>
       <ResponsiveNavLink
        :href="route('water-level-sensors')"
        :active="route().current('water-level-sensors')"
      >
        <div class="flex items-center">
             <img src="/images/water-level.png" alt="Water Level" class="w-8 h-8 mr-2" />
             <span :class="{'block': showSidebar, 'hidden': ! showSidebar}">Water Level Sensors</span>
        </div>
      </ResponsiveNavLink>
      <ResponsiveNavLink
        :href="route('weather-stations')"
        :active="route().current('weather-stations')"
      >
        <div class="flex items-center">
             <img src="/images/weather-station.png" alt="Weather Station" class="w-8 h-8 mr-2" />
             <span :class="{'block': showSidebar, 'hidden': ! showSidebar}">Weather Stations</span>
        </div>
      </ResponsiveNavLink>
      <ResponsiveNavLink
        :href="route('rivers.index')"
        :active="route().current('rivers.*')"
      >
        <div class="flex items-center">
             <img src="/images/landscape.png" alt="Rivers" class="w-8 h-8 mr-2" />
             <span :class="{'block': showSidebar, 'hidden': ! showSidebar}">Rivers</span>
        </div>
      </ResponsiveNavLink>

      <ResponsiveNavLink
        v-if="$page.props.auth.can.manage"
        :href="route('data-migration.index')"
        :active="route().current('data-migration.*')"
      >
        <div class="flex items-center">
             <img src="/images/migration.png" alt="Data Migration" class="w-8 h-8 mr-2" />
             <span :class="{'block': showSidebar, 'hidden': ! showSidebar}">Data Migration</span>
        </div>
      </ResponsiveNavLink>

      <!-- Add more sidebar links here -->
    </div>
  </aside>
</template>
