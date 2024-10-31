<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://rewardstream.com
 * @since             1.0.0
 * @package           RewardStream
 *
 * @wordpress-plugin
 * Plugin Name:       Refer A Friend by RewardStream
 * Plugin URI:        http://rewardstream.com/woocommerce
 * Description:       An automated customer referral program for your WooCommerce Store.
 * Version:           1.2.3
 * Author:            RewardStream
 * Author URI:        http://www.rewardstream.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rewardstream
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check if WooCommerce is active
if ( ! function_exists( 'is_plugin_active_for_network' ) ) :
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
endif;

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) :
	if ( ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) :
		return;
	endif;
endif;

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_rewardstream() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rewardstream-activator.php';
	Rewardstream_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_rewardstream() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rewardstream-deactivator.php';
	RewardStream_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rewardstream' );
register_deactivation_hook( __FILE__, 'deactivate_rewardstream' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rewardstream.php';

/**
 * Plugin constants
 */
define( 'REWARDSTREAM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'REWARDSTREAM_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rewardstream() {

	$plugin = new RewardStream();
	$plugin->run();

}
run_rewardstream();
