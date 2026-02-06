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
class CRM_Mattermost_Civirules_Actions_User_Add extends CRM_Civirules_Action {

	/**
	 * Returns the URL for additional form processing for the Rule Action.
	 *
	 * Note: return false if none is needed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $rule_action_id The ID of the Rule Action.
	 * @return bool|string The URL of the the Form - or false if not needed.
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

		// Get the full Contact data.
		$contact = wpcv_civicrm_mattermost()->civicrm->contact->get_by_id( (int) $contact_id );
		if ( empty( $contact ) ) {
			return;
		}

		// Create a Mattermost User for the Contact.
		$user = wpcv_civicrm_mattermost()->civicrm->contact->user_create( $contact );
		if ( empty( $user ) ) {
			return;
		}

		// Get the synced Mattermost Team ID.
		$team_id = wpcv_civicrm_mattermost()->mattermost->remote->api_team_id_get();

		// Now add the Mattermost User to the Team.
		$team_member = wpcv_civicrm_mattermost()->mattermost->team->member_create( $team_id, $user->id );

	}

}
