<?php
/**
 * Remote Class.
 *
 * Handles functionality for remote operations.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Remote Class.
 *
 * A class that encapsulates functionality for remote operations.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Remote {

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
	 * Cache object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Remote_Cache
	 */
	public $cache;

	/**
	 * Test object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Remote_Test
	 */
	public $test;

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

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return;
		}

		// Bootstrap class.
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpcvmm/mattermost/remote/loaded' );

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0.0
	 */
	private function include_files() {

		// Include class files.
		include WPCVMM_PATH . 'includes/mattermost/remote/class-remote-api.php';
		include WPCVMM_PATH . 'includes/mattermost/remote/class-remote-cache.php';

		/*
		// Use when testing.
		include WPCVMM_PATH . 'includes/mattermost/remote/class-remote-test.php';
		*/

	}

	/**
	 * Instantiates objects.
	 *
	 * @since 1.0.0
	 */
	private function setup_objects() {

		// Init objects.
		$this->cache = new WPCV_CiviCRM_Mattermost_Remote_Cache();

		/*
		// Use when testing.
		$this->test  = new WPCV_CiviCRM_Mattermost_Remote_Test( $this );
		*/

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the Mattermost API credentials.
	 *
	 * @since 1.0.0
	 *
	 * @return array|bool $credentials The array of Mattermost API credentials, or false otherwise.
	 */
	public function api_credentials_get() {

		// Init return.
		$credentials = [];

		// Try constants first.
		if ( defined( 'WPCVMM_API_URL' ) ) {
			$credentials['url'] = WPCVMM_API_URL;
		}
		if ( defined( 'WPCVMM_API_TOKEN' ) ) {
			$credentials['token'] = WPCVMM_API_TOKEN;
		}
		if ( ! empty( $credentials['url'] ) && ! empty( $credentials['token'] ) ) {
			return $credentials;
		}

		// Try settings next.
		$url = $this->plugin->admin->setting_get( 'mm_url' );
		if ( ! empty( $url ) ) {
			$credentials['url'] = $url;
		}
		$token = $this->plugin->admin->setting_get( 'mm_token' );
		if ( ! empty( $url ) ) {
			$credentials['token'] = $token;
		}
		if ( ! empty( $credentials['url'] ) && ! empty( $credentials['token'] ) ) {
			return $credentials;
		}

		// Failed to retrieve credentials.
		return false;

	}

	/**
	 * Gets the Mattermost Team that integrates with CiviCRM.
	 *
	 * @since 1.0.0
	 *
	 * @return stdClass|bool $team The Mattermost Team object, or false otherwise.
	 */
	public function api_team_get() {

		// Get the setting data.
		$team = $this->plugin->admin->setting_get( 'mm_team', false );

		// --<
		return $team;

	}

	/**
	 * Gets the Mattermost Team ID that integrates with CiviCRM.
	 *
	 * @since 1.0.0
	 *
	 * @return string|bool $team_id The Mattermost Team ID, or false otherwise.
	 */
	public function api_team_id_get() {

		// Get the setting data.
		$team_id = $this->plugin->admin->setting_get( 'mm_team_id', false );

		// --<
		return $team_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a Mattermost Team.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetTeam
	 *
	 * @since 1.0.0
	 *
	 * @param string $team_id The ID of the Mattermost Team.
	 * @return stdClass|bool $response The Mattermost Team object if successful, false otherwise.
	 */
	public function team_get( $team_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'teams/' . $team_id, [], true, [], true );

		// Did we get an error?
		if ( false === $response ) {
			return false;
		}

		// Disambiguate 404 responses.
		if ( ! empty( $response->status_code ) && 404 === (int) $response->status_code ) {
			if ( 'api.context.404.app_error' === $response->id ) {
				// There has been an API call error.
				$e   = new \Exception();
				$log = [
					'method'    => __METHOD__,
					'response'  => $response,
					'backtrace' => $e->getTraceAsString(),
				];
				$this->plugin->log_error( $log );
				return false;
			}
			if ( 'app.team.get.existing.app_error' === $response->id ) {
				// Unable to find the existing channel.
				return [];
			}
		}

		// --<
		return $response;

	}

	/**
	 * Gets a set of Mattermost Teams.
	 *
	 * For regular Users only returns open Teams. Users with the "manage_system"
	 * permission will return Teams regardless of type. The result is based on query
	 * string parameters - page and per_page.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetAllTeams
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The array of query parameters. See API docs for details.
	 * @return stdClass[]|bool $channel The array of Mattermost Teams data if successful, false otherwise.
	 */
	public function teams_get( $args = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Build query parameters.
		$parameters = [];
		if ( ! empty( $args ) ) {
			foreach ( $args as $param => $value ) {
				$parameters[] = $param . '=' . $value;
			}
		}

		// Maybe format them.
		$query = '';
		if ( ! empty( $parameters ) ) {
			$query = '?' . implode( '&', $parameters );
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'teams' . $query, [], true );

		// --<
		return $response;

	}

	/**
	 * Gets the set of Teams that a User is on.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetTeamsForUser
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User.
	 * @return stdClass[]|bool $response The array of Mattermost Teams data if successful, false otherwise.
	 */
	public function teams_get_for_user( $user_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data. Does not return 404.
		$response = $connection->get( 'users/' . $user_id . '/teams', [], true );

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a set of Mattermost Channels.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetAllChannels
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The array of query parameters. See API docs for details.
	 * @return stdClass[]|bool $response The array of Mattermost Channels if successful, false otherwise.
	 */
	public function channels_get( $args = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Build query parameters.
		$parameters = [];
		if ( ! empty( $args ) ) {
			foreach ( $args as $param => $value ) {
				$parameters[] = $param . '=' . $value;
			}
		}

		// Maybe format them.
		$query = '';
		if ( ! empty( $parameters ) ) {
			$query = '?' . implode( '&', $parameters );
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'channels' . $query, [], true );

		// Filter by our Team ID.
		if ( ! empty( $response ) ) {
			$team_id  = $this->api_team_id_get();
			$response = array_filter(
				$response,
				function( $channel ) use ( $team_id ) {
					return $channel->team_id === $team_id;
				}
			);
		}

		// --<
		return $response;

	}

	/**
	 * Gets the set of Channels for a User.
	 *
	 * Note: This call may return Channels of Type "D" which is undocumented but stands
	 * for "Direct". They shouldn't be returned according to the GitHub issue linked
	 * below, but are. There may also be "Group Message" Channels (perhaps of Type "G")
	 * according to the docs, but I haven't found any as yet.
	 *
	 * Note: Channels with no `team_id` also appear to be returned for the System Admin
	 * Users. These all seem to be Channels of Type "D".
	 *
	 * @see https://github.com/mattermost/mattermost-api-reference/issues/429
	 * @see https://docs.mattermost.com/collaborate/channel-types.html
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetChannelsForTeamForUser
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User.
	 * @param string $team_id The ID of the Mattermost Team.
	 * @param array  $args The array of query parameters. See API docs for details.
	 * @return stdClass[]|bool $response The array of Mattermost Channels if successful, false otherwise.
	 */
	public function channels_get_for_user( $user_id, $team_id = '', $args = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get the global Team ID if not supplied.
		if ( empty( $team_id ) ) {
			$team_id = $this->api_team_id_get();
		}

		// Build query parameters.
		$parameters = [];
		if ( ! empty( $args ) ) {
			foreach ( $args as $param => $value ) {
				$parameters[] = $param . '=' . $value;
			}
		}

		// Maybe format them.
		$query = '';
		if ( ! empty( $parameters ) ) {
			$query = '?' . implode( '&', $parameters );
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'users/' . $user_id . '/teams/' . $team_id . '/channels' . $query, [], true, [], true );

		// Did we get an error?
		if ( false === $response ) {
			return false;
		}

		// Disambiguate 404 responses.
		if ( ! empty( $response->status_code ) && 404 === (int) $response->status_code ) {
			if ( 'api.context.404.app_error' === $response->id ) {
				// There has been an API call error.
				$e   = new \Exception();
				$log = [
					'method'    => __METHOD__,
					'response'  => $response,
					'backtrace' => $e->getTraceAsString(),
				];
				$this->plugin->log_error( $log );
				return false;
			}
			if ( 'app.channel.get_channels.not_found.app_error' === $response->id ) {
				// No channels were found.
				return [];
			}
		}

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a Mattermost Channel.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetChannel
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The ID of the Mattermost Channel.
	 * @return stdClass|bool $response The Mattermost Channel object if successful, false otherwise.
	 */
	public function channel_get( $channel_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'channels/' . $channel_id, [], true, [], true );

		// Did we get an error?
		if ( false === $response ) {
			return false;
		}

		// Disambiguate 404 responses.
		if ( ! empty( $response->status_code ) && 404 === (int) $response->status_code ) {
			if ( 'api.context.404.app_error' === $response->id ) {
				// There has been an API call error.
				$e   = new \Exception();
				$log = [
					'method'    => __METHOD__,
					'response'  => $response,
					'backtrace' => $e->getTraceAsString(),
				];
				$this->plugin->log_error( $log );
				return false;
			}
			if ( 'app.channel.get.existing.app_error' === $response->id ) {
				// Unable to find the existing channel.
				return [];
			}
		}

		// --<
		return $response;

	}

	/**
	 * Creates a Mattermost Channel.
	 *
	 * * If creating a public channel, "create_public_channel" permission is required.
	 * * If creating a private channel, "create_private_channel" permission is required.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/CreateChannel
	 *
	 * @since 1.0.0
	 *
	 * @param array $channel The data to create the Mattermost Channel with.
	 * @return stdClass|bool $response The Mattermost Channel object if successful, false otherwise.
	 */
	public function channel_create( $channel ) {

		// Bail if the required fields are not in the data.
		if ( empty( $channel['team_id'] ) || empty( $channel['name'] ) || empty( $channel['display_name'] ) || empty( $channel['type'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->post( 'channels', $channel, [], true );

		// --<
		return $response;

	}

	/**
	 * Fully updates a Mattermost Channel.
	 *
	 * Only certain fields are updatable. Omitted fields will be treated as blank.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/UpdateChannel
	 *
	 * @since 1.0.0
	 *
	 * @param array $channel The data to update the Mattermost Channel with.
	 * @return stdClass|bool $response The Mattermost Channel object if successful, false otherwise.
	 */
	public function channel_update( $channel ) {

		// Bail if the required fields are not in the data.
		if ( empty( $channel['id'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->request( 'channels/' . $channel['id'], $channel, 'PUT' );

		// --<
		return $response;

	}

	/**
	 * Partially updates a Mattermost Channel.
	 *
	 * Partially updates a Channel by providing only the fields to update.
	 * Omitted fields will not be updated.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/PatchChannel
	 *
	 * @since 1.0.0
	 *
	 * @param array $channel The data to patch the Mattermost Channel with.
	 * @return stdClass|bool $response The Mattermost Channel object if successful, false otherwise.
	 */
	public function channel_patch( $channel ) {

		// Bail if the required fields are not in the data.
		if ( empty( $channel['id'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->request( 'channels/' . $channel['id'] . '/patch', $channel, 'PUT' );

		// --<
		return $response;

	}

	/**
	 * Tries to delete a Mattermost Channel.
	 *
	 * Default behaviour is to archive the Channel. This will set the `deleteAt` to the
	 * current timestamp in the database. Soft-deleted Channels may not be accessible
	 * in the user interface.
	 *
	 * They can be viewed and unarchived in "System Console > User Management > Channels".
	 * Direct and Group Message Channels cannot be deleted.
	 *
	 * As of server version 5.28, optionally use the `permanent=true` query parameter
	 * to permanently delete the Channel for compliance reasons.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/DeleteChannel
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The ID of the Mattermost Channel to delete.
	 * @param bool   $permanent Passing true will permanently delete the Mattermost Channel.
	 * @return stdClass|bool $response The Mattermost Channel object if successful, false otherwise.
	 */
	public function channel_delete( $channel_id, $permanent = false ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Maybe add permanent delete query string.
		$query = '';
		if ( true === $permanent ) {
			$query = '?permanent=true';
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->delete( 'channels/' . $channel_id . $query );

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a set of Mattermost Users.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetUsers
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The array of query parameters. See API docs for details.
	 * @return stdClass[]|bool $response The paged array of Mattermost Users if successful, false otherwise.
	 */
	public function users_get( $args = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Build query parameters.
		$parameters = [];
		if ( ! empty( $args ) ) {
			foreach ( $args as $param => $value ) {
				$parameters[] = $param . '=' . $value;
			}
		}

		// Maybe format them.
		$query = '';
		if ( ! empty( $parameters ) ) {
			$query = '?' . implode( '&', $parameters );
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'users' . $query, [], true );

		// --<
		return $response;

	}

	/**
	 * Gets a set of Mattermost Users by their IDs.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetUsersByIds
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $user_ids An array of Mattermost User IDs.
	 * @return stdClass[]|bool $response The array of Mattermost Users if successful, false otherwise.
	 */
	public function users_get_by_id( $user_ids ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->post( 'users/ids', $user_ids, [], true );

		// --<
		return $response;

	}

	/**
	 * Gets a set of Mattermost Users by their usernames.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetUsersByUsernames
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $user_names An array of Mattermost User usernames.
	 * @return stdClass[]|bool $response The array of Mattermost Users if successful, false otherwise.
	 */
	public function users_get_by_username( $user_names ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->post( 'users/usernames', $user_names, [], true );

		// --<
		return $response;

	}

	/**
	 * Gets a set of Mattermost Users in a given set of Channels.
	 *
	 * This API endpoint requires an active session but no other permissions. The Bot
	 * Account would have to go through the login process before this endpoint will
	 * return any data.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetUsersByGroupChannelIds
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $channel_ids An array of Mattermost Channel IDs.
	 * @return stdClass[]|bool $response The array of Mattermost Users if successful, false otherwise.
	 */
	public function users_get_by_channel_ids( $channel_ids = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->post( 'users/group_channels', $channel_ids, [], true );

		// --<
		return $response;

	}

	/**
	 * Gets a set of Mattermost Users in a given Channel.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetChannelMembers
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The ID of the Mattermost Channel.
	 * @param array  $args The array of query parameters. See API docs for details.
	 * @return stdClass[]|bool $response The array of Mattermost Users if successful, false otherwise.
	 */
	public function users_get_by_channel_id( $channel_id, $args = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Build query parameters.
		$parameters = [];
		if ( ! empty( $args ) ) {
			foreach ( $args as $param => $value ) {
				$parameters[] = $param . '=' . $value;
			}
		}

		// Maybe format them.
		$query = '';
		if ( ! empty( $parameters ) ) {
			$query = '?' . implode( '&', $parameters );
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'channels/' . $channel_id . '/members' . $query, [], true );

		// --<
		return $response;

	}

	/**
	 * Adds a set of Mattermost Users to a Channel.
	 *
	 * Add a User(s) to a Channel by creating a Channel Member object(s).
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/AddChannelMember
	 *
	 * @since 1.0.0
	 *
	 * @param string          $channel_id The Mattermost Channel ID.
	 * @param string|string[] $user_ids An array of Mattermost User IDs. A single User ID can also be passed.
	 * @return stdClass[]|bool $response The array of Mattermost Channel data if successful, false otherwise.
	 */
	public function users_add_to_channel( $channel_id, $user_ids ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Build data.
		$data = [];
		if ( is_string( $user_ids ) ) {
			$data['user_id'] = $user_ids;
		} else {
			$data['user_ids'] = $user_ids;
		}

		// Send the data.
		$response = $connection->post( 'channels/' . $channel_id . '/members', $data, [], true );

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Removes a Mattermost User from a Channel.
	 *
	 * Deletes a Channel Member, effectively removing them from a Channel.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/RemoveUserFromChannel
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The Mattermost Channel ID.
	 * @param string $user_id The Mattermost User ID to remove.
	 * @return stdClass|bool $response The Mattermost status object if successful, false otherwise.
	 */
	public function user_remove_from_channel( $channel_id, $user_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->delete( 'channels/' . $channel_id . '/members/' . $user_id );

		// --<
		return $response;

	}

	/**
	 * Adds a Mattermost User to a Team.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/AddTeamMember
	 *
	 * @since 1.0.0
	 *
	 * @param string $team_id The Mattermost Team ID.
	 * @param string $user_id The Mattermost User ID.
	 * @return stdClass[]|bool $response The Mattermost Team Member object if successful, false otherwise.
	 */
	public function user_add_to_team( $team_id, $user_id ) {

		// Bail if the required fields are not populated.
		if ( empty( $team_id ) || empty( $user_id ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Build data.
		$data = [
			'team_id' => $team_id,
			'user_id' => $user_id,
		];

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->post( 'teams/' . $team_id . '/members', $data, [], true );

		// --<
		return $response;

	}

	/**
	 * Removes a Mattermost User from a Team.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/RemoveTeamMember
	 *
	 * @since 1.0.0
	 *
	 * @param string $team_id The Mattermost Team ID.
	 * @param string $user_id The Mattermost User ID to remove.
	 * @return stdClass|bool $response The Mattermost status object if successful, false otherwise.
	 */
	public function user_remove_from_team( $team_id, $user_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->delete( 'teams/' . $team_id . '/members/' . $user_id );

		// --<
		return $response;

	}

	/**
	 * Gets a Mattermost User by their ID.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetUser
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User.
	 * @return stdClass|bool $response The Mattermost User object if successful, false otherwise.
	 */
	public function user_get_by_id( $user_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'users/' . $user_id, [], true, [], true );

		// Did we get an error?
		if ( false === $response ) {
			return false;
		}

		// Disambiguate 404 responses.
		if ( ! empty( $response->status_code ) && 404 === (int) $response->status_code ) {
			if ( 'api.context.404.app_error' === $response->id ) {
				// There has been an API call error.
				$e   = new \Exception();
				$log = [
					'method'    => __METHOD__,
					'response'  => $response,
					'backtrace' => $e->getTraceAsString(),
				];
				$this->plugin->log_error( $log );
				return false;
			}
			if ( 'app.user.missing_account.const' === $response->id ) {
				// Unable to find an existing account.
				return [];
			}
		}

		// --<
		return $response;

	}

	/**
	 * Gets a Mattermost User by their username.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetUserByUsername
	 *
	 * @since 1.0.0
	 *
	 * @param string $username The username of the Mattermost User.
	 * @return stdClass|array|bool $response The Mattermost User object if found, empty array if not found, false otherwise.
	 */
	public function user_get_by_username( $username ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'users/username/' . $username, [], true, [], true );

		// Did we get an error?
		if ( false === $response ) {
			return false;
		}

		// Disambiguate 404 responses.
		if ( ! empty( $response->status_code ) && 404 === (int) $response->status_code ) {
			if ( 'api.context.404.app_error' === $response->id ) {
				// There has been an API call error.
				$e   = new \Exception();
				$log = [
					'method'    => __METHOD__,
					'response'  => $response,
					'backtrace' => $e->getTraceAsString(),
				];
				$this->plugin->log_error( $log );
				return false;
			}
			if ( 'app.user.get_by_username.app_error' === $response->id ) {
				// Unable to find an existing account.
				return [];
			}
		}

		// --<
		return $response;

	}

	/**
	 * Gets a Mattermost User by their email address.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetUserByEmail
	 *
	 * @since 1.0.0
	 *
	 * @param string $email The email address of the Mattermost User.
	 * @return stdClass|array|bool $response The Mattermost User object if found, empty array if not found, false otherwise.
	 */
	public function user_get_by_email( $email ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'users/email/' . $email, [], true, [], true );

		// Did we get an error?
		if ( false === $response ) {
			return false;
		}

		// Disambiguate 404 responses.
		if ( ! empty( $response->status_code ) && 404 === (int) $response->status_code ) {
			if ( 'api.context.404.app_error' === $response->id ) {
				// There has been an API call error.
				$e   = new \Exception();
				$log = [
					'method'    => __METHOD__,
					'response'  => $response,
					'backtrace' => $e->getTraceAsString(),
				];
				$this->plugin->log_error( $log );
				return false;
			}
			if ( 'app.user.missing_account.const' === $response->id ) {
				// Unable to find an existing account.
				return [];
			}
		}

		// --<
		return $response;

	}

	/**
	 * Creates a Mattermost User.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/CreateUser
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_data The data to create the Mattermost User with.
	 * @return stdClass|bool $response The Mattermost User object if successful, false otherwise.
	 */
	public function user_create( $user_data ) {

		// Bail if the required fields are not in the data.
		if ( empty( $user_data['email'] ) || empty( $user_data['username'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->post( 'users', $user_data, [], true );

		// --<
		return $response;

	}

	/**
	 * Activates a Mattermost User.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/UpdateUserActive
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User to activate.
	 * @return stdClass|bool $response The Mattermost response object if successful, false otherwise.
	 */
	public function user_activate( $user_id ) {

		// Bail if the required fields are not in the data.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Build data.
		$data = [
			'active' => true,
		];

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->request( 'users/' . $user_id . '/active', $data, 'PUT' );

		// --<
		return $response;

	}

	/**
	 * Updates the password for a Mattermost User.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/UpdateUserPassword
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User.
	 * @param string $password The new password for the Mattermost User.
	 * @return stdClass|bool $response The Mattermost response object if successful, false otherwise.
	 */
	public function user_password_update( $user_id, $password ) {

		// Bail if the required fields are not in the data.
		if ( empty( $user_id ) || empty( $password ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Build data.
		$data = [
			'new_password' => $password,
		];

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->request( 'users/' . $user_id . '/password', $data, 'PUT' );

		// --<
		return $response;

	}

	/**
	 * Fully updates a Mattermost User.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/UpdateUser
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_data The data to update the Mattermost User with.
	 * @return stdClass|bool $response The Mattermost User object if successful, false otherwise.
	 */
	public function user_update( $user_data ) {

		// Bail if the required fields are not in the data.
		if ( empty( $user_data['id'] ) || empty( $user_data['email'] ) || empty( $user_data['username'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->request( 'users/' . $user_data['id'], $user_data, 'PUT' );

		// --<
		return $response;

	}

	/**
	 * Patches a Mattermost User.
	 *
	 * Partially updates a User by providing only the fields to update. Omitted fields
	 * will not be updated.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/PatchUser
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_data The data to update the Mattermost User with.
	 * @return stdClass|bool $response The Mattermost User object if successful, false otherwise.
	 */
	public function user_patch( $user_data ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->request( 'users/' . $user_data['id'] . '/patch', $user_data, 'PUT' );

		// --<
		return $response;

	}

	/**
	 * Searches for Mattermost Users.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/SearchUsers
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_data The data to search for the Mattermost User with.
	 * @return stdClass[]|bool $response The array of Mattermost User objects if successful, false otherwise.
	 */
	public function user_search( $user_data ) {

		// Bail if the required fields are not in the data.
		if ( empty( $user_data['term'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->post( 'users/search', $user_data, [], true );

		// --<
		return $response;

	}

	/**
	 * Tries to delete a Mattermost User.
	 *
	 * Default behaviour is to deactivate the User and revoke all its sessions by
	 * archiving its User object.
	 *
	 * As of server version 5.28, optionally use the `permanent=true` query parameter
	 * to permanently delete the User for compliance reasons.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/DeleteUser
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User to delete.
	 * @param bool   $permanent Passing true will permanently delete the Mattermost User.
	 * @return string|bool $response The Mattermost API status if successful, false otherwise.
	 */
	public function user_delete( $user_id, $permanent = false ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Maybe add permanent delete query string.
		$query = '';
		if ( true === $permanent ) {
			$query = '?permanent=true';
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->delete( 'users/' . $user_id . $query );

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a set of Mattermost Posts in a given Channel.
	 *
	 * Note: You must pass a valid Channel ID otherwise the API will respond with a
	 * permissions error - it assumes there is a Channel, but that you don't have
	 * access to it.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetPostsForChannel
	 *
	 * @since 1.0.0
	 *
	 * @param string $channel_id The Mattermost Channel ID.
	 * @param array  $args The array of query parameters. See API docs for details.
	 * @return stdClass[]|bool $response The array of Mattermost Posts data if successful, false otherwise.
	 */
	public function posts_get( $channel_id, $args = [] ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Build query parameters.
		$parameters = [];
		if ( ! empty( $args ) ) {
			foreach ( $args as $param => $value ) {
				$parameters[] = $param . '=' . $value;
			}
		}

		// Maybe format them.
		$query = '';
		if ( ! empty( $parameters ) ) {
			$query = '?' . implode( '&', $parameters );
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'channels/' . $channel_id . '/posts' . $query, [], true );

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a Post in a Mattermost Channel.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/GetPost
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_id The ID of the Mattermost Post.
	 * @return stdClass|bool $response The Mattermost Post object if successful, false otherwise.
	 */
	public function post_get( $post_id ) {

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Get the data.
		$response = $connection->get( 'posts/' . $post_id, [], true, [], true );

		// Did we get an error?
		if ( false === $response ) {
			return false;
		}

		// Disambiguate 404 responses.
		if ( ! empty( $response->status_code ) && 404 === (int) $response->status_code ) {
			if ( 'api.context.404.app_error' === $response->id ) {
				// There has been an API call error.
				$e   = new \Exception();
				$log = [
					'method'    => __METHOD__,
					'response'  => $response,
					'backtrace' => $e->getTraceAsString(),
				];
				$this->plugin->log_error( $log );
				return false;
			}
			if ( 'app.post.get.app_error' === $response->id ) {
				// Unable to get the post.
				return [];
			}
		}

		// --<
		return $response;

	}

	/**
	 * Creates a Post in a Mattermost Channel.
	 *
	 * Creates a new Post in a Channel. To create the Post as a Comment on another Post,
	 * provide "root_id".
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/CreatePost
	 *
	 * @since 1.0.0
	 *
	 * @param array $post The data to create the Mattermost Post with.
	 * @return stdClass|bool $response The Mattermost Post object if successful, false otherwise.
	 */
	public function post_create( $post ) {

		// Bail if the required fields are not in the data.
		if ( empty( $post['channel_id'] ) || empty( $post['message'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->post( 'posts', $post, [], true );

		// --<
		return $response;

	}

	/**
	 * Fully updates a Post in a Mattermost Channel.
	 *
	 * Only certain fields are updatable. Omitted fields will be treated as blank.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/UpdatePost
	 *
	 * @since 1.0.0
	 *
	 * @param array $post The data to update the Mattermost Post with.
	 * @return stdClass|bool $response The Mattermost Post object if successful, false otherwise.
	 */
	public function post_update( $post ) {

		// Bail if the required fields are not in the data.
		if ( empty( $post['id'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->request( 'posts/' . $post['id'], $post, 'PUT' );

		// --<
		return $response;

	}

	/**
	 * Partially updates a Post in a Mattermost Channel.
	 *
	 * Partially updates a Post by providing only the fields to update.
	 * Omitted fields will not be updated.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/PatchPost
	 *
	 * @since 1.0.0
	 *
	 * @param array $post The data to patch the Mattermost Post with.
	 * @return stdClass|bool $response The Mattermost Post object if successful, false otherwise.
	 */
	public function post_patch( $post ) {

		// Bail if the required fields are not in the data.
		if ( empty( $post['id'] ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->request( 'posts/' . $post['id'] . '/patch', $post, 'PUT' );

		// --<
		return $response;

	}

	/**
	 * Pins a Post in a Mattermost Channel.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/PinPost
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_id The ID of the Mattermost Post.
	 * @return stdClass|bool $response The Mattermost status object if successful, false otherwise.
	 */
	public function post_pin( $post_id ) {

		// Bail if the required fields are not in the data.
		if ( empty( $post_id ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->post( 'posts/' . $post_id . '/pin', [], [], true );

		// --<
		return $response;

	}

	/**
	 * Unpins a Post in a Mattermost Channel.
	 *
	 * @see https://developers.mattermost.com/api-documentation/#/operations/UnpinPost
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_id The ID of the Mattermost Post.
	 * @return stdClass|bool $response The Mattermost status object if successful, false otherwise.
	 */
	public function post_unpin( $post_id ) {

		// Bail if the required fields are not in the data.
		if ( empty( $post_id ) ) {
			return false;
		}

		// Bail if we can't connect to Mattermost.
		$credentials = $this->api_credentials_get();
		if ( empty( $credentials ) ) {
			return false;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Send the data.
		$response = $connection->post( 'posts/' . $post_id . '/unpin', [], [], true );

		// --<
		return $response;

	}

}
