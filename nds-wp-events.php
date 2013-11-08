<?php
/**
 * @package   NDS_WP_Events
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 * @license   GPL-2.0+
 * @link      http://www.ndigitals.com
 * @copyright 2013 NDigital Solutions
 *
 * @wordpress-plugin
 *            Plugin Name: NDS WordPress Events
 *            Plugin URI:  http://www.ndigitals.com/wordpress/events-plugin/
 *            Description: A simple events module using WordPress custom post types
 *            Version:     1.0.0
 *            Author:      Tim Nolte
 *            Author URI:  http://www.ndigitals.com/
 *            Text Domain: nds-wp-events-locale
 *            License:     GPL-2.0+
 *            License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *            Domain Path: /languages
 *            GitHub Plugin URI: https://github.com/timnolte/nds-wp-events
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) )
{
    die;
}

/**
 * Define a plugin path global so we don't have to call the function
 */
if ( !defined( 'NDSWP_EVENTS_PATH' ) )
{
    define( 'NDSWP_EVENTS_PATH', plugin_dir_path( __FILE__ ) );
}

/**
 * Define a plugin directory global so we don't have to call functions
 */
if ( !defined( 'NDSWP_EVENTS_DIR' ) )
{
    define( 'NDSWP_EVENTS_DIR', basename( NDSWP_EVENTS_PATH ) );
}

/**
 * Define a plugin URL global so we don't have to call the function
 * NOTE: Protocol stripped in order to provide an agnostic URL reference
 */
if ( !defined( 'NDSWP_EVENTS_URL' ) )
{
    define( 'NDSWP_EVENTS_URL', str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) );
}

require_once( NDSWP_EVENTS_PATH . 'inc/class-nds-wp-events.php' );
if ( is_admin() )
{
    require_once( NDSWP_EVENTS_PATH . 'inc/admin/class-admin.php' );
}

// Register hooks that are fired when the plugin is activated or deactivated.
// When the plugin is deleted, the uninstall.php file is loaded.
register_activation_hook( __FILE__, array( 'NDS_WP_Events', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'NDS_WP_Events', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'NDS_WP_Events', 'get_instance' ) );
if ( is_admin() )
{
    add_action( 'plugins_loaded', array( 'NDS_WordPress_Events_Admin', 'get_instance' ) );
}