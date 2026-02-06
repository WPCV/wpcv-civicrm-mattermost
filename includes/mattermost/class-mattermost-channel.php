<?php
/**
 * Mattermost Channel class.
 *
 * Handles Mattermost Channel-related functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Mattermost Channel Class.
 *
 * A class that encapsulates functionality for Mattermost Channels.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Mattermost_Channel {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost
	 */
	public $plugin;

	/**
	 * Mattermost object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Mattermost
	 */
	public $mattermost;

	/**
	 * Mattermost Channel Custom Group "slug".
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $group_slug = 'WPCVMM_Group';

	/**
	 * Mattermost Channel ID Custom Field "slug".
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $field_id_slug = 'WPCVMM_Group_Channel_ID';

	/**
	 * Mattermost Channel Name/Slug Custom Field "slug".
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $field_name_slug = 'WPCVMM_Group_Channel_Name';

	/**
	 * Mattermost Channel URL Custom Field "slug".
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $field_url_slug = 'WPCVMM_Group_Channel_URL';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_Mattermost $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->plugin     = $parent->plugin;
		$this->mattermost = $parent;

		// Init when this plugin is loaded.
		add_action( 'wpcvmm/mattermost/loaded', [ $this, 'initialise' ] );

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
		do_action( 'wpcvmm/mattermost/channel/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		// Install Group Meta on plugin activation.
		add_action( 'wpcvmm/activated', [ $this, 'meta_install' ] );

		// Verify Group Meta on plugin settings save.
		add_action( 'wpcvmm_settings/settings/form/save_after', [ $this, 'meta_install' ] );

		/*
		// Uninstall Group Meta on plugin deactivation.
		add_action( 'wpcvmm/deactivated', [ $this, 'meta_uninstall' ] );

		// Add callbacks for cache queue results.
		add_action( 'wpcvmm/queue/item', [ $this, 'channel_created' ], 10, 2 );
		add_action( 'wpcvmm/queue/item', [ $this, 'member_created' ], 10, 2 );
		add_action( 'wpcvmm/queue/item', [ $this, 'member_deleted' ], 10, 2 );
		*/

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the Mattermost Team data for a given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $channel_id The Mattermost Channel ID.
	 * @return stdClass|bool $channel The Mattermost Channel object, or false on failure.
	 */
	public function get_by_id( $channel_id ) {

		// Init return.
		$channel = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $channel;
		}

		// Get data from Mattermost.
		$response = $this->mattermost->remote->channel_get( $channel_id );
		if ( empty( $response ) ) {
			return $channel;
		}

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Creates a Mattermost Channel.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The "slug" of the Mattermost Channel.
	 * @param string $title The "display name" of the Mattermost Channel.
	 * @param string $type The Mattermost Channel Type to create. Either "O" (open) or "P" (private).
	 * @param string $team_id The ID of the Mattermost Team in which the Channel should be created.
	 * @return stdClass|bool $channel The Mattermost Channel object if successful, false otherwise.
	 */
	public function create( $name, $title, $type = 'O', $team_id = '' ) {

		// Init return.
		$channel = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $channel;
		}

		// Bail if the required fields have not been passed in.
		if ( empty( $name ) || empty( $title ) ) {
			return $channel;
		}

		// Bail if the Type is not valid.
		if ( empty( $type ) || ! in_array( $type, [ 'O', 'P' ], true ) ) {
			return $channel;
		}

		// Check the Mattermost Team ID.
		if ( empty( $team_id ) ) {
			$team_id = $this->mattermost->remote->api_team_id_get();
			// Bail if we do not have one.
			if ( empty( $team_id ) ) {
				return $channel;
			}
		}

		// Process Channel "name" to be URL-friendly.
		$name = sanitize_title( sanitize_user( $name, true ) );

		// Populate Channel data.
		$data = [
			'team_id'      => $team_id,
			'name'         => $name, // Matches CiviCRM Group "name", but is more URL-friendly.
			'display_name' => $title,
			'type'         => $type,
		];

		// Now create the Channel.
		$response = $this->mattermost->remote->channel_create( $data );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "id": "w16bnhbehjnifgxd86q9i4taea",
		 *   ... more data ...
		 * )
		 *
		 * When there is an error, we could add this query to the query cache.
		 * However we may not need caching for this particular method.
		 */
		if ( false === $response ) {

			// Build query.
			$query = [
				'action'   => 'channel_create',
				'endpoint' => 'channels',
				'body'     => $data,
				'method'   => 'POST',
			];

			/*
			// Add to cache queue.
			// Disabled, but shows how this would be done.
			$this->mattermost->remote->cache->queue_add( $query );
			*/

			// --<
			return $channel;

		}

		// Sanity check.
		if ( ! empty( $response->status_code ) ) {
			return $channel;
		}

		// --<
		return $response;

	}

	/**
	 * Called when a queue item has been moved off the stack.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item The queue item.
	 * @param object $result The result of a successful Mattermost API call.
	 */
	public function channel_created( $item, $result ) {

		// Bail if not the action we're after.
		if ( 'channel_create' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a set of Mattermost Users who are members of a given set of Channel IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $channel_ids An array of Mattermost Channel IDs.
	 * @return stdClass[]|bool $response The array of Mattermost Users if successful, false otherwise.
	 */
	public function members_get( $channel_ids = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Call the Mattermost API.
		$response = $this->mattermost->remote->users_get_by_channel_ids( $channel_ids );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "channel_id": "w16bnhbehjnifgxd86q9i4taea",
		 *   ... more data ...
		 * )
		 */
		if ( false === $response ) {
			return false;
		}

		// Sanity check.
		if ( empty( $response ) ) {
			return false;
		}

		// --<
		return $response;

	}

	/**
	 * Gets a set of Mattermost Channel Members in synced Channels with a given limit and offset.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $limit The numeric limit for the query.
	 * @param integer $offset The numeric offset for the query.
	 * @return stdClass[] $batch The array of Mattermost Channel Members.
	 */
	public function members_batch_get( $limit = 0, $offset = 0 ) {

		// Get all Channel Members.
		$channel_members = $this->members_in_synced_groups_get();

		// Grab just those that we want to process.
		$batch = array_slice( $channel_members, $offset, $limit );

		// --<
		return $batch;

	}

	/**
	 * Gets a set of Mattermost Users who are members of the synced CiviCRM Groups.
	 *
	 * @since 1.0.0
	 *
	 * @return stdClass[] $channel_members The array of Mattermost Users if successful, empty array otherwise.
	 */
	public function members_in_synced_groups_get() {

		// Get all Channel IDs for the synced CiviCRM Group IDs.
		$channel_ids = $this->plugin->civicrm->group->channel_ids_get();

		// Build the args to retrieve all members - review if this is not enough.
		$args = [
			'per_page' => 10000,
		];

		// Get all the Group Users in the Synced Groups.
		$channel_members = [];
		foreach ( $channel_ids as $channel_id ) {
			$members = $this->members_get_by_channel_id( $channel_id, $args );
			if ( ! empty( $members ) ) {
				$channel_members = array_merge( $channel_members, $members );
			}
		}

		// --<
		return $channel_members;

	}

	/**
	 * Gets a set of Mattermost Users who are members of a given Channel ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The Mattermost Channel ID.
	 * @param array  $args The array of query parameters. See API docs for details.
	 * @return stdClass[]|bool $response The array of Mattermost Users if successful, false otherwise.
	 */
	public function members_get_by_channel_id( $channel_id, $args = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Call the Mattermost API.
		$response = $this->mattermost->remote->users_get_by_channel_id( $channel_id, $args );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "channel_id": "w16bnhbehjnifgxd86q9i4taea",
		 *   ... more data ...
		 * )
		 */
		if ( false === $response ) {
			return false;
		}

		// Sanity check.
		if ( empty( $response ) ) {
			return false;
		}

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Checks if a Mattermost User is a Member of a Channel.
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The Mattermost User ID.
	 * @param string $channel_id The Mattermost Channel ID.
	 * @param string $team_id The ID of the Mattermost Team in which the Channel is located.
	 * @return stdClass|bool|string $exists The Channel Object if the User is a Channel Member, false if not. Error string on failure.
	 */
	public function member_exists( $user_id, $channel_id, $team_id = '' ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return __( 'Cannot connect to Mattermost.', 'wpcv-civicrm-mattermost' );
		}

		// Check the Mattermost Team ID.
		if ( empty( $team_id ) ) {
			$team_id = $this->mattermost->remote->api_team_id_get();
			// Bail if we do not have one.
			if ( empty( $team_id ) ) {
				return __( 'Cannot determine the Mattermost Team ID.', 'wpcv-civicrm-mattermost' );
			}
		}

		// Get all the User's Channels.
		$channels = $this->mattermost->remote->channels_get_for_user( $user_id, $team_id );

		// Init return.
		$exists = false;

		// Let's try and find the requested Channel ID.
		foreach ( $channels as $channel ) {
			if ( $channel->id === $channel_id ) {
				$exists = $channel;
				break;
			}
		}

		// --<
		return $exists;

	}

	/**
	 * Adds one or more Mattermost Users to a Channel.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $channel_id The Mattermost Channel ID.
	 * @param string|string[] $user_ids A single User ID or an array of Mattermost User IDs.
	 * @return stdClass|bool $response The Mattermost Channel Member object if successful, false otherwise.
	 */
	public function member_create( $channel_id, $user_ids ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Call the Mattermost API.
		$response = $this->mattermost->remote->users_add_to_channel( $channel_id, $user_ids );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "channel_id": "w16bnhbehjnifgxd86q9i4taea",
		 *   ... more data ...
		 * )
		 *
		 * When there is an error, we could add this query to the query cache.
		 * However we may not need caching for this particular method.
		 */
		if ( false === $response ) {

			// Build data.
			$data = [];
			if ( is_string( $user_ids ) ) {
				$data['user_id'] = $user_ids;
			} else {
				$data['user_ids'] = $user_ids;
			}

			// Build query.
			$query = [
				'action'   => 'channel_member_create',
				'endpoint' => 'channels/' . $channel_id . '/members',
				'body'     => $data,
				'method'   => 'POST',
			];

			/*
			// Add to cache queue.
			// Disabled, but shows how this would be done.
			$this->mattermost->remote->cache->queue_add( $query );
			*/

			// --<
			return false;

		}

		// Sanity check.
		if ( empty( $response->channel_id ) ) {
			return false;
		}

		// --<
		return $response;

	}

	/**
	 * Called when a queue item has been moved off the stack.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item The queue item.
	 * @param object $result The result of a successful Mattermost API call.
	 */
	public function member_created( $item, $result ) {

		// Bail if not the action we're after.
		if ( 'channel_member_create' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Removes a Mattermost User from a Channel.
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The Mattermost Channel ID.
	 * @param string $user_id The Mattermost User ID to remove from the Channel.
	 * @return string|bool $status The Mattermost status if successful, false otherwise.
	 */
	public function member_delete( $channel_id, $user_id ) {

		// Init return.
		$status = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $status;
		}

		// Call the Mattermost API.
		$response = $this->mattermost->remote->user_remove_from_channel( $channel_id, $user_id );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "status": "foo",
		 *   ... more data ...
		 * )
		 *
		 * When there is an error, we could add this query to the query cache.
		 * However we may not need caching for this particular method.
		 */
		if ( false === $response ) {

			// Build query.
			$query = [
				'action'   => 'channel_member_delete',
				'endpoint' => 'channels/' . $channel_id . '/members/' . $user_id,
				'body'     => [],
				'method'   => 'DELETE',
			];

			/*
			// Add to cache queue.
			// Disabled, but shows how this would be done.
			$this->mattermost->remote->cache->queue_add( $query );
			*/

			// --<
			return $status;

		}

		// Sanity check.
		if ( empty( $response->status ) ) {
			return $status;
		}

		// Extract the status.
		$status = $response->status;

		// --<
		return $status;

	}

	/**
	 * Called when a queue item has been moved off the stack.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item The queue item.
	 * @param object $result The result of a successful Mattermost API call.
	 */
	public function member_deleted( $item, $result ) {

		// Bail if not the action we're after.
		if ( 'channel_member_delete' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the User IDs to add to a given Mattermost Channel for a given set of Contact IDs.
	 *
	 * @since 0.2.1
	 *
	 * @param array $contact_ids The array of CiviCRM Contact IDs.
	 * @param int   $channel_id The numeric ID of the Mattermost Channel.
	 * @return array $result The array of User IDs.
	 */
	public function user_ids_to_add( $contact_ids, $channel_id ) {

		$result = [
			'no-user-id'  => [],
			'has-user-id' => [],
		];

		foreach ( $contact_ids as $contact_id ) {

			// Skip if there is no User ID.
			$user_id = $this->plugin->civicrm->contact->user_id_get( $contact_id );
			if ( empty( $user_id ) ) {
				$result['no-user-id'][] = $contact_id;
				continue;
			}

			// Skip if they are already a Mattermost Channel Member.
			if ( $this->member_exists( $user_id, $channel_id ) ) {
				continue;
			}

			$result['has-user-id'][ $contact_id ] = $user_id;

		}

		return $result;

	}

	/**
	 * Bulk adds a set of User IDs to a given Mattermost Channel.
	 *
	 * @since 0.2.1
	 *
	 * @param array $user_ids The array of User IDs.
	 * @param int   $channel_id The numeric ID of the Mattermost Channel.
	 * @return array $result The array of User IDs where adding succeeded or failed.
	 */
	public function user_ids_add( $user_ids, $channel_id ) {

		$result = [
			'added'  => [],
			'failed' => [],
		];

		// Bail if there are no Users to add to the Mattermost Channel.
		if ( empty( $user_ids ) ) {
			return $result;
		}

		foreach ( $user_ids as $contact_id => $user_id ) {
			$success = $this->member_create( $channel_id, $user_id );
			if ( false === $success ) {
				$result['failed'][ $contact_id ] = $user_id;
			} else {
				$result['added'][ $contact_id ] = $user_id;
			}
		}

		return $result;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a CiviCRM Group ID for a given Mattermost Channel ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The Mattermost Channel ID.
	 * @return integer|bool $group_id The CiviCRM Group ID, or false on failure.
	 */
	public function group_id_get( $channel_id ) {

		// Init return.
		$group_id = false;

		// Bail if no CiviCRM.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $group_id;
		}

		// Get the Custom Field for the Mattermost Channel ID.
		$custom_field = $this->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( 'id' )
				->addWhere( $custom_field, '=', $channel_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $group_id;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $group_id;
		}

		// We want the first result.
		$group = $result->first();

		// We only want the ID of the first result.
		$group_id = (int) $group['id'];

		// --<
		return $group_id;

	}

	/**
	 * Gets a CiviCRM Group for a given Mattermost Channel ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The Mattermost Channel ID.
	 * @return stdClass|bool $group The CiviCRM Group object, or false on failure.
	 */
	public function group_get( $channel_id ) {

		// Init return.
		$group = false;

		// Bail if no CiviCRM.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $group;
		}

		// Get the Custom Field for the Mattermost Channel ID.
		$custom_field = $this->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( '*' )
				->addWhere( $custom_field, '=', $channel_id )
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

		// --<
		return $group;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the Mattermost Channel ID Field in Group Meta.
	 *
	 * This is used when querying the CiviCRM API for values in the Custom Field.
	 *
	 * @since 1.0.0
	 */
	public function meta_field_id_get() {

		// Join Custom Group slug and Custom Field slug.
		return $this->group_slug . '.' . $this->field_id_slug;

	}

	/**
	 * Gets the Mattermost Channel Name/Slug Field in Group Meta.
	 *
	 * This is used when querying the CiviCRM API for values in the Custom Field.
	 *
	 * @since 1.0.0
	 */
	public function meta_field_name_get() {

		// Join Custom Group slug and Custom Field slug.
		return $this->group_slug . '.' . $this->field_name_slug;

	}

	/**
	 * Gets the Mattermost Channel URL Field in Group Meta.
	 *
	 * This is used when querying the CiviCRM API for values in the Custom Field.
	 *
	 * @since 1.0.0
	 */
	public function meta_field_url_get() {

		// Join Custom Group slug and Custom Field slug.
		return $this->group_slug . '.' . $this->field_url_slug;

	}

	/**
	 * Installs the Mattermost Channel data as Group Meta.
	 *
	 * @since 1.0.0
	 */
	public function meta_install() {

		// Maybe create a "Mattermost" Custom Group.
		$custom_group = $this->plugin->civicrm->meta->custom_group_get_by_slug( $this->group_slug );
		if ( false !== $custom_group && empty( $custom_group ) ) {
			$title        = __( 'Mattermost Channel', 'wpcv-civicrm-mattermost' );
			$extends      = 'Group';
			$style        = 'Tab';
			$custom_group = $this->plugin->civicrm->meta->custom_group_create( $title, $this->group_slug, $extends, $style );
		}

		// Maybe create Custom Fields.
		if ( false !== $custom_group ) {

			// Maybe create the "Mattermost Channel ID" Custom Field.
			$custom_field = $this->plugin->civicrm->meta->custom_field_get_by_slug( $this->field_id_slug );
			if ( false !== $custom_field && empty( $custom_field ) ) {
				$group_id     = (int) $custom_group['id'];
				$label        = __( 'Channel ID', 'wpcv-civicrm-mattermost' );
				$html_type    = 'Text';
				$read_only    = false;
				$custom_field = $this->plugin->civicrm->meta->custom_field_create( $group_id, $label, $this->field_id_slug, $html_type, $read_only );
			}

			// Maybe create the "Mattermost Channel Name/Slug" Custom Field.
			$custom_field = $this->plugin->civicrm->meta->custom_field_get_by_slug( $this->field_name_slug );
			if ( false !== $custom_field && empty( $custom_field ) ) {
				$group_id     = (int) $custom_group['id'];
				$label        = __( 'Channel Slug', 'wpcv-civicrm-mattermost' );
				$html_type    = 'Text';
				$read_only    = true;
				$custom_field = $this->plugin->civicrm->meta->custom_field_create( $group_id, $label, $this->field_name_slug, $html_type, $read_only );
			}

			// Maybe create the "Mattermost Channel URL" Custom Field.
			$custom_field = $this->plugin->civicrm->meta->custom_field_get_by_slug( $this->field_url_slug );
			if ( false !== $custom_field && empty( $custom_field ) ) {
				$group_id     = (int) $custom_group['id'];
				$label        = __( 'Channel URL', 'wpcv-civicrm-mattermost' );
				$html_type    = 'Text';
				$read_only    = true;
				$custom_field = $this->plugin->civicrm->meta->custom_field_create( $group_id, $label, $this->field_url_slug, $html_type, $read_only );
			}

		}

		/**
		 * Fires when the Mattermost Channel data has been installed.
		 *
		 * @since 1.0.0
		 *
		 * @param array|bool $custom_group The Custom Group data, or false on failure to install.
		 */
		do_action( 'wpcvmm/mattermost/channel/meta/installed', $custom_group );

	}

	/**
	 * Uninstalls the Mattermost Channel data from Group Meta.
	 *
	 * @since 1.0.0
	 */
	public function meta_uninstall() {

		// TODO: Does deleting the "Mattermost" Custom Group delete the Fields?
		$result = $this->plugin->civicrm->meta->custom_group_delete( $this->slug );

		/**
		 * Fires when the Mattermost Channel data has been uninstalled.
		 *
		 * @since 1.0.0
		 *
		 * @param array|bool $custom_group The Custom Group data, or false on failure to uninstall.
		 */
		do_action( 'wpcvmm/mattermost/channel/meta/uninstalled', $custom_group );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the URL of a Mattermost Channel.
	 *
	 * This is quite an expensive lookup since it needs an API call to build the URL.
	 * We should think about caching this somewhere (e.g. a transient or the WordPress
	 * or CiviCRM cache) or storing it in another Custom Field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The Mattermost Channel ID.
	 * @return string $url The URL of the Mattermost Channel.
	 */
	public function url_get( $channel_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return '';
		}

		// Get the synced Team.
		$team = $this->mattermost->remote->api_team_get();
		if ( empty( $team ) ) {
			return '';
		}

		// Try and get the full Channel data.
		$channel = $this->mattermost->remote->channel_get( $channel_id );
		if ( empty( $channel ) ) {
			return '';
		}

		// The domain can be retrieved from the API URL. This should work until API v9.
		$domain = substr( trailingslashit( $credentials['url'] ), 0, -8 );

		// Build Channel URL.
		$url = $domain . '/' . $team->name . '/channels/' . $channel->name;

		/**
		 * Filters the URL of the Mattermost Channel.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url The URL of the Mattermost Channel.
		 * @param string $channel_id The Mattermost Channel ID.
		 */
		return apply_filters( 'wpcvmm/mattermost/channel/url_get', $url, $channel_id );

	}

}
