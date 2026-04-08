<?php
/**
 * Plugin Name:       WP Offload Media - Orphaned File Handler
 * Description: Directly pushes orphaned files to DigitalOcean Spaces via WP Offload Media. Includes public ACL enforcement and dependency checks.
 * Plugin URI:        https://atwebforge.com
 * Description:       Directly pushes orphaned files to DigitalOcean Spaces via WP Offload Media. Includes public ACL enforcement and dependency checks.
 * Version:           8.0
 * Author:            Bhanu Jallandhra
 * Author URI:        https://atwebforge.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       orphaned-media-handler
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WP_Offload_Orphans_Unified {

    public function __construct() {
        if ( is_multisite() ) {
            add_action( 'network_admin_menu', [ $this, 'add_network_admin_menu' ] );
        } else {
            add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        }

        add_action( 'wp_ajax_scan_orphaned_files', [ $this, 'ajax_scan_orphaned_files' ] );
        add_action( 'wp_ajax_process_orphaned_file', [ $this, 'ajax_process_orphaned_file' ] );
    }

    public function add_network_admin_menu() {
        add_submenu_page(
            'settings.php',
            'Offload Orphaned Files',
            'Offload Orphans',
            'manage_network_options',
            'wp-offload-orphans',
            [ $this, 'render_admin_page' ]
        );
    }

    public function add_admin_menu() {
        add_management_page(
            'Offload Orphaned Files',
            'Offload Orphans',
            'manage_options',
            'wp-offload-orphans',
            [ $this, 'render_admin_page' ]
        );
    }

    public function render_admin_page() {
        // Dependency Check: Ensure WP Offload Media is active before rendering the tool
        global $as3cf;
        if ( empty( $as3cf ) ) {
            ?>
            <div class="wrap">
                <h1>Offload Orphaned Files</h1>
                <div class="notice notice-error inline" style="margin-top: 15px;">
                    <p><strong>Error:</strong> The WP Offload Media plugin is either not installed or not activated. Please install and activate it to use this tool.</p>
                </div>
            </div>
            <?php
            return;
        }

        $is_multisite = is_multisite();
        ?>
        <div class="wrap">
            <h1>Direct Upload Orphaned Files</h1>
            <p>Scan your upload directory for files not registered in the Media Library. Selected files will be pushed <strong>directly</strong> to your DigitalOcean Space with public permissions, without being added to the WordPress database.</p>
            
            <?php if ( $is_multisite ) : 
                $sites = get_sites( [ 'number' => 1000 ] );
            ?>
                <div style="margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <label for="site-selector"><strong>Select Network Site to Scan: </strong></label>
                    <select id="site-selector">
                        <option value="">-- Choose a Site --</option>
                        <?php foreach ( $sites as $site ) : 
                            $details = get_blog_details( $site->blog_id );
                        ?>
                            <option value="<?php echo esc_attr( $site->blog_id ); ?>"><?php echo esc_html( $details->blogname . ' (' . $details->siteurl . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                <input type="text" id="search-orphan" placeholder="Search by filename (e.g., -1024x771.jpeg)" style="width: 300px; max-width: 100%;">
                
                <label style="background: #f0f0f1; padding: 5px 10px; border-radius: 4px; border: 1px solid #8c8f94;">
                    <input type="checkbox" id="include-thumbnails" value="1"> 
                    Include resized thumbnails (e.g., -1024x771.jpg)
                </label>

                <button id="scan-orphans-btn" class="button button-primary" <?php echo $is_multisite ? 'disabled' : ''; ?>>
                    Scan For Orphaned Files (Limit 50)
                </button>
            </div>
            
            <div id="scan-results" style="margin-top: 20px;"></div>

            <script>
            jQuery(document).ready(function($) {
                let isMultisite = <?php echo $is_multisite ? 'true' : 'false'; ?>;

                if (isMultisite) {
                    $('#site-selector').on('change', function() {
                        $('#scan-orphans-btn').prop('disabled', $(this).val() === "");
                    });
                }

                $('#scan-orphans-btn').on('click', function() {
                    let blogId = isMultisite ? $('#site-selector').val() : 1;
                    if(isMultisite && !blogId) return;

                    let searchTerm = $('#search-orphan').val().trim();
                    let includeThumbs = $('#include-thumbnails').is(':checked') ? 1 : 0;

                    $('#scan-results').html('<p>Scanning directory for unregistered files...</p>');
                    
                    $.post(ajaxurl, { action: 'scan_orphaned_files', blog_id: blogId, search: searchTerm, include_thumbs: includeThumbs }, function(response) {
                        if(response.success) {
                            if(response.data.length === 0) {
                                $('#scan-results').html('<p>No orphaned files found matching your criteria!</p>');
                                return;
                            }
                            
                            let html = '<h3>Found ' + response.data.length + ' Orphaned Files</h3>';
                            html += '<div style="margin-bottom: 15px;"><label><strong><input type="checkbox" id="select-all-orphans"> Select All</strong></label></div>';
                            html += '<ul style="list-style-type: none; padding-left: 0; background: #fff; padding: 15px; border: 1px solid #ccd0d4; max-height: 400px; overflow-y: auto;">';
                            
                            response.data.forEach(function(file) {
                                html += '<li style="margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 8px;">' + 
                                        '<label><input type="checkbox" class="orphan-checkbox" value="' + btoa(file) + '"> ' + 
                                        '<code>' + file.replace(/\\/g, '/') + '</code></label></li>';
                            });
                            
                            html += '</ul>';
                            html += '<button id="bulk-process-btn" class="button button-primary button-large" data-blog="' + blogId + '">Direct Upload to DO Spaces</button>';
                            
                            $('#scan-results').html(html);
                        } else {
                            $('#scan-results').html('<p style="color: red;">Error: ' + response.data + '</p>');
                        }
                    });
                });

                $(document).on('change', '#select-all-orphans', function() {
                    $('.orphan-checkbox').prop('checked', $(this).prop('checked'));
                });

                $(document).on('click', '#bulk-process-btn', function() {
                    let selectedFiles = [];
                    $('.orphan-checkbox:checked').each(function() {
                        selectedFiles.push($(this).val());
                    });

                    if (selectedFiles.length === 0) {
                        alert('Please select at least one file to process.');
                        return;
                    }

                    let blogId = $(this).data('blog');
                    let btn = $(this);
                    
                    btn.text('Uploading 0 / ' + selectedFiles.length + '...').attr('disabled', true);
                    $('.orphan-checkbox, #select-all-orphans').attr('disabled', true);

                    processQueue(selectedFiles, blogId, btn, 0, 0);
                });

                function processQueue(files, blogId, btn, index, successCount) {
                    if (index >= files.length) {
                        btn.text('Completed! Successfully uploaded: ' + successCount + ' of ' + files.length).addClass('button-success').css({'background': '#46b450', 'border-color': '#46b450', 'color': '#fff'});
                        $('.orphan-checkbox, #select-all-orphans').removeAttr('disabled');
                        return;
                    }

                    let file = files[index];
                    let listItem = $('input[value="' + file + '"]').closest('li');
                    
                    btn.text('Uploading ' + (index + 1) + ' / ' + files.length + '...');
                    listItem.append(' <span class="status-text" style="color: #ffb900; font-weight: bold;">Uploading...</span>');

                    $.post(ajaxurl, { action: 'process_orphaned_file', file: file, blog_id: blogId }, function(response) {
                        listItem.find('.status-text').remove();
                        if(response.success) {
                            listItem.append(' <span class="status-text" style="color: #46b450; font-weight: bold;">Pushed to DO Spaces (Public)!</span>');
                            listItem.find('input').prop('checked', false);
                            successCount++;
                        } else {
                            listItem.append(' <span class="status-text" style="color: red; font-weight: bold;">Failed: ' + response.data + '</span>');
                        }
                        
                        processQueue(files, blogId, btn, index + 1, successCount);
                    }).fail(function() {
                        listItem.find('.status-text').remove();
                        listItem.append(' <span class="status-text" style="color: red; font-weight: bold;">Server Timeout/Error</span>');
                        
                        processQueue(files, blogId, btn, index + 1, successCount);
                    });
                }
            });
            </script>
        </div>
        <?php
    }

    public function ajax_scan_orphaned_files() {
        $is_multisite = is_multisite();

        if ( $is_multisite ) {
            if ( empty( $_POST['blog_id'] ) ) wp_send_json_error( 'No site selected.' );
            $blog_id = intval( $_POST['blog_id'] );
            switch_to_blog( $blog_id );
        }

        $upload_dir = wp_upload_dir();
        $basedir = $upload_dir['basedir'];
        
        $search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $include_thumbs = ! empty( $_POST['include_thumbs'] ); 
        
        if ( ! is_dir( $basedir ) ) {
            if ( $is_multisite ) restore_current_blog();
            wp_send_json_error( 'Upload directory does not exist for this site.' );
        }

        $orphaned_files = [];
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $basedir, RecursiveDirectoryIterator::SKIP_DOTS ) );
        $limit = 50; 
        $count = 0;

        global $wpdb;

        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) continue;

            $filepath = $file->getPathname();
            $filename = basename( $filepath );
            
            if ( preg_match( '/(\.htaccess|\.php|\.DS_Store|\.html)$/i', $filepath ) ) continue;
            
            if ( ! $include_thumbs && preg_match( '/-\d+x\d+\.[a-z]{3,4}$/i', $filepath ) ) {
                continue; 
            }

            if ( ! empty( $search_term ) && stripos( $filename, $search_term ) === false ) {
                continue;
            }

            $relative_path = str_replace( trailingslashit( $basedir ), '', $filepath );

            $exists = $wpdb->get_var( $wpdb->prepare( "
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_wp_attached_file' 
                AND meta_value = %s LIMIT 1
            ", $relative_path ) );

            if ( ! $exists ) {
                $orphaned_files[] = $filepath;
                $count++;
            }

            if ( $count >= $limit ) break;
        }

        if ( $is_multisite ) restore_current_blog();

        wp_send_json_success( $orphaned_files );
    }

    public function ajax_process_orphaned_file() {
        if ( empty( $_POST['file'] ) ) {
            wp_send_json_error( 'Missing file data.' );
        }

        $is_multisite = is_multisite();

        if ( $is_multisite ) {
            if ( empty( $_POST['blog_id'] ) ) wp_send_json_error( 'Missing site data.' );
            $blog_id = intval( $_POST['blog_id'] );
            switch_to_blog( $blog_id );
        }

        $filepath = base64_decode( sanitize_text_field( $_POST['file'] ) );

        if ( ! file_exists( $filepath ) ) {
            if ( $is_multisite ) restore_current_blog();
            wp_send_json_error( 'File does not exist on server.' );
        }

        global $as3cf;
        if ( empty( $as3cf ) ) {
            if ( $is_multisite ) restore_current_blog();
            wp_send_json_error( 'WP Offload Media is not active.' );
        }

        $bucket = $as3cf->get_setting( 'bucket' );
        if ( empty( $bucket ) ) {
            if ( $is_multisite ) restore_current_blog();
            wp_send_json_error( 'No bucket configured in WP Offload Media settings.' );
        }

        $upload_dir = wp_upload_dir();
        $basedir = wp_normalize_path( $upload_dir['basedir'] );
        $normalized_filepath = wp_normalize_path( $filepath );
        
        $relative_path = ltrim( str_replace( $basedir, '', $normalized_filepath ), '/' );
        
        $prefix = $as3cf->get_setting( 'object-prefix' );
        $key = $prefix . $relative_path;

        $filetype = wp_check_filetype( basename( $filepath ), null );
        $mime_type = ! empty( $filetype['type'] ) ? $filetype['type'] : 'application/octet-stream';

        // Direct push with explicit Public ACL
        try {
            $provider_client = $as3cf->get_provider_client();
            $provider_client->putObject( [
                'Bucket'      => $bucket,
                'Key'         => $key,
                'SourceFile'  => $filepath,
                'ContentType' => $mime_type,
                'ACL'         => 'public-read', // Explicitly setting to public read
            ] );

            if ( $is_multisite ) restore_current_blog();
            wp_send_json_success( 'Pushed directly to DigitalOcean Spaces.' );

        } catch ( Exception $e ) {
            if ( $is_multisite ) restore_current_blog();
            wp_send_json_error( 'Upload failed: ' . $e->getMessage() );
        }
    }
}

new WP_Offload_Orphans_Unified();
