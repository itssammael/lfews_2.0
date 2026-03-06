<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
</script>

<template>
  <Head title="Home" />

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
                class="inline-flex items-center px-1 pt-1 border-b-2 border-orange-500 text-sm font-medium leading-5 text-gray-900 dark:text-gray-100 focus:outline-none transition duration-150 ease-in-out"
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
                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none transition duration-150 ease-in-out"
              >
                Services
              </Link>
              <Link
                href="/heat-index-map"
                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none transition duration-150 ease-in-out"
              >
                Heat Index Map
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
    <!-- Hero Section -->
    <main>
      <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
          <div class="text-center">
            <h1
              class="text-4xl tracking-tight font-extrabold text-gray-900 dark:text-white sm:text-5xl md:text-6xl"
            >
              <span class="block">Local Flood</span>
              <span class="block text-orange-600">Early Warning System</span>
            </h1>
            <p
              class="mt-3 max-w-md mx-auto text-base text-gray-500 dark:text-gray-400 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl"
            >
              Empowering communities with real-time flood monitoring and early
              warning alerts to ensure safety and resilience.
            </p>
            <div
              class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8"
            >
              <div class="rounded-md shadow">
                <Link
                  :href="route('login')"
                  class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 md:py-4 md:text-lg md:px-10 transition duration-150 ease-in-out"
                >
                  Get Started
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Decorative background -->
        <div
          class="absolute top-0 right-0 -mr-20 -mt-20 opacity-10 dark:opacity-20 pointer-events-none"
        >
          <svg
            class="h-64 w-64 text-orange-600"
            fill="currentColor"
            viewBox="0 0 100 100"
          >
            <circle cx="50" cy="50" r="40" />
          </svg>
        </div>
      </div>

      <!-- Features Section -->
      <div class="py-12 bg-white dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="lg:text-center">
            <h2
              class="text-base text-orange-600 font-semibold tracking-wide uppercase"
            >
              Monitoring
            </h2>
            <p
              class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl"
            >
              A better way to stay safe
            </p>
          </div>

          <div class="mt-10">
            <dl
              class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10"
            >
              <div class="relative">
                <dt>
                  <div
                    class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-orange-500 text-white"
                  >
                    <svg
                      class="h-6 w-6"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"
                      />
                    </svg>
                  </div>
                  <p
                    class="ml-16 text-lg leading-6 font-medium text-gray-900 dark:text-white"
                  >
                    Real-time Data
                  </p>
                </dt>
                <dd
                  class="mt-2 ml-16 text-base text-gray-500 dark:text-gray-400"
                >
                  Continuous data collection from water level sensors and
                  weather stations.
                </dd>
              </div>

              <div class="relative">
                <dt>
                  <div
                    class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-orange-500 text-white"
                  >
                    <svg
                      class="h-6 w-6"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                      />
                    </svg>
                  </div>
                  <p
                    class="ml-16 text-lg leading-6 font-medium text-gray-900 dark:text-white"
                  >
                    Early Alerts
                  </p>
                </dt>
                <dd
                  class="mt-2 ml-16 text-base text-gray-500 dark:text-gray-400"
                >
                  Instant notifications sent to authorities and residents during
                  critical levels.
                </dd>
              </div>
            </dl>
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
            class="h- w-auto object-contain"
          />
        </div>
      </div>
    </footer>
  </div>
</template>
