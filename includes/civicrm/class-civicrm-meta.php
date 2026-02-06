<?php
/**
 * CiviCRM Metadata class.
 *
 * Handles CiviCRM Metadata-related functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Metadata class.
 *
 * A class that encapsulates CiviCRM Metadata functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_CiviCRM_Meta {

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
		$this->register_hooks();

		/**
		 * Broadcast that this object is loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpcvmm/civicrm/meta/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a Custom Group for a given name/slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The "slug" of the Custom Group.
	 * @return array|bool $custom_group The Custom Group data, or false on failure.
	 */
	public function custom_group_get_by_slug( $slug ) {

		// Init return.
		$custom_group = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_group;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\CustomGroup::get( false )
				->addSelect( '*' )
				->addWhere( 'name', '=', $slug )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $custom_group;
		}

		// Bail if there is no result.
		if ( 0 === $result->count() ) {
			return [];
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $custom_group;
		}

		// We want the first result.
		$custom_group = $result->first();

		// --<
		return $custom_group;

	}

	/**
	 * Adds a Custom Group to CiviCRM.
	 *
	 * Note: We may want to make this a Reserved Group so that no-one can mess with it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $title The title of the Custom Group.
	 * @param string $slug The "slug" of the Custom Group.
	 * @param string $extends The CiviCRM Entity that the Custom Group extends.
	 * @param string $style The "style" of the Custom Group. "Tab" is hidden on Groups.
	 * @return array|bool $custom_group The Custom Group data, or false on failure.
	 */
	public function custom_group_create( $title, $slug, $extends = 'Individual', $style = 'Inline' ) {

		// Init return.
		$custom_group = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_group;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\CustomGroup::create( false )
				->addValue( 'title', $title )
				->addValue( 'name', $slug )
				->addValue( 'extends', $extends )
				->addValue( 'style', $style )
				->addValue( 'collapse_display', true )
				->addValue( 'is_public', false )
				->addValue( 'is_active', true )
				->addValue( 'is_reserved', true ) // Maybe should be true?
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $custom_group;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $custom_group;
		}

		// We want the first result.
		$custom_group = $result->first();

		// --<
		return $custom_group;

	}

	/**
	 * Deletes a Custom Group from CiviCRM.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The "slug" of the Custom Group.
	 * @return integer|bool $success The Custom Group ID if deleted, or false on failure.
	 */
	public function custom_group_delete( $slug ) {

		// Init return.
		$success = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\CustomGroup::delete( false )
				->addWhere( 'name', '=', $slug )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $custom_group;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $success;
		}

		// We want the first result.
		$success = $result->first();

		// --<
		return $success;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a Custom Field for a given name/slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The "slug" of the Custom Field.
	 * @return array|bool $custom_field The Custom Field data, or false on failure.
	 */
	public function custom_field_get_by_slug( $slug ) {

		// Init return.
		$custom_field = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_field;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\CustomField::get( false )
				->addSelect( '*' )
				->addWhere( 'name', '=', $slug )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $custom_field;
		}

		// Bail if there is no result.
		if ( 0 === $result->count() ) {
			return [];
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $custom_field;
		}

		// We want the first result.
		$custom_field = $result->first();

		// --<
		return $custom_field;

	}

	/**
	 * Adds a Custom Field to a Custom Group.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The ID of the CiviCRM Custom Group.
	 * @param string  $label The label for the Custom Field.
	 * @param string  $slug The "slug" of the Custom Field.
	 * @param string  $html_type The HTML Type of the Custom Field. Default "Text".
	 * @param bool    $read_only True makes the Custom Field read-only. Default false.
	 * @return stdClass|bool $custom_field The Custom Field data, or false on failure.
	 */
	public function custom_field_create( $group_id, $label, $slug, $html_type = 'Text', $read_only = false ) {

		// Init return.
		$custom_field = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_field;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\CustomField::create( false )
				->addValue( 'custom_group_id', $group_id )
				->addValue( 'label', $label )
				->addValue( 'name', $slug )
				->addValue( 'html_type', $html_type )
				->addValue( 'is_view', $read_only )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $custom_field;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $custom_field;
		}

		// We want the first result.
		$custom_field = $result->first();

		// --<
		return $custom_field;

	}

}
