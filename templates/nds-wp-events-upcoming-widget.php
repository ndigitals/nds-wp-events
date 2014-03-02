<?php
/**
 * Created by PhpStorm.
 * User: timnolte
 * Date: 2/27/14
 * Time: 10:52 PM
 */

global $nwe_template_args;
?>
<li id="<?php echo $nwe_template_args['css_post_id']; ?>" <?php post_class($nwe_template_args['css_oddeven_class'] . " clearfix"); ?>>
    <div class="event-details">
        <h5><?php the_title(); ?></h5>
        <?php
            echo $nwe_template_args['event_start_fmt'];
            if (strlen($nwe_template_args['event_categories']) > 0) { echo ' / ', $nwe_template_args['event_categories']; }
            if (strlen($nwe_template_args['event_location']) > 0) { echo "<br/>Location: ", $nwe_template_args['event_location']; }
        ?>
    </div>
    <button class="btn"><a href="<?php echo $nwe_template_args['event_url']; ?>"><?php echo $nwe_template_args['event_url_label']; ?></a></button>
</li>
