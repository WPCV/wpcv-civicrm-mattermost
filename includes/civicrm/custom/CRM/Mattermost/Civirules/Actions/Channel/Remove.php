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
class CRM_Mattermost_Civirules_Actions_Channel_Remove extends CRM_Civirules_Action {

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
		$route = wpcv_civicrm_mattermost()->civicrm->civirules->channel_add->route_get();
		return CRM_Utils_System::url( $route, 'rule_action_id=' . $rule_action_id, false, null, false, false, true );
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

		// Get the Rule Action params.
		$action_params = $this->getActionParameters();

		// Skip if we have no Channel ID.
		if ( empty( $action_params['channel_id'] ) ) {
			return;
		}

		// Get the Mattermost User ID.
		$user_id = wpcv_civicrm_mattermost()->civicrm->contact->user_id_get( $contact_id );
		if ( empty( $user_id ) ) {
			return;
		}

		// Add User to Mattermost Channel.
		$response = wpcv_civicrm_mattermost()->mattermost->channel->member_delete( $action_params['channel_id'], $user_id );

	}

	/**
	 * Returns user-friendly text explaining the condition params.
	 *
	 * @return string
	 */
	public function userFriendlyConditionParams() {

		// Set a default "Display Name" for the Channel.
		$label = __( 'Unknown Channel', 'wpcv-civicrm-mattermost' );

		// Get the actual Channel "Display Name".
		// TODO: This could be saved in a transient.
		$action_params = $this->getActionParameters();
		if ( ! empty( $action_params['channel_id'] ) ) {
			$response = wpcv_civicrm_mattermost()->mattermost->channel->get_by_id( $action_params['channel_id'] );
			if ( ! empty( $response ) ) {
				$label = $response->display_name;
			}
		}

		// --<
		return $label;

	}

}
