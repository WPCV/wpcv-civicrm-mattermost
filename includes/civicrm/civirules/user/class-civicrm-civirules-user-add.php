<?php
/**
 * CiviRules "Add Mattermost User" class.
 *
 * Handles "Add Mattermost User" CiviRules Action functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviRules "Add Mattermost User" Class.
 *
 * A class that encapsulates "Add Mattermost User" CiviRules Action functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_User_Add extends WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Base {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_CiviCRM $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Configure this CiviRules Action.
		$this->civirule_name  = 'wpcvmm_add_user';
		$this->civirule_class = 'CRM_Mattermost_Civirules_Actions_User_Add';

		// Bootstrap parent.
		parent::__construct( $parent );

	}

	/**
	 * Installs the CiviRules Action.
	 *
	 * @since 1.0.0
	 */
	public function action_install() {

		// Install CiviRules Action.
		$result = $this->action_create(
			$this->civirule_name, // Name.
			__( 'Create a Mattermost User for a Contact', 'wpcv-civicrm-mattermost' ), // Label.
			$this->civirule_class // Class name.
		);

	}

	/**
	 * Skip adding a HTTP route to CiviCRM.
	 *
	 * @param array $routes The array of CiviCRM routes.
	 */
	public function route_add( &$routes ) {}

}
