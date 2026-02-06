<?php
/**
 * CiviCRM Group class.
 *
 * Handles CiviCRM Group-related functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Class.
 *
 * A class that encapsulates CiviCRM functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_CiviCRM_Group {

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
	 * Group Contact object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_Group_Contact
	 */
	public $contact;

	/**
	 * Group Sync object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_Group_Sync
	 */
	public $sync;

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
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Broadcast that this object is loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpcvmm/civicrm/group/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0.0
	 */
	public function include_files() {

		// Load our class files.
		include WPCVMM_PATH . 'includes/civicrm/class-civicrm-group-contact.php';
		include WPCVMM_PATH . 'includes/civicrm/class-civicrm-group-sync.php';

	}

	/**
	 * Sets up objects for this class.
	 *
	 * @since 1.0.0
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->contact = new WPCV_CiviCRM_Mattermost_CiviCRM_Group_Contact( $this );
		$this->sync    = new WPCV_CiviCRM_Mattermost_CiviCRM_Group_Sync( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Groups that have a Mattermost Channel.
	 *
	 * @since 1.0.0
	 *
	 * @return array|bool $groups The array of CiviCRM Groups, or false on failure.
	 */
	public function synced_get() {

		// Init return.
		$groups = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $groups;
		}

		// Get the Custom Field that stores the Mattermost Channel ID.
		$custom_field = $this->plugin->mattermost->channel->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( '*', $custom_field )
				->addWhere( $custom_field, 'IS NOT EMPTY' )
				->execute()
				->indexBy( 'id' );

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $e;
		}

		// Bail if there are none.
		if ( 0 === $result->count() ) {
			return [];
		}

		// Convert the ArrayObject to a simple array.
		$groups = array_values( $result->getArrayCopy() );

		// --<
		return $groups;

	}

	/**
	 * Gets the CiviCRM Group IDs that have a Mattermost Channel.
	 *
	 * @since 1.0.0
	 *
	 * @return array|bool $group_ids The array of CiviCRM Group IDs, or false on failure.
	 */
	public function synced_ids_get() {

		// Init return.
		$group_ids = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_ids;
		}

		// Get the Custom Field that stores the Mattermost Channel ID.
		$custom_field = $this->plugin->mattermost->channel->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( 'id' )
				->addWhere( $custom_field, 'IS NOT EMPTY' )
				->execute()
				->indexBy( 'id' );

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $group_ids;
		}

		// Bail if there are none.
		if ( 0 === $result->count() ) {
			return [];
		}

		// We only need the keys of the ArrayObject.
		$group_ids = array_keys( $result->getArrayCopy() );

		// --<
		return $group_ids;

	}

	/**
	 * Gets a CiviCRM Group by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The numeric ID of the Group.
	 * @return array|bool $group The array of CiviCRM Group data, or false on failure.
	 */
	public function get_by_id( $group_id ) {

		// Init return.
		$group = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( '*' )
				->addWhere( 'id', '=', $group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $group;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $group;
		}

		// We want the first result.
		$group = $result->first();

		// Return Group.
		return $group;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Creates a Mattermost Channel for a given CiviCRM Group.
	 *
	 * @since 1.0.0
	 *
	 * @param array|object $group The CiviCRM Group data.
	 * @param string       $type The Mattermost Channel Type to create. Either "O" (Open/Public) or "P" (Private).
	 * @param string       $team_id The ID of the Mattermost Team ID in which to create the Channel.
	 * @return stdClass|bool $channel The Mattermost Channel object, or false on failure.
	 */
	public function channel_create( $group, $type = 'O', $team_id = '' ) {

		// Make sure Group data is an object.
		if ( ! is_object( $group ) ) {
			$group = (object) $group;
		}

		// Bail if required Fields are not populated.
		if ( empty( $group->name ) || empty( $group->title ) ) {
			return false;
		}

		// Bail if the Group already has a Mattermost Channel.
		$channel_id = $this->channel_id_get( (int) $group->id );
		if ( ! empty( $channel_id ) ) {
			return $this->plugin->mattermost->channel->get_by_id( [ $channel_id ] );
		}

		// Set required params.
		$name  = $group->name;
		$title = $group->title;

		// Go ahead and create the Mattermost Channel.
		$channel = $this->plugin->mattermost->channel->create( $name, $title, $type, $team_id );
		if ( empty( $channel ) ) {
			return;
		}

		// Get the Channel URL.
		$channel->url = $this->plugin->mattermost->channel->url_get( $channel->id );

		// Store Channel data in Group Meta.
		$this->channel_meta_set( (int) $group->id, $channel );

		// --<
		return $channel;

	}

	/**
	 * Saves the Mattermost Channel metadata for a CiviCRM Group ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer  $group_id The CiviCRM Group ID.
	 * @param stdClass $channel The Mattermost Channel object.
	 * @return bool $success True on success or false on failure.
	 */
	public function channel_meta_set( $group_id, $channel ) {

		// Init return.
		$success = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Get the Custom Field that stores the Mattermost Channel ID.
		$custom_field_id = $this->plugin->mattermost->channel->meta_field_id_get();

		// Get the Custom Field that stores the Mattermost Channel Name.
		$custom_field_name = $this->plugin->mattermost->channel->meta_field_name_get();

		// Get the Custom Field that stores the Mattermost Channel URL.
		$custom_field_url = $this->plugin->mattermost->channel->meta_field_url_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::update( false )
				->addValue( $custom_field_id, $channel->id )
				->addValue( $custom_field_name, $channel->name )
				->addValue( $custom_field_url, $channel->url )
				->addWhere( 'id', '=', $group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $success;
		}

		// All's well.
		return true;

	}

	/**
	 * Gets the Mattermost Channel IDs for all synced CiviCRM Groups.
	 *
	 * @since 1.0.0
	 *
	 * @return array|bool $channel_ids The Mattermost Channel IDs, or false on failure.
	 */
	public function channel_ids_get() {

		// Init return.
		$channel_ids = false;

		// Get the Custom Field that stores the Mattermost Channel ID.
		$custom_field = $this->plugin->mattermost->channel->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( $custom_field )
				->addWhere( $custom_field, 'IS NOT EMPTY' )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $channel_ids;
		}

		// Bail if there are none.
		if ( 0 === $result->count() ) {
			return [];
		}

		// Extract the Channel IDs from the ArrayObject.
		$channel_ids = [];
		foreach ( $result as $item ) {
			$channel_ids[] = $item [ $custom_field ];
		}

		// --<
		return $channel_ids;

	}

	/**
	 * Gets the Mattermost Channel ID for a given CiviCRM Group ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The CiviCRM Group ID.
	 * @return string|bool $channel_id The Mattermost Channel ID, or false on failure.
	 */
	public function channel_id_get( $group_id ) {

		// Init return.
		$channel_id = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $channel_id;
		}

		// Get the Custom Field that stores the Mattermost Channel ID.
		$custom_field = $this->plugin->mattermost->channel->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( $custom_field )
				->addWhere( 'id', '=', $group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $channel_id;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $channel_id;
		}

		// We want the first result.
		$group = $result->first();

		// We only want the Custom Field in the first result.
		$channel_id = $group[ $custom_field ];

		// Let's always return an empty result as false.
		if ( empty( $channel_id ) ) {
			return false;
		}

		// --<
		return $channel_id;

	}

	/**
	 * Saves the Mattermost Channel ID for a CiviCRM Group ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The CiviCRM Group ID.
	 * @param string  $channel_id The Mattermost Channel ID.
	 * @return bool $success True on success or false on failure.
	 */
	public function channel_id_set( $group_id, $channel_id ) {

		// Init return.
		$success = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Get the Custom Field that stores the Mattermost Channel ID.
		$custom_field = $this->plugin->mattermost->channel->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::update( false )
				->addValue( $custom_field, $channel_id )
				->addWhere( 'id', '=', $group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $success;
		}

		// All's well.
		return true;

	}

	/**
	 * Gets the Mattermost Channel Name for a given CiviCRM Group ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The CiviCRM Group ID.
	 * @return string|bool $channel_name The Mattermost Channel Name, or false on failure.
	 */
	public function channel_name_get( $group_id ) {

		// Init return.
		$channel_name = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $channel_name;
		}

		// Get the Custom Field that stores the Mattermost Channel Name.
		$custom_field = $this->plugin->mattermost->channel->meta_field_name_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( $custom_field )
				->addWhere( 'id', '=', $group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $channel_name;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $channel_name;
		}

		// We want the first result.
		$group = $result->first();

		// We only want the Custom Field in the first result.
		$channel_name = $group[ $custom_field ];

		// Let's always return an empty result as false.
		if ( empty( $channel_name ) ) {
			return false;
		}

		// --<
		return $channel_name;

	}

	/**
	 * Saves the Mattermost Channel Name for a CiviCRM Group ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The CiviCRM Group ID.
	 * @param string  $channel_name The Mattermost Channel Name.
	 * @return bool $success True on success or false on failure.
	 */
	public function channel_name_set( $group_id, $channel_name ) {

		// Init return.
		$success = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Get the Custom Field that stores the Mattermost Channel Name.
		$custom_field = $this->plugin->mattermost->channel->meta_field_name_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::update( false )
				->addValue( $custom_field, $channel_name )
				->addWhere( 'id', '=', $group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $success;
		}

		// All's well.
		return true;

	}

	/**
	 * Gets the Mattermost Channel URL for a given CiviCRM Group ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The CiviCRM Group ID.
	 * @return string|bool $channel_url The Mattermost Channel URL, or false on failure.
	 */
	public function channel_url_get( $group_id ) {

		// Init return.
		$channel_url = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $channel_url;
		}

		// Get the Custom Field that stores the Mattermost Channel URL.
		$custom_field = $this->plugin->mattermost->channel->meta_field_url_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( $custom_field )
				->addWhere( 'id', '=', $group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $channel_url;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $channel_url;
		}

		// We want the first result.
		$group = $result->first();

		// We only want the Custom Field in the first result.
		$channel_url = $group[ $custom_field ];

		// Let's always return an empty result as false.
		if ( empty( $channel_url ) ) {
			return false;
		}

		// --<
		return $channel_url;

	}

	/**
	 * Saves the Mattermost Channel URL for a CiviCRM Group ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The CiviCRM Group ID.
	 * @param string  $channel_url The Mattermost Channel URL.
	 * @return bool $success True on success or false on failure.
	 */
	public function channel_url_set( $group_id, $channel_url ) {

		// Init return.
		$success = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Get the Custom Field that stores the Mattermost Channel URL.
		$custom_field = $this->plugin->mattermost->channel->meta_field_url_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::update( false )
				->addValue( $custom_field, $channel_url )
				->addWhere( 'id', '=', $group_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $success;
		}

		// All's well.
		return true;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get a CiviCRM Group's admin URL.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The numeric ID of the CiviCRM Group.
	 * @return string $group_url The CiviCRM Group's admin URL.
	 */
	public function url_get( $group_id ) {

		// Kick out if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return '';
		}

		// Get Group URL.
		$group_url = CRM_Utils_System::url( 'civicrm/group', 'reset=1&action=update&id=' . $group_id );

		/**
		 * Filter the URL of the CiviCRM Group's admin page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $group_url The existing URL.
		 * @param integer $group_id The numeric ID of the CiviCRM Group.
		 */
		return apply_filters( 'wpcvmm/civicrm/group/url_get', $group_url, $group_id );

	}

	// -----------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------
	// Unused methods below.
	// -----------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------

	/**
	 * Create a CiviCRM Group using a Mattermost Channel object.
	 *
	 * @since 1.0.0
	 *
	 * @param object $channel The Mattermost Channel object.
	 * @return integer|bool $group_id The ID of the Group, or false on failure.
	 */
	public function create_from_channel( $channel ) {

		// Sanity check.
		if ( ! is_object( $channel ) ) {
			return false;
		}

		// Remove hooks.
		remove_action( 'civicrm_pre', [ $this, 'group_created_pre' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'group_created_post' ], 10 );

		// Init params.
		$params = [
			'version'     => 3,
			'name'        => wp_unslash( $channel->name ),
			'title'       => wp_unslash( $channel->name ),
			'description' => isset( $channel->description ) ? wp_unslash( $channel->description ) : '',
			'group_type'  => [ 1 => 1 ],
			'source'      => 'wpcvmm-group-' . $channel->id,
		];

		// Create the synced CiviCRM Group.
		$result = civicrm_api( 'Group', 'create', $params );

		// Reinstate hooks.
		add_action( 'civicrm_pre', [ $this, 'group_created_pre' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'group_created_post' ], 10, 4 );

		// Log error and bail on failure.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Return new Group ID.
		return absint( $result['id'] );

	}

	/**
	 * Update a CiviCRM Group using a Mattermost Channel object.
	 *
	 * @since 1.0.0
	 *
	 * @param object $channel The Mattermost Channel object.
	 * @return integer|bool $group_id The ID of the Group, or false on failure.
	 */
	public function update_from_channel( $channel ) {

		// Sanity check.
		if ( ! is_object( $channel ) ) {
			return false;
		}

		// Get the synced CiviCRM Group.
		$group = $this->plugin->mattermost->channel->group_get( $channel->id );

		// Sanity check.
		if ( false === $group || empty( $group['id'] ) ) {
			return false;
		}

		// Remove hook.
		remove_action( 'civicrm_post', [ $this, 'group_updated' ], 10 );

		// Init params.
		$params = [
			'version'     => 3,
			'id'          => $group['id'],
			'name'        => wp_unslash( $channel->name ),
			'title'       => wp_unslash( $channel->name ),
			'description' => isset( $channel->description ) ? wp_unslash( $channel->description ) : '',
		];

		// Update the synced CiviCRM Group.
		$result = civicrm_api( 'Group', 'create', $params );

		// Reinstate hook.
		add_action( 'civicrm_post', [ $this, 'group_updated' ], 10, 4 );

		// Log error and bail on failure.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return (int) $group['id'];

	}

	/**
	 * Delete a CiviCRM Group using a Mattermost Channel ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $channel_id The numeric ID of the Mattermost Channel.
	 * @return integer|bool $group_id The ID of the Group, or false on failure.
	 */
	public function delete_by_channel_id( $channel_id ) {

		// Get the synced CiviCRM Group.
		$group = $this->plugin->mattermost->channel->group_get( $channel_id );

		// Sanity check.
		if ( false === $group || empty( $group['id'] ) ) {
			return false;
		}

		// Init params.
		$params = [
			'version' => 3,
			'id'      => $group['id'],
		];

		// Delete the synced CiviCRM Group.
		$result = civicrm_api( 'Group', 'delete', $params );

		// Log error and bail on failure.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'        => __METHOD__,
				'mm_channel_id' => $channel_id,
				'group'         => $group,
				'params'        => $params,
				'result'        => $result,
				'backtrace'     => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return (int) $group['id'];

	}

}
