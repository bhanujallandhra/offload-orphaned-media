# offload-orphaned-media
Directly pushes orphaned files to DigitalOcean Spaces via WP Offload Media. Includes public ACL enforcement and dependency checks.

Here is a complete `README.md` file you can use for your custom plugin. It covers the features, installation steps, usage instructions, and the important technical caveats we discussed regarding how the files are handled.

***

# WP Offload Media - Direct Orphan Uploader

A utility plugin for WordPress that scans your local `wp-content/uploads` directory for "orphaned" files (files that exist on your server but are not registered in the WordPress Media Library) and directly uploads them to your cloud storage bucket (like DigitalOcean Spaces or Amazon S3) via the WP Offload Media plugin.

## Features

* **Direct Cloud Push:** Uploads files directly to your cloud bucket using WP Offload Media's API, completely bypassing the WordPress Media Library database.
* **Public Permissions:** Automatically forces the `public-read` Access Control List (ACL) so your files are immediately visible on the web without 403 Forbidden errors.
* **Multisite Compatible:** Intelligently detects WordPress Network environments. Provides a dropdown in the Network Admin dashboard to safely scan specific sub-site upload directories.
* **Bulk Processing Queue:** Select multiple files and upload them in a sequential AJAX queue, preventing server timeouts (504 errors) when dealing with large batches.
* **Smart Search & Filtering:** Search for specific filenames (like missing logos or specific image names) before running the scan.
* **Thumbnail Toggle:** Choose whether to include or ignore WordPress-generated scaled thumbnails (e.g., `image-150x150.jpg`) during your scan.
* **Dependency Checking:** Automatically verifies that WP Offload Media is installed, active, and has a bucket configured before allowing scans.

## Requirements

* WordPress 5.0+
* **WP Offload Media** (Lite or Pro) installed, activated, and configured with a working bucket (e.g., DigitalOcean Spaces, Amazon S3, Google Cloud Storage).
* PHP 7.4+

## Installation

1. Navigate to your WordPress `wp-content/plugins/` directory.
2. Create a new folder named `wp-offload-orphans`.
3. Create a file inside that folder named `wp-offload-orphans.php` and paste the provided plugin code into it.
4. Go to your WordPress Admin dashboard.
   * **Single Site:** Go to **Plugins** and activate "WP Offload Media - Orphaned File Handler".
   * **Multisite:** Go to **Network Admin > Plugins** and click **Network Activate**.

## How to Use

### On a Single WordPress Site
1. Navigate to **Tools > Offload Orphans** in your left-hand sidebar.
2. (Optional) Enter a search term or toggle the thumbnail setting.
3. Click **Scan For Orphaned Files**.
4. Select the files you wish to upload using the checkboxes.
5. Click **Direct Upload to DO Spaces**.

### On a WordPress Multisite Network
1. Navigate to **My Sites > Network Admin > Dashboard**.
2. Go to **Settings > Offload Orphans** in the left-hand sidebar.
3. Select the specific sub-site you want to scan from the dropdown menu.
4. (Optional) Enter a search term or toggle the thumbnail setting.
5. Click **Scan For Orphaned Files**.
6. Select the files you wish to upload and click **Direct Upload to DO Spaces**.

## ⚠️ Important Caveats

Please read these technical notes before using the plugin on a production environment:

* **URL Rewriting:** Because this tool bypasses the `wp_insert_attachment` function to avoid flooding your Media Library with duplicates/thumbnails, WP Offload Media will *not* track these files in its database tables. Consequently, **WP Offload Media will not automatically rewrite the URLs for these specific files on your live website**. They are simply pushed to your cloud bucket maintaining their folder structure.
* **Local Files are Kept:** This plugin copies the file to your cloud storage. It **does not** automatically delete the local file from your server after a successful upload. 
* **Scan Limits:** To prevent memory exhaustion, the scanner is hard-coded to return a maximum of 50 files per scan. If you have thousands of orphaned files, you will need to process them in batches. 

## Disclaimer

This plugin interacts directly with your server's file system and your cloud storage provider's API. Always test on a staging or development clone of your website before running bulk operations on a live production server.
