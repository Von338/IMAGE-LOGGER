<?php
/**
 * Plugin Name: EventMate Pro - Ultimate Event Management
 * Plugin URI: https://yourwebsite.com/eventmate-pro
 * Description: Un plugin WordPress tutto-in-uno per creare, gestire e promuovere eventi. Offre vendita di biglietti, gestione prenotazioni, mappe integrate, e molto altro.
 * Version: 1.0.0
 * Author: EventMate Team
 * License: GPL v2 or later
 * Text Domain: eventmate-pro
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EVENTMATE_PRO_VERSION', '1.0.0');
define('EVENTMATE_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EVENTMATE_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EVENTMATE_PRO_PLUGIN_FILE', __FILE__);

/**
 * Main EventMate Pro Class
 */
class EventMatePro {
    
    /**
     * Single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main EventMate Pro Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->includes();
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Include required files
     */
    public function includes() {
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-post-types.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-meta-boxes.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-shortcodes.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-admin.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-frontend.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-booking.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-woocommerce.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-widgets.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-maps.php';
        include_once EVENTMATE_PRO_PLUGIN_PATH . 'includes/class-eventmate-recurring.php';
    }
    
    /**
     * Init EventMate Pro when WordPress Initialises
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('eventmate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize classes
        new EventMate_Post_Types();
        new EventMate_Meta_Boxes();
        new EventMate_Shortcodes();
        new EventMate_Admin();
        new EventMate_Frontend();
        new EventMate_Booking();
        new EventMate_WooCommerce();
        new EventMate_Widgets();
        new EventMate_Maps();
        new EventMate_Recurring();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('eventmate-pro-style', EVENTMATE_PRO_PLUGIN_URL . 'assets/css/eventmate-pro.css', array(), EVENTMATE_PRO_VERSION);
        wp_enqueue_script('eventmate-pro-script', EVENTMATE_PRO_PLUGIN_URL . 'assets/js/eventmate-pro.js', array('jquery'), EVENTMATE_PRO_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('eventmate-pro-script', 'eventmate_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eventmate_nonce'),
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_style('eventmate-pro-admin-style', EVENTMATE_PRO_PLUGIN_URL . 'assets/css/admin.css', array(), EVENTMATE_PRO_VERSION);
        wp_enqueue_script('eventmate-pro-admin-script', EVENTMATE_PRO_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-datepicker'), EVENTMATE_PRO_VERSION, true);
        
        // Enqueue Google Maps API if needed
        $google_maps_api = get_option('eventmate_google_maps_api_key');
        if (!empty($google_maps_api)) {
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api . '&libraries=places', array(), null, true);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $this->set_default_options();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bookings table
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            user_name varchar(100) NOT NULL,
            user_email varchar(100) NOT NULL,
            user_phone varchar(20),
            booking_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'confirmed',
            notes text,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        add_option('eventmate_date_format', 'd/m/Y');
        add_option('eventmate_time_format', 'H:i');
        add_option('eventmate_currency', 'EUR');
        add_option('eventmate_currency_position', 'before');
        add_option('eventmate_google_maps_api_key', '');
        add_option('eventmate_default_view', 'grid');
        add_option('eventmate_events_per_page', 12);
    }
}

/**
 * Main instance of EventMate Pro
 */
function EventMate_Pro() {
    return EventMatePro::instance();
}

// Global for backwards compatibility
$GLOBALS['eventmate_pro'] = EventMate_Pro();