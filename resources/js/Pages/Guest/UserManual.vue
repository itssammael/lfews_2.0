<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

// Since this is a standalone manual page with its own sidebar, 
// we'll keep the design self-contained but accessible as a guest page.
</script>

<template>
    <Head title="User Manual - LFEWS 2.0" />

    <div class="bg-slate-50 text-slate-800 min-h-screen">
        <!-- Sidebar Navigation -->
        <aside class="no-print fixed top-0 left-0 h-full w-72 glass-sidebar z-50 hidden lg:block overflow-y-auto p-6">
            <div class="flex items-center gap-3 mb-10">
                <div class="bg-blue-600 p-2 rounded-lg">
                    <i class="fas fa-droplet text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-xl leading-tight">LFEWS 2.0</h1>
                    <p class="text-xs text-slate-500 font-medium tracking-wider uppercase">User Manual</p>
                </div>
            </div>

            <nav class="space-y-1">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 px-3">Table of Contents</p>
                <a href="#introduction"
                    class="toc-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-blue-50">
                    <span class="w-6 text-center">1</span> Introduction
                </a>
                <a href="#getting-started"
                    class="toc-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-blue-50">
                    <span class="w-6 text-center">2</span> Getting Started
                </a>
                <a href="#dashboard"
                    class="toc-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-blue-50">
                    <span class="w-6 text-center">3</span> Dashboard
                </a>
                <a href="#monitoring"
                    class="toc-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-blue-50">
                    <span class="w-6 text-center">4</span> Monitoring System
                </a>
                <a href="#maps"
                    class="toc-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-blue-50">
                    <span class="w-6 text-center">5</span> Maps & Geolocation
                </a>
                <a href="#reports"
                    class="toc-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-blue-50">
                    <span class="w-6 text-center">6</span> Reports
                </a>
                <a href="#admin"
                    class="toc-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-blue-50">
                    <span class="w-6 text-center">7</span> Administration
                </a>

                <div class="pt-6 border-t border-slate-100 mt-6">
                    <Link href="/" class="flex items-center gap-3 px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50 rounded-md">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </Link>
                </div>
            </nav>

            <div class="mt-12 p-4 bg-blue-50 rounded-xl">
                <p class="text-xs text-blue-700 font-semibold mb-1">Need Support?</p>
                <p class="text-[11px] text-blue-600 leading-relaxed">Contact your system administrator for technical
                    assistance or credential issues.</p>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="lg:ml-72 min-h-screen p-6 md:p-12 max-w-5xl mx-auto">

            <!-- Header / Cover Section -->
            <header class="mb-16 border-b border-slate-200 pb-10">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <span
                            class="inline-block bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-1 rounded mb-4 uppercase tracking-widest">Official
                            Documentation</span>
                        <h1 class="text-4xl font-extrabold text-slate-900 mb-2">Local Flood Early Warning System</h1>
                        <p class="text-lg text-slate-600 font-light">Comprehensive operational guide for LFEWS 2.0</p>
                    </div>
                    <div class="text-left md:text-right text-sm text-slate-400">
                        <p>Revision: 2.0.2</p>
                        <p>Updated: March 17, 2026</p>
                    </div>
                </div>
            </header>

            <!-- 1. Introduction -->
            <section id="introduction" class="mb-16 scroll-mt-10">
                <div class="flex items-center gap-4 mb-6">
                    <span
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-900 text-white font-bold text-sm">01</span>
                    <h2 class="text-2xl font-bold text-slate-900">Introduction</h2>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 leading-relaxed text-slate-600">
                    <p class="mb-4">
                        Welcome to the <strong>LFEWS 2.0</strong> User Manual. This platform is a critical infrastructure
                        component designed to safeguard communities by providing real-time intelligence on environmental
                        conditions.
                    </p>
                    <p>
                        LFEWS integrates advanced sensor networks with intuitive visualization tools to deliver a unified
                        view of flood monitoring, weather patterns, and tidal movements, enabling data-driven disaster risk
                        reduction.
                    </p>
                </div>
            </section>

            <!-- 2. Getting Started -->
            <section id="getting-started" class="mb-16 scroll-mt-10">
                <div class="flex items-center gap-4 mb-6">
                    <span
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-900 text-white font-bold text-sm">02</span>
                    <h2 class="text-2xl font-bold text-slate-900">Getting Started</h2>
                </div>
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                            <i class="fas fa-right-to-bracket"></i>
                        </div>
                        <h3 class="font-bold text-slate-900 mb-2">Access & Login</h3>
                        <p class="text-sm text-slate-600 leading-relaxed mb-4">
                            Navigate to the application URL provided by your IT department. Enter your assigned email and
                            password to securely enter the system.
                        </p>
                        <ol class="list-decimal list-inside text-sm text-slate-600 space-y-2 bg-slate-50 p-4 rounded-xl">
                            <li>Enter your designated email address.</li>
                            <li>Input your secure password.</li>
                            <li>Click "Log In" to access your tailored dashboard.</li>
                        </ol>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <div
                            class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center mb-4">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3 class="font-bold text-slate-900 mb-2">Role-Based Access</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Features are restricted by roles (Administrator, Command Center, and User). Administrators have full access, Command Center staff can manage data but cannot delete records, and standard Users have view-only and remote download access.
                        </p>
                    </div>
                </div>
                
                <div class="mb-6 border-4 border-slate-50 rounded-xl overflow-hidden shadow-inner bg-slate-100">
                    <img src="/images/screenshots/login_screen.png" alt="Login Screen" class="w-full h-auto block">
                </div>
            </section>

            <!-- 3. Dashboard -->
            <section id="dashboard" class="mb-16 scroll-mt-10">
                <div class="flex items-center gap-4 mb-6">
                    <span
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-900 text-white font-bold text-sm">03</span>
                    <h2 class="text-2xl font-bold text-slate-900">Dashboard</h2>
                </div>
                <div class="bg-slate-900 text-white p-8 rounded-2xl shadow-xl overflow-hidden relative mb-8">
                    <div class="relative z-10">
                        <h3 class="text-xl font-bold mb-6">Command Center Overview</h3>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <i class="fas fa-check-circle text-emerald-400 mt-1"></i>
                                <div>
                                    <span class="font-semibold block">Summary Visualizations</span>
                                    <span class="text-sm text-slate-300">Instant high-level status of all system
                                        endpoints.</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-water text-blue-400 mt-1"></i>
                                <div>
                                    <span class="font-semibold block">Environmental Visualizations</span>
                                    <span class="text-sm text-slate-300">Track Water Levels, local Weather Station observations, Lunar Tides, and Evacuation Center occupant capacities.</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-bolt text-amber-400 mt-1"></i>
                                <div>
                                    <span class="font-semibold block">Quick Controls</span>
                                    <span class="text-sm text-slate-300">Manual trigger buttons to refresh Water and Weather
                                        data instantly.</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <i class="fas fa-gauge-high absolute -right-10 -bottom-10 text-[180px] text-white/5 rotate-12"></i>
                </div>
                
                <div class="mb-6 border-4 border-slate-50 rounded-xl overflow-hidden shadow-inner bg-slate-100">
                    <img src="/images/screenshots/dashboard.png" alt="Dashboard" class="w-full h-auto block">
                </div>

                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Interacting with the Dashboard</h4>
                <ol class="list-decimal list-inside text-sm text-slate-600 space-y-3 bg-slate-50 p-6 rounded-xl">
                    <li><strong>Auto-Pull Data:</strong> Use the toggle switch on the upper right of the Water/Weather widget frames to enable or disable the 5-minute automatic data refresh protocol.</li>
                    <li><strong>Manual Refresh:</strong> Click the "Pull Data Now" or "Refresh Data" manual action buttons to instantly sync live data from the remote modems.</li>
                    <li><strong>Acknowledge Alerts:</strong> If a sensor hits a critical threshold, a flashing alert will appear over the sensor card. Click the "X" button to acknowledge and dismiss the visual warning.</li>
                    <li><strong>Tidal Analysis:</strong> Scroll down to view the predicted high/low tide graphical representation mapping out the current lunar cycle limits.</li>
                </ol>
            </section>

            <!-- 4. Monitoring System -->
            <section id="monitoring" class="mb-16 scroll-mt-10">
                <div class="flex items-center gap-4 mb-6">
                    <span
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-900 text-white font-bold text-sm">04</span>
                    <h2 class="text-2xl font-bold text-slate-900">Monitoring System</h2>
                </div>

                <div class="space-y-6">
                    <!-- Water Sensors -->
                    <div class="section-card bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-bold text-blue-700 flex items-center gap-2">
                                <i class="fas fa-water"></i> Water Level Sensors
                            </h3>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4 text-sm mb-6">
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="font-bold block mb-1">Overview</span>
                                Monitor connected sensors across river systems.
                            </div>
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="font-bold block mb-1">Management</span>
                                Admins can Create, Edit, or Delete sensors.
                            </div>
                            <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <span class="font-bold block mb-1">Data Pull</span>
                                Manual triggers available for real-time syncing.
                            </div>
                        </div>

                        <div class="mb-6 border-4 border-slate-50 rounded-xl overflow-hidden shadow-inner bg-slate-100">
                            <img src="/images/screenshots/water_sensors.png" alt="Water Level Sensors" class="w-full h-auto block">
                        </div>

                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Sensor Management Steps</h4>
                        <ol class="list-decimal list-inside text-sm text-slate-600 space-y-3 bg-slate-50 p-6 rounded-xl">
                            <li><strong>Register New Sensor:</strong> Click "Add Sensor" to define the sensor name, geographical coordinates (lat/long), and link it to an existing river profile.</li>
                            <li><strong>Set Warning Thresholds:</strong> Assign specific depth values for Level 2 (Caution), Level 3 (Warning), and Level 4 (Critical) hazard heights.</li>
                            <li><strong>Perform Diagnostics:</strong> Trigger manual communication pulls to ensure reliable pingbacks.</li>
                        </ol>
                    </div>

                    <!-- Weather Stations -->
                    <div class="section-card bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <h3 class="text-lg font-bold text-amber-600 flex items-center gap-2 mb-4">
                            <i class="fas fa-cloud-sun-rain"></i> Weather Stations
                        </h3>
                        <p class="text-sm text-slate-600 mb-4">Lists all synced equipment monitoring precipitation,
                            temperature, and wind metrics.</p>

                        <div class="mb-6 border-4 border-slate-50 rounded-xl overflow-hidden shadow-inner bg-slate-100">
                            <img src="/images/screenshots/weather_stations.png" alt="Weather Stations" class="w-full h-auto block">
                        </div>

                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Station Management Steps</h4>
                        <ol class="list-decimal list-inside text-sm text-slate-600 space-y-3 bg-slate-50 p-6 rounded-xl">
                            <li><strong>Link Hardware:</strong> Click "Register Station" and supply the unique string identifier issued by the hardware provider API.</li>
                            <li><strong>Verify Sync:</strong> Check the "Last Observation" column in the data table for recent timestamps to confirm continuous communication.</li>
                            <li><strong>Geographical Assignment:</strong> Ensure the marker longitude/latitude accurately reflects actual deployment for precision on dashboard map visualizations.</li>
                        </ol>
                    </div>

                    <!-- Lunar Tides -->
                    <div class="section-card bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <h3 class="text-lg font-bold text-indigo-700 flex items-center gap-2 mb-4">
                            <i class="fas fa-moon"></i> Lunar Tides
                        </h3>
                        <div class="flex gap-4 items-center">
                            <div class="flex-1">
                                <p class="text-sm text-slate-600 leading-relaxed mb-6">
                                    Integrated with <strong>WorldTides API</strong>. This module graphs "Tidal Extremes,"
                                    allowing users to anticipate high and low tide phases impacting coastal flood risk.
                                </p>

                                <ol class="list-decimal list-inside text-sm text-slate-600 space-y-3 bg-slate-50 p-6 rounded-xl">
                                    <li><strong>Access Tide Tool:</strong> Click "Lunar Tides" on the administration navigation layout.</li>
                                    <li><strong>API Sync:</strong> Click the "Sync External API" button to forcefully pull in forward-looking predictive tide data for the upcoming week from external data services.</li>
                                </ol>
                            </div>
                            <div
                                class="hidden md:block w-32 h-20 bg-indigo-50 rounded-lg border border-indigo-100 flex items-center justify-center">
                                <i class="fas fa-chart-line text-indigo-300 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. Maps and Geolocation -->
            <section id="maps" class="mb-16 scroll-mt-10">
                <div class="flex items-center gap-4 mb-6">
                    <span
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-900 text-white font-bold text-sm">05</span>
                    <h2 class="text-2xl font-bold text-slate-900">Maps and Geolocation</h2>
                </div>

                <div class="space-y-8">
                    <!-- Locator Map -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h3 class="font-bold text-lg mb-4 text-slate-900 flex items-center gap-2">
                            <i class="fas fa-map-location-dot text-rose-500"></i> Locator Map
                        </h3>
                        <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                            The comprehensive command map for administrators. This interactive interface displays all connected monitoring devices, geographic administrative boundaries, and critical infrastructure.
                        </p>
                        
                        <div class="mb-6 border-4 border-slate-50 rounded-xl overflow-hidden shadow-inner bg-slate-100">
                            <img src="/images/screenshots/locator_map.png" alt="Locator Map" class="w-full h-auto block">
                        </div>

                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">How to Use the Locator Map</h4>
                        <ol class="list-decimal list-inside text-sm text-slate-600 space-y-3 mb-6 bg-slate-50 p-6 rounded-xl">
                            <li><strong>Navigate</strong> to the Locator page from the sidebar menu.</li>
                            <li><strong>Toggle Layers:</strong> Use the "MAP LAYERS" filtering menu on the left side to show or hide specific data: Weather Stations, Water Level Sensors, Evacuation Centers, Rivers, Contour Lines, Flood Risk Maps, Sitios, or Barangays.</li>
                            <li><strong>View Device Data:</strong> Click on any sensor marker (Water Level or Weather Station) to open a popup showing its connection status, recent readings, and last update timestamp.</li>
                            <li><strong>Check Evacuation Status:</strong> Click on Evacuation Center markers to see capacity and current occupant percentages.</li>
                        </ol>
                    </div>

                    <!-- Local Weather Map (Guest) -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h3 class="font-bold text-lg mb-4 text-slate-900 flex items-center gap-2">
                            <i class="fas fa-sun text-amber-500"></i> Public Local Weather Map
                        </h3>
                        <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                            A specialized public-facing map equipped with always-visible popovers and auto-refresh mechanisms, allowing citizens to monitor realtime weather conditions without navigating complex menus.
                        </p>
                        
                        <div class="mb-6 border-4 border-slate-50 rounded-xl overflow-hidden shadow-inner bg-slate-100">
                            <img src="/images/screenshots/local_weather_map.png" alt="Local Weather Map" class="w-full h-auto block">
                        </div>

                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">How to Use the Weather Map</h4>
                        <ol class="list-decimal list-inside text-sm text-slate-600 space-y-3 bg-slate-50 p-6 rounded-xl">
                            <li><strong>Access the Map:</strong> Open the Local Weather Map link. The map auto-refreshes every 5 minutes (a countdown timer is visible in the corner).</li>
                            <li><strong>Read Popovers:</strong> Device markers feature <strong>always-visible popovers</strong>, providing an immediate view of real-time metrics without requiring any clicks.</li>
                            <li><strong>Toggle Views:</strong> Locate the view mode buttons on the bottom left. Click the <i class="fas fa-fire text-red-500 mx-1"></i> Heat Index button for heat warnings, or the <i class="fas fa-cloud-sun text-blue-500 mx-1"></i> Full Weather button to see temperature and precipitation totals.</li>
                        </ol>
                    </div>

                    <!-- Risk & Hazard Maps -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <h3 class="font-bold text-lg mb-4 text-slate-900 flex items-center gap-2">
                            <i class="fas fa-triangle-exclamation text-amber-500"></i> Flood Risk & Hazard Maps
                        </h3>
                        <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                            Color-coded geographical overlays illustrating the vulnerability of specific areas to flooding, utilizing historical data and topographical contours.
                        </p>

                        <div class="mb-6 border-4 border-slate-50 rounded-xl overflow-hidden shadow-inner bg-slate-100">
                            <img src="/images/screenshots/hazard_map.png" alt="Hazard Map" class="w-full h-auto block">
                        </div>

                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Analyzing Hazard Maps</h4>
                        <ol class="list-decimal list-inside text-sm text-slate-600 space-y-3 bg-slate-50 p-6 rounded-xl">
                            <li><strong>Select the Map:</strong> Navigate to the Flood Hazard Map via the administration sidebar.</li>
                            <li><strong>Interpret Colors:</strong> The map overlays are color-coded (e.g., Light Green for Low Risk, Blue for High, Purple for Very High).</li>
                            <li><strong>Strategic Planning:</strong> Cross-reference high-risk zones against nearby Evacuation Centers to plan safe emergency deployment routes.</li>
                        </ol>
                    </div>
                </div>
            </section>

            <!-- 6. Reports -->
            <section id="reports" class="mb-16 scroll-mt-10">
                <div class="flex items-center gap-4 mb-6">
                    <span
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-900 text-white font-bold text-sm">06</span>
                    <h2 class="text-2xl font-bold text-slate-900">Reports</h2>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                    <div class="flex flex-col md:flex-row gap-8">
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-900 mb-3">Historical Archiving</h3>
                            <p class="text-sm text-slate-600 leading-relaxed mb-6">
                                Export metric histories for analysis and forecasting. Data can be filtered by timeframe or
                                specific hardware nodes.
                            </p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                                    <i class="fas fa-file-export text-slate-400"></i>
                                    <span class="text-xs font-bold text-slate-700 uppercase">Export PDF/CSV</span>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                                    <i class="fas fa-filter text-slate-400"></i>
                                    <span class="text-xs font-bold text-slate-700 uppercase">Custom Filters</span>
                                </div>
                            </div>
                        </div>
                        <div class="md:w-px bg-slate-100"></div>
                        <div class="flex-1 space-y-4 mb-8">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs">
                                    <i class="fas fa-ruler-vertical"></i>
                                </div>
                                <span class="text-sm font-medium text-slate-700">Water Level History</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xs">
                                    <i class="fas fa-temperature-half"></i>
                                </div>
                                <span class="text-sm font-medium text-slate-700">Weather Observations</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xs">
                                    <i class="fas fa-fire"></i>
                                </div>
                                <span class="text-sm font-medium text-slate-700">Heat Index Reports &amp; Charting</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border-4 border-slate-50 rounded-xl overflow-hidden shadow-inner bg-slate-100">
                        <img src="/images/screenshots/reports_interface.png" alt="Reports Interface" class="w-full h-auto block">
                    </div>

                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-8 mb-3">Generating System Reports</h4>
                    <ol class="list-decimal list-inside text-sm text-slate-600 space-y-3 bg-slate-50 p-6 rounded-xl">
                        <li><strong>Select Asset Type:</strong> Choose either "Water Level Sensors" or "Weather Stations" from the top tabular navigation menu to load the relevant data sets.</li>
                        <li><strong>Select Target Asset:</strong> Choose a specific device model or River association from the drop-down selector to plot its independent archive records.</li>
                        <li><strong>Define Timespans:</strong> Utilize the comprehensive Date-Range picker system to zoom into localized specific event occurrences (e.g. tracking typhoon data dumps spanning last month).</li>
                        <li><strong>Visual Charts:</strong> As you configure the filters, the line and bar graphs will dynamically adjust to visually capture trends. Hover over plotted graphical nodes to review specific statistical instances in popovers.</li>
                        <li><strong>Heat Index Maps:</strong> If reviewing Weather Stations, scroll down to the Heat Index map. The heatmap chart colors dynamically match severity thresholds configured in System Settings.</li>
                        <li><strong>Exporting:</strong> Click the "Download" or "Export Data" utilities to parse the compiled filter results down into accessible Excel/CSV compatible archives or printer-friendly PDF summaries for archival records.</li>
                    </ol>
                </div>
            </section>

            <!-- 7. Administration -->
            <section id="admin" class="mb-24 scroll-mt-10">
                <div class="flex items-center gap-4 mb-6">
                    <span
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-900 text-white font-bold text-sm">07</span>
                    <h2 class="text-2xl font-bold text-slate-900">Administration & Settings</h2>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-1 text-sm overflow-hidden">
                    <div class="bg-white p-8 rounded-[calc(1.5rem-4px)] shadow-sm">
                        <h3 class="font-bold text-slate-900 mb-6 flex items-center gap-2">
                            <i class="fas fa-gears text-slate-400"></i> System Core Configuration
                        </h3>

                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-4">Core
                                    Settings</h4>
                                <ul class="space-y-4">
                                    <li>
                                        <span class="block font-bold text-slate-800">System Settings</span>
                                        <span class="text-slate-500">Configure core advisory thresholds like Heat Index levels and alert messages.</span>
                                    </li>
                                    <li>
                                        <span class="block font-bold text-slate-800">Rivers Module</span>
                                        <span class="text-slate-500">Define river systems for sensor mapping.</span>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-4">Users &
                                    Security</h4>
                                <ul class="space-y-4">
                                    <li>
                                        <span class="block font-bold text-slate-800">User Management</span>
                                        <span class="text-slate-500">Administrators can add, revoke, or edit staff profiles, roles, and credentials.</span>
                                    </li>
                                    <li>
                                        <span class="block font-bold text-slate-800">Roles Manager</span>
                                        <span class="text-slate-500">Customize granular permissions for each user
                                            role.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="border-t border-slate-200 pt-8 text-center text-slate-400 text-xs">
                <p class="mb-2">System Document generated for <strong>LFEWS 2.0</strong></p>
                <p>&copy; 2026 Local Flood Early Warning System. All rights reserved.</p>
            </footer>

        </main>

        <!-- Mobile Navigation Trigger (Optional) -->
        <div class="lg:hidden fixed bottom-6 right-6 no-print">
            <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                class="w-12 h-12 bg-slate-900 text-white rounded-full shadow-2xl flex items-center justify-center">
                <i class="fas fa-arrow-up"></i>
            </button>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

.glass-sidebar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-right: 1px solid #e5e7eb;
}

.section-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.section-card:hover {
    border-left-color: #3b82f6;
    background: #f8fafc;
}

.toc-link {
    transition: color 0.2s ease;
}

.toc-link:hover {
    color: #3b82f6;
}

@media print {
    .no-print {
        display: none;
    }

    .print-break {
        page-break-before: always;
    }

    body {
        background: white;
    }

    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
}
</style>
