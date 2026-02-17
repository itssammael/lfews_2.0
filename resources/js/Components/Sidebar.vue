<script setup lang="ts">
  import { ref } from 'vue';
import { Link } from "@inertiajs/vue3";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";

import { useDashboardSettings } from '@/Composables/useDashboardSettings';

defineProps({
  //
});
const showSidebar = ref(false);
const { showWaterLevelSensors, showWeatherStations, showEvacuationCenters } = useDashboardSettings();
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
        :href="route('evacuation-center.index')"
        :active="route().current('evacuation-center.*')"
      >
        <div class="flex items-center">
             <img src="/images/disaster.png" alt="Evacuation Center" class="w-8 h-8 mr-2" />
             <span :class="{'block': showSidebar, 'hidden': ! showSidebar}">Evacuation Center</span>
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
      
      <!-- Dashboard Settings Toggles -->
      <div class="px-4 py-4 space-y-4 border-t border-gray-200 dark:border-gray-700">
        <span class="text-xs font-bold block" :class="{'block': showSidebar, 'hidden': ! showSidebar}" v-if="route().current('dashboard')">Dashboard Settings</span>
          <div class="flex items-center justify-between" v-if="route().current('dashboard')">
            
               <label class="flex items-center cursor-pointer">
                  <div class="relative inline-block w-8 h-4 transition duration-200 ease-in-out mr-2">
                      <input 
                          type="checkbox" 
                          v-model="showWaterLevelSensors"
                          class="opacity-0 w-0 h-0 peer"
                      />
                      <div class="absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-300 dark:bg-gray-600 rounded-full transition-all duration-300 peer-checked:bg-orange-500"></div>
                      <div class="absolute cursor-pointer h-3 w-3 left-0.5 bottom-0.5 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-4 shadow-sm"></div>
                  </div>
                  <span :class="{'block': showSidebar, 'hidden': ! showSidebar}" class="text-xs font-medium text-gray-600 dark:text-gray-400">Water Level</span>
              </label>
          </div>
           <div class="flex items-center justify-between" v-if="route().current('dashboard')">
               <label class="flex items-center cursor-pointer">
                  <div class="relative inline-block w-8 h-4 transition duration-200 ease-in-out mr-2">
                      <input 
                          type="checkbox" 
                          v-model="showWeatherStations"
                          class="opacity-0 w-0 h-0 peer"
                      />
                      <div class="absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-300 dark:bg-gray-600 rounded-full transition-all duration-300 peer-checked:bg-orange-500"></div>
                      <div class="absolute cursor-pointer h-3 w-3 left-0.5 bottom-0.5 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-4 shadow-sm"></div>
                  </div>
                  <span :class="{'block': showSidebar, 'hidden': ! showSidebar}" class="text-xs font-medium text-gray-600 dark:text-gray-400">Weather Stn</span>
              </label>
          </div>
           <div class="flex items-center justify-between" v-if="route().current('dashboard')">
               <label class="flex items-center cursor-pointer">
                  <div class="relative inline-block w-8 h-4 transition duration-200 ease-in-out mr-2">
                      <input 
                          type="checkbox" 
                          v-model="showEvacuationCenters"
                          class="opacity-0 w-0 h-0 peer"
                      />
                      <div class="absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-300 dark:bg-gray-600 rounded-full transition-all duration-300 peer-checked:bg-orange-500"></div>
                      <div class="absolute cursor-pointer h-3 w-3 left-0.5 bottom-0.5 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-4 shadow-sm"></div>
                  </div>
                  <span :class="{'block': showSidebar, 'hidden': ! showSidebar}" class="text-xs font-medium text-gray-600 dark:text-gray-400">Evac Centers</span>
              </label>
          </div>
      </div>
    </div>
  </aside>
</template>
