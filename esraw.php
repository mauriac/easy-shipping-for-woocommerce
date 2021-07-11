<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              #
 * @since             1.0.0
 * @package           Esraw
 *
 * @wordpress-plugin
 * Plugin Name:       Easy Shipping Rate for Woocommerce
 * Plugin URI:        #
 * Description:       Easy Shipping for Woocommerce allows you to easily create new shipping methods. It is a very flexible plugin with which you can condition the pricing of your shipping methods.
 * Version:           1.0.4
 * Author:            DigitCode
 * Author URI:        #
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       esraw
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
define( 'ESRAW_VERSION', '1.0.4' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-esraw-activator.php
 */
function activate_esraw() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-esraw-activator.php';
	Esraw_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-esraw-deactivator.php
 */
function deactivate_esraw() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-esraw-deactivator.php';
	Esraw_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_esraw' );
register_deactivation_hook( __FILE__, 'deactivate_esraw' );


if ( ! class_exists( 'WooCommerce' ) ) {
	require plugin_dir_path( __FILE__ ) . '../woocommerce/woocommerce.php';
}
require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';

require_once plugin_dir_path( __FILE__ ) . 'includes/class-esraw-shipping-easy-rate.php';
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-esraw.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_esraw() {

	$plugin = new Esraw();
	$plugin->run();

}
run_esraw();
