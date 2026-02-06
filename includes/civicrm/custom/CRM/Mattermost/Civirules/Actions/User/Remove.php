<?php
/**
 * CiviRules Action class.
 *
 * Handles the functionality of a CiviRules Action.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviRules Action Class.
 *
 * A class that encapsulates the functionality of a CiviRules Action.
 *
 * @since 1.0.0
 */
class CRM_Mattermost_Civirules_Actions_User_Remove extends CRM_Civirules_Action {

	/**
	 * Returns the URL for additional form processing for the Action.
	 *
	 * Note: return false if none is needed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $rule_action_id The ID of the CiviRules Action.
	 * @return bool|string
	 */
	public function getExtraDataInputUrl( $rule_action_id ) {
		return false;
	}

	/**
	 * Processes the Action.
	 *
	 * @since 1.0.0
	 *
	 * @param CRM_Civirules_TriggerData_TriggerData $trigger_data The Trigger data.
	 */
	public function processAction( CRM_Civirules_TriggerData_TriggerData $trigger_data ) {

		// Get the Contact ID.
		$contact_id = $trigger_data->getContactId();

		// Get the additional Rule Action params.
		$action_params = $this->getActionParameters();

		// Delete the Mattermost User for the Contact ID.
		$success = wpcv_civicrm_mattermost()->civicrm->contact->user_delete( $contact_id );

	}

}
