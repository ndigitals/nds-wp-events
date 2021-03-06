<?php
/**
 * NDS WordPress Events.
 *
 * @package   NDS_WordPress_Events\Admin
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 * @license   GPL-2.0+
 * @link      http://www.ndigitals.com
 * @copyright 2013 NDigital Solutions
 */

/**
 * Plugin Admin class.
 *
 * @package   NDS_WordPress_Events_Admin
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 */
class NDS_WordPress_Events_Admin
{

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = NULL;

    /**
     * Slug of the plugin screen.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_screen_hook_suffix = NULL;

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a settings page and menu.
     *
     * @since     1.0.0
     */
    private function __construct()
    {

        global $wp_version;

        // Call $plugin_slug from initial plugin class.
        $this->plugin           = NDS_WP_Events::get_instance();
        $this->plugin_slug      = $this->plugin->get_plugin_slug();
        $this->plugin_post_type = $this->plugin->get_plugin_post_type();

        // Load admin style sheet and JavaScript.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Add the options page and menu item.
        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

        // Add an action link pointing to the options page.
        $plugin_basename = plugin_basename( NDSWP_EVENTS_PATH . 'nds-wp-events.php' );
        add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

        // Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
        // Only target versions less than 3.8 that aren't using the MP6 admin interface
        if (!defined( 'MP6' ) && version_compare( $wp_version, '3.8', '<' )) {
            add_action( 'admin_head', array( $this, 'icons_styles' ) );
        }
        add_filter( 'manage_' . $this->plugin_post_type . '_posts_columns', array( $this, 'edit_columns' ) );
        add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ) );
        add_action( 'pre_get_posts', array( $this, 'manage_listing_query' ) );
        add_filter(
            'manage_edit-' . $this->plugin_post_type . '_sortable_columns',
            array( $this, 'column_register_sortable' )
        );
        add_action( 'restrict_manage_posts', array( $this, 'category_filter_list' ) );
        add_filter( 'parse_query', array( $this, 'events_filtering' ) );
        add_action( 'admin_init', array( $this, 'events_admin_init' ) );
        add_action( 'save_post', array( $this, 'save_event' ) );
        add_filter( 'post_updated_messages', array( $this, 'update_messages' ) );

    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if ( NULL == self::$instance )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register and enqueue admin-specific style sheet.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles()
    {

        if ( !isset( $this->plugin_screen_hook_suffix ) )
        {
            return;
        }

        $screen = get_current_screen();
        if ( $screen->id == $this->plugin_screen_hook_suffix || $screen->post_type == $this->plugin_post_type )
        {
            wp_enqueue_style(
                $this->plugin_slug . '-admin-styles',
                NDSWP_EVENTS_URL . 'assets/css/admin.css',
                array(),
                NDS_WP_Events::VERSION
            );
        }

    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts()
    {

        if ( !isset( $this->plugin_screen_hook_suffix ) )
        {
            return;
        }

        $screen = get_current_screen();
        if ( $screen->id == $this->plugin_screen_hook_suffix || $screen->post_type == $this->plugin_post_type )
        {
            wp_enqueue_script(
                $this->plugin_slug . '-admin-script',
                NDSWP_EVENTS_URL . 'assets/js/admin.js',
                array( 'jquery' ),
                NDS_WP_Events::VERSION
            );
        }

    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu()
    {

        /*
         * Add a settings page for this plugin to the Settings menu.
         *
         * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
         *
         *        Administration Menus: http://codex.wordpress.org/Administration_Menus
         */
        $this->plugin_screen_hook_suffix = add_options_page(
            __( 'Events Settings', $this->plugin_slug ),
            __( 'Events Settings', $this->plugin_slug ),
            'manage_options',
            $this->plugin_slug,
            array( $this, 'display_plugin_admin_page' )
        );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page()
    {
        include_once( NDSWP_EVENTS_PATH . 'inc/admin/settings.php' );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links( $links )
    {

        return array_merge(
            array(
                 'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __(
                         'Settings',
                         $this->plugin_slug
                     ) . '</a>'
            ),
            $links
        );

    }

    /**
     * Define icon styles for the Events custom post type
     *
     * @since    1.0.0
     */
    public function icons_styles()
    {
        $menu_post_type_class = '#menu-posts-' . $this->plugin_post_type;
        $menu_icon            = NDSWP_EVENTS_URL . 'assets/images/calendar-month.png';
        $page_icon            = NDSWP_EVENTS_URL . 'assets/images/calendar-month-32x32.png';        ?>
        <style type="text/css" media="screen"><?php
echo <<<CSS
            {$menu_post_type_class} .wp-menu-image {
                background: url({$menu_icon}) no-repeat 6px -18px !important;
            }

            {$menu_post_type_class}:hover .wp-menu-image,
            {$menu_post_type_class}.wp-has-current-submenu .wp-menu-image {
                background-position: 6px 6px !important;
            }

            #icon-edit.icon32-posts-{$this->plugin_post_type} {
                background: url({$page_icon}) no-repeat;
            }
CSS;
?>
        </style>
    <?php
    }


    /**
     * Setup Admin Event Listing Headers
     *
     * @since    1.0.0
     */
    public function edit_columns( $columns )
    {
        $columns = array(
            'cb'                                        => '<input type="checkbox" />',
            'title'                                     => 'Event',
            $this->plugin_post_type . '_category_fmt'   => 'Category',
            $this->plugin_post_type . '_tags_fmt'       => 'Tags',
            $this->plugin_post_type . '_location_fmt'   => 'Location',
            $this->plugin_post_type . '_start_date_fmt' => 'Start Date/Time',
            $this->plugin_post_type . '_end_date_fmt'   => 'End Date/Time'
        );

        return $columns;
    }


    /**
     * Setup Admin Event Listing Item Formats
     *
     * @since    1.0.0
     */
    public function custom_columns( $column )
    {
        global $post;
        $custom = get_post_custom();
        // get WordPress settings user defined Date/Time formats
        $date_format = get_option( 'date_format' );
        $time_format = get_option( 'time_format' );

        switch ( $column )
        {
            case $this->plugin_post_type . '_category_fmt':
                // - show taxonomy terms -
                $event_categories      = get_the_terms( $post->ID, $this->plugin_post_type . '_category' );
                $event_categories_html = array();
                if ( $event_categories )
                {
                    foreach ( $event_categories as $event_category )
                    {
                        array_push( $event_categories_html, $event_category->name );
                    }
                    echo implode( $event_categories_html, ", " );
                }
                else
                {
                    _e( 'None' );
                }
                break;
            case $this->plugin_post_type . '_tags_fmt':
                // - show taxonomy terms -
                $event_tags      = get_the_terms( $post->ID, $this->plugin_post_type . '_tag' );
                $event_tags_html = array();
                if ( $event_tags )
                {
                    foreach ( $event_tags as $event_tag )
                    {
                        array_push(
                            $event_tags_html,
                            '<a href="edit.php?post_type=' . $this->plugin_post_type . '&taxonomy=' . $this->plugin_post_type . '_tag&term=' . $event_tag->slug . '">' . $event_tag->name . '</a>'
                        );
                    }
                    echo implode( $event_tags_html, ", " );
                }
                else
                {
                    _e( 'None' );
                }
                break;
            case $this->plugin_post_type . '_start_date_fmt':
                $start_date = date( $date_format, $custom[$this->plugin_post_type . "_start_date"][0] );
                $start_time = date( $time_format, $custom[$this->plugin_post_type . "_start_date"][0] );
                echo $start_date, '<br /><em>', $start_time . '</em>';
                break;
            case $this->plugin_post_type . '_end_date_fmt':
                $end_date = date( $date_format, $custom[$this->plugin_post_type . "_end_date"][0] );
                $end_time = date( $time_format, $custom[$this->plugin_post_type . "_end_date"][0] );
                echo $end_date, '<br /><em>', $end_time . '</em>';
                break;
            case $this->plugin_post_type . '_location_fmt':
                echo $custom[$this->plugin_post_type . "_location"][0];
                break;
        }
    }


    /**
     * Customize the admin Events Query using Post Meta
     *
     * @since    1.0.0
     *
     * @param object $query data
     */
    public function manage_listing_query( $query )
    {
        // http://codex.wordpress.org/Function_Reference/current_time
        $current_time = current_time( 'timestamp' );

        global $wp_the_query;

        // Admin LIsting
        if ( $wp_the_query === $query && is_admin() && is_post_type_archive( $this->plugin_post_type ) )
        {
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'meta_key', $this->plugin_post_type . '_start_date' );
            $query->set( 'order', 'DESC' );
        }
    }


    /**
     * Setup which columns are sortable.
     *
     * @since    1.0.0
     *
     * @param array $columns
     *
     * @return array
     */
    public function column_register_sortable( $columns )
    {
        $columns[$this->plugin_post_type . '_category_fmt']   = $this->plugin_post_type . '_category';
        $columns[$this->plugin_post_type . '_start_date_fmt'] = $this->plugin_post_type . '_start_date';
        $columns[$this->plugin_post_type . '_end_date_fmt']   = $this->plugin_post_type . '_end_date';

        return $columns;
    }


    /**
     * Setup an events category filtering list.
     *
     * @since    1.0.0
     */
    public function category_filter_list()
    {
        $screen = get_current_screen();
        global $wp_query;
        if ( is_admin() && $screen->post_type == $this->plugin_post_type )
        {
            wp_dropdown_categories(
                array(
                     'show_option_all' => 'Show All Categories',
                     'taxonomy'        => $this->plugin_post_type . '_category',
                     'name'            => $this->plugin_post_type . '_category',
                     'orderby'         => 'name',
                     'selected'        => ( isset( $wp_query->query[$this->plugin_post_type . '_category'] ) ? $wp_query->query[$this->plugin_post_type . '_category'] : '' ),
                     'hierarchical'    => TRUE,
                     'depth'           => 3,
                     'show_count'      => TRUE,
                     'hide_empty'      => TRUE
                )
            );
        }
    }


    /**
     * Setup custom filtering for events.
     *
     * @since    1.0.0
     */
    public function events_filtering( $query )
    {
        $query_vars = & $query->query_vars;

        if ( isset( $query_vars[$this->plugin_post_type . '_category'] ) && is_numeric(
                $query_vars[$this->plugin_post_type . '_category']
            )
        )
        {
            $term                                              = get_term_by(
                'id',
                $query_vars[$this->plugin_post_type . '_category'],
                $this->plugin_post_type . '_category'
            );
            $query_vars[$this->plugin_post_type . '_category'] = $term->slug;
        }
    }


    /**
     * Setup the custom Event details meta box
     *
     * @since    1.0.0
     */
    public function post_type_metabox()
    {
        // We need the jQuery UI Datepicker
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style(
            'jquery-style',
            '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css'
        );

        // - grab wp time format -
        $date_format = get_option( 'date_format' );
        $time_format = get_option( 'time_format' );

        $meta_start_time = $meta_start_date = $this->get_event_field( $this->plugin_post_type . '_start_date' );
        $meta_end_time   = $meta_end_date = $this->get_event_field( $this->plugin_post_type . '_end_date' );

        // - populate today if empty, 00:00 for time -
        if ( $meta_start_date == NULL || $meta_start_time == '' || strlen( $meta_start_time ) <= 0 )
        {
            $meta_end_date = $meta_start_date = current_time( 'timestamp' );
            $meta_end_time = $meta_start_time = current_time( 'timestamp' );
        }

        // - convert to pretty formats -
        $meta_start_date_fmt = date( $date_format, $meta_start_date );
        $meta_start_time_fmt = date( $time_format, $meta_start_time );
        $meta_end_date_fmt   = date( $date_format, $meta_end_date );
        $meta_end_time_fmt   = date( $time_format, $meta_end_time );

        $css_meta_class = $this->plugin_slug . '-meta';
        ?>
        <input type="hidden" name="<?php echo $this->plugin_post_type ?>_nonce" id="<?php echo $this->plugin_slug ?>-nonce"
               value="<?php echo wp_create_nonce( $this->plugin_slug . '-nonce' ); ?>"/>
        <ul class="<?php echo $css_meta_class ?> clearfix">
            <li class="clearfix">
                <label>Start
                    Date: </label><input type="text" name="<?php echo $this->plugin_post_type ?>_start_date" class="eventdate"
                                         value="<?php echo $meta_start_date_fmt; ?>"/>
                <label>Start
                    Time: </label><input type="text" name="<?php echo $this->plugin_post_type ?>_start_time"
                                         value="<?php echo $meta_start_time_fmt; ?>"/>
                <em>(e.g. <?php echo date( $time_format, 0 ); ?>)</em>
            </li>
            <li class="clearfix">
                <label>End
                    Date: </label><input type="text" name="<?php echo $this->plugin_post_type ?>_end_date" class="eventdate"
                                         value="<?php echo $meta_end_date_fmt; ?>"/>
                <label>End
                    Time: </label><input type="text" name="<?php echo $this->plugin_post_type ?>_end_time"
                                         value="<?php echo $meta_end_time_fmt; ?>"/> <em>(e.g. <?php echo date(
                        $time_format,
                        0
                    ); ?>)</em>
            </li>
            <li class="clearfix">
                <label>Location: </label><input type="text" size="70" name="<?php echo $this->plugin_post_type ?>_location"
                                                value="<?php echo $this->get_event_field(
                                                                       $this->plugin_post_type . '_location'
                                                ); ?>"/></li>
            <li class="clearfix">
                <label>URL: </label><input type="text" size="70" name="<?php echo $this->plugin_post_type ?>_url"
                                           value="<?php echo $this->get_event_field(
                                                                  $this->plugin_post_type . '_url'
                                           ); ?>"/> <em>(optional)</em></li>
        </ul>
        <style type="text/css"><?php
echo <<<CSS
            .{$css_meta_class} {
                margin: 0;
            }

            .{$css_meta_class} li {
                clear: left;
                vertical-align: middle;
            }

            .{$css_meta_class} label,
            .{$css_meta_class} input,
            .{$css_meta_class} em {
                float: left;
            }

            .{$css_meta_class} label,
            .{$css_meta_class} em {
                width: 100px;
                padding: 5px 0 0 0;
            }

            .{$css_meta_class} input {
                margin-right: 4px;
            }

            .{$css_meta_class} em {
                color: gray;
            }
CSS;
?>
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $(".eventdate").datepicker({
                    dateFormat: '<?php echo $this->php_to_datepicker_format($date_format); ?>',
                    numberOfMonths: 2
                });
            });
        </script>
    <?php
    }

    public function events_admin_init()
    {
        add_meta_box(
            'event_meta',
            'Event Details',
            array( $this, 'post_type_metabox' ),
            $this->plugin_post_type,
            'normal',
            'high'
        );
    }

    /**
     * Save Event meta box data.
     *
     * @since    1.0.0
     */
    function save_event()
    {
        global $post;

        // Only save for this post type.
        if ( !isset($post->post_type) || $this->plugin_post_type != $post->post_type )
        {
            return;
        }

        // - still require nonce
        $post_type_nonce = $this->plugin_post_type . '_nonce';
        if ( !wp_verify_nonce( $_POST[$post_type_nonce], $this->plugin_slug . '-nonce' ) )
        {
            return $post->ID;
        }

        if ( !current_user_can( 'edit_post', $post->ID ) )
        {
            return $post->ID;
        }

        // - convert back to unix & update post
        if ( !isset( $_POST[$this->plugin_post_type . '_start_date'] ) )
        {
            return $post;
        }
        $updatestartd = strtotime(
            $_POST[$this->plugin_post_type . '_start_date'] . $_POST[$this->plugin_post_type . '_start_time']
        );
        update_post_meta(
            $post->ID,
            $this->plugin_post_type . '_start_date',
            sanitize_text_field( $updatestartd )
        );

        if ( !isset( $_POST[$this->plugin_post_type . '_end_date'] ) )
        {
            return $post;
        }
        $updateendd = strtotime(
            $_POST[$this->plugin_post_type . '_end_date'] . $_POST[$this->plugin_post_type . '_end_time']
        );
        update_post_meta(
            $post->ID,
            $this->plugin_post_type . '_end_date',
            sanitize_text_field( $updateendd )
        );

        if ( isset( $_POST[$this->plugin_post_type . '_location'] ) )
        {
            update_post_meta(
                $post->ID,
                $this->plugin_post_type . '_location',
                sanitize_text_field( $_POST[$this->plugin_post_type . '_location'] )
            );
        }

        if ( isset( $_POST[$this->plugin_post_type . '_url'] ) )
        {
            update_post_meta(
                $post->ID,
                $this->plugin_post_type . '_url',
                sanitize_text_field( $_POST[$this->plugin_post_type . '_url'] )
            );
        }
    }


    /**
     * Setup nice admin ui messages.
     *
     * @since    1.0.0
     *
     * @param array $messages
     *
     * @return array
     */
    public function update_messages( $messages )
    {
        global $post, $post_ID;

        $messages[$this->plugin_post_type] = array(
            0  => '', // Unused. Messages start at index 1.
            1  => sprintf( __( 'Event updated. <a href="%s">View item</a>' ), esc_url( get_permalink( $post_ID ) ) ),
            2  => __( 'Custom field updated.' ),
            3  => __( 'Custom field deleted.' ),
            4  => __( 'Event updated.' ),
            /* translators: %s: date and time of the revision */
            5  => isset( $_GET['revision'] ) ? sprintf(
                    __( 'Event restored to revision from %s' ),
                    wp_post_revision_title( (int)$_GET['revision'], FALSE )
                ) : FALSE,
            6  => sprintf( __( 'Event published. <a href="%s">View event</a>' ), esc_url( get_permalink( $post_ID ) ) ),
            7  => __( 'Event saved.' ),
            8  => sprintf(
                __( 'Event submitted. <a target="_blank" href="%s">Preview event</a>' ),
                esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
            ),
            9  => sprintf(
                __( 'Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>' ),
                // translators: Publish box date format, see http://php.net/date
                date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ),
                esc_url( get_permalink( $post_ID ) )
            ),
            10 => sprintf(
                __( 'Event draft updated. <a target="_blank" href="%s">Preview event</a>' ),
                esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
            ),
        );

        return $messages;
    }


    /**
     * Event field helper method.
     *
     * @since    1.0.0
     *
     * @param string $event_field
     */
    private function get_event_field( $event_field )
    {
        global $post;

        $custom = get_post_custom( $post->ID );

        if ( isset( $custom[$event_field] ) )
        {

            return $custom[$event_field][0];
        }
    }


    /**
     * PHP date() format to jQueryUI Datepicker format
     *
     * @since    1.0.0
     *
     * @param string $php_format
     *
     * @return string
     */
    private function php_to_datepicker_format( $php_format )
    {
        $PHP_matching_JS = array(
            // Day
            'd' => 'dd',
            'D' => 'D',
            'j' => 'd',
            'l' => 'DD',
            'N' => '',
            'S' => '',
            'w' => '',
            'z' => 'o',
            // Week
            'W' => '',
            // Month
            'F' => 'MM',
            'm' => 'mm',
            'M' => 'M',
            'n' => 'm',
            't' => '',
            // Year
            'L' => '',
            'o' => '',
            'Y' => 'yy',
            'y' => 'y',
            // Time
            'a' => '',
            'A' => '',
            'B' => '',
            'g' => '',
            'G' => '',
            'h' => '',
            'H' => '',
            'i' => '',
            's' => '',
            'u' => ''
        );

        $js_format = "";
        $escaping  = FALSE;

        for ( $i = 0; $i < strlen( $php_format ); $i++ )
        {
            $char = $php_format[$i];
            if ( $char === '\\' ) // PHP date format escaping character
            {
                $i++;
                if ( $escaping )
                {
                    $js_format .= $php_format[$i];
                }
                else
                {
                    $js_format .= '\'' . $php_format[$i];
                }
                $escaping = TRUE;
            }
            else
            {
                if ( $escaping )
                {
                    $js_format .= "'";
                    $escaping = FALSE;
                }
                if ( isset( $PHP_matching_JS[$char] ) )
                {
                    $js_format .= $PHP_matching_JS[$char];
                }
                else
                {
                    $js_format .= $char;
                }
            }
        }

        return $js_format;
    }

}