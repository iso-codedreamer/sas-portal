<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://codedreamers.co.tz
 * @since             1.0.0
 * @package           SAS Portal
 *
 * @wordpress-plugin
 * Plugin Name:       ISO SAS Web Portal
 * Plugin URI:        http://codedreamers.co.tz
 * Description:       Provides a portal for parents to access student information & updates
 * Version:           1.0.0
 * Author:            Code Dreamers
 * Author URI:        http://codedreamers.co.tz
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SAS_PORTAL_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sas-portal-activator.php
 */
function activate_SAS_Portal() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-sas-portal-activator.php';
    SAS_Portal_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sas-portal-deactivator.php
 */
function deactivate_SAS_Portal() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-sas-portal-deactivator.php';
    SAS_Portal_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_SAS_Portal' );
register_deactivation_hook( __FILE__, 'deactivate_SAS_Portal' );
add_shortcode('sasportal', function() {
    return SAS_Portal_Public::run_shortcode();
});
add_action('init', function() {
    session_name("SASPORTAL");
    session_set_cookie_params(60*60*24*365);
    session_start();
});


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sas-portal.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_SAS_Portal() {

    $plugin = new SAS_Portal();
    $plugin->run();

}
run_SAS_Portal();
