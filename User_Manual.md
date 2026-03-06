# LFEWS 2.0 - Local Flood Early Warning System
## User Manual

---

### Table of Contents
1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [Dashboard](#3-dashboard)
4. [Monitoring System](#4-monitoring-system)
   - Water Level Sensors
   - Weather Stations
   - Lunar Tides
5. [Maps and Geolocation](#5-maps-and-geolocation)
   - Locator Map
   - Hazard & Flood Risk Maps
   - Evacuation Centers
6. [Reports](#6-reports)
7. [Administration & Settings](#7-administration--settings)
   - System Settings
   - Users & Roles Configuration

---

### 1. Introduction
Welcome to the LFEWS 2.0 (Local Flood Early Warning System) User Manual. LFEWS is a comprehensive platform designed to provide real-time updates and historical data regarding flood monitoring, weather observations, tidal behaviors, and critical mappings for evacuation and hazard risk reduction.

### 2. Getting Started
To access the system, navigate to the application URL provided by your administrator. You will need a registered account with the appropriate role (e.g., Administrator, Reviewer) to access secured modules.

- **Login**: Use your assigned credentials (email and password) to log in securely.
- **Roles**: Depending on your user role, you might have restricted access to certain management or settings panels.

### 3. Dashboard
The **Dashboard** is the main overview screen presented after logging in. It features:
- **Summary Visualizations**: High-level representations of current system status.
- **Budget Visualizations**: amCharts displaying MOOE and Capital Outlay allocations for the current operational year.
- **Quick Controls**: Buttons to manually pull or refresh recent data for Water and Weather parameters instantly.

### 4. Monitoring System
The core operation of LFEWS relies on active data collection endpoints seamlessly integrated into the application.

#### Water Level Sensors
- **Overview**: View all connected water level sensors deployed across the designated river systems.
- **Management**: Administrators can Create, Edit, or Delete sensors.
- **Data Pull**: Authorized personnel can manually trigger data pulls to reflect real-time river conditions.

#### Weather Stations
- **Overview**: Lists all weather monitoring equipment synced with the platform.
- **Management**: You can add new stations, update current details, or remove obsolete configurations.
- **Data Pull**: Similar to water sensors, users can initiate manual synchronization for latest weather reading metrics like precipitation, temperature, and wind speed.

#### Lunar Tides
- **Tide Graphing**: Visual representation of "Tidal Extremes" utilizing the WorldTides API integration. Keep track of incoming high and low tide phases relative to the coastline.
- **Synchronization**: Sync the tide projections within the system for updated graphing calculations.

### 5. Maps and Geolocation
LFEWS emphasizes visualized locational data for better response coordination during calamity scenarios.

#### Locator Map
A consolidated view of all available devices and stations pinged onto an interactive map area.
- Click on any specific sensor or station marker to view a **Device Status Popover**, reflecting its current online visibility and its latest recorded reading limit.

#### Hazard & Flood Risk Maps
Provides multi-layered map configurations to outline areas that are highly susceptible to flood hazards and other topographical risks. 
- You can manage recorded risk areas within the "Hazard Maps" and "Flood Risks" modules.

#### Evacuation Centers
An accessible view charting all identified Evacuation Centers in the region, aiding in strategic disaster response deployment.

### 6. Reports
The **Reports** module serves as the primary tool for archiving and exporting metric histories.
- **Water Level Data**: Retrieve historical flood depths corresponding to specific timeframes or sensor nodes.
- **Weather Observation Data**: Pull out past weather conditions recorded over time for accurate forecasting references.

### 7. Administration & Settings
Reserved primarily for system administrators, these menus dictate the core structure of LFEWS 2.0.

#### System Settings
- **Categories**: Configure core metadata like Appropriation Categories.
- **Budget Config**: Manage active "Budget Years" and designated "Fund Sources".
- **Rivers Modules**: Manage predefined target rivers aligned with sensor installations.

#### Users & Roles Configuration
- Navigate to the **Users Management** screen to add, revoke, or edit user profiles.
- **Roles Manager**: Directly create and customize User Roles defining exact permissions across the platform interface. Users can be selectively switched between respective access levels easily.

---
*System Document generated for LFEWS 2.0*
