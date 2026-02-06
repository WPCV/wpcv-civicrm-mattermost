<?php
/**
 * CiviRules "Remove from Mattermost Channel" class.
 *
 * Handles "Remove from Mattermost Channel" CiviRules Action functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviRules "Remove from Mattermost Channel" Class.
 *
 * A class that encapsulates "Remove from Mattermost Channel" CiviRules Action functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Channel_Remove extends WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Base {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_CiviCRM $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Configure this CiviRules Action.
		$this->civirule_name  = 'wpcvmm_remove_from_channel';
		$this->civirule_class = 'CRM_Mattermost_Civirules_Actions_Channel_Remove';
		$this->civirule_url   = 'civicrm/civirule/form/action/mattermost/channel/remove';
		$this->civirule_page  = 'CRM_Mattermost_Civirules_Form_Channel_Remove';

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
			__( 'Remove the Mattermost User for a Contact from a Mattermost Channel', 'wpcv-civicrm-mattermost' ), // Label.
			$this->civirule_class // Class name.
		);

	}

}
