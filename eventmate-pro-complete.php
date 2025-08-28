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
    
    protected static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        $this->init_hooks();
        $this->init_classes();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    private function init_classes() {
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
    
    public function init() {
        load_plugin_textdomain('eventmate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('eventmate-pro-style', EVENTMATE_PRO_PLUGIN_URL . 'assets/css/style.css', array(), EVENTMATE_PRO_VERSION);
        wp_enqueue_script('eventmate-pro-script', EVENTMATE_PRO_PLUGIN_URL . 'assets/js/script.js', array('jquery'), EVENTMATE_PRO_VERSION, true);
        
        // Add inline CSS
        wp_add_inline_style('eventmate-pro-style', $this->get_inline_css());
        
        // Add inline JS
        wp_add_inline_script('eventmate-pro-script', $this->get_inline_js());
        
        wp_localize_script('eventmate-pro-script', 'eventmate_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eventmate_nonce'),
        ));
    }
    
    public function admin_enqueue_scripts() {
        wp_enqueue_style('eventmate-pro-admin-style', EVENTMATE_PRO_PLUGIN_URL . 'assets/css/admin.css', array(), EVENTMATE_PRO_VERSION);
        wp_enqueue_script('eventmate-pro-admin-script', EVENTMATE_PRO_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-datepicker'), EVENTMATE_PRO_VERSION, true);
        
        // Add inline admin CSS
        wp_add_inline_style('eventmate-pro-admin-style', $this->get_admin_css());
        
        $google_maps_api = get_option('eventmate_google_maps_api_key');
        if (!empty($google_maps_api)) {
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api . '&libraries=places', array(), null, true);
        }
    }
    
    public function activate() {
        $this->create_tables();
        flush_rewrite_rules();
        $this->set_default_options();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
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
    
    private function set_default_options() {
        add_option('eventmate_date_format', 'd/m/Y');
        add_option('eventmate_time_format', 'H:i');
        add_option('eventmate_currency', 'EUR');
        add_option('eventmate_currency_position', 'before');
        add_option('eventmate_google_maps_api_key', '');
        add_option('eventmate_default_view', 'grid');
        add_option('eventmate_events_per_page', 12);
    }
    
    private function get_inline_css() {
        return "
        .eventmate-events-container {
            margin: 20px 0;
        }
        .eventmate-events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .eventmate-event-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .eventmate-event-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .eventmate-event-image {
            position: relative;
            overflow: hidden;
            height: 200px;
        }
        .eventmate-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .eventmate-no-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        .eventmate-event-date-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            padding: 5px 10px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }
        .eventmate-date-day {
            display: block;
            font-size: 18px;
            color: #333;
        }
        .eventmate-date-month {
            display: block;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .eventmate-event-content {
            padding: 20px;
        }
        .eventmate-event-title {
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 600;
        }
        .eventmate-event-title a {
            color: #333;
            text-decoration: none;
        }
        .eventmate-event-title a:hover {
            color: #667eea;
        }
        .eventmate-event-meta {
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }
        .eventmate-meta-item {
            margin: 5px 0;
            display: flex;
            align-items: center;
        }
        .eventmate-icon {
            margin-right: 8px;
            width: 16px;
        }
        .eventmate-event-excerpt {
            margin: 15px 0;
            color: #555;
            line-height: 1.5;
        }
        .eventmate-event-actions {
            margin-top: 15px;
        }
        .eventmate-btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .eventmate-btn-primary {
            background: #667eea;
            color: white;
        }
        .eventmate-btn-primary:hover {
            background: #5a6fd8;
            color: white;
        }
        .eventmate-btn-secondary {
            background: #6c757d;
            color: white;
        }
        .eventmate-btn-secondary:hover {
            background: #5a6268;
            color: white;
        }
        .eventmate-events-list .eventmate-event-item {
            display: flex;
            margin-bottom: 20px;
            padding: 20px;
        }
        .eventmate-event-date {
            flex: 0 0 100px;
            text-align: center;
            margin-right: 20px;
        }
        .eventmate-date-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .eventmate-event-details {
            flex: 1;
        }
        .eventmate-event-details-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .eventmate-booking-form-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .eventmate-form-group {
            margin-bottom: 20px;
        }
        .eventmate-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .eventmate-form-group input,
        .eventmate-form-group textarea,
        .eventmate-form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .eventmate-form-group input:focus,
        .eventmate-form-group textarea:focus,
        .eventmate-form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .required {
            color: #e74c3c;
        }
        .eventmate-map-container {
            margin: 20px 0;
        }
        .eventmate-map {
            width: 100%;
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
        }
        .eventmate-map-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 0 0 8px 8px;
        }
        .eventmate-widget {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .eventmate-widget-title {
            background: #667eea;
            color: white;
            padding: 15px;
            margin: 0;
            font-size: 16px;
        }
        .eventmate-widget-content {
            padding: 15px;
        }
        .eventmate-upcoming-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .eventmate-upcoming-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .eventmate-upcoming-image {
            flex: 0 0 60px;
            margin-right: 15px;
        }
        .eventmate-upcoming-image img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .eventmate-upcoming-content h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        .eventmate-upcoming-date,
        .eventmate-upcoming-location {
            font-size: 12px;
            color: #666;
            margin: 2px 0;
        }
        @media (max-width: 768px) {
            .eventmate-events-grid {
                grid-template-columns: 1fr;
            }
            .eventmate-events-list .eventmate-event-item {
                flex-direction: column;
            }
            .eventmate-event-date {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
        ";
    }
    
    private function get_inline_js() {
        return "
        jQuery(document).ready(function($) {
            // Event booking form handler
            $('.eventmate-booking-form').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var submitBtn = form.find('button[type=\"submit\"]');
                var messageDiv = form.find('.eventmate-booking-message');
                
                // Show loading state
                submitBtn.find('.button-text').hide();
                submitBtn.find('.button-loading').show();
                submitBtn.prop('disabled', true);
                
                var formData = {
                    action: 'eventmate_submit_booking',
                    nonce: eventmate_ajax.nonce,
                    event_id: form.data('event-id'),
                    name: form.find('#booking_name').val(),
                    email: form.find('#booking_email').val(),
                    phone: form.find('#booking_phone').val(),
                    notes: form.find('#booking_notes').val()
                };
                
                $.post(eventmate_ajax.ajax_url, formData, function(response) {
                    if (response.success) {
                        messageDiv.html('<div class=\"success\">' + response.data.message + '</div>').show();
                        form[0].reset();
                    } else {
                        messageDiv.html('<div class=\"error\">' + response.data.message + '</div>').show();
                    }
                }).fail(function() {
                    messageDiv.html('<div class=\"error\">Errore di connessione. Riprova.</div>').show();
                }).always(function() {
                    // Reset button state
                    submitBtn.find('.button-text').show();
                    submitBtn.find('.button-loading').hide();
                    submitBtn.prop('disabled', false);
                });
            });
            
            // Google Maps initialization
            if (typeof google !== 'undefined' && google.maps) {
                initEventMateMaps();
            }
        });
        ";
    }
    
    private function get_admin_css() {
        return "
        .eventmate-meta-table {
            width: 100%;
        }
        .eventmate-meta-table th {
            width: 150px;
            text-align: left;
            padding: 10px 0;
            vertical-align: top;
        }
        .eventmate-meta-table td {
            padding: 10px 0;
        }
        .eventmate-admin-page {
            max-width: 1200px;
        }
        .eventmate-settings-section {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .eventmate-bookings-table {
            width: 100%;
            border-collapse: collapse;
        }
        .eventmate-bookings-table th,
        .eventmate-bookings-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .eventmate-bookings-table th {
            background: #f5f5f5;
            font-weight: 600;
        }
        ";
    }
}

/**
 * Custom Post Types Class
 */
class EventMate_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('the_content', array($this, 'add_event_details_to_content'));
    }
    
    public function register_post_types() {
        $labels = array(
            'name' => 'Eventi',
            'singular_name' => 'Evento',
            'menu_name' => 'Eventi',
            'add_new' => 'Aggiungi Nuovo',
            'add_new_item' => 'Aggiungi Nuovo Evento',
            'edit_item' => 'Modifica Evento',
            'new_item' => 'Nuovo Evento',
            'view_item' => 'Visualizza Evento',
            'search_items' => 'Cerca Eventi',
            'not_found' => 'Non trovato',
            'not_found_in_trash' => 'Non trovato nel cestino',
        );
        
        $args = array(
            'labels' => $labels,
            'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions'),
            'taxonomies' => array('event_category', 'event_tag'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-calendar-alt',
            'has_archive' => true,
            'rewrite' => array('slug' => 'eventi'),
            'show_in_rest' => true,
        );
        
        register_post_type('eventmate_event', $args);
    }
    
    public function register_taxonomies() {
        // Event Categories
        register_taxonomy('event_category', array('eventmate_event'), array(
            'labels' => array(
                'name' => 'Categorie Eventi',
                'singular_name' => 'Categoria Evento',
                'menu_name' => 'Categorie',
            ),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => 'categoria-evento'),
        ));
        
        // Event Tags
        register_taxonomy('event_tag', array('eventmate_event'), array(
            'labels' => array(
                'name' => 'Tag Eventi',
                'singular_name' => 'Tag Evento',
                'menu_name' => 'Tag',
            ),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => 'tag-evento'),
        ));
    }
    
    public function add_event_details_to_content($content) {
        if (is_singular('eventmate_event') && is_main_query()) {
            $event_details = $this->get_event_details_html(get_the_ID());
            $content = $event_details . $content;
        }
        return $content;
    }
    
    public function get_event_details_html($event_id) {
        $start_date = get_post_meta($event_id, '_eventmate_start_date', true);
        $end_date = get_post_meta($event_id, '_eventmate_end_date', true);
        $location = get_post_meta($event_id, '_eventmate_location', true);
        $address = get_post_meta($event_id, '_eventmate_address', true);
        $price = get_post_meta($event_id, '_eventmate_price', true);
        $is_free = get_post_meta($event_id, '_eventmate_is_free', true);
        
        ob_start();
        ?>
        <div class="eventmate-event-details-container">
            <div class="eventmate-event-meta">
                <?php if ($start_date): ?>
                    <div class="eventmate-meta-item">
                        <span class="eventmate-icon">📅</span>
                        <strong>Data:</strong>
                        <?php 
                        echo date_i18n('d/m/Y H:i', strtotime($start_date));
                        if ($end_date && $end_date != $start_date) {
                            echo ' - ' . date_i18n('d/m/Y H:i', strtotime($end_date));
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($location): ?>
                    <div class="eventmate-meta-item">
                        <span class="eventmate-icon">📍</span>
                        <strong>Luogo:</strong> <?php echo esc_html($location); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($address): ?>
                    <div class="eventmate-meta-item">
                        <span class="eventmate-icon">🗺️</span>
                        <strong>Indirizzo:</strong> <?php echo esc_html($address); ?>
                    </div>
                <?php endif; ?>
                
                <div class="eventmate-meta-item">
                    <span class="eventmate-icon">💰</span>
                    <strong>Prezzo:</strong>
                    <?php 
                    if ($is_free == 'yes') {
                        echo 'Gratuito';
                    } else {
                        echo 'EUR ' . esc_html($price);
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Meta Boxes Class
 */
class EventMate_Meta_Boxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'eventmate-event-details',
            'Dettagli Evento',
            array($this, 'event_details_meta_box'),
            'eventmate_event',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eventmate-event-booking',
            'Impostazioni Prenotazione',
            array($this, 'event_booking_meta_box'),
            'eventmate_event',
            'side',
            'default'
        );
    }
    
    public function event_details_meta_box($post) {
        wp_nonce_field('eventmate_meta_box_nonce', 'eventmate_meta_box_nonce');
        
        $start_date = get_post_meta($post->ID, '_eventmate_start_date', true);
        $end_date = get_post_meta($post->ID, '_eventmate_end_date', true);
        $location = get_post_meta($post->ID, '_eventmate_location', true);
        $address = get_post_meta($post->ID, '_eventmate_address', true);
        $price = get_post_meta($post->ID, '_eventmate_price', true);
        $is_free = get_post_meta($post->ID, '_eventmate_is_free', true);
        $max_attendees = get_post_meta($post->ID, '_eventmate_max_attendees', true);
        ?>
        
        <table class="form-table eventmate-meta-table">
            <tr>
                <th><label for="eventmate_start_date">Data e Ora Inizio</label></th>
                <td>
                    <input type="datetime-local" id="eventmate_start_date" name="eventmate_start_date" 
                           value="<?php echo esc_attr($start_date ? date('Y-m-d\TH:i', strtotime($start_date)) : ''); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_end_date">Data e Ora Fine</label></th>
                <td>
                    <input type="datetime-local" id="eventmate_end_date" name="eventmate_end_date" 
                           value="<?php echo esc_attr($end_date ? date('Y-m-d\TH:i', strtotime($end_date)) : ''); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_location">Luogo</label></th>
                <td>
                    <input type="text" id="eventmate_location" name="eventmate_location" 
                           value="<?php echo esc_attr($location); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_address">Indirizzo</label></th>
                <td>
                    <input type="text" id="eventmate_address" name="eventmate_address" 
                           value="<?php echo esc_attr($address); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_is_free">Evento Gratuito</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="eventmate_is_free" name="eventmate_is_free" value="yes" 
                               <?php checked($is_free, 'yes'); ?> />
                        Questo evento è gratuito
                    </label>
                </td>
            </tr>
            
            <tr class="eventmate-price-row" <?php echo ($is_free == 'yes') ? 'style="display:none;"' : ''; ?>>
                <th><label for="eventmate_price">Prezzo</label></th>
                <td>
                    <input type="number" id="eventmate_price" name="eventmate_price" 
                           value="<?php echo esc_attr($price); ?>" min="0" step="0.01" class="small-text" />
                    <span>EUR</span>
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_max_attendees">Massimo Partecipanti</label></th>
                <td>
                    <input type="number" id="eventmate_max_attendees" name="eventmate_max_attendees" 
                           value="<?php echo esc_attr($max_attendees); ?>" min="0" class="small-text" />
                    <p class="description">Lascia vuoto per illimitato</p>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#eventmate_is_free').change(function() {
                if ($(this).is(':checked')) {
                    $('.eventmate-price-row').hide();
                } else {
                    $('.eventmate-price-row').show();
                }
            });
        });
        </script>
        <?php
    }
    
    public function event_booking_meta_box($post) {
        $enable_booking = get_post_meta($post->ID, '_eventmate_enable_booking', true);
        $booking_type = get_post_meta($post->ID, '_eventmate_booking_type', true);
        
        $woocommerce_active = class_exists('WooCommerce');
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="eventmate_enable_booking">Abilita Prenotazioni</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="eventmate_enable_booking" name="eventmate_enable_booking" value="yes" 
                               <?php checked($enable_booking, 'yes'); ?> />
                        Permetti prenotazioni
                    </label>
                </td>
            </tr>
            
            <tr class="eventmate-booking-options" <?php echo ($enable_booking != 'yes') ? 'style="display:none;"' : ''; ?>>
                <th><label for="eventmate_booking_type">Tipo Prenotazione</label></th>
                <td>
                    <select id="eventmate_booking_type" name="eventmate_booking_type">
                        <option value="simple" <?php selected($booking_type, 'simple'); ?>>Prenotazione Semplice</option>
                        <?php if ($woocommerce_active): ?>
                            <option value="woocommerce" <?php selected($booking_type, 'woocommerce'); ?>>WooCommerce</option>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#eventmate_enable_booking').change(function() {
                if ($(this).is(':checked')) {
                    $('.eventmate-booking-options').show();
                } else {
                    $('.eventmate-booking-options').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['eventmate_meta_box_nonce']) || !wp_verify_nonce($_POST['eventmate_meta_box_nonce'], 'eventmate_meta_box_nonce')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (get_post_type($post_id) != 'eventmate_event') {
            return;
        }
        
        $meta_fields = array(
            'eventmate_start_date',
            'eventmate_end_date',
            'eventmate_location',
            'eventmate_address',
            'eventmate_price',
            'eventmate_is_free',
            'eventmate_max_attendees',
            'eventmate_enable_booking',
            'eventmate_booking_type'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                
                if (in_array($field, array('eventmate_start_date', 'eventmate_end_date'))) {
                    if (!empty($value)) {
                        $value = date('Y-m-d H:i:s', strtotime($value));
                    }
                }
                
                update_post_meta($post_id, '_' . $field, $value);
            } else {
                if (in_array($field, array('eventmate_is_free', 'eventmate_enable_booking'))) {
                    update_post_meta($post_id, '_' . $field, 'no');
                }
            }
        }
    }
}

/**
 * Shortcodes Class
 */
class EventMate_Shortcodes {
    
    public function __construct() {
        add_action('init', array($this, 'init_shortcodes'));
    }
    
    public function init_shortcodes() {
        add_shortcode('event_list', array($this, 'event_list_shortcode'));
        add_shortcode('event_single', array($this, 'event_single_shortcode'));
        add_shortcode('event_booking_form', array($this, 'event_booking_form_shortcode'));
        add_shortcode('upcoming_events', array($this, 'upcoming_events_shortcode'));
        add_shortcode('event_map', array($this, 'event_map_shortcode'));
    }
    
    public function event_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => 'grid',
            'limit' => 12,
            'category' => '',
            'tag' => '',
            'orderby' => 'date',
            'order' => 'ASC',
            'show_past' => 'no',
        ), $atts);
        
        $query_args = array(
            'post_type' => 'eventmate_event',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(),
        );
        
        if ($atts['orderby'] == 'date') {
            $query_args['meta_key'] = '_eventmate_start_date';
            $query_args['orderby'] = 'meta_value';
            $query_args['order'] = $atts['order'];
        } else {
            $query_args['orderby'] = $atts['orderby'];
            $query_args['order'] = $atts['order'];
        }
        
        if ($atts['show_past'] == 'no') {
            $query_args['meta_query'][] = array(
                'key' => '_eventmate_start_date',
                'value' => current_time('Y-m-d H:i:s'),
                'compare' => '>=',
                'type' => 'DATETIME'
            );
        }
        
        if (!empty($atts['category'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'event_category',
                'field' => 'slug',
                'terms' => explode(',', $atts['category'])
            );
        }
        
        $events_query = new WP_Query($query_args);
        
        ob_start();
        
        if ($events_query->have_posts()) {
            echo '<div class="eventmate-events-container eventmate-' . esc_attr($atts['view']) . '-view">';
            
            if ($atts['view'] == 'grid') {
                echo '<div class="eventmate-events-grid">';
                while ($events_query->have_posts()) {
                    $events_query->the_post();
                    $this->render_event_grid_item(get_the_ID());
                }
                echo '</div>';
            } else {
                echo '<div class="eventmate-events-list">';
                while ($events_query->have_posts()) {
                    $events_query->the_post();
                    $this->render_event_list_item(get_the_ID());
                }
                echo '</div>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="eventmate-no-events">';
            echo '<p>Nessun evento trovato.</p>';
            echo '</div>';
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    private function render_event_grid_item($event_id) {
        $start_date = get_post_meta($event_id, '_eventmate_start_date', true);
        $location = get_post_meta($event_id, '_eventmate_location', true);
        $price = get_post_meta($event_id, '_eventmate_price', true);
        $is_free = get_post_meta($event_id, '_eventmate_is_free', true);
        
        ?>
        <div class="eventmate-event-item eventmate-grid-item">
            <div class="eventmate-event-image">
                <?php if (has_post_thumbnail($event_id)): ?>
                    <a href="<?php echo get_permalink($event_id); ?>">
                        <?php echo get_the_post_thumbnail($event_id, 'medium', array('class' => 'eventmate-thumbnail')); ?>
                    </a>
                <?php else: ?>
                    <div class="eventmate-no-image">
                        <span class="eventmate-no-image-icon">📅</span>
                    </div>
                <?php endif; ?>
                
                <div class="eventmate-event-date-badge">
                    <?php if ($start_date): ?>
                        <span class="eventmate-date-day"><?php echo date_i18n('d', strtotime($start_date)); ?></span>
                        <span class="eventmate-date-month"><?php echo date_i18n('M', strtotime($start_date)); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="eventmate-event-content">
                <h3 class="eventmate-event-title">
                    <a href="<?php echo get_permalink($event_id); ?>"><?php echo get_the_title($event_id); ?></a>
                </h3>
                
                <div class="eventmate-event-meta">
                    <?php if ($start_date): ?>
                        <div class="eventmate-meta-item">
                            <span class="eventmate-icon">🕒</span>
                            <?php echo date_i18n('H:i', strtotime($start_date)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                        <div class="eventmate-meta-item">
                            <span class="eventmate-icon">📍</span>
                            <?php echo esc_html($location); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="eventmate-meta-item">
                        <span class="eventmate-icon">💰</span>
                        <?php 
                        if ($is_free == 'yes') {
                            echo 'Gratuito';
                        } else {
                            echo 'EUR ' . esc_html($price);
                        }
                        ?>
                    </div>
                </div>
                
                <div class="eventmate-event-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt($event_id), 20); ?>
                </div>
                
                <div class="eventmate-event-actions">
                    <a href="<?php echo get_permalink($event_id); ?>" class="eventmate-btn eventmate-btn-primary">
                        Dettagli
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_event_list_item($event_id) {
        $start_date = get_post_meta($event_id, '_eventmate_start_date', true);
        $location = get_post_meta($event_id, '_eventmate_location', true);
        $price = get_post_meta($event_id, '_eventmate_price', true);
        $is_free = get_post_meta($event_id, '_eventmate_is_free', true);
        
        ?>
        <div class="eventmate-event-item eventmate-list-item">
            <div class="eventmate-event-date">
                <?php if ($start_date): ?>
                    <div class="eventmate-date-display">
                        <span class="eventmate-date-day"><?php echo date_i18n('d', strtotime($start_date)); ?></span>
                        <span class="eventmate-date-month"><?php echo date_i18n('M', strtotime($start_date)); ?></span>
                        <span class="eventmate-date-year"><?php echo date_i18n('Y', strtotime($start_date)); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="eventmate-event-details">
                <h3 class="eventmate-event-title">
                    <a href="<?php echo get_permalink($event_id); ?>"><?php echo get_the_title($event_id); ?></a>
                </h3>
                
                <div class="eventmate-event-meta">
                    <?php if ($start_date): ?>
                        <span>🕒 <?php echo date_i18n('H:i', strtotime($start_date)); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                        <span>📍 <?php echo esc_html($location); ?></span>
                    <?php endif; ?>
                    
                    <span>
                        💰 
                        <?php 
                        if ($is_free == 'yes') {
                            echo 'Gratuito';
                        } else {
                            echo 'EUR ' . esc_html($price);
                        }
                        ?>
                    </span>
                </div>
                
                <div class="eventmate-event-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt($event_id), 30); ?>
                </div>
            </div>
            
            <div class="eventmate-event-actions">
                <a href="<?php echo get_permalink($event_id); ?>" class="eventmate-btn eventmate-btn-primary">
                    Dettagli
                </a>
            </div>
        </div>
        <?php
    }
    
    public function event_single_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        if (empty($atts['id'])) {
            return '<p>ID evento non specificato.</p>';
        }
        
        $event = get_post($atts['id']);
        
        if (!$event || $event->post_type != 'eventmate_event') {
            return '<p>Evento non trovato.</p>';
        }
        
        ob_start();
        
        echo '<div class="eventmate-single-event">';
        echo '<h2>' . esc_html($event->post_title) . '</h2>';
        
        if (has_post_thumbnail($event->ID)) {
            echo '<div class="eventmate-event-featured-image">';
            echo get_the_post_thumbnail($event->ID, 'large');
            echo '</div>';
        }
        
        $post_types = new EventMate_Post_Types();
        echo $post_types->get_event_details_html($event->ID);
        
        echo '<div class="eventmate-event-content">';
        echo apply_filters('the_content', $event->post_content);
        echo '</div>';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    public function event_booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        if (empty($atts['id'])) {
            global $post;
            if ($post && $post->post_type == 'eventmate_event') {
                $atts['id'] = $post->ID;
            } else {
                return '<p>ID evento non specificato.</p>';
            }
        }
        
        $booking = new EventMate_Booking();
        return $booking->render_booking_form($atts['id']);
    }
    
    public function upcoming_events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'show_image' => 'yes',
            'show_date' => 'yes',
            'show_location' => 'yes',
        ), $atts);
        
        $query_args = array(
            'post_type' => 'eventmate_event',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_key' => '_eventmate_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_eventmate_start_date',
                    'value' => current_time('Y-m-d H:i:s'),
                    'compare' => '>=',
                    'type' => 'DATETIME'
                )
            )
        );
        
        $events_query = new WP_Query($query_args);
        
        ob_start();
        
        if ($events_query->have_posts()) {
            echo '<div class="eventmate-upcoming-events">';
            
            while ($events_query->have_posts()) {
                $events_query->the_post();
                $event_id = get_the_ID();
                $start_date = get_post_meta($event_id, '_eventmate_start_date', true);
                $location = get_post_meta($event_id, '_eventmate_location', true);
                
                echo '<div class="eventmate-upcoming-item">';
                
                if ($atts['show_image'] == 'yes' && has_post_thumbnail($event_id)) {
                    echo '<div class="eventmate-upcoming-image">';
                    echo '<a href="' . get_permalink($event_id) . '">';
                    echo get_the_post_thumbnail($event_id, 'thumbnail');
                    echo '</a>';
                    echo '</div>';
                }
                
                echo '<div class="eventmate-upcoming-content">';
                echo '<h4><a href="' . get_permalink($event_id) . '">' . get_the_title($event_id) . '</a></h4>';
                
                if ($atts['show_date'] == 'yes' && $start_date) {
                    echo '<div class="eventmate-upcoming-date">';
                    echo '📅 ' . date_i18n('d/m/Y H:i', strtotime($start_date));
                    echo '</div>';
                }
                
                if ($atts['show_location'] == 'yes' && $location) {
                    echo '<div class="eventmate-upcoming-location">';
                    echo '📍 ' . esc_html($location);
                    echo '</div>';
                }
                
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="eventmate-no-upcoming-events">';
            echo '<p>Nessun evento in programma.</p>';
            echo '</div>';
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    public function event_map_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'height' => '300px',
            'width' => '100%',
        ), $atts);
        
        if (empty($atts['id'])) {
            global $post;
            if ($post && $post->post_type == 'eventmate_event') {
                $atts['id'] = $post->ID;
            } else {
                return '<p>ID evento non specificato.</p>';
            }
        }
        
        $google_maps_api = get_option('eventmate_google_maps_api_key');
        if (empty($google_maps_api)) {
            return '<p>Google Maps API Key non configurata. Vai in Eventi > Impostazioni per configurarla.</p>';
        }
        
        $address = get_post_meta($atts['id'], '_eventmate_address', true);
        $location = get_post_meta($atts['id'], '_eventmate_location', true);
        
        if (empty($address)) {
            return '<p>Indirizzo non disponibile per questo evento.</p>';
        }
        
        $map_id = 'eventmate-map-' . $atts['id'];
        $map_title = $location ? $location : get_the_title($atts['id']);
        
        ob_start();
        ?>
        <div class="eventmate-map-container">
            <div id="<?php echo esc_attr($map_id); ?>" 
                 class="eventmate-map" 
                 style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;"
                 data-address="<?php echo esc_attr($address); ?>"
                 data-title="<?php echo esc_attr($map_title); ?>">
                <div class="eventmate-map-loading">
                    <p>Caricamento mappa...</p>
                </div>
            </div>
            
            <div class="eventmate-map-info">
                <?php if ($location): ?>
                    <div class="eventmate-map-location">
                        <strong><?php echo esc_html($location); ?></strong>
                    </div>
                <?php endif; ?>
                <div class="eventmate-map-address">
                    <span class="eventmate-icon">📍</span>
                    <?php echo esc_html($address); ?>
                </div>
                <div class="eventmate-map-actions">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($address); ?>" 
                       target="_blank" class="eventmate-btn eventmate-btn-secondary">
                        Apri in Google Maps
                    </a>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        if (typeof google !== 'undefined' && google.maps) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': '<?php echo esc_js($address); ?>'}, function(results, status) {
                if (status === 'OK') {
                    var map = new google.maps.Map(document.getElementById('<?php echo esc_js($map_id); ?>'), {
                        zoom: 15,
                        center: results[0].geometry.location
                    });
                    
                    var marker = new google.maps.Marker({
                        position: results[0].geometry.location,
                        map: map,
                        title: '<?php echo esc_js($map_title); ?>'
                    });
                    
                    var infoWindow = new google.maps.InfoWindow({
                        content: '<div><strong><?php echo esc_js($map_title); ?></strong></div>'
                    });
                    
                    marker.addListener('click', function() {
                        infoWindow.open(map, marker);
                    });
                } else {
                    document.getElementById('<?php echo esc_js($map_id); ?>').innerHTML = '<p>Impossibile caricare la mappa.</p>';
                }
            });
        }
        </script>
        <?php
        
        return ob_get_clean();
    }
}

/**
 * Booking System Class
 */
class EventMate_Booking {
    
    public function __construct() {
        add_action('wp_ajax_eventmate_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_nopriv_eventmate_submit_booking', array($this, 'ajax_submit_booking'));
        add_filter('the_content', array($this, 'add_booking_form_to_single_event'));
    }
    
    public function render_booking_form($event_id) {
        $enable_booking = get_post_meta($event_id, '_eventmate_enable_booking', true);
        $booking_type = get_post_meta($event_id, '_eventmate_booking_type', true);
        $max_attendees = get_post_meta($event_id, '_eventmate_max_attendees', true);
        $start_date = get_post_meta($event_id, '_eventmate_start_date', true);
        
        if ($enable_booking != 'yes') {
            return '<p>Prenotazioni non disponibili per questo evento.</p>';
        }
        
        // Check if event is in the past
        if (!empty($start_date) && strtotime($start_date) < current_time('timestamp')) {
            return '<p>Prenotazioni chiuse: l\'evento è già terminato.</p>';
        }
        
        // Check availability
        $current_bookings = $this->get_booking_count($event_id);
        if (!empty($max_attendees) && $current_bookings >= $max_attendees) {
            return '<div class="eventmate-booking-full"><p>Evento al completo. Non è possibile effettuare altre prenotazioni.</p></div>';
        }
        
        ob_start();
        ?>
        <div class="eventmate-booking-form-container">
            <div class="eventmate-booking-info">
                <h3>Prenota per questo evento</h3>
                <?php if (!empty($max_attendees)): ?>
                    <p class="eventmate-availability">
                        <?php 
                        $remaining = $max_attendees - $current_bookings;
                        printf('Posti disponibili: %d su %d', $remaining, $max_attendees);
                        ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <form id="eventmate-booking-form" class="eventmate-booking-form" data-event-id="<?php echo esc_attr($event_id); ?>">
                <div class="eventmate-form-group">
                    <label for="booking_name">Nome Completo <span class="required">*</span></label>
                    <input type="text" id="booking_name" name="booking_name" required>
                </div>
                
                <div class="eventmate-form-group">
                    <label for="booking_email">Email <span class="required">*</span></label>
                    <input type="email" id="booking_email" name="booking_email" required>
                </div>
                
                <div class="eventmate-form-group">
                    <label for="booking_phone">Telefono</label>
                    <input type="tel" id="booking_phone" name="booking_phone">
                </div>
                
                <div class="eventmate-form-group">
                    <label for="booking_notes">Note (opzionale)</label>
                    <textarea id="booking_notes" name="booking_notes" rows="3"></textarea>
                </div>
                
                <div class="eventmate-form-group eventmate-privacy">
                    <label>
                        <input type="checkbox" id="booking_privacy" name="booking_privacy" required>
                        Accetto il trattamento dei dati personali <span class="required">*</span>
                    </label>
                </div>
                
                <div class="eventmate-form-group">
                    <button type="submit" class="eventmate-btn eventmate-btn-primary">
                        <span class="button-text">Prenota Ora</span>
                        <span class="button-loading" style="display: none;">Prenotazione...</span>
                    </button>
                </div>
                
                <div class="eventmate-booking-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    public function ajax_submit_booking() {
        check_ajax_referer('eventmate_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Validation
        if (empty($event_id) || empty($name) || empty($email)) {
            wp_send_json_error(array('message' => 'Tutti i campi obbligatori devono essere compilati.'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Indirizzo email non valido.'));
        }
        
        // Check if booking is enabled
        $enable_booking = get_post_meta($event_id, '_eventmate_enable_booking', true);
        if ($enable_booking != 'yes') {
            wp_send_json_error(array('message' => 'Prenotazioni non disponibili per questo evento.'));
        }
        
        // Check availability
        $max_attendees = get_post_meta($event_id, '_eventmate_max_attendees', true);
        $current_bookings = $this->get_booking_count($event_id);
        
        if (!empty($max_attendees) && $current_bookings >= $max_attendees) {
            wp_send_json_error(array('message' => 'Evento al completo.'));
        }
        
        // Check for duplicate booking
        if ($this->has_user_booked($event_id, $email)) {
            wp_send_json_error(array('message' => 'Hai già effettuato una prenotazione per questo evento.'));
        }
        
        // Create booking
        $booking_id = $this->create_booking($event_id, $name, $email, $phone, $notes);
        
        if ($booking_id) {
            // Send confirmation emails
            $this->send_booking_confirmation($booking_id);
            $this->send_admin_notification($booking_id);
            
            wp_send_json_success(array(
                'message' => 'Prenotazione effettuata con successo! Ti abbiamo inviato una email di conferma.',
                'booking_id' => $booking_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Errore durante la prenotazione. Riprova.'));
        }
    }
    
    private function create_booking($event_id, $name, $email, $phone, $notes) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'event_id' => $event_id,
                'user_name' => $name,
                'user_email' => $email,
                'user_phone' => $phone,
                'notes' => $notes,
                'booking_date' => current_time('mysql'),
                'status' => 'confirmed'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public function get_booking_count($event_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND status = 'confirmed'",
            $event_id
        ));
    }
    
    private function has_user_booked($event_id, $email) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND user_email = %s AND status = 'confirmed'",
            $event_id,
            $email
        ));
        
        return $count > 0;
    }
    
    private function send_booking_confirmation($booking_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            return false;
        }
        
        $event = get_post($booking->event_id);
        $start_date = get_post_meta($booking->event_id, '_eventmate_start_date', true);
        $location = get_post_meta($booking->event_id, '_eventmate_location', true);
        
        $subject = sprintf('Conferma prenotazione per %s', $event->post_title);
        
        $message = "
        <h2>Conferma Prenotazione</h2>
        <p>Ciao {$booking->user_name},</p>
        <p>Grazie per la tua prenotazione! Ecco i dettagli:</p>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
            <h3>{$event->post_title}</h3>
            " . ($start_date ? "<p><strong>Data:</strong> " . date_i18n('d/m/Y H:i', strtotime($start_date)) . "</p>" : "") . "
            " . ($location ? "<p><strong>Luogo:</strong> {$location}</p>" : "") . "
        </div>
        
        <div style='background: #e8f4f8; padding: 20px; border-radius: 5px;'>
            <h4>Dettagli Prenotazione</h4>
            <p><strong>Nome:</strong> {$booking->user_name}</p>
            <p><strong>Email:</strong> {$booking->user_email}</p>
            " . ($booking->user_phone ? "<p><strong>Telefono:</strong> {$booking->user_phone}</p>" : "") . "
            <p><strong>ID Prenotazione:</strong> #{$booking->id}</p>
        </div>
        
        <p>Ti aspettiamo all'evento!</p>
        ";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($booking->user_email, $subject, $message, $headers);
    }
    
    private function send_admin_notification($booking_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            return false;
        }
        
        $event = get_post($booking->event_id);
        $admin_email = get_option('admin_email');
        
        $subject = sprintf('Nuova prenotazione per %s', $event->post_title);
        $message = "
        <h2>Nuova Prenotazione Ricevuta</h2>
        <p>È stata ricevuta una nuova prenotazione:</p>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>
            <h3>{$event->post_title}</h3>
            <p><strong>Nome:</strong> {$booking->user_name}</p>
            <p><strong>Email:</strong> {$booking->user_email}</p>
            " . ($booking->user_phone ? "<p><strong>Telefono:</strong> {$booking->user_phone}</p>" : "") . "
            " . ($booking->notes ? "<p><strong>Note:</strong> {$booking->notes}</p>" : "") . "
            <p><strong>Data prenotazione:</strong> {$booking->booking_date}</p>
        </div>
        ";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($admin_email, $subject, $message, $headers);
    }
    
    public function add_booking_form_to_single_event($content) {
        if (is_singular('eventmate_event') && is_main_query()) {
            $enable_booking = get_post_meta(get_the_ID(), '_eventmate_enable_booking', true);
            
            if ($enable_booking == 'yes') {
                $booking_form = $this->render_booking_form(get_the_ID());
                $content .= '<div class="eventmate-single-event-booking">';
                $content .= $booking_form;
                $content .= '</div>';
            }
        }
        
        return $content;
    }
}

/**
 * Admin Interface Class
 */
class EventMate_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('add_meta_boxes', array($this, 'add_bookings_meta_box'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=eventmate_event',
            'Impostazioni EventMate Pro',
            'Impostazioni',
            'manage_options',
            'eventmate-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=eventmate_event',
            'Prenotazioni',
            'Prenotazioni',
            'manage_options',
            'eventmate-bookings',
            array($this, 'bookings_page')
        );
    }
    
    public function register_settings() {
        register_setting('eventmate_settings', 'eventmate_google_maps_api_key');
        register_setting('eventmate_settings', 'eventmate_currency');
        register_setting('eventmate_settings', 'eventmate_default_view');
        register_setting('eventmate_settings', 'eventmate_events_per_page');
    }
    
    public function settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('eventmate_messages', 'eventmate_message', 'Impostazioni salvate', 'updated');
        }
        
        settings_errors('eventmate_messages');
        ?>
        <div class="wrap eventmate-admin-page">
            <h1>Impostazioni EventMate Pro</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('eventmate_settings'); ?>
                
                <div class="eventmate-settings-section">
                    <h2>Configurazione Google Maps</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Google Maps API Key</th>
                            <td>
                                <input type="text" name="eventmate_google_maps_api_key" 
                                       value="<?php echo esc_attr(get_option('eventmate_google_maps_api_key')); ?>" 
                                       class="regular-text" />
                                <p class="description">
                                    Inserisci la tua API Key di Google Maps per abilitare le mappe. 
                                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Ottieni qui la tua API Key</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="eventmate-settings-section">
                    <h2>Impostazioni Generali</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Valuta</th>
                            <td>
                                <select name="eventmate_currency">
                                    <option value="EUR" <?php selected(get_option('eventmate_currency'), 'EUR'); ?>>EUR (€)</option>
                                    <option value="USD" <?php selected(get_option('eventmate_currency'), 'USD'); ?>>USD ($)</option>
                                    <option value="GBP" <?php selected(get_option('eventmate_currency'), 'GBP'); ?>>GBP (£)</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Vista predefinita eventi</th>
                            <td>
                                <select name="eventmate_default_view">
                                    <option value="grid" <?php selected(get_option('eventmate_default_view'), 'grid'); ?>>Griglia</option>
                                    <option value="list" <?php selected(get_option('eventmate_default_view'), 'list'); ?>>Lista</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Eventi per pagina</th>
                            <td>
                                <input type="number" name="eventmate_events_per_page" 
                                       value="<?php echo esc_attr(get_option('eventmate_events_per_page', 12)); ?>" 
                                       min="1" max="50" class="small-text" />
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function bookings_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        
        // Handle booking actions
        if (isset($_GET['action']) && isset($_GET['booking_id'])) {
            $booking_id = intval($_GET['booking_id']);
            
            if ($_GET['action'] == 'delete' && wp_verify_nonce($_GET['_wpnonce'], 'delete_booking_' . $booking_id)) {
                $wpdb->delete($table_name, array('id' => $booking_id), array('%d'));
                echo '<div class="notice notice-success"><p>Prenotazione eliminata.</p></div>';
            }
        }
        
        // Get all bookings
        $bookings = $wpdb->get_results("
            SELECT b.*, p.post_title as event_title 
            FROM $table_name b 
            LEFT JOIN {$wpdb->posts} p ON b.event_id = p.ID 
            ORDER BY b.booking_date DESC
        ");
        
        ?>
        <div class="wrap eventmate-admin-page">
            <h1>Gestione Prenotazioni</h1>
            
            <?php if (empty($bookings)): ?>
                <p>Nessuna prenotazione trovata.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped eventmate-bookings-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Evento</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefono</th>
                            <th>Data Prenotazione</th>
                            <th>Status</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo esc_html($booking->id); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($booking->event_id); ?>">
                                        <?php echo esc_html($booking->event_title); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($booking->user_name); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($booking->user_email); ?>"><?php echo esc_html($booking->user_email); ?></a></td>
                                <td><?php echo esc_html($booking->user_phone); ?></td>
                                <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($booking->booking_date))); ?></td>
                                <td>
                                    <span class="booking-status booking-status-<?php echo esc_attr($booking->status); ?>">
                                        <?php echo esc_html(ucfirst($booking->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'delete', 'booking_id' => $booking->id)), 'delete_booking_' . $booking->id); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Sei sicuro di voler eliminare questa prenotazione?')">
                                        Elimina
                                    </a>
                                </td>
                            </tr>
                            <?php if ($booking->notes): ?>
                                <tr>
                                    <td colspan="8">
                                        <strong>Note:</strong> <?php echo esc_html($booking->notes); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function add_bookings_meta_box() {
        add_meta_box(
            'eventmate-bookings-list',
            'Prenotazioni per questo evento',
            array($this, 'bookings_meta_box'),
            'eventmate_event',
            'normal',
            'default'
        );
    }
    
    public function bookings_meta_box($post) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE event_id = %d ORDER BY booking_date DESC",
            $post->ID
        ));
        
        if (empty($bookings)) {
            echo '<p>Nessuna prenotazione per questo evento.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Nome</th><th>Email</th><th>Telefono</th><th>Data</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($bookings as $booking) {
            echo '<tr>';
            echo '<td>' . esc_html($booking->user_name) . '</td>';
            echo '<td>' . esc_html($booking->user_email) . '</td>';
            echo '<td>' . esc_html($booking->user_phone) . '</td>';
            echo '<td>' . esc_html(date_i18n('d/m/Y H:i', strtotime($booking->booking_date))) . '</td>';
            echo '<td>' . esc_html($booking->status) . '</td>';
            echo '</tr>';
            
            if ($booking->notes) {
                echo '<tr><td colspan="5"><strong>Note:</strong> ' . esc_html($booking->notes) . '</td></tr>';
            }
        }
        
        echo '</tbody></table>';
        
        // Export to CSV button
        echo '<p><a href="' . admin_url('admin-ajax.php?action=eventmate_export_bookings&event_id=' . $post->ID . '&_wpnonce=' . wp_create_nonce('export_bookings')) . '" class="button">Esporta CSV</a></p>';
    }
}

/**
 * Widgets Class
 */
class EventMate_Widgets extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'eventmate_upcoming_widget',
            'EventMate - Prossimi Eventi',
            array('description' => 'Mostra i prossimi eventi in arrivo')
        );
        
        add_action('widgets_init', function() {
            register_widget('EventMate_Widgets');
        });
    }
    
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Prossimi Eventi';
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        
        echo $args['before_widget'];
        
        echo '<div class="eventmate-widget">';
        echo '<h3 class="eventmate-widget-title">' . esc_html($title) . '</h3>';
        echo '<div class="eventmate-widget-content">';
        
        // Use shortcode to display events
        $shortcode = new EventMate_Shortcodes();
        echo $shortcode->upcoming_events_shortcode(array(
            'limit' => $limit,
            'show_image' => 'yes',
            'show_date' => 'yes',
            'show_location' => 'yes'
        ));
        
        echo '</div>';
        echo '</div>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Prossimi Eventi';
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Titolo:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>">Numero di eventi:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>" 
                   name="<?php echo $this->get_field_name('limit'); ?>" type="number" 
                   value="<?php echo esc_attr($limit); ?>" min="1" max="20">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 5;
        return $instance;
    }
}

/**
 * Placeholder classes for WooCommerce, Maps, Frontend, and Recurring
 */
class EventMate_WooCommerce {
    public function __construct() {
        // WooCommerce integration placeholder
        if (class_exists('WooCommerce')) {
            add_action('init', array($this, 'init_woocommerce_integration'));
        }
    }
    
    public function init_woocommerce_integration() {
        // Placeholder for WooCommerce integration
    }
}

class EventMate_Maps {
    public function __construct() {
        add_filter('the_content', array($this, 'add_map_to_single_event'));
    }
    
    public function add_map_to_single_event($content) {
        if (is_singular('eventmate_event') && is_main_query()) {
            $address = get_post_meta(get_the_ID(), '_eventmate_address', true);
            $google_maps_api = get_option('eventmate_google_maps_api_key');
            
            if (!empty($address) && !empty($google_maps_api)) {
                $shortcode = new EventMate_Shortcodes();
                $map = $shortcode->event_map_shortcode(array('id' => get_the_ID()));
                $content .= '<div class="eventmate-single-event-map">';
                $content .= '<h3>Dove si svolge</h3>';
                $content .= $map;
                $content .= '</div>';
            }
        }
        
        return $content;
    }
}

class EventMate_Frontend {
    public function __construct() {
        // Frontend enhancements placeholder
    }
}

class EventMate_Recurring {
    public function __construct() {
        // Recurring events placeholder
    }
}

// Initialize the plugin
function EventMate_Pro() {
    return EventMatePro::instance();
}

// Global for backwards compatibility
$GLOBALS['eventmate_pro'] = EventMate_Pro();