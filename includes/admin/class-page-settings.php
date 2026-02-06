<?php
/**
 * Settings Page class.
 *
 * Handles Settings Page functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Settings Page class.
 *
 * A class that encapsulates Settings Page functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Page_Settings extends WPCV_CiviCRM_Mattermost_Page_Settings_Base {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost
	 */
	public $plugin;

	/**
	 * Admin object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Admin
	 */
	public $admin;

	/**
	 * Form Mattermost API URL.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $form_mm_url = 'mm_url';

	/**
	 * Form Mattermost API Token.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $form_mm_token = 'mm_token';

	/**
	 * Form Mattermost Team ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $form_mm_team_id = 'mm_team_id';

	/**
	 * Form Mattermost Group Sync Enabled.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $form_group_sync_id = 'mm_group_sync_id';

	/**
	 * Form interval ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $form_interval_id = 'interval_id';

	/**
	 * Form sync direction ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $form_direction_id = 'direction_id';

	/**
	 * Form batch ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $form_batch_id = 'batch_id';

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param object $admin The admin object.
	 */
	public function __construct( $admin ) {

		// Store references to objects.
		$this->plugin = $admin->plugin;
		$this->admin  = $admin;

		// Set a unique prefix for all Pages.
		$this->hook_prefix_common = 'wpcvmm_admin';

		// Set a unique prefix.
		$this->hook_prefix = 'wpcvmm_settings';

		// Assign page slugs.
		$this->page_slug = 'wpcvmm_settings';

		/*
		// Assign page layout.
		$this->page_layout = 'dashboard';
		*/

		// Assign path to plugin directory.
		$this->path_plugin = WPCVMM_PATH;

		// Assign form IDs.
		$this->form_mm_url       = $this->hook_prefix . '_' . $this->form_mm_url;
		$this->form_mm_token     = $this->hook_prefix . '_' . $this->form_mm_token;
		$this->form_mm_team_id   = $this->hook_prefix . '_' . $this->form_mm_team_id;
		$this->form_interval_id  = $this->hook_prefix . '_' . $this->form_interval_id;
		$this->form_direction_id = $this->hook_prefix . '_' . $this->form_direction_id;
		$this->form_batch_id     = $this->hook_prefix . '_' . $this->form_batch_id;

		// Bootstrap parent.
		parent::__construct();

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0.0
	 */
	public function initialise() {

		// Assign translated strings.
		$this->plugin_name          = __( 'Integrate CiviCRM with Mattermost', 'wpcv-civicrm-mattermost' );
		$this->page_title           = __( 'Settings for Integrate CiviCRM with Mattermost', 'wpcv-civicrm-mattermost' );
		$this->page_tab_label       = __( 'Settings', 'wpcv-civicrm-mattermost' );
		$this->page_menu_label      = __( 'Mattermost', 'wpcv-civicrm-mattermost' );
		$this->page_help_label      = __( 'Integrate CiviCRM with Mattermost', 'wpcv-civicrm-mattermost' );
		$this->metabox_submit_title = __( 'Settings', 'wpcv-civicrm-mattermost' );

	}

	/**
	 * Adds styles.
	 *
	 * @since 1.0.0
	 */
	public function admin_styles() {

		// Enqueue our "Settings Page" stylesheet.
		wp_enqueue_style(
			$this->hook_prefix . '-css',
			plugins_url( 'assets/css/page-settings.css', WPCVMM_FILE ),
			false,
			WPCVMM_VERSION, // Version.
			'all' // Media.
		);

	}

	/**
	 * Adds scripts.
	 *
	 * @since 1.0.0
	 */
	public function admin_scripts() {

		// Enqueue our "Settings Page" script.
		wp_enqueue_script(
			$this->hook_prefix . '-js',
			plugins_url( 'assets/js/page-settings.js', WPCVMM_FILE ),
			[ 'jquery' ],
			WPCVMM_VERSION, // Version.
			true
		);

	}

	/**
	 * Registers meta boxes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $screen_id The Settings Page Screen ID.
	 * @param array  $data The array of metabox data.
	 */
	public function meta_boxes_register( $screen_id, $data ) {

		// Bail if not the Screen ID we want.
		if ( $screen_id !== $this->page_context . $this->page_slug ) {
			return;
		}

		// Check User permissions.
		if ( ! $this->page_capability() ) {
			return;
		}

		// Define a handle for the following metabox.
		$handle = $this->hook_prefix . '_settings_credentials';

		// Add the metabox.
		add_meta_box(
			$handle,
			__( 'Mattermost Credentials', 'wpcv-civicrm-mattermost' ),
			[ $this, 'meta_box_credentials_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Define a handle for the following metabox.
		$handle = $this->hook_prefix . '_settings_sync';

		// Add the metabox.
		add_meta_box(
			$handle,
			__( 'Group Sync', 'wpcv-civicrm-mattermost' ),
			[ $this, 'meta_box_group_sync_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		// Define a handle for the following metabox.
		$handle = $this->hook_prefix . '_settings_schedule';

		// Add the metabox.
		add_meta_box(
			$handle,
			__( 'Recurring Schedules', 'wpcv-civicrm-mattermost' ),
			[ $this, 'meta_box_schedule_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

		/*
		// Make this metabox closed by default.
		add_filter( "postbox_classes_{$screen_id}_{$handle}", [ $this, 'meta_box_closed' ] );
		*/

		/**
		 * Broadcast that the metaboxes have been added.
		 *
		 * @since 1.0.0
		 *
		 * @param string $screen_id The Screen indentifier.
		 * @param array $vars The array of metabox data.
		 */
		do_action( $this->hook_prefix . '_settings_page_meta_boxes_added', $screen_id, $data );

	}

	/**
	 * Renders "Mattermost Credentials" meta box on Settings screen.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_credentials_render( $unused, $metabox ) {

		// Get our credentials.
		$credentials = $this->plugin->mattermost->remote->api_credentials_get();
		$url         = ! empty( $credentials ) ? $credentials['url'] : '';
		$token       = ! empty( $credentials ) ? $credentials['token'] : '';

		// Get our settings.
		$team_id = $this->admin->setting_get( 'mm_team_id' );

		// Init template vars.
		$message = '';
		$team    = [];
		$teams   = [];

		// Check if we have credentials.
		if ( empty( $credentials ) ) {
			$message = __( 'Cannot connect to your Mattermost API.', 'wpcv-civicrm-mattermost' );
		} else {

			// Try to get all Teams.
			$response = $this->plugin->mattermost->remote->teams_get();
			if ( false === $response ) {
				$message = __( 'Could not connect to your Mattermost API. Please check your credentials.', 'wpcv-civicrm-mattermost' );
			}

			// When there are no Teams.
			if ( empty( $response ) || ! is_array( $response ) ) {
				$message = __( 'No Mattermost Team found. Please create one.', 'wpcv-civicrm-mattermost' );
			}

			// When there is only one Team.
			if ( is_array( $response ) && 1 === count( $response ) ) {
				$team = array_pop( $response );
				// Let's save the ID and Team immediately.
				if ( $team_id !== $team->id ) {
					$this->admin->setting_set( 'mm_team', $team );
					$this->admin->setting_set( 'mm_team_id', $team->id );
					$this->admin->settings_save();
				}
			}

			// When there are multiple Teams.
			if ( is_array( $response ) && 1 < count( $response ) ) {
				foreach ( $response as $item ) {
					$teams[ $item->id ] = $item->display_name;
				}
			}

		}

		// Include template file.
		include $this->path_plugin . $this->path_template . $this->path_metabox . 'metabox-settings-credentials.php';

	}

	/**
	 * Renders "Group Sync" meta box on Settings screen.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_group_sync_render( $unused, $metabox ) {

		// Get our settings.
		$group_sync = $this->admin->setting_get( 'mm_group_sync', 'no' );

		// Include template file.
		include $this->path_plugin . $this->path_template . $this->path_metabox . 'metabox-settings-group-sync.php';

	}

	/**
	 * Renders "Recurring Schedules" meta box on Settings screen.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_schedule_render( $unused, $metabox ) {

		// Get our settings.
		$interval    = $this->admin->setting_get( 'interval' );
		$direction   = $this->admin->setting_get( 'direction' );
		$batch_count = (int) $this->admin->setting_get( 'batch_count' );

		// First item.
		$first = [
			'off' => [
				'interval' => 0,
				'display'  => __( 'Off', 'wpcv-civicrm-mattermost' ),
			],
		];

		// Build schedules.
		$schedules = $this->admin->schedule->intervals_get();
		$schedules = $first + $schedules;

		// Include template file.
		include $this->path_plugin . $this->path_template . $this->path_metabox . 'metabox-settings-schedule.php';

	}

	/**
	 * Performs save actions when the form has been submitted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $submit_id The Settings Page form submit ID.
	 */
	public function form_save( $submit_id ) {

		// Check that we trust the source of the data.
		check_admin_referer( $this->form_nonce_action, $this->form_nonce_field );

		// Set Mattermost API URL.
		$mm_url_raw = filter_input( INPUT_POST, $this->form_mm_url );
		$mm_url     = sanitize_text_field( wp_unslash( $mm_url_raw ) );
		$this->admin->setting_set( 'mm_url', $mm_url );

		// Set Mattermost API Token.
		$mm_token_raw = filter_input( INPUT_POST, $this->form_mm_token );
		$mm_token     = sanitize_text_field( wp_unslash( $mm_token_raw ) );
		$this->admin->setting_set( 'mm_token', $mm_token );

		// Get existing Mattermost Team ID.
		$existing_mm_team_id = $this->admin->setting_get( 'mm_team_id' );

		// Set Mattermost Team ID.
		$mm_team_id_raw = filter_input( INPUT_POST, $this->form_mm_team_id );
		if ( ! empty( $mm_team_id_raw ) ) {
			$mm_team_id = sanitize_text_field( wp_unslash( $mm_team_id_raw ) );
			$this->admin->setting_set( 'mm_team_id', $mm_team_id );
			// Maybe update the saved Team data.
			if ( $existing_mm_team_id !== $mm_team_id ) {
				$mm_team = $this->plugin->mattermost->team->get_by_id( $mm_team_id );
				if ( ! empty( $mm_team ) ) {
					$this->admin->setting_set( 'mm_team', $mm_team );
				}
			}
		}

		// Set Mattermost Channel <-> CiviCRM Group Sync setting.
		$mm_group_sync_raw = filter_input( INPUT_POST, $this->form_group_sync_id );
		$mm_group_sync     = sanitize_text_field( wp_unslash( $mm_group_sync_raw ) );
		$this->admin->setting_set( 'mm_group_sync', $mm_group_sync );

		// Unschedule when not syncing Groups with Channels.
		if ( 'no' === $mm_group_sync ) {
			$this->admin->setting_set( 'interval', 'off' );
			$this->admin->settings_save();
			$this->admin->schedule->unschedule();
			return;
		}

		// Get existing interval.
		$existing_interval = $this->admin->setting_get( 'interval' );

		// Set new interval.
		$interval     = 'off';
		$interval_raw = filter_input( INPUT_POST, $this->form_interval_id );
		if ( ! empty( $interval_raw ) ) {
			$interval = sanitize_text_field( wp_unslash( $interval_raw ) );
		}
		$this->admin->setting_set( 'interval', $interval );

		// Set new sync direction.
		$direction     = 'civicrm';
		$direction_raw = filter_input( INPUT_POST, $this->form_direction_id );
		if ( ! empty( $direction_raw ) ) {
			$direction = sanitize_text_field( wp_unslash( $direction_raw ) );
		}
		$this->admin->setting_set( 'direction', $direction );

		// Set new batch count.
		$batch_count_raw = filter_input( INPUT_POST, $this->form_batch_id );
		$batch_count     = (int) sanitize_text_field( wp_unslash( $batch_count_raw ) );
		$this->admin->setting_set( 'batch_count', $batch_count );

		// Clear current scheduled event if the schedule is being deactivated.
		if ( 'off' !== $existing_interval && 'off' === $interval ) {
			$this->admin->schedule->unschedule();
		}

		/*
		 * Clear current scheduled event and add new scheduled event
		 * if the schedule is active and the interval has changed.
		 */
		if ( 'off' !== $interval && $interval !== $existing_interval ) {
			$this->admin->schedule->unschedule();
			$this->admin->schedule->schedule( $interval );
		}

		// Save settings.
		$this->admin->settings_save();

	}

}
