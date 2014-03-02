<?php
/**
 * NDS WordPress Events.
 *
 * @package   NDS_WordPress_Events\Widgets\Upcoming_Events
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 * @license   GPL-2.0+
 * @link      http://www.ndigitals.com
 * @copyright 2013 NDigital Solutions
 *
 * TODO: http://plugins.svn.wordpress.org/latest-custom-post-type-updates/trunk/index.php
 */


/**
 * Adds NDS_WP_Upcoming_Events_Widget widget.
 */
class NDS_WP_Upcoming_Events_Widget extends WP_Widget
{

    /**
     * Widget template file name part. Should be prepended with
     * the plugin_slug when used to find/load the widget template.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $template_filename_base = 'upcoming-widget';

    /**
     * Register widget with WordPress.
     */
    function __construct()
    {

        // Call $plugin_slug from initial plugin class.
        $this->plugin           = NDS_WP_Events::get_instance();
        $this->plugin_slug      = $this->plugin->get_plugin_slug();
        $this->plugin_post_type = $this->plugin->get_plugin_post_type();
        // Defining a class attribute for the widget template filename
        $this->widget_template = $this->plugin_slug . '-' . $this->template_filename_base . '.php';

        parent::__construct(
              $this->plugin_post_type . '_upcoming_events_widget', // Base ID
                  'Upcoming Events', // Name
                  array( 'description' => __( 'Upcoming Events Widget', 'text_domain' ), ) // Args
        );

    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance )
    {
        global $post, $nwe_template_args;

        $args['post_count'] = 4; // TODO: Setup widget admin to allow users to specify this.

        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $args['before_widget'];
        if ( !empty( $title ) )
        {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $events = $this->plugin->get_latest_events($args['post_count']);

        if ( $events->have_posts() )
        {
            $row_counter = 0;

            echo '<ul>';
            while ( $events->have_posts() ) : $events->the_post();
                $nwe_template_args['css_post_id']       = 'post-' . $post->ID;
                $nwe_template_args['css_oddeven_class'] = is_int(
                    ++$row_counter / 2
                ) ? 'upcoming-event-even' : 'upcoming-event-odd';

                $nwe_template_args['event_categories'] = get_the_term_list(
                    $post->ID,
                    $this->plugin_post_type . '_category',
                    '',
                    ', '
                );
                $tag_list                              = get_the_term_list(
                    $post->ID,
                    $this->plugin_post_type . '_tag',
                    '',
                    ', '
                );
                $nwe_template_args['tag_list']         = $tag_list;

                // - grab wp time format -
                $date_format = get_option( 'date_format' );
                $time_format = get_option( 'time_format' );
                // - get meta field values for start/end date/time
                $event_start = get_post_meta( $post->ID, $this->plugin_post_type . '_start_date', true );
                $event_end   = get_post_meta( $post->ID, $this->plugin_post_type . '_end_date', true );
                // - convert to pretty formats -
                $nwe_template_args['event_start_fmt'] = date( $date_format, $event_start ) . ' ' . date(
                        $time_format,
                        $event_start
                    );
                $nwe_template_args['event_end_fmt']   = date( $date_format, $event_end ) . ' ' . date(
                        $time_format,
                        $event_end
                    );

                $nwe_template_args['event_location'] = get_post_meta(
                    $post->ID,
                    $this->plugin_post_type . '_location',
                    true
                );

                $event_url                                      = get_post_meta(
                    $post->ID,
                    $this->plugin_post_type . '_url',
                    true
                );
                $nwe_template_args['event_url']       = ( strlen( $event_url ) > 0 )
                    ? $event_url
                    : get_permalink(
                        $post->ID
                    );
                $nwe_template_args['event_url_label'] = stristr(
                    $tag_list,
                    'Register'
                ) ? 'Register' : 'More';

                if ( $overridden_template = locate_template( $this->widget_template ) ) {
                    // locate_template() returns path to file
                    // if either the child theme or the parent theme have overridden the template
                    include( $overridden_template );
                } else {
                    // If neither the child nor parent theme have overridden the template,
                    // we load the template from the 'templates' sub-directory of the plugin directory
                    include( NDSWP_EVENTS_PATH . 'templates/' . $this->widget_template );
                }
            endwhile;
            echo '</ul>';
        }

        echo $args['after_widget'];

        // Reset Post Data
        wp_reset_postdata();
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance )
    {
        if ( isset( $instance['title'] ) )
        {
            $title = $instance['title'];
        }
        else
        {
            $title = __( 'New title', 'text_domain' );
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/>
        </p>
    <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance )
    {
        $instance          = array();
        $instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;
    }

} // class NDS_WP_Upcoming_Events_Widget