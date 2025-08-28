<?php
/**
 * Shortcodes for EventMate Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class EventMate_Shortcodes {
    
    public function __construct() {
        add_action('init', array($this, 'init_shortcodes'));
    }
    
    /**
     * Initialize shortcodes
     */
    public function init_shortcodes() {
        add_shortcode('event_list', array($this, 'event_list_shortcode'));
        add_shortcode('event_single', array($this, 'event_single_shortcode'));
        add_shortcode('event_booking_form', array($this, 'event_booking_form_shortcode'));
        add_shortcode('upcoming_events', array($this, 'upcoming_events_shortcode'));
    }
    
    /**
     * Event list shortcode
     * Usage: [event_list view="grid" limit="12" category="music" orderby="date"]
     */
    public function event_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => get_option('eventmate_default_view', 'grid'), // grid or list
            'limit' => get_option('eventmate_events_per_page', 12),
            'category' => '',
            'tag' => '',
            'orderby' => 'date', // date, title, menu_order
            'order' => 'ASC', // ASC or DESC
            'show_past' => 'no', // yes or no
            'featured_only' => 'no', // yes or no
        ), $atts);
        
        // Build query arguments
        $query_args = array(
            'post_type' => 'eventmate_event',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(),
        );
        
        // Handle ordering
        if ($atts['orderby'] == 'date') {
            $query_args['meta_key'] = '_eventmate_start_date';
            $query_args['orderby'] = 'meta_value';
            $query_args['order'] = $atts['order'];
        } else {
            $query_args['orderby'] = $atts['orderby'];
            $query_args['order'] = $atts['order'];
        }
        
        // Hide past events by default
        if ($atts['show_past'] == 'no') {
            $query_args['meta_query'][] = array(
                'key' => '_eventmate_start_date',
                'value' => current_time('Y-m-d H:i:s'),
                'compare' => '>=',
                'type' => 'DATETIME'
            );
        }
        
        // Filter by category
        if (!empty($atts['category'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'event_category',
                'field' => 'slug',
                'terms' => explode(',', $atts['category'])
            );
        }
        
        // Filter by tag
        if (!empty($atts['tag'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'event_tag',
                'field' => 'slug',
                'terms' => explode(',', $atts['tag'])
            );
        }
        
        // Featured events only
        if ($atts['featured_only'] == 'yes') {
            $query_args['meta_query'][] = array(
                'key' => '_eventmate_featured',
                'value' => 'yes',
                'compare' => '='
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
            echo '<p>' . __('Nessun evento trovato.', 'eventmate-pro') . '</p>';
            echo '</div>';
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Render event grid item
     */
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
                        <div class="eventmate-meta-item eventmate-date">
                            <span class="eventmate-icon">🕒</span>
                            <?php echo date_i18n(get_option('time_format'), strtotime($start_date)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                        <div class="eventmate-meta-item eventmate-location">
                            <span class="eventmate-icon">📍</span>
                            <?php echo esc_html($location); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="eventmate-meta-item eventmate-price">
                        <span class="eventmate-icon">💰</span>
                        <?php 
                        if ($is_free == 'yes') {
                            _e('Gratuito', 'eventmate-pro');
                        } else {
                            $currency = get_option('eventmate_currency', 'EUR');
                            echo esc_html($currency . ' ' . $price);
                        }
                        ?>
                    </div>
                </div>
                
                <div class="eventmate-event-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt($event_id), 20); ?>
                </div>
                
                <div class="eventmate-event-actions">
                    <a href="<?php echo get_permalink($event_id); ?>" class="eventmate-btn eventmate-btn-primary">
                        <?php _e('Dettagli', 'eventmate-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render event list item
     */
    private function render_event_list_item($event_id) {
        $start_date = get_post_meta($event_id, '_eventmate_start_date', true);
        $end_date = get_post_meta($event_id, '_eventmate_end_date', true);
        $location = get_post_meta($event_id, '_eventmate_location', true);
        $address = get_post_meta($event_id, '_eventmate_address', true);
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
                        <span class="eventmate-time">
                            🕒 <?php echo date_i18n(get_option('time_format'), strtotime($start_date)); ?>
                            <?php if ($end_date && $end_date != $start_date): ?>
                                - <?php echo date_i18n(get_option('time_format'), strtotime($end_date)); ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                        <span class="eventmate-location">📍 <?php echo esc_html($location); ?></span>
                    <?php endif; ?>
                    
                    <span class="eventmate-price">
                        💰 
                        <?php 
                        if ($is_free == 'yes') {
                            _e('Gratuito', 'eventmate-pro');
                        } else {
                            $currency = get_option('eventmate_currency', 'EUR');
                            echo esc_html($currency . ' ' . $price);
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
                    <?php _e('Dettagli', 'eventmate-pro'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Single event shortcode
     * Usage: [event_single id="123"]
     */
    public function event_single_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        if (empty($atts['id'])) {
            return '<p>' . __('ID evento non specificato.', 'eventmate-pro') . '</p>';
        }
        
        $event = get_post($atts['id']);
        
        if (!$event || $event->post_type != 'eventmate_event') {
            return '<p>' . __('Evento non trovato.', 'eventmate-pro') . '</p>';
        }
        
        ob_start();
        
        echo '<div class="eventmate-single-event">';
        echo '<h2>' . esc_html($event->post_title) . '</h2>';
        
        if (has_post_thumbnail($event->ID)) {
            echo '<div class="eventmate-event-featured-image">';
            echo get_the_post_thumbnail($event->ID, 'large');
            echo '</div>';
        }
        
        // Event details (reuse from post types class)
        $post_types = new EventMate_Post_Types();
        echo $post_types->get_event_details_html($event->ID);
        
        echo '<div class="eventmate-event-content">';
        echo apply_filters('the_content', $event->post_content);
        echo '</div>';
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Event booking form shortcode
     * Usage: [event_booking_form id="123"]
     */
    public function event_booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        if (empty($atts['id'])) {
            return '<p>' . __('ID evento non specificato.', 'eventmate-pro') . '</p>';
        }
        
        $enable_booking = get_post_meta($atts['id'], '_eventmate_enable_booking', true);
        
        if ($enable_booking != 'yes') {
            return '<p>' . __('Prenotazioni non disponibili per questo evento.', 'eventmate-pro') . '</p>';
        }
        
        // This will be handled by the booking class
        $booking = new EventMate_Booking();
        return $booking->render_booking_form($atts['id']);
    }
    
    /**
     * Upcoming events shortcode
     * Usage: [upcoming_events limit="5"]
     */
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
                    echo '📅 ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start_date));
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
            echo '<p>' . __('Nessun evento in programma.', 'eventmate-pro') . '</p>';
            echo '</div>';
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
}