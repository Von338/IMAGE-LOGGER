<?php
/**
 * Google Maps Integration for EventMate Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class EventMate_Maps {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_maps_scripts'));
        add_action('wp_footer', array($this, 'add_maps_init_script'));
        add_shortcode('event_map', array($this, 'event_map_shortcode'));
        add_filter('the_content', array($this, 'add_map_to_single_event'));
    }
    
    /**
     * Enqueue Google Maps scripts
     */
    public function enqueue_maps_scripts() {
        $google_maps_api = get_option('eventmate_google_maps_api_key');
        
        if (!empty($google_maps_api) && (is_singular('eventmate_event') || $this->has_event_shortcode())) {
            wp_enqueue_script(
                'google-maps-api',
                'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api . '&libraries=places',
                array(),
                null,
                true
            );
            
            wp_enqueue_script(
                'eventmate-maps',
                EVENTMATE_PRO_PLUGIN_URL . 'assets/js/maps.js',
                array('jquery', 'google-maps-api'),
                EVENTMATE_PRO_VERSION,
                true
            );
        }
    }
    
    /**
     * Check if page has event shortcode
     */
    private function has_event_shortcode() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        return has_shortcode($post->post_content, 'event_map') || 
               has_shortcode($post->post_content, 'event_list') ||
               has_shortcode($post->post_content, 'event_single');
    }
    
    /**
     * Add maps initialization script to footer
     */
    public function add_maps_init_script() {
        $google_maps_api = get_option('eventmate_google_maps_api_key');
        
        if (!empty($google_maps_api) && (is_singular('eventmate_event') || $this->has_event_shortcode())) {
            ?>
            <script type="text/javascript">
            var eventmate_maps_data = [];
            
            function initEventMateMaps() {
                if (typeof google !== 'undefined' && google.maps) {
                    // Initialize all maps on the page
                    jQuery('.eventmate-map').each(function() {
                        var mapElement = this;
                        var mapData = jQuery(mapElement).data();
                        
                        if (mapData.address) {
                            eventmate_geocodeAndCreateMap(mapElement, mapData);
                        } else if (mapData.lat && mapData.lng) {
                            eventmate_createMap(mapElement, mapData.lat, mapData.lng, mapData.title || '');
                        }
                    });
                }
            }
            
            function eventmate_geocodeAndCreateMap(mapElement, mapData) {
                var geocoder = new google.maps.Geocoder();
                
                geocoder.geocode({'address': mapData.address}, function(results, status) {
                    if (status === 'OK') {
                        var lat = results[0].geometry.location.lat();
                        var lng = results[0].geometry.location.lng();
                        eventmate_createMap(mapElement, lat, lng, mapData.title || '');
                    } else {
                        jQuery(mapElement).html('<p><?php _e("Impossibile caricare la mappa per questo indirizzo.", "eventmate-pro"); ?></p>');
                    }
                });
            }
            
            function eventmate_createMap(mapElement, lat, lng, title) {
                var mapOptions = {
                    zoom: 15,
                    center: new google.maps.LatLng(lat, lng),
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                };
                
                var map = new google.maps.Map(mapElement, mapOptions);
                
                var marker = new google.maps.Marker({
                    position: new google.maps.LatLng(lat, lng),
                    map: map,
                    title: title
                });
                
                if (title) {
                    var infoWindow = new google.maps.InfoWindow({
                        content: '<div class="eventmate-map-info"><strong>' + title + '</strong></div>'
                    });
                    
                    marker.addListener('click', function() {
                        infoWindow.open(map, marker);
                    });
                }
            }
            
            // Initialize maps when Google Maps API is loaded
            if (typeof google !== 'undefined' && google.maps) {
                jQuery(document).ready(function() {
                    initEventMateMaps();
                });
            } else {
                // Wait for Google Maps API to load
                window.initEventMateMaps = initEventMateMaps;
            }
            </script>
            <?php
        }
    }
    
    /**
     * Event map shortcode
     * Usage: [event_map id="123" height="300px"]
     */
    public function event_map_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'height' => '300px',
            'width' => '100%',
            'zoom' => 15,
            'show_info' => 'yes',
        ), $atts);
        
        if (empty($atts['id'])) {
            global $post;
            if ($post && $post->post_type == 'eventmate_event') {
                $atts['id'] = $post->ID;
            } else {
                return '<p>' . __('ID evento non specificato.', 'eventmate-pro') . '</p>';
            }
        }
        
        $google_maps_api = get_option('eventmate_google_maps_api_key');
        if (empty($google_maps_api)) {
            return '<p>' . __('Google Maps API Key non configurata.', 'eventmate-pro') . '</p>';
        }
        
        $address = get_post_meta($atts['id'], '_eventmate_address', true);
        $location = get_post_meta($atts['id'], '_eventmate_location', true);
        $event_title = get_the_title($atts['id']);
        
        if (empty($address)) {
            return '<p>' . __('Indirizzo non disponibile per questo evento.', 'eventmate-pro') . '</p>';
        }
        
        $map_id = 'eventmate-map-' . $atts['id'];
        $map_title = $location ? $location : $event_title;
        
        ob_start();
        ?>
        <div class="eventmate-map-container">
            <div id="<?php echo esc_attr($map_id); ?>" 
                 class="eventmate-map" 
                 style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;"
                 data-address="<?php echo esc_attr($address); ?>"
                 data-title="<?php echo esc_attr($map_title); ?>"
                 data-zoom="<?php echo esc_attr($atts['zoom']); ?>">
                <div class="eventmate-map-loading">
                    <p><?php _e('Caricamento mappa...', 'eventmate-pro'); ?></p>
                </div>
            </div>
            
            <?php if ($atts['show_info'] == 'yes'): ?>
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
                            <?php _e('Apri in Google Maps', 'eventmate-pro'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Add map to single event content
     */
    public function add_map_to_single_event($content) {
        if (is_singular('eventmate_event') && is_main_query()) {
            $show_map = get_option('eventmate_show_map_on_single', 'yes');
            
            if ($show_map == 'yes') {
                $address = get_post_meta(get_the_ID(), '_eventmate_address', true);
                
                if (!empty($address)) {
                    $map_shortcode = '[event_map id="' . get_the_ID() . '"]';
                    $content .= '<div class="eventmate-single-event-map">';
                    $content .= '<h3>' . __('Dove si svolge', 'eventmate-pro') . '</h3>';
                    $content .= do_shortcode($map_shortcode);
                    $content .= '</div>';
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Get coordinates from address
     */
    public function get_coordinates_from_address($address) {
        $google_maps_api = get_option('eventmate_google_maps_api_key');
        
        if (empty($google_maps_api) || empty($address)) {
            return false;
        }
        
        // Check cache first
        $cache_key = 'eventmate_coords_' . md5($address);
        $cached_coords = get_transient($cache_key);
        
        if ($cached_coords !== false) {
            return $cached_coords;
        }
        
        // Geocode address
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $google_maps_api;
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] == 'OK' && !empty($data['results'])) {
            $coords = array(
                'lat' => $data['results'][0]['geometry']['location']['lat'],
                'lng' => $data['results'][0]['geometry']['location']['lng']
            );
            
            // Cache for 24 hours
            set_transient($cache_key, $coords, 24 * HOUR_IN_SECONDS);
            
            return $coords;
        }
        
        return false;
    }
    
    /**
     * AJAX handler for address autocomplete
     */
    public function ajax_address_autocomplete() {
        check_ajax_referer('eventmate_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $google_maps_api = get_option('eventmate_google_maps_api_key');
        
        if (empty($google_maps_api) || empty($query)) {
            wp_die('Invalid request');
        }
        
        $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input=' . urlencode($query) . '&key=' . $google_maps_api;
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            wp_die('Error fetching data');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        wp_send_json($data);
    }
}