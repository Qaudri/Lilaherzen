<?php
class NearbyHelpersAdmin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_save_nearby_helper', [$this, 'handle_form_submission']);
        add_action('wp_ajax_delete_nearby_helper', [$this, 'handle_ajax_delete']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            'Helpers', 
            'Helpers', 
            'manage_options', 
            'helpers', 
            [$this, 'display_main_page'],
            'dashicons-groups',
            30
        );

        // Submenu pages
        add_submenu_page(
            'helpers',
            'Add/Edit Helper',
            'Add New Helper',
            'manage_options',
            'helpers-add',
            [$this, 'display_add_helper_page']
        );

        add_submenu_page(
            'helpers',
            'Settings',
            'Settings',
            'manage_options',
            'helpers-settings',
            [$this, 'display_settings_page']
        );
    }

    public function register_settings() {
        // Register settings section
        add_settings_section(
            'helpers_settings',
            'General Settings',
            [$this, 'settings_section_callback'],
            'helpers-settings'
        );

        // Register individual settings
        register_setting('helpers_settings', 'helpers_radius');
        register_setting('helpers_settings', 'helpers_cache_time');

        // Add settings fields
        add_settings_field(
            'helpers_radius',
            'Search Radius (miles)',
            [$this, 'radius_field_callback'],
            'helpers-settings',
            'helpers_settings'
        );

        add_settings_field(
            'helpers_cache_time',
            'Cache Duration (minutes)',
            [$this, 'cache_time_field_callback'],
            'helpers-settings',
            'helpers_settings'
        );
    }

    public function display_main_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'helper_locations';
        
        // Handle bulk actions
        if (isset($_POST['bulk_action']) && isset($_POST['helper_ids'])) {
            $this->handle_bulk_actions();
        }

        // Get all helpers with locations
        $helpers = $wpdb->get_results("SELECT * FROM $table_name ORDER BY full_name");
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Nearby helpers Management</h1>
            <a href="<?php echo admin_url('admin.php?page=helpers-add'); ?>" class="page-title-action">Add New helper</a>
            
            <form method="post">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value="-1">Bulk Actions</option>
                            <option value="delete">Delete</option>
                        </select>
                        <input type="submit" class="button action" value="Apply">
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox">
                            </td>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($helpers as $helper): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="helper_ids[]" value="<?php echo esc_attr($helper->id); ?>">
                            </th>
                            <td><?php echo esc_html($helper->full_name); ?></td>
                            <td><?php echo esc_html($helper->email); ?></td>
                            <td><?php echo esc_html($helper->telephone); ?></td>
                            <td><?php echo esc_html($helper->address); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=helpers-add&edit=' . $helper->id); ?>" 
                                   class="button-secondary">Edit</a>
                                <a href="#" class="button-secondary delete-helper" 
                                   data-id="<?php echo esc_attr($helper->id); ?>">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.delete-helper').on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this helper?')) {
                    var helperId = $(this).data('id');
                    $.post(ajaxurl, {
                        action: 'delete_helper',
                        helper_id: helperId,
                        nonce: '<?php echo wp_create_nonce("delete_helper"); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    public function display_add_helper_page() {
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $helper = null;
        
        if ($edit_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'helper_locations';
            $helper = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $edit_id ? 'Edit Helper' : 'Add New Helper'; ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_nearby_helper">
                <?php wp_nonce_field('save_nearby_helper', 'nearby_helper_nonce'); ?>
                <?php if ($edit_id): ?>
                    <input type="hidden" name="helper_id" value="<?php echo esc_attr($edit_id); ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="full_name">Full Name</label></th>
                        <td>
                            <input type="text" name="full_name" id="full_name" class="regular-text" 
                                   value="<?php echo $helper ? esc_attr($helper->full_name) : ''; ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="email">Email</label></th>
                        <td>
                            <input type="email" name="email" id="email" class="regular-text"
                                   value="<?php echo $helper ? esc_attr($helper->email) : ''; ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="telephone">Phone</label></th>
                        <td>
                            <input type="tel" name="telephone" id="telephone" class="regular-text"
                                   value="<?php echo $helper ? esc_attr($helper->telephone) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="address">Address</label></th>
                        <td>
                            <input type="text" name="address" id="address" class="regular-text"
                                   value="<?php echo $helper ? esc_attr($helper->address) : ''; ?>" required>
                            <input type="hidden" name="latitude" id="latitude" 
                                   value="<?php echo $helper ? esc_attr($helper->latitude) : ''; ?>">
                            <input type="hidden" name="longitude" id="longitude"
                                   value="<?php echo $helper ? esc_attr($helper->longitude) : ''; ?>">
                            <p class="description">Start typing to autocomplete address</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="profile_picture">Profile Picture</label></th>
                        <td>
                            <?php if ($helper && $helper->profile_picture): ?>
                                <img src="<?php echo esc_url($helper->profile_picture); ?>" 
                                     style="max-width: 150px; margin-bottom: 10px;"><br>
                            <?php endif; ?>
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="additional_info">Additional Information</label></th>
                        <td>
                            <textarea name="additional_info" id="additional_info" class="large-text" rows="5"><?php 
                                echo $helper ? esc_textarea($helper->additional_info) : ''; 
                            ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button($edit_id ? 'Update Helper' : 'Add Helper'); ?>
            </form>
        </div>
        <?php
    }

    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if settings were saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'helpers_messages',
                'helpers_message',
                'Settings Saved',
                'updated'
            );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('helpers_messages'); ?>

            <form action="options.php" method="post">
                <?php
                settings_fields('helpers_settings');
                do_settings_sections('helpers-settings');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    // Settings field callbacks
    public function settings_section_callback() {
        echo '<p>Configure the settings for the Nearby Helpers plugin.</p>';
    }

    public function radius_field_callback() {
        $radius = get_option('helpers_radius', 10);
        ?>
        <input type="number" 
               name="helpers_radius" 
               value="<?php echo esc_attr($radius); ?>" 
               min="1" 
               max="100" 
               step="1">
        <p class="description">Maximum distance (in miles) to search for helpers.</p>
        <?php
    }

    public function cache_time_field_callback() {
        $cache_time = get_option('helpers_cache_time', 60);
        ?>
        <input type="number" 
               name="helpers_cache_time" 
               value="<?php echo esc_attr($cache_time); ?>" 
               min="1" 
               max="1440" 
               step="1">
        <p class="description">How long to cache search results (in minutes).</p>
        <?php
    }

    private function handle_bulk_actions() {
        if (!isset($_POST['helper_ids']) || !is_array($_POST['helper_ids'])) {
            return;
        }

        $action = $_POST['bulk_action'];
        if ($action === 'delete') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'helper_locations';
            $ids = array_map('intval', $_POST['helper_ids']);
            
            foreach ($ids as $id) {
                $wpdb->delete($table_name, ['id' => $id], ['%d']);
            }
            
            add_settings_error(
                'helpers',
                'helpers_deleted',
                'Selected helpers have been deleted.',
                'updated'
            );
        }
    }

    public function handle_form_submission() {
        try {
            // Debug incoming data
            error_log('Form submission started');
            error_log('POST data: ' . print_r($_POST, true));
            error_log('FILES data: ' . print_r($_FILES, true));

            // Verify nonce
            if (!isset($_POST['nearby_helper_nonce']) || !wp_verify_nonce($_POST['nearby_helper_nonce'], 'save_nearby_helper')) {
                error_log('Nonce verification failed');
                wp_die('Security check failed');
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'helper_locations';
            
            // Prepare data
            $data = [
                'full_name' => sanitize_text_field($_POST['full_name']),
                'email' => sanitize_email($_POST['email']),
                'telephone' => sanitize_text_field($_POST['telephone']),
                'address' => sanitize_text_field($_POST['address']),
                'latitude' => !empty($_POST['latitude']) ? floatval($_POST['latitude']) : 0,
                'longitude' => !empty($_POST['longitude']) ? floatval($_POST['longitude']) : 0,
                'additional_info' => sanitize_textarea_field($_POST['additional_info'])
            ];

            // Handle profile picture upload
            if (!empty($_FILES['profile_picture']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $upload_overrides = array('test_form' => false);
                $file = $_FILES['profile_picture'];
                
                // Check if file is an image
                $file_type = wp_check_filetype($file['name']);
                if (!in_array($file_type['type'], array('image/jpeg', 'image/png', 'image/gif'))) {
                    throw new Exception('Invalid file type. Please upload an image.');
                }

                $movefile = wp_handle_upload($file, $upload_overrides);

                if ($movefile && !isset($movefile['error'])) {
                    $data['profile_picture'] = $movefile['url'];
                } else {
                    error_log('Profile picture upload error: ' . $movefile['error']);
                }
            }

            error_log('Processed data before save: ' . print_r($data, true));

            // Insert or update
            if (isset($_POST['helper_id']) && !empty($_POST['helper_id'])) {
                $result = $wpdb->update(
                    $table_name,
                    $data,
                    ['id' => intval($_POST['helper_id'])],
                    array_fill(0, count($data), '%s'),
                    ['%d']
                );
            } else {
                $result = $wpdb->insert(
                    $table_name,
                    $data,
                    array_fill(0, count($data), '%s')
                );
            }

            error_log('Database operation result: ' . print_r($result, true));
            if ($wpdb->last_error) {
                error_log('Database error: ' . $wpdb->last_error);
            }

            // Redirect back with success message
            wp_redirect(add_query_arg(
                ['page' => 'helpers', 'message' => 'success'],
                admin_url('admin.php')
            ));
            exit;

        } catch (Exception $e) {
            error_log('Exception caught: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_die('Error: ' . $e->getMessage());
        }
    }

    public function handle_ajax_delete() {
        check_ajax_referer('delete_nearby_helper', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'helper_locations';
        $helper_id = intval($_POST['helper_id']);
        
        $result = $wpdb->delete($table_name, ['id' => $helper_id], ['%d']);
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Delete failed');
        }
    }

    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'helpers') === false) {
            return;
        }

        // Enqueue jQuery first
        wp_enqueue_script('jquery');

        // Enqueue our admin script first
        wp_enqueue_script(
            'helpers-admin',
            plugins_url('js/admin.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );

        // Then enqueue Google Maps with proper async loading
        wp_enqueue_script(
            'google-maps',
            'https://maps.googleapis.com/maps/api/js?key=API_KEY&libraries=places&callback=initGoogleMaps',
            array(),
            null,
            true
        );
        
        // Add defer attribute to Google Maps script
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'google-maps') {
                return str_replace(' src', ' async defer src', $tag);
            }
            return $tag;
        }, 10, 2);
    }
}