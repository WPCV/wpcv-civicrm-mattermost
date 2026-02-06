<?php
/**
 * Integrate CiviCRM with Mattermost
 *
 * Plugin Name:       Integrate CiviCRM with Mattermost
 * Description:       Integrates CiviCRM with Mattermost.
 * Plugin URI:        https://github.com/wpcv/wpcv-civicrm-mattermost
 * GitHub Plugin URI: https://github.com/wpcv/wpcv-civicrm-mattermost
 * Version:           1.0.0b
 * Author:            Christian Wach
 * Author URI:        https://haystack.co.uk
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Text Domain:       wpcv-civicrm-mattermost
 * Domain Path:       /languages
 *
 * @package WPCV_CiviCRM_Mattermost
 * @link    https://github.com/wpcv/wpcv-civicrm-mattermost
 * @license GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set plugin version here.
define( 'WPCVMM_VERSION', '1.0.0b' );

// Store reference to this file.
if ( ! defined( 'WPCVMM_FILE' ) ) {
	define( 'WPCVMM_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'WPCVMM_URL' ) ) {
	define( 'WPCVMM_URL', plugin_dir_url( WPCVMM_FILE ) );
}

// Store path to this plugin's directory.
if ( ! defined( 'WPCVMM_PATH' ) ) {
	define( 'WPCVMM_PATH', plugin_dir_path( WPCVMM_FILE ) );
}

// Set plugin debugging state.
if ( ! defined( 'WPCVMM_DEBUG' ) ) {
	define( 'WPCVMM_DEBUG', false );
}

/**
 * Plugin Class.
 *
 * A class that encapsulates this plugin's functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost {

	/**
	 * Admin object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Admin
	 */
	public $admin;

	/**
	 * Mattermost object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Mattermost
	 */
	public $mattermost;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM
	 */
	public $civicrm;

	/**
	 * Initialises this object.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Initialise this plugin.
		$this->initialise();

	}

	/**
	 * Initialises this plugin.
	 *
	 * @since 1.0.0
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Include WP-CLI command.
		require_once WPCVMM_PATH . 'includes/wp-cli/wp-cli-wpcvmm.php';

		// Bootstrap plugin.
		add_action( 'init', [ $this, 'translation' ] );
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Broadcast that this plugin is loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpcvmm/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Enables translation.
	 *
	 * @since 1.0.0
	 */
	public function translation() {

		// Load translations.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'wpcv-civicrm-mattermost', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( WPCVMM_FILE ) ) . '/languages/' // Relative path to files.
		);

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0.0
	 */
	public function include_files() {

		// Load our class files.
		include WPCVMM_PATH . 'includes/admin/class-admin.php';
		include WPCVMM_PATH . 'includes/mattermost/class-mattermost.php';
		include WPCVMM_PATH . 'includes/civicrm/class-civicrm.php';

	}

	/**
	 * Sets up this plugin's objects.
	 *
	 * @since 1.0.0
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->admin      = new WPCV_CiviCRM_Mattermost_Admin( $this );
		$this->mattermost = new WPCV_CiviCRM_Mattermost_Mattermost( $this );
		$this->civicrm    = new WPCV_CiviCRM_Mattermost_CiviCRM( $this );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		// Add action links.
		add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Checks if this plugin is network activated.
	 *
	 * @since 1.0.0
	 *
	 * @return bool $is_network_active True if network activated, false otherwise.
	 */
	public function is_network_activated() {

		// Only need to test once.
		static $is_network_active;

		// Have we done this already?
		if ( isset( $is_network_active ) ) {
			return $is_network_active;
		}

		// If not multisite, it cannot be.
		if ( ! is_multisite() ) {
			$is_network_active = false;
			return $is_network_active;
		}

		// Make sure plugin file is included when outside admin.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Get path from 'plugins' directory to this plugin.
		$this_plugin = plugin_basename( WPCVMM_FILE );

		// Test if network active.
		$is_network_active = is_plugin_active_for_network( $this_plugin );

		// --<
		return $is_network_active;

	}

	/**
	 * Adds links to settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $links The existing links array.
	 * @param string $file The name of the plugin file.
	 * @return array $links The modified links array.
	 */
	public function action_links( $links, $file ) {

		// Bail if not this plugin.
		if ( plugin_basename( dirname( __FILE__ ) . '/wpcv-civicrm-mattermost.php' ) !== $file ) {
			return $links;
		}

		// Add links only when CiviCRM is fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) || ! CIVICRM_INSTALLED ) {
			return $links;
		}

		// Bail if CiviCRM plugin is not present.
		if ( ! function_exists( 'civi_wp' ) ) {
			return $links;
		}

		// Add settings link if not network activated and not viewing network admin.
		$link    = add_query_arg( [ 'page' => 'wpcvmm_settings' ], admin_url( 'admin.php' ) );
		$links[] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'wpcv-civicrm-mattermost' ) . '</a>';

		// Always add Paypal link.
		$paypal  = 'https://www.paypal.me/interactivist';
		$links[] = '<a href="' . esc_url( $paypal ) . '" target="_blank">' . __( 'Donate!', 'wpcv-civicrm-mattermost' ) . '</a>';

		// --<
		return $links;

	}

	/**
	 * Write to the error log.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The data to write to the log file.
	 */
	public function log_error( $data = [] ) {

		// Skip if not debugging.
		if ( WP_DEBUG === false ) {
			return;
		}

		// Skip if empty.
		if ( empty( $data ) ) {
			return;
		}

		// Format data.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$error = print_r( $data, true );

		// Write to log file.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $error );

	}

	/**
	 * Write a message to the log file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The message to write to the log file.
	 */
	public function log_message( $message = '' ) {

		// Skip if not debugging.
		if ( WP_DEBUG === false ) {
			return;
		}

		// Write to log file.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $message );

	}

}

/**
 * Loads plugin if not yet loaded and return reference.
 *
 * @since 1.0.0
 *
 * @return WPCV_CiviCRM_Mattermost $plugin The plugin reference.
 */
function wpcv_civicrm_mattermost() {

	// Instantiate plugin if not yet instantiated.
	static $plugin;
	if ( ! isset( $plugin ) ) {
		$plugin = new WPCV_CiviCRM_Mattermost();
	}

	// --<
	return $plugin;

}

// Load immediately.
wpcv_civicrm_mattermost();

/**
 * Performs plugin activation tasks.
 *
 * @since 1.0.0
 */
function wpcv_civicrm_mattermost_activate() {

	/**
	 * Fires when this plugin has been activated.
	 *
	 * @since 1.0.0
	 */
	do_action( 'wpcvmm/activated' );

}

// Activation.
register_activation_hook( __FILE__, 'wpcv_civicrm_mattermost_activate' );

/**
 * Performs plugin deactivation tasks.
 *
 * @since 1.0.0
 */
function wpcv_civicrm_mattermost_deactivated() {

	/**
	 * Fires when this plugin has been deactivated.
	 *
	 * @since 1.0.0
	 */
	do_action( 'wpcvmm/deactivated' );

}

// Deactivation.
register_deactivation_hook( __FILE__, 'wpcv_civicrm_mattermost_deactivated' );

/*
 * Uninstall uses the 'uninstall.php' method.
 *
 * @see https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */
