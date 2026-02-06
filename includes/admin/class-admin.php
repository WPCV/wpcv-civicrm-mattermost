<?php
/**
 * Admin class.
 *
 * Handles admin functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 *
 * A class that encapsulates admin functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Admin {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost
	 */
	public $plugin;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $plugin_version;

	/**
	 * Plugin settings option name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $plugin_version_name = 'wpcv_civicrm_mattermost_version';

	/**
	 * Plugin settings option name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $settings_name = 'wpcv_civicrm_mattermost_settings';

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public $settings = [];

	/**
	 * WordPress Schedule object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Schedule
	 */
	public $schedule;

	/**
	 * Settings Page object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Page_Settings
	 */
	public $page_settings;

	/**
	 * Manual Sync Page object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Page_Manual_Sync
	 */
	public $page_manual_sync;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Add action for init.
		add_action( 'wpcvmm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 1.0.0
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Bootstrap admin.
		$this->include_files();
		$this->setup_objects();
		$this->admin_tasks();

		/**
		 * Fires when admin has loaded.
		 *
		 * Used internally to bootstrap objects.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpcvmm/admin/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0.0
	 */
	public function include_files() {

		// Load our class files.
		require WPCVMM_PATH . 'includes/admin/class-page-settings-base.php';
		require WPCVMM_PATH . 'includes/admin/class-page-settings.php';
		require WPCVMM_PATH . 'includes/admin/class-admin-batch.php';
		require WPCVMM_PATH . 'includes/admin/class-admin-stepper.php';
		require WPCVMM_PATH . 'includes/admin/class-page-manual-sync.php';
		require WPCVMM_PATH . 'includes/admin/class-admin-schedule.php';

	}

	/**
	 * Sets up this plugin's objects.
	 *
	 * @since 1.0.0
	 */
	public function setup_objects() {

		// Instantiate objects.
		$this->page_settings    = new WPCV_CiviCRM_Mattermost_Page_Settings( $this );
		$this->page_manual_sync = new WPCV_CiviCRM_Mattermost_Page_Manual_Sync( $this->page_settings );
		$this->schedule         = new WPCV_CiviCRM_Mattermost_Schedule( $this );

	}

	/**
	 * Performs plugin admin tasks.
	 *
	 * @since 1.0.0
	 */
	public function admin_tasks() {

		// Load plugin version.
		$this->plugin_version = $this->version_get();

		// Perform any upgrade tasks.
		$this->upgrade_tasks();

		// Upgrade version if needed.
		if ( WPCVMM_VERSION !== $this->plugin_version ) {
			$this->version_set();
		}

		// Load settings array.
		$this->settings = $this->option_get( $this->settings_name, $this->settings );

		// Upgrade settings.
		$this->upgrade_settings();

	}

	/**
	 * Get the stored plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function version_get() {
		return $this->option_get( $this->plugin_version_name, 'none' );
	}

	/**
	 * Store the plugin version.
	 *
	 * @since 1.0.0
	 */
	public function version_set() {
		$this->option_set( $this->plugin_version_name, WPCVMM_VERSION );
	}

	/**
	 * Perform upgrade tasks.
	 *
	 * @since 1.0.0
	 */
	public function upgrade_tasks() {

		/*
		// For upgrades by version, use something like the following.
		if ( version_compare( WPCVMM_VERSION, '0.3.4', '>=' ) ) {
			// Do something
		}
		*/

	}

	/**
	 * Upgrade settings when required.
	 *
	 * @since 1.0.0
	 */
	public function upgrade_settings() {

		// Don't save by default.
		$save = false;

		/**
		 * Filters the save flag.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $save The save settings flag.
		 */
		$save = apply_filters( 'wpcvmm/settings/upgrade', $save );

		// Save settings if need be.
		if ( true === $save ) {
			$this->settings_save();
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get default settings for this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return array $settings The default settings for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = [];

		/**
		 * Filter default settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings The array of default settings.
		 */
		$settings = apply_filters( 'wpcvmm/settings/defaults', $settings );

		// --<
		return $settings;

	}

	/**
	 * Gets the settings array from option.
	 *
	 * @since 1.0.0
	 *
	 * @return array $settings The array of plugin settings.
	 */
	public function settings_get() {
		return $this->option_get( $this->settings_name, [] );
	}

	/**
	 * Saves the settings array as option.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Success or failure.
	 */
	public function settings_save() {
		return $this->option_set( $this->settings_name, $this->settings );
	}

	/**
	 * Check whether a specified setting exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_name The name of the setting.
	 * @return bool Whether or not the setting exists.
	 */
	public function setting_exists( $setting_name ) {
		return array_key_exists( $setting_name, $this->settings );
	}

	/**
	 * Return a value for a specified setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed  $default The default value if the setting does not exist.
	 * @return mixed The setting or the default.
	 */
	public function setting_get( $setting_name, $default = false ) {
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[ $setting_name ] : $default;
	}

	/**
	 * Sets a value for a specified setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed  $value The value of the setting.
	 */
	public function setting_set( $setting_name, $value = '' ) {
		$this->settings[ $setting_name ] = $value;
	}

	/**
	 * Deletes a specified setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_name The name of the setting.
	 */
	public function setting_delete( $setting_name ) {
		unset( $this->settings[ $setting_name ] );
	}

	// -----------------------------------------------------------------------------------

	/**
	 * Test existence of a specified option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function option_exists( $option_name ) {

		// Test by getting option with unlikely default.
		if ( 'fenfgehgefdfdjgrkj' === $this->option_get( $option_name, 'fenfgehgefdfdjgrkj' ) ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Return a value for a specified option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name The name of the option.
	 * @param string $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function option_get( $option_name, $default = false ) {

		// Get option.
		$value = get_option( $option_name, $default );

		// --<
		return $value;

	}

	/**
	 * Set a value for a specified option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed  $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully updated.
	 */
	public function option_set( $option_name, $value = '' ) {
		return update_option( $option_name, $value );
	}

	/**
	 * Delete a specified option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name The name of the option.
	 * @return bool $success True if the option was successfully deleted.
	 */
	public function option_delete( $option_name ) {
		return delete_option( $option_name );
	}

}
