<?php
/**
 * Contact Summary Action Form class.
 *
 * Handles the functionality of a Contact Summary Action Form.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Contact Summary Action Form Class.
 *
 * A class that encapsulates the functionality of a Contact Summary Action Form.
 *
 * @since 1.0.0
 */
class CRM_Mattermost_Contact_Form_Add extends CRM_Core_Form {

	/**
	 * The CiviCRM Contact ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var integer
	 */
	protected $contact_id;

	/**
	 * Gets an array of Mattermost Channels keyed by ID.
	 *
	 * @since 1.0.0
	 *
	 * @return array $channels The keyed array of Mattermost Channels.
	 */
	private function getMattermostChannels() {

		// Get all Channels.
		$plugin   = wpcv_civicrm_mattermost();
		$response = $plugin->mattermost->remote->channels_get();
		if ( empty( $response ) ) {
			return [];
		}

		// Build key => value array for Smarty.
		$channels = [];
		foreach ( $response as $channel ) {
			$channels[ $channel->id ] = $channel->display_name;
		}

		// --<
		return $channels;

	}

	/**
	 * Build the data structures needed to build the Form.
	 *
	 * @since 1.0.0
	 */
	public function preProcess() {

		// Assign the Contact ID.
		$this->contact_id = CRM_Utils_Request::retrieve( 'cid', 'Integer', $this );

	}

	/**
	 * Overridden parent method to build the Form.
	 *
	 * @since 1.0.0
	 */
	public function buildQuickForm() {

		// Set a descriptive title.
		CRM_Utils_System::setTitle( __( 'Create Mattermost User', 'wpcv-civicrm-mattermost' ) );

		// Pass on the Contact ID.
		$this->add( 'hidden', 'contact_id', $this->contact_id );

		// Channel select.
		$this->add(
			'select',
			'channel_id',
			esc_html__( 'Add to a Mattermost Channel (optional)', 'wpcv-civicrm-mattermost' ),
			[ '' => esc_html__( '-- Select a Channel --', 'wpcv-civicrm-mattermost' ) ] + $this->getMattermostChannels()
		);

		// Form buttons.
		$this->addButtons(
			[
				[
					'type'      => 'next',
					'name'      => esc_html__( 'Create', 'wpcv-civicrm-mattermost' ),
					'isDefault' => true,
				],
				[
					'type' => 'cancel',
					'name' => esc_html__( 'Cancel', 'wpcv-civicrm-mattermost' ),
				],
			]
		);

	}

	/**
	 * Process Form data after submitting.
	 *
	 * @since 1.0.0
	 */
	public function postProcess() {

		// Extract params from Form submission.
		$params = $this->controller->exportValues( $this->_name );

		// Get the Contact ID.
		$contact_id = ! empty( $params['contact_id'] ) ? $params['contact_id'] : '';
		if ( empty( $contact_id ) ) {
			return;
		}

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
		if ( empty( $team_member ) ) {
			return;
		}

		// Get the Channel ID.
		$channel_id = ! empty( $params['channel_id'] ) ? $params['channel_id'] : '';
		if ( empty( $channel_id ) ) {
			return;
		}

		// Add User to Mattermost Channel.
		wpcv_civicrm_mattermost()->mattermost->channel->member_create( $channel_id, $user->id );

		// Maybe refresh the Custom Fields.
		// TODO: Perhaps not necessary on production sites - Fields should be hidden.
		// Also, the ID needs fetching to identify it. Dammit.
		$this->ajaxResponse['reloadBlocks'][] = '#custom-set-content-4';

	}

}
