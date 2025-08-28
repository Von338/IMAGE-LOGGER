<?php
/**
 * Booking System for EventMate Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class EventMate_Booking {
    
    public function __construct() {
        add_action('wp_ajax_eventmate_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_nopriv_eventmate_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_eventmate_cancel_booking', array($this, 'ajax_cancel_booking'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_booking_scripts'));
        add_action('init', array($this, 'handle_booking_confirmation'));
        add_shortcode('event_booking_form', array($this, 'booking_form_shortcode'));
    }
    
    /**
     * Enqueue booking scripts
     */
    public function enqueue_booking_scripts() {
        if (is_singular('eventmate_event') || $this->has_booking_form()) {
            wp_enqueue_script(
                'eventmate-booking',
                EVENTMATE_PRO_PLUGIN_URL . 'assets/js/booking.js',
                array('jquery'),
                EVENTMATE_PRO_VERSION,
                true
            );
            
            wp_localize_script('eventmate-booking', 'eventmate_booking_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eventmate_booking_nonce'),
                'messages' => array(
                    'confirm_cancel' => __('Sei sicuro di voler cancellare questa prenotazione?', 'eventmate-pro'),
                    'booking_success' => __('Prenotazione effettuata con successo!', 'eventmate-pro'),
                    'booking_error' => __('Errore durante la prenotazione. Riprova.', 'eventmate-pro'),
                )
            ));
        }
    }
    
    /**
     * Check if page has booking form
     */
    private function has_booking_form() {
        global $post;
        return $post && has_shortcode($post->post_content, 'event_booking_form');
    }
    
    /**
     * Render booking form
     */
    public function render_booking_form($event_id) {
        $enable_booking = get_post_meta($event_id, '_eventmate_enable_booking', true);
        $booking_type = get_post_meta($event_id, '_eventmate_booking_type', true);
        $max_attendees = get_post_meta($event_id, '_eventmate_max_attendees', true);
        $start_date = get_post_meta($event_id, '_eventmate_start_date', true);
        
        if ($enable_booking != 'yes') {
            return '<p>' . __('Prenotazioni non disponibili per questo evento.', 'eventmate-pro') . '</p>';
        }
        
        // Check if event is in the past
        if (!empty($start_date) && strtotime($start_date) < current_time('timestamp')) {
            return '<p>' . __('Prenotazioni chiuse: l\'evento è già terminato.', 'eventmate-pro') . '</p>';
        }
        
        // Check availability
        $current_bookings = $this->get_booking_count($event_id);
        if (!empty($max_attendees) && $current_bookings >= $max_attendees) {
            return '<div class="eventmate-booking-full"><p>' . __('Evento al completo. Non è possibile effettuare altre prenotazioni.', 'eventmate-pro') . '</p></div>';
        }
        
        // If WooCommerce booking, redirect to product
        if ($booking_type == 'woocommerce') {
            $product_id = get_post_meta($event_id, '_eventmate_woocommerce_product_id', true);
            if ($product_id) {
                $product_url = get_permalink($product_id);
                return '<div class="eventmate-woocommerce-booking">
                    <a href="' . esc_url($product_url) . '" class="eventmate-btn eventmate-btn-primary">
                        ' . __('Acquista Biglietto', 'eventmate-pro') . '
                    </a>
                </div>';
            }
        }
        
        // Simple booking form
        ob_start();
        ?>
        <div class="eventmate-booking-form-container">
            <div class="eventmate-booking-info">
                <?php if (!empty($max_attendees)): ?>
                    <p class="eventmate-availability">
                        <?php 
                        $remaining = $max_attendees - $current_bookings;
                        printf(
                            __('Posti disponibili: %d su %d', 'eventmate-pro'),
                            $remaining,
                            $max_attendees
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <form id="eventmate-booking-form" class="eventmate-booking-form" data-event-id="<?php echo esc_attr($event_id); ?>">
                <div class="eventmate-form-group">
                    <label for="booking_name"><?php _e('Nome Completo', 'eventmate-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="booking_name" name="booking_name" required>
                </div>
                
                <div class="eventmate-form-group">
                    <label for="booking_email"><?php _e('Email', 'eventmate-pro'); ?> <span class="required">*</span></label>
                    <input type="email" id="booking_email" name="booking_email" required>
                </div>
                
                <div class="eventmate-form-group">
                    <label for="booking_phone"><?php _e('Telefono', 'eventmate-pro'); ?></label>
                    <input type="tel" id="booking_phone" name="booking_phone">
                </div>
                
                <div class="eventmate-form-group">
                    <label for="booking_notes"><?php _e('Note (opzionale)', 'eventmate-pro'); ?></label>
                    <textarea id="booking_notes" name="booking_notes" rows="3"></textarea>
                </div>
                
                <div class="eventmate-form-group eventmate-privacy">
                    <label>
                        <input type="checkbox" id="booking_privacy" name="booking_privacy" required>
                        <?php _e('Accetto il trattamento dei dati personali secondo la Privacy Policy', 'eventmate-pro'); ?> <span class="required">*</span>
                    </label>
                </div>
                
                <div class="eventmate-form-group">
                    <button type="submit" class="eventmate-btn eventmate-btn-primary">
                        <span class="button-text"><?php _e('Prenota Ora', 'eventmate-pro'); ?></span>
                        <span class="button-loading" style="display: none;"><?php _e('Prenotazione...', 'eventmate-pro'); ?></span>
                    </button>
                </div>
                
                <div class="eventmate-booking-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        if (empty($atts['id'])) {
            global $post;
            if ($post && $post->post_type == 'eventmate_event') {
                $atts['id'] = $post->ID;
            } else {
                return '<p>' . __('ID evento non specificato.', 'eventmate-pro') . '</p>';
            }
        }
        
        return $this->render_booking_form($atts['id']);
    }
    
    /**
     * AJAX submit booking
     */
    public function ajax_submit_booking() {
        check_ajax_referer('eventmate_booking_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Validation
        if (empty($event_id) || empty($name) || empty($email)) {
            wp_send_json_error(array('message' => __('Tutti i campi obbligatori devono essere compilati.', 'eventmate-pro')));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Indirizzo email non valido.', 'eventmate-pro')));
        }
        
        // Check if booking is enabled
        $enable_booking = get_post_meta($event_id, '_eventmate_enable_booking', true);
        if ($enable_booking != 'yes') {
            wp_send_json_error(array('message' => __('Prenotazioni non disponibili per questo evento.', 'eventmate-pro')));
        }
        
        // Check availability
        $max_attendees = get_post_meta($event_id, '_eventmate_max_attendees', true);
        $current_bookings = $this->get_booking_count($event_id);
        
        if (!empty($max_attendees) && $current_bookings >= $max_attendees) {
            wp_send_json_error(array('message' => __('Evento al completo.', 'eventmate-pro')));
        }
        
        // Check for duplicate booking
        if ($this->has_user_booked($event_id, $email)) {
            wp_send_json_error(array('message' => __('Hai già effettuato una prenotazione per questo evento.', 'eventmate-pro')));
        }
        
        // Create booking
        $booking_id = $this->create_booking($event_id, $name, $email, $phone, $notes);
        
        if ($booking_id) {
            // Send confirmation emails
            $this->send_booking_confirmation($booking_id);
            $this->send_admin_notification($booking_id);
            
            wp_send_json_success(array(
                'message' => __('Prenotazione effettuata con successo! Ti abbiamo inviato una email di conferma.', 'eventmate-pro'),
                'booking_id' => $booking_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Errore durante la prenotazione. Riprova.', 'eventmate-pro')));
        }
    }
    
    /**
     * Create booking record
     */
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
    
    /**
     * Get booking count for event
     */
    public function get_booking_count($event_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND status = 'confirmed'",
            $event_id
        ));
    }
    
    /**
     * Check if user has already booked
     */
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
    
    /**
     * Send booking confirmation email
     */
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
        $address = get_post_meta($booking->event_id, '_eventmate_address', true);
        
        $subject = sprintf(__('Conferma prenotazione per %s', 'eventmate-pro'), $event->post_title);
        
        $message = $this->get_booking_email_template($booking, $event, 'user');
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($booking->user_email, $subject, $message, $headers);
    }
    
    /**
     * Send admin notification
     */
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
        
        $subject = sprintf(__('Nuova prenotazione per %s', 'eventmate-pro'), $event->post_title);
        $message = $this->get_booking_email_template($booking, $event, 'admin');
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Get booking email template
     */
    private function get_booking_email_template($booking, $event, $type = 'user') {
        $start_date = get_post_meta($booking->event_id, '_eventmate_start_date', true);
        $location = get_post_meta($booking->event_id, '_eventmate_location', true);
        $address = get_post_meta($booking->event_id, '_eventmate_address', true);
        
        $site_name = get_bloginfo('name');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($site_name); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #2c3e50;">
                    <?php if ($type == 'user'): ?>
                        <?php _e('Conferma Prenotazione', 'eventmate-pro'); ?>
                    <?php else: ?>
                        <?php _e('Nuova Prenotazione Ricevuta', 'eventmate-pro'); ?>
                    <?php endif; ?>
                </h2>
                
                <?php if ($type == 'user'): ?>
                    <p><?php printf(__('Ciao %s,', 'eventmate-pro'), esc_html($booking->user_name)); ?></p>
                    <p><?php _e('Grazie per la tua prenotazione! Ecco i dettagli:', 'eventmate-pro'); ?></p>
                <?php else: ?>
                    <p><?php _e('È stata ricevuta una nuova prenotazione:', 'eventmate-pro'); ?></p>
                <?php endif; ?>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #2c3e50;"><?php echo esc_html($event->post_title); ?></h3>
                    
                    <?php if ($start_date): ?>
                        <p><strong><?php _e('Data e Ora:', 'eventmate-pro'); ?></strong><br>
                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start_date)); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                        <p><strong><?php _e('Luogo:', 'eventmate-pro'); ?></strong><br>
                        <?php echo esc_html($location); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($address): ?>
                        <p><strong><?php _e('Indirizzo:', 'eventmate-pro'); ?></strong><br>
                        <?php echo esc_html($address); ?></p>
                    <?php endif; ?>
                </div>
                
                <div style="background: #e8f4f8; padding: 20px; border-radius: 5px; margin: 20px 0;">
                    <h4 style="margin-top: 0;"><?php _e('Dettagli Prenotazione', 'eventmate-pro'); ?></h4>
                    <p><strong><?php _e('Nome:', 'eventmate-pro'); ?></strong> <?php echo esc_html($booking->user_name); ?></p>
                    <p><strong><?php _e('Email:', 'eventmate-pro'); ?></strong> <?php echo esc_html($booking->user_email); ?></p>
                    <?php if ($booking->user_phone): ?>
                        <p><strong><?php _e('Telefono:', 'eventmate-pro'); ?></strong> <?php echo esc_html($booking->user_phone); ?></p>
                    <?php endif; ?>
                    <?php if ($booking->notes): ?>
                        <p><strong><?php _e('Note:', 'eventmate-pro'); ?></strong><br><?php echo nl2br(esc_html($booking->notes)); ?></p>
                    <?php endif; ?>
                    <p><strong><?php _e('ID Prenotazione:', 'eventmate-pro'); ?></strong> #<?php echo esc_html($booking->id); ?></p>
                </div>
                
                <?php if ($type == 'user'): ?>
                    <p><?php _e('Ti aspettiamo all\'evento!', 'eventmate-pro'); ?></p>
                    <p style="font-size: 12px; color: #666;">
                        <?php _e('Se hai bisogno di modificare o cancellare la prenotazione, contattaci.', 'eventmate-pro'); ?>
                    </p>
                <?php endif; ?>
                
                <hr style="border: 1px solid #eee; margin: 30px 0;">
                <p style="font-size: 12px; color: #666; text-align: center;">
                    <?php echo esc_html($site_name); ?><br>
                    <?php _e('Questo messaggio è stato generato automaticamente.', 'eventmate-pro'); ?>
                </p>
            </div>
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle booking confirmation from email
     */
    public function handle_booking_confirmation() {
        if (isset($_GET['eventmate_action']) && $_GET['eventmate_action'] == 'confirm_booking') {
            $booking_id = intval($_GET['booking_id']);
            $token = sanitize_text_field($_GET['token']);
            
            // Verify token and confirm booking
            $this->confirm_booking($booking_id, $token);
        }
    }
    
    /**
     * Get bookings for event (admin use)
     */
    public function get_event_bookings($event_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eventmate_bookings';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE event_id = %d ORDER BY booking_date DESC",
            $event_id
        ));
    }
    
    /**
     * Cancel booking
     */
    public function ajax_cancel_booking() {
        check_ajax_referer('eventmate_booking_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id']);
        
        if (current_user_can('manage_options')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'eventmate_bookings';
            
            $result = $wpdb->update(
                $table_name,
                array('status' => 'cancelled'),
                array('id' => $booking_id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => __('Prenotazione cancellata.', 'eventmate-pro')));
            }
        }
        
        wp_send_json_error(array('message' => __('Errore durante la cancellazione.', 'eventmate-pro')));
    }
}