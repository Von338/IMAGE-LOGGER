<?php
/**
 * Meta Boxes for EventMate Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class EventMate_Meta_Boxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'eventmate-event-details',
            __('Dettagli Evento', 'eventmate-pro'),
            array($this, 'event_details_meta_box'),
            'eventmate_event',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eventmate-event-booking',
            __('Impostazioni Prenotazione', 'eventmate-pro'),
            array($this, 'event_booking_meta_box'),
            'eventmate_event',
            'side',
            'default'
        );
        
        add_meta_box(
            'eventmate-event-recurring',
            __('Eventi Ricorrenti', 'eventmate-pro'),
            array($this, 'event_recurring_meta_box'),
            'eventmate_event',
            'side',
            'default'
        );
    }
    
    /**
     * Event details meta box
     */
    public function event_details_meta_box($post) {
        wp_nonce_field('eventmate_meta_box_nonce', 'eventmate_meta_box_nonce');
        
        // Get existing values
        $start_date = get_post_meta($post->ID, '_eventmate_start_date', true);
        $end_date = get_post_meta($post->ID, '_eventmate_end_date', true);
        $location = get_post_meta($post->ID, '_eventmate_location', true);
        $address = get_post_meta($post->ID, '_eventmate_address', true);
        $price = get_post_meta($post->ID, '_eventmate_price', true);
        $is_free = get_post_meta($post->ID, '_eventmate_is_free', true);
        $max_attendees = get_post_meta($post->ID, '_eventmate_max_attendees', true);
        $enable_booking = get_post_meta($post->ID, '_eventmate_enable_booking', true);
        $woocommerce_product_id = get_post_meta($post->ID, '_eventmate_woocommerce_product_id', true);
        ?>
        
        <table class="form-table eventmate-meta-table">
            <tr>
                <th><label for="eventmate_start_date"><?php _e('Data e Ora Inizio', 'eventmate-pro'); ?></label></th>
                <td>
                    <input type="datetime-local" id="eventmate_start_date" name="eventmate_start_date" 
                           value="<?php echo esc_attr($start_date ? date('Y-m-d\TH:i', strtotime($start_date)) : ''); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Seleziona data e ora di inizio dell\'evento', 'eventmate-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_end_date"><?php _e('Data e Ora Fine', 'eventmate-pro'); ?></label></th>
                <td>
                    <input type="datetime-local" id="eventmate_end_date" name="eventmate_end_date" 
                           value="<?php echo esc_attr($end_date ? date('Y-m-d\TH:i', strtotime($end_date)) : ''); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Seleziona data e ora di fine dell\'evento (opzionale)', 'eventmate-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_location"><?php _e('Luogo', 'eventmate-pro'); ?></label></th>
                <td>
                    <input type="text" id="eventmate_location" name="eventmate_location" 
                           value="<?php echo esc_attr($location); ?>" class="regular-text" />
                    <p class="description"><?php _e('Nome del luogo dell\'evento (es. "Teatro Comunale")', 'eventmate-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_address"><?php _e('Indirizzo', 'eventmate-pro'); ?></label></th>
                <td>
                    <input type="text" id="eventmate_address" name="eventmate_address" 
                           value="<?php echo esc_attr($address); ?>" class="regular-text" />
                    <p class="description"><?php _e('Indirizzo completo per la mappa', 'eventmate-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_is_free"><?php _e('Evento Gratuito', 'eventmate-pro'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="eventmate_is_free" name="eventmate_is_free" value="yes" 
                               <?php checked($is_free, 'yes'); ?> />
                        <?php _e('Questo evento è gratuito', 'eventmate-pro'); ?>
                    </label>
                </td>
            </tr>
            
            <tr class="eventmate-price-row" <?php echo ($is_free == 'yes') ? 'style="display:none;"' : ''; ?>>
                <th><label for="eventmate_price"><?php _e('Prezzo', 'eventmate-pro'); ?></label></th>
                <td>
                    <input type="number" id="eventmate_price" name="eventmate_price" 
                           value="<?php echo esc_attr($price); ?>" min="0" step="0.01" class="small-text" />
                    <span><?php echo esc_html(get_option('eventmate_currency', 'EUR')); ?></span>
                    <p class="description"><?php _e('Prezzo del biglietto', 'eventmate-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="eventmate_max_attendees"><?php _e('Massimo Partecipanti', 'eventmate-pro'); ?></label></th>
                <td>
                    <input type="number" id="eventmate_max_attendees" name="eventmate_max_attendees" 
                           value="<?php echo esc_attr($max_attendees); ?>" min="0" class="small-text" />
                    <p class="description"><?php _e('Lascia vuoto per illimitato', 'eventmate-pro'); ?></p>
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
    
    /**
     * Event booking meta box
     */
    public function event_booking_meta_box($post) {
        $enable_booking = get_post_meta($post->ID, '_eventmate_enable_booking', true);
        $woocommerce_product_id = get_post_meta($post->ID, '_eventmate_woocommerce_product_id', true);
        $booking_type = get_post_meta($post->ID, '_eventmate_booking_type', true);
        
        // Check if WooCommerce is active
        $woocommerce_active = class_exists('WooCommerce');
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="eventmate_enable_booking"><?php _e('Abilita Prenotazioni', 'eventmate-pro'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="eventmate_enable_booking" name="eventmate_enable_booking" value="yes" 
                               <?php checked($enable_booking, 'yes'); ?> />
                        <?php _e('Permetti prenotazioni per questo evento', 'eventmate-pro'); ?>
                    </label>
                </td>
            </tr>
            
            <tr class="eventmate-booking-options" <?php echo ($enable_booking != 'yes') ? 'style="display:none;"' : ''; ?>>
                <th><label for="eventmate_booking_type"><?php _e('Tipo Prenotazione', 'eventmate-pro'); ?></label></th>
                <td>
                    <select id="eventmate_booking_type" name="eventmate_booking_type">
                        <option value="simple" <?php selected($booking_type, 'simple'); ?>><?php _e('Prenotazione Semplice', 'eventmate-pro'); ?></option>
                        <?php if ($woocommerce_active): ?>
                            <option value="woocommerce" <?php selected($booking_type, 'woocommerce'); ?>><?php _e('WooCommerce', 'eventmate-pro'); ?></option>
                        <?php endif; ?>
                    </select>
                    <p class="description"><?php _e('Scegli il sistema di prenotazione', 'eventmate-pro'); ?></p>
                </td>
            </tr>
            
            <?php if ($woocommerce_active): ?>
            <tr class="eventmate-woocommerce-options" <?php echo ($booking_type != 'woocommerce') ? 'style="display:none;"' : ''; ?>>
                <th><label for="eventmate_woocommerce_product_id"><?php _e('Prodotto WooCommerce', 'eventmate-pro'); ?></label></th>
                <td>
                    <select id="eventmate_woocommerce_product_id" name="eventmate_woocommerce_product_id">
                        <option value=""><?php _e('Seleziona un prodotto', 'eventmate-pro'); ?></option>
                        <?php
                        $products = get_posts(array(
                            'post_type' => 'product',
                            'numberposts' => -1,
                            'post_status' => 'publish'
                        ));
                        
                        foreach ($products as $product) {
                            echo '<option value="' . $product->ID . '" ' . selected($woocommerce_product_id, $product->ID, false) . '>' . esc_html($product->post_title) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Collega questo evento a un prodotto WooCommerce esistente', 'eventmate-pro'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#eventmate_enable_booking').change(function() {
                if ($(this).is(':checked')) {
                    $('.eventmate-booking-options').show();
                    updateBookingTypeOptions();
                } else {
                    $('.eventmate-booking-options').hide();
                }
            });
            
            $('#eventmate_booking_type').change(function() {
                updateBookingTypeOptions();
            });
            
            function updateBookingTypeOptions() {
                if ($('#eventmate_booking_type').val() === 'woocommerce') {
                    $('.eventmate-woocommerce-options').show();
                } else {
                    $('.eventmate-woocommerce-options').hide();
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * Event recurring meta box
     */
    public function event_recurring_meta_box($post) {
        $is_recurring = get_post_meta($post->ID, '_eventmate_is_recurring', true);
        $recurring_type = get_post_meta($post->ID, '_eventmate_recurring_type', true);
        $recurring_interval = get_post_meta($post->ID, '_eventmate_recurring_interval', true);
        $recurring_end_date = get_post_meta($post->ID, '_eventmate_recurring_end_date', true);
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="eventmate_is_recurring"><?php _e('Evento Ricorrente', 'eventmate-pro'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="eventmate_is_recurring" name="eventmate_is_recurring" value="yes" 
                               <?php checked($is_recurring, 'yes'); ?> />
                        <?php _e('Questo evento si ripete', 'eventmate-pro'); ?>
                    </label>
                </td>
            </tr>
            
            <tr class="eventmate-recurring-options" <?php echo ($is_recurring != 'yes') ? 'style="display:none;"' : ''; ?>>
                <th><label for="eventmate_recurring_type"><?php _e('Tipo Ricorrenza', 'eventmate-pro'); ?></label></th>
                <td>
                    <select id="eventmate_recurring_type" name="eventmate_recurring_type">
                        <option value="daily" <?php selected($recurring_type, 'daily'); ?>><?php _e('Giornaliera', 'eventmate-pro'); ?></option>
                        <option value="weekly" <?php selected($recurring_type, 'weekly'); ?>><?php _e('Settimanale', 'eventmate-pro'); ?></option>
                        <option value="monthly" <?php selected($recurring_type, 'monthly'); ?>><?php _e('Mensile', 'eventmate-pro'); ?></option>
                        <option value="yearly" <?php selected($recurring_type, 'yearly'); ?>><?php _e('Annuale', 'eventmate-pro'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr class="eventmate-recurring-options" <?php echo ($is_recurring != 'yes') ? 'style="display:none;"' : ''; ?>>
                <th><label for="eventmate_recurring_interval"><?php _e('Intervallo', 'eventmate-pro'); ?></label></th>
                <td>
                    <input type="number" id="eventmate_recurring_interval" name="eventmate_recurring_interval" 
                           value="<?php echo esc_attr($recurring_interval ?: 1); ?>" min="1" class="small-text" />
                    <p class="description"><?php _e('Ogni quanti giorni/settimane/mesi/anni', 'eventmate-pro'); ?></p>
                </td>
            </tr>
            
            <tr class="eventmate-recurring-options" <?php echo ($is_recurring != 'yes') ? 'style="display:none;"' : ''; ?>>
                <th><label for="eventmate_recurring_end_date"><?php _e('Fine Ricorrenza', 'eventmate-pro'); ?></label></th>
                <td>
                    <input type="date" id="eventmate_recurring_end_date" name="eventmate_recurring_end_date" 
                           value="<?php echo esc_attr($recurring_end_date ? date('Y-m-d', strtotime($recurring_end_date)) : ''); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Fino a quando ripetere l\'evento (opzionale)', 'eventmate-pro'); ?></p>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#eventmate_is_recurring').change(function() {
                if ($(this).is(':checked')) {
                    $('.eventmate-recurring-options').show();
                } else {
                    $('.eventmate-recurring-options').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        // Check nonce
        if (!isset($_POST['eventmate_meta_box_nonce']) || !wp_verify_nonce($_POST['eventmate_meta_box_nonce'], 'eventmate_meta_box_nonce')) {
            return;
        }
        
        // Check if user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if not an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) != 'eventmate_event') {
            return;
        }
        
        // Save meta fields
        $meta_fields = array(
            'eventmate_start_date',
            'eventmate_end_date',
            'eventmate_location',
            'eventmate_address',
            'eventmate_price',
            'eventmate_is_free',
            'eventmate_max_attendees',
            'eventmate_enable_booking',
            'eventmate_booking_type',
            'eventmate_woocommerce_product_id',
            'eventmate_is_recurring',
            'eventmate_recurring_type',
            'eventmate_recurring_interval',
            'eventmate_recurring_end_date'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                
                // Special handling for datetime fields
                if (in_array($field, array('eventmate_start_date', 'eventmate_end_date'))) {
                    $value = sanitize_text_field($_POST[$field]);
                    if (!empty($value)) {
                        $value = date('Y-m-d H:i:s', strtotime($value));
                    }
                }
                
                // Special handling for recurring end date
                if ($field == 'eventmate_recurring_end_date' && !empty($value)) {
                    $value = date('Y-m-d', strtotime($value));
                }
                
                update_post_meta($post_id, '_' . $field, $value);
            } else {
                // For checkboxes, if not set, save as 'no'
                if (in_array($field, array('eventmate_is_free', 'eventmate_enable_booking', 'eventmate_is_recurring'))) {
                    update_post_meta($post_id, '_' . $field, 'no');
                }
            }
        }
        
        // Handle recurring events
        if (isset($_POST['eventmate_is_recurring']) && $_POST['eventmate_is_recurring'] == 'yes') {
            $this->create_recurring_events($post_id);
        }
    }
    
    /**
     * Create recurring events
     */
    private function create_recurring_events($parent_id) {
        $start_date = get_post_meta($parent_id, '_eventmate_start_date', true);
        $recurring_type = get_post_meta($parent_id, '_eventmate_recurring_type', true);
        $recurring_interval = get_post_meta($parent_id, '_eventmate_recurring_interval', true);
        $recurring_end_date = get_post_meta($parent_id, '_eventmate_recurring_end_date', true);
        
        if (empty($start_date) || empty($recurring_type)) {
            return;
        }
        
        // This will be implemented in the recurring events class
        do_action('eventmate_create_recurring_events', $parent_id, $start_date, $recurring_type, $recurring_interval, $recurring_end_date);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type == 'eventmate_event') {
            wp_enqueue_style('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-datepicker');
        }
    }
}