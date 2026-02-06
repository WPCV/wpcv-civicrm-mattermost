<?php
/**
 * CiviCRM Group Contact class.
 *
 * Handles CiviCRM Group Contact-related functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Class.
 *
 * A class that encapsulates CiviCRM Group Contact functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_CiviCRM_Group_Contact {

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
	 * Group object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_Group
	 */
	public $group;

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
		$this->civicrm = $parent->civicrm;
		$this->group   = $parent;

		// Add action for init.
		add_action( 'wpcvmm/civicrm/group/loaded', [ $this, 'initialise' ] );

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
		do_action( 'wpcvmm/civicrm/group/contact/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		// Bail if our Group Sync setting is off.
		$group_sync = $this->plugin->admin->setting_get( 'mm_group_sync', 'no' );
		if ( 'no' === $group_sync ) {
			return;
		}

		// Intercept CiviCRM's add Contacts to Group.
		add_action( 'civicrm_pre', [ $this, 'group_contacts_added' ], 10, 4 );

		// Intercept CiviCRM's delete Contacts from Group.
		add_action( 'civicrm_pre', [ $this, 'group_contacts_deleted' ], 10, 4 );

		// Intercept CiviCRM's rejoin Contacts to Group.
		add_action( 'civicrm_pre', [ $this, 'group_contacts_rejoined' ], 10, 4 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a CiviCRM Group Contact.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_id The numeric ID of a CiviCRM Contact.
	 * @return stdClass|bool $group_contact The Group Contact object, or false on failure.
	 */
	public function get( $group_id, $contact_id ) {

		// Init return.
		$group_contact = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_contact;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::get( false )
				->addSelect( '*' )
				->addWhere( 'group_id', '=', $group_id )
				->addWhere( 'contact_id', '=', $contact_id )
				->setLimit( 1 )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $group_contact;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $group_contact;
		}

		// We want the first result.
		$group_contact = $result->first();

		// --<
		return $group_contact;

	}

	/**
	 * Adds a CiviCRM Contact to a CiviCRM Group.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_id The numeric ID of a CiviCRM Contact.
	 * @return stdClass|bool $group_contact The Group Contact object, or false on failure.
	 */
	public function create( $group_id, $contact_id ) {

		// Init return.
		$group_contact = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_contact;
		}

		// Remove hook.
		remove_action( 'civicrm_pre', [ $this, 'group_contacts_added' ], 10 );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::create( false )
				->addValue( 'group_id', $group_id )
				->addValue( 'contact_id', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		// Reinstate hook.
		add_action( 'civicrm_pre', [ $this, 'group_contacts_added' ], 10, 4 );

		// Bail if there was an error.
		if ( isset( $log ) ) {
			return $group_contact;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $group_contact;
		}

		// We want the first result.
		$group_contact = $result->first();

		// --<
		return $group_contact;

	}

	/**
	 * Sets the status of an existing CiviCRM Group Contact to "Added".
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_contact_id The ID of the CiviCRM Group Contact.
	 * @return stdClass|bool $group_contact The Group Contact object, or false on failure.
	 */
	public function activate( $group_contact_id ) {

		// Init return.
		$group_contact = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_contact;
		}

		// Remove "edit" operation listener.
		remove_action( 'civicrm_pre', [ $this, 'group_contacts_rejoined' ], 10 );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::update( false )
				->addWhere( 'id', '=', $group_contact_id )
				->addValue( 'status', 'Added' )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		// Reinstate hook.
		add_action( 'civicrm_pre', [ $this, 'group_contacts_rejoined' ], 10, 4 );

		// Bail if there was an error.
		if ( isset( $log ) ) {
			return $group_contact;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $group_contact;
		}

		// We want the first result.
		$group_contact = $result->first();

		// --<
		return $group_contact;

	}

	/**
	 * Sets the status of an existing CiviCRM Group Contact to "Removed".
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_contact_id The ID of the CiviCRM Group Contact.
	 * @return stdClass|bool $group_contact The Group Contact object, or false on failure.
	 */
	public function deactivate( $group_contact_id ) {

		// Init return.
		$group_contact = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_contact;
		}

		// Remove "edit" operation listener.
		remove_action( 'civicrm_pre', [ $this, 'group_contacts_rejoined' ], 10 );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::update( false )
				->addWhere( 'id', '=', $group_contact_id )
				->addValue( 'status', 'Removed' )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		// Reinstate hook.
		add_action( 'civicrm_pre', [ $this, 'group_contacts_rejoined' ], 10, 4 );

		// Bail if there was an error.
		if ( isset( $log ) ) {
			return $group_contact;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $group_contact;
		}

		// We want the first result.
		$group_contact = $result->first();

		// --<
		return $group_contact;

	}

	/**
	 * Deletes a CiviCRM Contact from a CiviCRM Group.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_id The numeric ID of a CiviCRM Contact.
	 * @return integer|bool $group_contact_id The Group Contact ID, or false on failure.
	 */
	public function delete( $group_id, $contact_id ) {

		// Init return.
		$group_contact_id = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_contact_id;
		}

		// Remove hook.
		remove_action( 'civicrm_pre', [ $this, 'group_contacts_deleted' ], 10 );

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::delete( false )
				->addWhere( 'group_id', '=', $group_id )
				->addWhere( 'contact_id', '=', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
		}

		// Reinstate hooks.
		add_action( 'civicrm_pre', [ $this, 'group_contacts_deleted' ], 10, 4 );

		// Bail if there was an error.
		if ( isset( $log ) ) {
			return $group_contact_id;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $group_contact_id;
		}

		// We want the first result.
		$group_contact = $result->first();

		// We only want the ID in the first result.
		$group_contact_id = (int) $group_contact['id'];

		// --<
		return $group_contact_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Acts when one or more CiviCRM Contacts are added to a Group.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_ids The array of CiviCRM Contact IDs.
	 */
	public function group_contacts_added( $op, $object_name, $group_id, $contact_ids ) {

		// Target our operation.
		if ( 'create' !== $op ) {
			return;
		}

		// Target our object type.
		if ( 'GroupContact' !== $object_name ) {
			return;
		}

		// Get Mattermost Channel ID.
		$channel_id = $this->group->channel_id_get( $group_id );
		if ( false === $channel_id ) {
			return;
		}

		// Build array of Mattermost User IDs.
		$user_ids = [];
		if ( ! empty( $contact_ids ) ) {
			foreach ( $contact_ids as $contact_id ) {

				// Check if there is an existing Mattermost User ID.
				$user_id = $this->plugin->civicrm->contact->user_id_get( $contact_id );

				// Add it and move on if there is.
				if ( false !== $user_id ) {
					$user_ids[] = $user_id;
					continue;
				}

				// Get the full Contact data.
				$contact = $this->plugin->civicrm->contact->get_by_id( (int) $contact_id );
				if ( empty( $contact ) ) {
					continue;
				}

				// Create a Mattermost User for the Contact.
				$user = $this->plugin->civicrm->contact->user_create( $contact );
				if ( empty( $user ) ) {
					continue;
				}

				// Get the synced Mattermost Team ID.
				$team_id = $this->plugin->mattermost->remote->api_team_id_get();

				// Now add the Mattermost User to the Team.
				$this->plugin->mattermost->team->member_create( $team_id, $user->id );

				// Finally add the Mattermost User ID to the array.
				$user_ids[] = $user->id;

			}
		}

		// Bail if none need to be added.
		if ( empty( $user_ids ) ) {
			return;
		}

		// Add Users to Mattermost Channel.
		foreach ( $user_ids as $user_id ) {
			$response = $this->plugin->mattermost->channel->member_create( $channel_id, $user_id );
		}

	}

	/**
	 * Acts when one or more CiviCRM Contacts are deleted (or removed) from a Group.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_ids Array of CiviCRM Contact IDs.
	 */
	public function group_contacts_deleted( $op, $object_name, $group_id, $contact_ids ) {

		// Target our operation.
		if ( 'delete' !== $op ) {
			return;
		}

		// Target our object type.
		if ( 'GroupContact' !== $object_name ) {
			return;
		}

		// Get Mattermost Channel ID.
		$channel_id = $this->group->channel_id_get( $group_id );
		if ( false === $channel_id ) {
			return;
		}

		// Build array of Mattermost User IDs.
		$user_ids = [];
		if ( ! empty( $contact_ids ) ) {
			foreach ( $contact_ids as $contact_id ) {
				$user_id = $this->plugin->civicrm->contact->user_id_get( $contact_id );
				if ( false !== $user_id ) {
					$user_ids[] = $user_id;
				}
			}
		}

		// Bail if none need to be deleted.
		if ( empty( $user_ids ) ) {
			return;
		}

		// Remove Users from Mattermost Channel.
		foreach ( $user_ids as $user_id ) {
			$response = $this->plugin->mattermost->channel->member_delete( $channel_id, $user_id );
		}

	}

	/**
	 * Acts when one or more CiviCRM Contacts are re-added to a Group.
	 *
	 * The issue here is that CiviCRM fires 'civicrm_pre' with $op = 'delete' regardless
	 * of whether the Contact is being removed or deleted. If a Contact is later re-added
	 * to the Group, then $op != 'create', so we need to intercept $op = 'edit'.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_ids Array of CiviCRM Contact IDs.
	 */
	public function group_contacts_rejoined( $op, $object_name, $group_id, $contact_ids ) {

		// Target our operation.
		if ( 'edit' !== $op ) {
			return;
		}

		// Target our object type.
		if ( 'GroupContact' !== $object_name ) {
			return;
		}

		// Get Mattermost Channel ID.
		$channel_id = $this->group->channel_id_get( $group_id );
		if ( false === $channel_id ) {
			return;
		}

		// Build array of Mattermost User IDs.
		$user_ids = [];
		if ( ! empty( $contact_ids ) ) {
			foreach ( $contact_ids as $contact_id ) {
				$user_id = $this->plugin->civicrm->contact->user_id_get( $contact_id );
				if ( false !== $user_id ) {
					$user_ids[] = $user_id;
				}
			}
		}

		// Bail if none need to be added.
		if ( empty( $user_ids ) ) {
			return;
		}

		// Add Users to Mattermost Channel.
		foreach ( $user_ids as $user_id ) {
			$response = $this->plugin->mattermost->channel->member_create( $channel_id, $user_id );
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the synced CiviCRM Group IDs for a given Contact ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The ID of the CiviCRM Contact.
	 * @return array|bool $group_ids The array of synced CiviCRM Group IDs, or false on failure.
	 */
	public function group_ids_get_for_contact( $contact_id ) {

		// Init return.
		$group_ids = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_ids;
		}

		// Get all synced CiviCRM Group IDs.
		$synced_group_ids = $this->group->synced_ids_get();
		if ( empty( $synced_group_ids ) ) {
			return $group_ids;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::get( false )
				->addSelect( 'group_id' )
				->addWhere( 'contact_id', '=', $contact_id )
				->addWhere( 'group_id', 'IN', $synced_group_ids )
				->addWhere( 'status:name', '=', 'Added' )
				->addOrderBy( 'group_id', 'ASC' )
				->execute()
				->indexBy( 'group_id' );

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
			return $group_ids;
		}

		// We only need the keys of the ArrayObject.
		$group_ids = array_keys( $result->getArrayCopy() );

		// --<
		return $group_ids;

	}

	/**
	 * Gets a set of Group Contacts in synced Groups with a given limit and offset.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $limit The numeric limit for the query.
	 * @param integer $offset The numeric offset for the query.
	 * @return array|CRM_Core_Exception $group_contacts The array of CiviCRM Group Contacts, or Exception on failure.
	 * @throws CRM_Core_Exception The Exception object.
	 */
	public function group_contacts_get( $limit = 0, $offset = 0 ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			try {
				throw new CRM_Core_Exception( __( 'Could not initialize CiviCRM', 'wpcv-civicrm-mattermost' ) );
			} catch ( CRM_Core_Exception $e ) {
				return $e;
			}
		}

		// Get all synced CiviCRM Group IDs.
		$synced_group_ids = $this->group->synced_ids_get();
		if ( empty( $synced_group_ids ) ) {
			return [];
		}

		// Get the Custom Fields that store the Mattermost Channel ID and User ID.
		$channel_field = $this->plugin->mattermost->channel->meta_field_id_get();
		$user_field    = $this->plugin->mattermost->user->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::get( false )
				->addSelect( '*', 'group.title', 'contact.' . $user_field, 'group.' . $channel_field )
				->addJoin( 'Group AS group', 'LEFT', [ 'group_id', '=', 'group.id' ] )
				->addJoin( 'Contact AS contact', 'LEFT', [ 'contact_id', '=', 'contact.id' ] )
				->addWhere( 'group_id', 'IN', $synced_group_ids )
				->addWhere( 'status:name', '=', 'Added' )
				->addOrderBy( 'group_id', 'ASC' )
				->setLimit( $limit )
				->setOffset( $offset )
				->execute();

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
		$group_contacts = array_values( $result->getArrayCopy() );

		// --<
		return $group_contacts;

	}

	/**
	 * Gets the Contact IDs in a given CiviCRM Group.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id The numeric ID of the CiviCRM Group.
	 * @return array|CRM_Core_Exception $contact_ids The array of CiviCRM Contact IDs, or Exception on failure.
	 * @throws CRM_Core_Exception The Exception object.
	 */
	public function group_contact_ids_get( $group_id ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			try {
				throw new CRM_Core_Exception( __( 'Could not initialize CiviCRM', 'wpcv-civicrm-mattermost' ) );
			} catch ( CRM_Core_Exception $e ) {
				return $e;
			}
		}

		try {

			// Call the API.
			$result = \Civi\Api4\GroupContact::get( false )
				->addWhere( 'group_id', '=', $group_id )
				->addWhere( 'status:name', '=', 'Added' )
				->execute()
				->indexBy( 'contact_id' );

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

		// The ArrayObject is keyed by Contact ID.
		$contact_ids = array_keys( $result->getArrayCopy() );

		// --<
		return $contact_ids;

	}

	/**
	 * Gets the Contact IDs for a given set of Mattermost User IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_ids The array of WordPress User IDs.
	 * @return array $data The array of Contact IDs keyed by User ID.
	 */
	public function group_contact_ids_for_user_ids_get( $user_ids ) {

		$data = [];

		foreach ( $user_ids as $user_id ) {

			// Skip if there is no Contact ID.
			$contact_id = $this->plugin->mattermost->user->contact_id_get( $user_id );
			if ( empty( $contact_id ) ) {
				$data[ $user_id ] = '';
				continue;
			}

			$data[ $user_id ] = $contact_id;

		}

		return $data;

	}

}
