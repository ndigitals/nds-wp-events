<?php
/**
 * Created by PhpStorm.
 * User: timnolte
 * Date: 2/27/14
 * Time: 10:52 PM
 */

$plugin = NDS_WP_Events::get_instance();
$plugin_post_type = $plugin->get_plugin_post_type();

$css_post_id = 'post-' . $post->ID;
$css_oddeven_class = is_int($postcount/2) ? 'upcoming-event-odd' : 'upcoming-event-odd';

$event_categories = get_the_term_list( $post->ID, $plugin_post_type . '_category' );
$tag_list = get_the_term_list( $post->ID, $plugin_post_type . '_tag' );

// - grab wp time format -
$date_format = get_option( 'date_format' );
$time_format = get_option( 'time_format' );
// - get meta field values for start/end date/time
$event_start = get_post_meta( $post->ID, $plugin_post_type . '_start_date', true );
$event_end = get_post_meta( $post->ID, $plugin_post_type . '_end_date', true );
// - convert to pretty formats -
$event_start_fmt = date( $date_format, $event_start ) . ' ' . date( $time_format, $event_start );
$event_end_fmt = date( $date_format, $event_end ) . ' ' . date( $time_format, $event_end );

$event_location = get_post_meta( $post->ID, $plugin_post_type . '_location', true );

$event_url = get_post_meta( $post->ID, $plugin_post_type . '_url', true );
$event_url = ( strlen( $event_url ) > 0 ) ? $event_url : get_permalink( $post->ID );
$event_url_label = stristr($tag_list, 'Register') ? 'Register' : 'More';

?>
<li id="<?php echo $css_post_id; ?>" <?php post_class("$css_oddeven_class clearfix"); ?>>
    <div class="event-details">
        <h5><?php the_title(); ?></h5>
        <?php echo $event_start_fmt ?> / <? echo $event_categories; ?>
        <?php echo (strlen($event_location) > 0) ? '<br/>Location: ' . $event_location : ''; ?>
    </div>
    <button class="btn"><a href="<? echo $event_url; ?>"><? echo $event_url_label; ?></a></button>
</li>
