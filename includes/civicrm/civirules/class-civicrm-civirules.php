<?php
/**
 * CiviRules class.
 *
 * Handles CiviRules-related functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviRules Class.
 *
 * A class that encapsulates CiviRules functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM
	 */
	public $civicrm;

	/**
	 * CiviRules "Add User" object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_User_Add
	 */
	public $user_add;

	/**
	 * CiviRules "Remove User" object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_User_Remove
	 */
	public $user_remove;

	/**
	 * CiviRules "Add to Channel" object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Channel_Add
	 */
	public $channel_add;

	/**
	 * CiviRules "Remove from Channel" object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Channel_Remove
	 */
	public $channel_remove;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_CiviCRM $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->plugin  = $parent->plugin;
		$this->civicrm = $parent;

		// Add action for init.
		add_action( 'wpcvmm/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0.0
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Bootstrap this object.
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Broadcast that this object is loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpcvmm/civicrm/civirules/loaded' );

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
		include WPCVMM_PATH . 'includes/civicrm/civirules/class-civicrm-civirules-base.php';
		include WPCVMM_PATH . 'includes/civicrm/civirules/user/class-civicrm-civirules-user-add.php';
		include WPCVMM_PATH . 'includes/civicrm/civirules/user/class-civicrm-civirules-user-remove.php';
		include WPCVMM_PATH . 'includes/civicrm/civirules/channel/class-civicrm-civirules-channel-add.php';
		include WPCVMM_PATH . 'includes/civicrm/civirules/channel/class-civicrm-civirules-channel-remove.php';

	}

	/**
	 * Sets up objects for this class.
	 *
	 * @since 1.0.0
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->user_add       = new WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_User_Add( $this );
		$this->user_remove    = new WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_User_Remove( $this );
		$this->channel_add    = new WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Channel_Add( $this );
		$this->channel_remove = new WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Channel_Remove( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {}

}
