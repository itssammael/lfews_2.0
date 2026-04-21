# LFEWS Windows IIS Deployment Guide

This guide provides step-by-step instructions for deploying the **LFEWS** (Laravel + Vue.js/Inertia) application on a Windows Server running Internet Information Services (IIS).

---

## 1. Prerequisites

Before starting, ensure the following components are installed on your Windows Server:

*   **Internet Information Services (IIS):** Enabled via Windows Features.
*   **CGI Feature for IIS:** Must be enabled under IIS Features (required for PHP FastCGI).
*   **URL Rewrite Module v2.1:** [Download from Microsoft](https://www.iis.net/downloads/microsoft/url-rewrite). **Required for Laravel routing.**
*   **PHP 8.2 (Non-Thread Safe):** Download the x64 Non-Thread Safe version from [windows.php.net](https://windows.php.net/download/).
*   **Composer:** [Download Composer for Windows](https://getcomposer.org/download/).
*   **Node.js & NPM:** (Optional but recommended for building assets).

---

## 2. Server Configuration

### Enable IIS and CGI
1.  Open **Control Panel** > **Programs and Features** > **Turn Windows features on or off**.
2.  Expand **Internet Information Services** > **World Wide Web Services** > **Application Development Features**.
3.  Check **CGI**, **ISAPI Extensions**, and **ISAPI Filters**.
4.  Click OK and wait for installation.

### Install PHP
1.  Extract PHP 8.2 to `C:\php`.
2.  Copy `php.ini-production` to `php.ini`.
3.  Open `php.ini` and ensure the following extensions are enabled (remove the `;` prefix):
    ```ini
    extension=bcmath
    extension=curl
    extension=fileinfo
    extension=gd
    extension=mbstring
    extension=openssl
    extension=pdo_mysql
    extension=sockets
    ```
4.  Set the following settings to optimize for IIS:
    ```ini
    fastcgi.impersonate = 1
    fastcgi.logging = 0
    cgi.fix_pathinfo = 1
    cgi.force_redirect = 0
    ```
5.  Set your timezone (e.g., `date.timezone = Asia/Manila`).

---

## 3. Project Deployment

### Prepare Folders
1.  Copy the `lfews` project directory to `C:\inetpub\wwwroot\lfews`.
2.  Open a command prompt in the project folder and run:
    ```bash
    composer install --optimize-autoloader --no-dev
    cp .env.example .env
    php artisan key:generate
    ```

### Configure Permissions (CRITICAL)
IIS requires specific permissions to write to certain folders.
1.  Right-click the `storage` folder > **Properties** > **Security** > **Edit**.
2.  Add the user `IUSR` and grant **Modify**, **Read & Execute**, **List folder contents**, **Read**, and **Write**.
3.  Add the user `IIS_IUSRS` and grant the same permissions.
4.  Repeat these steps for the `bootstrap/cache` folder.

### Set Up the IIS Site
1.  Open **IIS Manager**.
2.  Right-click **Sites** > **Add Website**.
3.  **Site Name:** `LFEWS`
4.  **Physical Path:** `C:\inetpub\wwwroot\lfews\public` (Ensure you point to the **public** folder).
5.  **Binding:** Configure your Hostname or Port.
6.  Click OK.

---

## 4. Troubleshooting Guide

### ❌ Error 500.19 - Internal Server Error
*   **Cause:** This is usually caused by a missing **URL Rewrite Module** or an invalid `web.config` file.
*   **Fix:** Ensure the URL Rewrite Module is installed. Re-check the `public/web.config` file for syntax errors.

### ❌ Error 500.0 - FastCGI Process Failure
*   **Cause:** PHP is failing to execute.
*   **Fix:** 
    *   Open a command prompt and run `php -v`. If it errors, you likely need to install the [Visual C++ Redistributable](https://aka.ms/vs/17/release/vc_redist.x64.exe).
    *   Check `C:\php\php.ini` for configuration errors.

### ❌ 404 Found or Routing Issues
*   **Cause:** If only the homepage works but other routes fail, URL rewriting is NOT working.
*   **Fix:** Verify `web.config` exists in the `public` folder and URL Rewrite is enabled in IIS.

### ❌ Permission Denied (Unable to write log/cache)
*   **Cause:** Incorrect folder permissions.
*   **Fix:** Run this command in PowerShell to quickly fix permissions:
    ```powershell
    $path = "C:\inetpub\wwwroot\lfews"
    icacls "$path\storage" /grant "IUSR:(OI)(CI)M" /T
    icacls "$path\storage" /grant "IIS_IUSRS:(OI)(CI)M" /T
    icacls "$path\bootstrap\cache" /grant "IUSR:(OI)(CI)M" /T
    icacls "$path\bootstrap\cache" /grant "IIS_IUSRS:(OI)(CI)M" /T
    ```

### ❌ "The link cannot be created" (Storage Link)
*   **Cause:** IIS/Windows requires Administrative privileges to create symlinks.
*   **Fix:** Open PowerShell as **Administrator** and run:
    ```bash
    php artisan storage:link
    ```

---

## 5. Background Tasks (Scheduler)

The project includes a `run_scheduler.bat` file. To run this automatically:
1.  Open **Task Scheduler**.
2.  Create a **Basic Task** named `LFEWS Scheduler`.
3.  **Trigger:** Daily (then set to repeat every 1 minute).
4.  **Action:** Start a Program.
5.  **Program/script:** `C:\inetpub\wwwroot\lfews\run_scheduler.bat`
6.  Ensure "Run whether user is logged on or not" is checked in the task properties.

---

## 6. Optimization for Production

Once the site is running, run these commands to boost performance:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
