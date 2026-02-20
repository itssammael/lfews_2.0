<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
</script>

<template>
  <Head title="Awards" />

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
                class="inline-flex items-center px-1 pt-1 border-b-2 border-orange-500 text-sm font-medium leading-5 text-gray-900 dark:text-gray-100 focus:outline-none transition duration-150 ease-in-out"
              >
                Awards
              </Link>
              <Link
                href="/services"
                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none transition duration-150 ease-in-out"
              >
                Services
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
            Awards & Recognition
          </h1>
          <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">
            Celebrating our commitment to community safety and innovation.
          </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          <!-- Award Card 1 -->
          <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 transform hover:scale-105 transition duration-300"
          >
            <div class="p-6">
              <div
                class="flex items-center justify-center h-12 w-12 rounded-md bg-orange-100 dark:bg-orange-900 text-orange-600 mb-4 mx-auto"
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
                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                  />
                </svg>
              </div>
              <h3
                class="text-xl font-bold text-gray-900 dark:text-white text-center"
              >
                Innovation Excellence
              </h3>
              <p class="mt-2 text-gray-500 dark:text-gray-400 text-center">
                Awarded for implementing state-of-the-art sensor technology in
                local flood monitoring.
              </p>
            </div>
          </div>

          <!-- Award Card 2 -->
          <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 transform hover:scale-105 transition duration-300"
          >
            <div class="p-6">
              <div
                class="flex items-center justify-center h-12 w-12 rounded-md bg-orange-100 dark:bg-orange-900 text-orange-600 mb-4 mx-auto"
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
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                  />
                </svg>
              </div>
              <h3
                class="text-xl font-bold text-gray-900 dark:text-white text-center"
              >
                Community Safety Award
              </h3>
              <p class="mt-2 text-gray-500 dark:text-gray-400 text-center">
                Recognized for outstanding contribution to flood risk reduction
                and community awareness.
              </p>
            </div>
          </div>

          <!-- Award Card 3 -->
          <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 transform hover:scale-105 transition duration-300"
          >
            <div class="p-6">
              <div
                class="flex items-center justify-center h-12 w-12 rounded-md bg-orange-100 dark:bg-orange-900 text-orange-600 mb-4 mx-auto"
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
                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                  />
                </svg>
              </div>
              <h3
                class="text-xl font-bold text-gray-900 dark:text-white text-center"
              >
                Technology for Good
              </h3>
              <p class="mt-2 text-gray-500 dark:text-gray-400 text-center">
                Sustainable technology implementation for disaster management.
              </p>
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
