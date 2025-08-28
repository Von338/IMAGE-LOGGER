<?php
/**
 * Custom Post Types for EventMate Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class EventMate_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('the_content', array($this, 'add_event_details_to_content'));
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register Event post type
        $labels = array(
            'name'                  => _x('Eventi', 'Post Type General Name', 'eventmate-pro'),
            'singular_name'         => _x('Evento', 'Post Type Singular Name', 'eventmate-pro'),
            'menu_name'             => __('Eventi', 'eventmate-pro'),
            'name_admin_bar'        => __('Evento', 'eventmate-pro'),
            'archives'              => __('Archivi Eventi', 'eventmate-pro'),
            'attributes'            => __('Attributi Evento', 'eventmate-pro'),
            'parent_item_colon'     => __('Evento Padre:', 'eventmate-pro'),
            'all_items'             => __('Tutti gli Eventi', 'eventmate-pro'),
            'add_new_item'          => __('Aggiungi Nuovo Evento', 'eventmate-pro'),
            'add_new'               => __('Aggiungi Nuovo', 'eventmate-pro'),
            'new_item'              => __('Nuovo Evento', 'eventmate-pro'),
            'edit_item'             => __('Modifica Evento', 'eventmate-pro'),
            'update_item'           => __('Aggiorna Evento', 'eventmate-pro'),
            'view_item'             => __('Visualizza Evento', 'eventmate-pro'),
            'view_items'            => __('Visualizza Eventi', 'eventmate-pro'),
            'search_items'          => __('Cerca Eventi', 'eventmate-pro'),
            'not_found'             => __('Non trovato', 'eventmate-pro'),
            'not_found_in_trash'    => __('Non trovato nel cestino', 'eventmate-pro'),
            'featured_image'        => __('Immagine in Evidenza', 'eventmate-pro'),
            'set_featured_image'    => __('Imposta immagine in evidenza', 'eventmate-pro'),
            'remove_featured_image' => __('Rimuovi immagine in evidenza', 'eventmate-pro'),
            'use_featured_image'    => __('Usa come immagine in evidenza', 'eventmate-pro'),
            'insert_into_item'      => __('Inserisci nell\'evento', 'eventmate-pro'),
            'uploaded_to_this_item' => __('Caricato in questo evento', 'eventmate-pro'),
            'items_list'            => __('Lista eventi', 'eventmate-pro'),
            'items_list_navigation' => __('Navigazione lista eventi', 'eventmate-pro'),
            'filter_items_list'     => __('Filtra lista eventi', 'eventmate-pro'),
        );
        
        $args = array(
            'label'                 => __('Evento', 'eventmate-pro'),
            'description'           => __('Eventi gestiti da EventMate Pro', 'eventmate-pro'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields'),
            'taxonomies'            => array('event_category', 'event_tag'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-calendar-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array('slug' => 'eventi'),
        );
        
        register_post_type('eventmate_event', $args);
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        // Event Categories
        $category_labels = array(
            'name'                       => _x('Categorie Eventi', 'Taxonomy General Name', 'eventmate-pro'),
            'singular_name'              => _x('Categoria Evento', 'Taxonomy Singular Name', 'eventmate-pro'),
            'menu_name'                  => __('Categorie', 'eventmate-pro'),
            'all_items'                  => __('Tutte le Categorie', 'eventmate-pro'),
            'parent_item'                => __('Categoria Padre', 'eventmate-pro'),
            'parent_item_colon'          => __('Categoria Padre:', 'eventmate-pro'),
            'new_item_name'              => __('Nome Nuova Categoria', 'eventmate-pro'),
            'add_new_item'               => __('Aggiungi Nuova Categoria', 'eventmate-pro'),
            'edit_item'                  => __('Modifica Categoria', 'eventmate-pro'),
            'update_item'                => __('Aggiorna Categoria', 'eventmate-pro'),
            'view_item'                  => __('Visualizza Categoria', 'eventmate-pro'),
            'separate_items_with_commas' => __('Separa le categorie con virgole', 'eventmate-pro'),
            'add_or_remove_items'        => __('Aggiungi o rimuovi categorie', 'eventmate-pro'),
            'choose_from_most_used'      => __('Scegli dalle più usate', 'eventmate-pro'),
            'popular_items'              => __('Categorie Popolari', 'eventmate-pro'),
            'search_items'               => __('Cerca Categorie', 'eventmate-pro'),
            'not_found'                  => __('Non Trovato', 'eventmate-pro'),
            'no_terms'                   => __('Nessuna categoria', 'eventmate-pro'),
        );
        
        $category_args = array(
            'labels'                     => $category_labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'categoria-evento'),
        );
        
        register_taxonomy('event_category', array('eventmate_event'), $category_args);
        
        // Event Tags
        $tag_labels = array(
            'name'                       => _x('Tag Eventi', 'Taxonomy General Name', 'eventmate-pro'),
            'singular_name'              => _x('Tag Evento', 'Taxonomy Singular Name', 'eventmate-pro'),
            'menu_name'                  => __('Tag', 'eventmate-pro'),
            'all_items'                  => __('Tutti i Tag', 'eventmate-pro'),
            'new_item_name'              => __('Nome Nuovo Tag', 'eventmate-pro'),
            'add_new_item'               => __('Aggiungi Nuovo Tag', 'eventmate-pro'),
            'edit_item'                  => __('Modifica Tag', 'eventmate-pro'),
            'update_item'                => __('Aggiorna Tag', 'eventmate-pro'),
            'view_item'                  => __('Visualizza Tag', 'eventmate-pro'),
            'separate_items_with_commas' => __('Separa i tag con virgole', 'eventmate-pro'),
            'add_or_remove_items'        => __('Aggiungi o rimuovi tag', 'eventmate-pro'),
            'choose_from_most_used'      => __('Scegli dai più usati', 'eventmate-pro'),
            'popular_items'              => __('Tag Popolari', 'eventmate-pro'),
            'search_items'               => __('Cerca Tag', 'eventmate-pro'),
            'not_found'                  => __('Non Trovato', 'eventmate-pro'),
            'no_terms'                   => __('Nessun tag', 'eventmate-pro'),
        );
        
        $tag_args = array(
            'labels'                     => $tag_labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'tag-evento'),
        );
        
        register_taxonomy('event_tag', array('eventmate_event'), $tag_args);
    }
    
    /**
     * Add event details to single event content
     */
    public function add_event_details_to_content($content) {
        if (is_singular('eventmate_event') && is_main_query()) {
            $event_details = $this->get_event_details_html(get_the_ID());
            $content = $event_details . $content;
        }
        return $content;
    }
    
    /**
     * Get event details HTML
     */
    private function get_event_details_html($event_id) {
        $start_date = get_post_meta($event_id, '_eventmate_start_date', true);
        $end_date = get_post_meta($event_id, '_eventmate_end_date', true);
        $location = get_post_meta($event_id, '_eventmate_location', true);
        $address = get_post_meta($event_id, '_eventmate_address', true);
        $price = get_post_meta($event_id, '_eventmate_price', true);
        $is_free = get_post_meta($event_id, '_eventmate_is_free', true);
        
        ob_start();
        ?>
        <div class="eventmate-event-details">
            <div class="eventmate-event-meta">
                <?php if ($start_date): ?>
                    <div class="eventmate-meta-item eventmate-date">
                        <span class="eventmate-icon">📅</span>
                        <strong><?php _e('Data:', 'eventmate-pro'); ?></strong>
                        <?php 
                        $start_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start_date));
                        echo $start_formatted;
                        
                        if ($end_date && $end_date != $start_date) {
                            $end_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($end_date));
                            echo ' - ' . $end_formatted;
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($location): ?>
                    <div class="eventmate-meta-item eventmate-location">
                        <span class="eventmate-icon">📍</span>
                        <strong><?php _e('Luogo:', 'eventmate-pro'); ?></strong>
                        <?php echo esc_html($location); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($address): ?>
                    <div class="eventmate-meta-item eventmate-address">
                        <span class="eventmate-icon">🗺️</span>
                        <strong><?php _e('Indirizzo:', 'eventmate-pro'); ?></strong>
                        <?php echo esc_html($address); ?>
                    </div>
                <?php endif; ?>
                
                <div class="eventmate-meta-item eventmate-price">
                    <span class="eventmate-icon">💰</span>
                    <strong><?php _e('Prezzo:', 'eventmate-pro'); ?></strong>
                    <?php 
                    if ($is_free == 'yes') {
                        _e('Gratuito', 'eventmate-pro');
                    } else {
                        $currency = get_option('eventmate_currency', 'EUR');
                        $currency_position = get_option('eventmate_currency_position', 'before');
                        
                        if ($currency_position == 'before') {
                            echo esc_html($currency . ' ' . $price);
                        } else {
                            echo esc_html($price . ' ' . $currency);
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}