<?php
/**
 * Plugin Name: Helpers Finder
 * Description: A plugin to find and manage nearby helpers based on location
 * Version: 1.0.0
 * Author: Muhammad AbdulQaudir Akanfe
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nearby-helpers
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Define plugin constants
define('NEARBY_HELPERS_VERSION', '1.0.0');
define('NEARBY_HELPERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEARBY_HELPERS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once NEARBY_HELPERS_PLUGIN_DIR . 'admin/class-nearby-users-admin.php';
require_once NEARBY_HELPERS_PLUGIN_DIR . 'includes/class-nearby-users-cache.php';

// Initialize plugin
class NearbyHelpersFinder {
    private static $instance = null;
    private $version;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->version = NEARBY_HELPERS_VERSION;
        $this->init_hooks();
    }

    private function init_hooks() {
        // Initialize admin
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Add AJAX handlers
        add_action('wp_ajax_find_nearby_helpers', [$this, 'find_nearby_helpers']);
        add_action('wp_ajax_nopriv_find_nearby_helpers', [$this, 'find_nearby_helpers']);
        
        // Add shortcode
        add_shortcode('nearby_helpers', [$this, 'render_shortcode']);
    }

    public function init() {
        if (is_admin()) {
            new NearbyHelpersAdmin();
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'nearby-helpers',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'helper_locations';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            full_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            telephone varchar(20),
            address text NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            profile_picture varchar(255),
            additional_info text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_location (latitude, longitude)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function set_default_options() {
        add_option('helpers_radius', 10);
        add_option('helpers_cache_time', 60);
    }

    public function enqueue_scripts() {
        // Enqueue styles
        wp_enqueue_style(
            'nearby-helpers-style',
            NEARBY_HELPERS_PLUGIN_URL . 'nearby-users.css',
            array(),
            $this->version
        );

        // Enqueue main script first
        wp_enqueue_script(
            'nearby-helpers-script',
            NEARBY_HELPERS_PLUGIN_URL . 'nearby-users.js',
            array('jquery'),  // Only depend on jQuery
            $this->version,
            true
        );

        // Localize script
        wp_localize_script('nearby-helpers-script', 'nearbyHelpersData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nearby_helpers_nonce'),
            'radius' => get_option('helpers_radius', 10),
            'i18n' => array(
                'noResults' => __('No helpers found in your area.', 'nearby-helpers'),
                'error' => __('Error occurred while searching.', 'nearby-helpers'),
                'searching' => __('Searching...', 'nearby-helpers')
            )
        ));

        // Enqueue Google Maps API last
        wp_enqueue_script(
            'google-maps',
            'https://maps.googleapis.com/maps/api/js?key=API_KEY&libraries=places',
            array('nearby-helpers-script'),  // Depend on our script
            null,
            true
        );
    }

    public function find_nearby_helpers() {
        try {
            // Verify nonce
            check_ajax_referer('nearby_helpers_nonce', 'nonce');

            // Validate inputs
            $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
            $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

            if ($latitude === false || $longitude === false) {
                throw new Exception(__('Invalid coordinates provided.', 'nearby-helpers'));
            }

            $radius = get_option('helpers_radius', 10);
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'helper_locations';
            
            // Haversine formula with explicit CAST
            $sql = $wpdb->prepare(
                "SELECT *,
                ROUND(
                    (
                        3959 * acos(
                            cos(radians(%f)) * 
                            cos(radians(CAST(latitude AS DECIMAL(10,8)))) * 
                            cos(radians(CAST(longitude AS DECIMAL(11,8))) - radians(%f)) + 
                            sin(radians(%f)) * 
                            sin(radians(CAST(latitude AS DECIMAL(10,8))))
                        )
                    ), 
                    2
                ) AS distance
                FROM $table_name 
                HAVING distance < %d 
                ORDER BY distance",
                $latitude,
                $longitude,
                $latitude,
                $radius
            );
            
            $results = $wpdb->get_results($sql);
            
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            // Ensure distance is properly formatted
            $results = array_map(function($result) {
                $result->distance = floatval($result->distance);
                return $result;
            }, $results);

            wp_send_json_success($results);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    public function render_shortcode($atts) {
        // Buffer output
        ob_start();
        
        // Include template
        include NEARBY_HELPERS_PLUGIN_DIR . 'templates/finder-form.php';
        
        return ob_get_clean();
    }
}

// Initialize plugin
function nearby_helpers_finder() {
    return NearbyHelpersFinder::get_instance();
}

nearby_helpers_finder();