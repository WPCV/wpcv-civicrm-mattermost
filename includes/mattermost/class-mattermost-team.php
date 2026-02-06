<?php
/**
 * Mattermost Team class.
 *
 * Handles Mattermost Team-related functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Mattermost Team Class.
 *
 * A class that encapsulates functionality for Mattermost Teams.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Mattermost_Team {

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
		do_action( 'wpcvmm/mattermost/team/loaded' );

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
	 * Gets the Mattermost Team data for a given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $team_id The Mattermost Team ID.
	 * @return stdClass|bool $team The Mattermost Team object, or false on failure.
	 */
	public function get_by_id( $team_id ) {

		// Init return.
		$team = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $team;
		}

		// Get data from Mattermost.
		$response = $this->mattermost->remote->team_get( $team_id );
		if ( empty( $response ) ) {
			return $team;
		}

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Adds a Mattermost User to a Team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $team_id The Mattermost Team ID.
	 * @param string $user_id The Mattermost User ID.
	 * @return stdClass|bool $response The Mattermost Team Member object if successful, false otherwise.
	 */
	public function member_create( $team_id, $user_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Call the Mattermost API.
		$response = $this->mattermost->remote->user_add_to_team( $team_id, $user_id );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "team_id": "w16bnhbehjnifgxd86q9i4taea",
		 *   ... more data ...
		 * )
		 *
		 * When there is an error, we could add this query to the query cache.
		 * However we may not need caching for this particular method.
		 */
		if ( false === $response ) {

			// Build data.
			$data = [
				'team_id' => $team_id,
				'user_id' => $user_id,
			];

			// Build query.
			$query = [
				'action'   => 'team_member_create',
				'endpoint' => 'teams/' . $team_id . '/members',
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
		if ( empty( $response->team_id ) ) {
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
		if ( 'team_member_create' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Removes a Mattermost User from a Team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $team_id The Mattermost Team ID.
	 * @param string $user_id The Mattermost User ID to remove from the Team.
	 * @return string|bool $status The Mattermost status if successful, false otherwise.
	 */
	public function member_delete( $team_id, $user_id ) {

		// Init return.
		$status = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $status;
		}

		// Call the Mattermost API.
		$response = $this->mattermost->remote->user_remove_from_team( $team_id, $user_id );

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
				'action'   => 'team_member_delete',
				'endpoint' => 'teams/' . $team_id . '/members/' . $user_id,
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
		if ( 'team_member_delete' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

}
