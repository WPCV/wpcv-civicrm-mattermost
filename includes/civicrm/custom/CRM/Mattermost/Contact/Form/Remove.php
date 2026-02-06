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
class CRM_Mattermost_Contact_Form_Remove extends CRM_Core_Form {

	/**
	 * The CiviCRM Contact ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var integer
	 */
	protected $contact_id;

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
		CRM_Utils_System::setTitle( __( 'Deactivate Mattermost User', 'wpcv-civicrm-mattermost' ) );

		// Pass on the Contact ID.
		$this->add( 'hidden', 'contact_id', $this->contact_id );

		// Add text.
		$this->assign( 'are_you_sure', __( 'Are you sure that you want to deactivate the Mattermost User for this Contact?', 'wpcv-civicrm-mattermost' ) );

		// Form buttons.
		$this->addButtons(
			[
				[
					'type'      => 'next',
					'name'      => esc_html__( 'Deactivate', 'wpcv-civicrm-mattermost' ),
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

		// Delete the Mattermost User for the Contact ID.
		wpcv_civicrm_mattermost()->civicrm->contact->user_delete( $contact_id );

	}

}
