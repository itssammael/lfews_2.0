<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
</script>

<template>
  <Head title="Services" />

  <div
    class="min-h-screen bg-gray-50 dark:bg-gray-900 border-t-4 border-orange-500"
  >
    <!-- Navigation -->
    <nav
      class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="shrink-0 flex items-center">
              <Link href="/">
                <ApplicationMark class="block h-10 w-auto text-orange-600" />
              </Link>
            </div>
            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
              <Link
                href="/"
                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none transition duration-150 ease-in-out"
              >
                Home
              </Link>
              <Link
                href="/awards"
                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none transition duration-150 ease-in-out"
              >
                Awards
              </Link>
              <Link
                href="/services"
                class="inline-flex items-center px-1 pt-1 border-b-2 border-orange-500 text-sm font-medium leading-5 text-gray-900 dark:text-gray-100 focus:outline-none transition duration-150 ease-in-out"
              >
                Services
              </Link>
              <Link
                href="/local-weather-map"
                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none transition duration-150 ease-in-out"
              >
                Local Weather Map
              </Link>
            </div>
          </div>
          <div class="flex items-center">
            <Link
              :href="route('login')"
              class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
            >
              Log in
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <main class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h1
            class="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl"
          >
            Our Services
          </h1>
          <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">
            Comprehensive solutions for disaster risk reduction and management.
          </p>
        </div>

        <div class="space-y-8">
          <!-- Service 1 -->
          <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-orange-500"
          >
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg
                  class="h-8 w-8 text-orange-600"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                  />
                </svg>
              </div>
              <div class="ml-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  Water Level Monitoring
                </h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                  Strategic placement of sensors to provide precise and
                  real-time data on river and stream levels.
                </p>
              </div>
            </div>
          </div>

          <!-- Service 2 -->
          <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-orange-500"
          >
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg
                  class="h-8 w-8 text-orange-600"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"
                  />
                </svg>
              </div>
              <div class="ml-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  Weather Observations
                </h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                  Advanced weather stations collecting rainfall, temperature,
                  and wind data to predict flood risks.
                </p>
              </div>
            </div>
          </div>

          <!-- Service 3 -->
          <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-orange-500"
          >
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg
                  class="h-8 w-8 text-orange-600"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <div class="ml-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  Early Warning Dissemination
                </h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                  Automated alert systems to notify stakeholders and the public
                  via multiple channels when thresholds are exceeded.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer
      class="bg-orange-50 dark:bg-orange-400 border-t border-orange-200 dark:border-orange-600"
    >
      <div
        class="w-full max-w-7xl mx-auto px-4 py-8 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8"
      >
        <!-- Left Column: Address -->
        <div
          class="flex flex-col items-center md:items-start text-center md:text-left text-black dark:text-black text-sm"
        >
          <div class="flex items-center gap-2 mb-4">
            <img
              src="/images/bayawanlogo.png"
              alt="Bayawan Logo"
              class="h-12 w-auto"
            />
            <ApplicationMark class="h-12 w-auto" />
          </div>
          <p class="font-bold">
            Bayawan City Disaster Risk Reduction Management Office
          </p>
          <p class="font-bold">CDRRM Bldg, Poblacion Bayawan City</p>

          <div class="font-bold mt-4">
            <span style="color: white"> &copy; </span>
            <span style="color: black"> 2025. </span>
          </div>
        </div>

        <!-- Center Column: Contact Info -->
        <div
          class="flex flex-col items-center justify-center md:items-start text-center md:text-left"
        >
          <div class="space-y-3">
            <div
              class="flex items-center gap-3 justify-center md:justify-start"
            >
              <img
                src="/images/phone.png"
                alt="Phone"
                class="h-6 w-auto brightness-0 invert"
              />
              <p class="text-base font-bold text-black dark:text-black">
                09364647233
              </p>
            </div>
            <div
              class="flex items-center gap-3 justify-center md:justify-start"
            >
              <img
                src="/images/email.png"
                alt="Email"
                class="h-6 w-auto brightness-0 invert"
              />
              <p class="text-base text-black font-bold dark:text-black">
                bayawandrrmo@gmail.com
              </p>
            </div>
            <div
              class="flex items-center gap-3 justify-center md:justify-start"
            >
              <img
                src="/images/facebook.png"
                alt="Facebook"
                class="h-6 w-auto brightness-0 invert"
              />
              <p class="text-base font-bold text-black dark:text-black">
                Cdrrmo Bayawan
              </p>
            </div>
          </div>
        </div>

        <!-- Right Column: Emergency Hotlines -->
        <div class="flex item-center justify-center md:justify-end">
          <img
            src="/images/EmergencyHotlines.png"
            alt="Emergency Hotlines"
            class="h-40 w-auto object-contain"
          />
        </div>
      </div>
    </footer>
  </div>
</template>

