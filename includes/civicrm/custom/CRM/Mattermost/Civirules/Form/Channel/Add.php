<?php
/**
 * CiviRules Action Form class.
 *
 * Handles the functionality of a CiviRules Action Form.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviRules Action Form Class.
 *
 * A class that encapsulates the functionality of a CiviRules Action Form.
 *
 * @since 1.0.0
 */
class CRM_Mattermost_Civirules_Form_Channel_Add extends CRM_CivirulesActions_Form_Form {

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
	 * Overridden parent method to build the Form.
	 *
	 * @since 1.0.0
	 */
	public function buildQuickForm() {

		// Always add Rule Action ID.
		$this->add( 'hidden', 'rule_action_id' );

		// Channel select.
		$this->add(
			'select',
			'channel_id',
			esc_html__( 'Mattermost Channel', 'wpcv-civicrm-mattermost' ),
			[ '' => __( '-- Select a Channel --', 'wpcv-civicrm-mattermost' ) ] + $this->getMattermostChannels()
		);

		// Form buttons.
		$this->addButtons(
			[
				[
					'type'      => 'next',
					'name'      => esc_html__( 'Save', 'wpcv-civicrm-mattermost' ),
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
	 * Adds a Form Rule for data validation.
	 *
	 * @since 1.0.0
	 */
	public function addRules() {
		$this->addFormRule( [ 'CRM_Mattermost_Civirules_Form_Channel_Add', 'validateChannel' ] );
	}

	/**
	 * Validates the Fields in the Rule Action Form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields The array of Rule Action Form Fields.
	 * @return array|bool $errors An array of errors - or boolean true if validation is successful.
	 */
	public static function validateChannel( $fields ) {

		// We must have a Channel ID.
		$errors = [];
		if ( empty( $fields['channel_id'] ) ) {
			$errors['channel_id'] = __( 'Please select a Mattermost Channel', 'wpcv-civicrm-mattermost' );
		}

		// Return the errors if there are any.
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		// --<
		return true;

	}

	/**
	 * Overridden parent method to set default values.
	 *
	 * @since 1.0.0
	 *
	 * @return array $default_values The array of default values.
	 */
	public function setDefaultValues() {

		// Parent defaults.
		$default_values = parent::setDefaultValues();

		// Grab existing data, if set.
		$data = [];
		if ( ! empty( $this->ruleAction->action_params ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			$data = unserialize( $this->ruleAction->action_params );
		}

		// May be set select default.
		if ( ! empty( $data['channel_id'] ) ) {
			$default_values['channel_id'] = $data['channel_id'];
		}

		// --<
		return $default_values;

	}

	/**
	 * Overridden parent method to process Form data after submitting.
	 *
	 * @since 1.0.0
	 */
	public function postProcess() {

		// Build data for this Rule.
		$data = [];
		if ( ! empty( $this->_submitValues['channel_id'] ) ) {
			$data['channel_id'] = $this->_submitValues['channel_id'];
		}

		// Admin tasks for this Rule.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$this->ruleAction->action_params = serialize( $data );
		$this->ruleAction->save();
		parent::postProcess();

	}

}
