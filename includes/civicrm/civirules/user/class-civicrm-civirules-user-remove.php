<?php
/**
 * CiviRules "Remove Mattermost User" class.
 *
 * Handles "Remove Mattermost User" CiviRules Action functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviRules "Remove Mattermost User" Class.
 *
 * A class that encapsulates "Remove Mattermost User" CiviRules Action functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_User_Remove extends WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Base {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_CiviCRM $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Configure this CiviRules Action.
		$this->civirule_name  = 'wpcvmm_remove_user';
		$this->civirule_class = 'CRM_Mattermost_Civirules_Actions_User_Remove';

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
			__( 'Deactivate the Mattermost User for a Contact', 'wpcv-civicrm-mattermost' ), // Label.
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
