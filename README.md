=== Orphaned Media Handler for WP Offload Media ===
Contributors: bhanujlndhra
Tags: media, offload, orphaned files, cloud storage
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 8.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Directly pushes orphaned files to cloud storage via WP Offload Media with public ACL enforcement and dependency checks.

== Description ==

This plugin scans your WordPress uploads directory for files that exist on your server but are not registered in the Media Library. It then pushes these "orphaned" files directly to your configured cloud storage (e.g., AWS S3, DigitalOcean Spaces) via WP Offload Media.

### Features
- Scan for orphaned files with search and filter options
- Direct upload to cloud storage without Media Library registration
- Multisite support with per-site scanning
- Memory of uploaded files to prevent duplicates
- Public ACL enforcement for cloud objects
- Thumbnail inclusion/exclusion control

### Requirements
- WordPress 5.8 or higher
- PHP 7.4 or higher
- WP Offload Media plugin installed and activated

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/orphaned-media-handler` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure WP Offload Media is installed and configured.
4. Navigate to Tools → Offload Orphans to use the plugin.

== Frequently Asked Questions ==

= Does this require WP Offload Media? =
Yes, this plugin is a companion tool for WP Offload Media and will not function without it.

= Will this delete files from my server? =
No, this plugin only uploads files to cloud storage. It does not delete local files.

= Can I use this on a multisite network? =
Yes, multisite is fully supported with per-site scanning capabilities.

== Changelog ==

= 8.1 =
* Fixed nonce verification for all AJAX calls
* Removed deprecated function usage
* Added database query caching
* Fixed unslash calls for sanitization
* Removed trademarked terms from descriptions
* Updated plugin metadata

= 8.0 =
* Initial release

== Upgrade Notice ==

= 8.1 =
Security improvements and code standards compliance. Update recommended.
