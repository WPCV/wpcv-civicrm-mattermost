<?php
/**
 * Mattermost User class.
 *
 * Handles Mattermost User-related functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Mattermost User Class.
 *
 * A class that encapsulates functionality for Mattermost Users.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Mattermost_User {

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
	 * Mattermost User Custom Group "slug".
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $group_slug = 'WPCVMM_Contact';

	/**
	 * Mattermost User ID Custom Field "slug".
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $field_id_slug = 'WPCVMM_Contact_User_ID';

	/**
	 * Mattermost User username Custom Field "slug".
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $field_username_slug = 'WPCVMM_Contact_User_Name';

	/**
	 * Mattermost User password Custom Field "slug".
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $field_password_slug = 'WPCVMM_Contact_User_Pass';

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
		do_action( 'wpcvmm/mattermost/user/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		// Install Contact Meta on plugin activation.
		add_action( 'wpcvmm/activated', [ $this, 'meta_install' ] );

		// Verify Contact Meta on plugin settings save.
		add_action( 'wpcvmm_settings/settings/form/save_after', [ $this, 'meta_install' ] );

		// Add a Menu Item to CiviCRM Contact "Action" menu.
		add_action( 'civicrm_summaryActions', [ $this, 'action_menu_item_add' ], 10, 2 );
		add_action( 'civicrm_alterMenu', [ $this, 'action_menu_route_add' ], 10, 1 );

		/*
		// Uninstall Contact Meta on plugin deactivation.
		add_action( 'wpcvmm/deactivated', [ $this, 'meta_uninstall' ] );
		*/

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the Mattermost User data for a given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $user_id The Mattermost User ID.
	 * @return stdClass|bool $user The Mattermost User object, or false on failure.
	 */
	public function get_by_id( $user_id ) {

		// Init return.
		$user = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $user;
		}

		// Get data from Mattermost.
		$response = $this->mattermost->remote->users_get_by_id( [ $user_id ] );
		if ( false === $response ) {
			return $user;
		}

		// We just want the first result.
		$user = array_pop( $response );

		// --<
		return $user;

	}

	/**
	 * Gets the Mattermost User data for a given Email Address.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $email The Mattermost User Email Address.
	 * @return stdClass|bool $user The Mattermost User object, or false on failure.
	 */
	public function get_by_email( $email ) {

		// Init return.
		$user = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $user;
		}

		// Get data from Mattermost.
		$response = $this->mattermost->remote->user_get_by_email( $email );
		if ( empty( $response ) ) {
			return $user;
		}

		// The response is the User object.
		return $response;

	}

	/**
	 * Creates a Mattermost User.
	 *
	 * Important: Make sure you pass a URL-friendly username to this method.
	 *
	 * The `$username` and `$email` params are required - I assume that the username
	 * must be unique, but it's not clear whether the email has to be unique as well
	 * but I assume that it does have to be.
	 *
	 * This could be problematic because there is no unique Primary Email requirement
	 * for Contacts in CiviCRM.
	 *
	 * @see WPCV_CiviCRM_Mattermost_CiviCRM_Contact::user_create()
	 * @see https://api.mattermost.com/#tag/users/operation/GetUserByUsername
	 * @see https://api.mattermost.com/#tag/users/operation/GetUserByEmail
	 * @see https://api.mattermost.com/#tag/users/operation/CreateUser
	 *
	 * @since 1.0.0
	 *
	 * @param string $username The username of the Mattermost User. Must be URL-friendly.
	 * @param string $email The email of the Mattermost User.
	 * @param string $password The password of the Mattermost User used for email authentication.
	 * @param string $first_name The first name of the Mattermost User.
	 * @param string $last_name The last name of the Mattermost User.
	 * @return stdClass|bool $user The Mattermost User object if successful, false otherwise.
	 */
	public function create( $username, $email, $password = '', $first_name = '', $last_name = '' ) {

		// Init return.
		$user = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $user;
		}

		// Bail if the required fields have not been passed in.
		if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
			return $user;
		}

		// Populate required User data.
		$data = [
			'username' => $username,
			'email'    => $email,
			'password' => $password,
		];

		/*
		 * For now, we are using the "email" authentication service. The "password"
		 * param is, however, optional so that the filter can switch to a different
		 * authentication service.
		 *
		 */
		$data['auth_service'] = 'email';

		// Populate optional User data.
		if ( ! empty( $first_name ) ) {
			$data['first_name'] = $first_name;
		}
		if ( ! empty( $last_name ) ) {
			$data['last_name'] = $last_name;
		}

		/*
		 * Try and disable the welcome email. The filter can switch this on if needed.
		 * This param is undocumented, but mentioned here:
		 *
		 * @see https://forum.mattermost.com/t/api-documentation-out-of-date/25211/2
		 */
		$data['disable_welcome_email'] = true;

		/**
		 * Filters the data from which the Mattermost User will be created.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data The data from which the Mattermost User will be created.
		 */
		$data = apply_filters( 'wpcvmm/mattermost/user/create', $data );

		// Now create the User.
		$response = $this->mattermost->remote->user_create( $data );

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
				'action'   => 'user_create',
				'endpoint' => 'users',
				'body'     => $data,
				'method'   => 'POST',
			];

			/*
			// Add to cache queue.
			// Disabled, but shows how this would be done.
			$this->mattermost->remote->cache->queue_add( $query );
			*/

			// --<
			return $user;

		}

		// Sanity check.
		if ( empty( $response->username ) ) {
			return $user;
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
	public function user_created( $item, $result ) {

		// Bail if not the action we're after.
		if ( 'user_create' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

	/**
	 * Activates a Mattermost User.
	 *
	 * @see WPCV_CiviCRM_Mattermost_CiviCRM_Contact::user_activate()
	 * @see https://api.mattermost.com/#tag/users/operation/ActivateUser
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User.
	 * @return string|bool $success The Mattermost status if successful, false otherwise.
	 */
	public function activate( $user_id ) {

		// Init return.
		$success = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $success;
		}

		// Bail if the required fiel have not been passed in.
		if ( empty( $user_id ) ) {
			return $success;
		}

		// Now "delete" the User.
		$response = $this->mattermost->remote->user_activate( $user_id );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "status": "ok",
		 * )
		 *
		 * When there is an error, we could add this query to the query cache.
		 * However we may not need caching for this particular method.
		 */
		if ( false === $response ) {

			// Build query.
			$query = [
				'action'   => 'user_activate',
				'endpoint' => 'users/' . $user_id . '/active',
				'body'     => [],
				'method'   => 'PUT',
			];

			/*
			// Add to cache queue.
			// Disabled, but shows how this would be done.
			$this->mattermost->remote->cache->queue_add( $query );
			*/

			// --<
			return $success;

		}

		// Sanity check.
		if ( empty( $response->status ) ) {
			return $success;
		}

		// Grab the status string.
		$success = $response->status;

		// --<
		return $success;

	}

	/**
	 * Called when a queue item has been moved off the stack.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item The queue item.
	 * @param object $result The result of a successful Mattermost API call.
	 */
	public function user_activated( $item, $result ) {

		// Bail if not the action we're after.
		if ( 'user_activate' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

	/**
	 * Deletes a Mattermost User for a given ID.
	 *
	 * Default behaviour is to deactivate the User and revoke all its sessions by
	 * archiving its User object. The `$permanent` param can be used if the server
	 * has been configured to allow hard-deletion of Users.
	 *
	 * @see WPCV_CiviCRM_Mattermost_CiviCRM_Contact::user_delete()
	 * @see WPCV_CiviCRM_Mattermost_Remote::user_delete()
	 * @see https://api.mattermost.com/#tag/users/operation/DeleteUser
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User.
	 * @return string|bool $success The Mattermost status if successful, false otherwise.
	 */
	public function delete( $user_id ) {

		// Init return.
		$success = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $success;
		}

		// Bail if the required fiel have not been passed in.
		if ( empty( $user_id ) ) {
			return $success;
		}

		// Now "delete" the User.
		$response = $this->mattermost->remote->user_delete( $user_id );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "status": "ok",
		 * )
		 *
		 * When there is an error, we could add this query to the query cache.
		 * However we may not need caching for this particular method.
		 */
		if ( false === $response ) {

			// Build query.
			$query = [
				'action'   => 'user_delete',
				'endpoint' => 'users/' . $user_id,
				'body'     => [],
				'method'   => 'DELETE',
			];

			/*
			// Add to cache queue.
			// Disabled, but shows how this would be done.
			$this->mattermost->remote->cache->queue_add( $query );
			*/

			// --<
			return $success;

		}

		// Sanity check.
		if ( empty( $response->status ) ) {
			return $success;
		}

		// Grab the status string.
		$success = $response->status;

		// --<
		return $success;

	}

	/**
	 * Called when a queue item has been moved off the stack.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item The queue item.
	 * @param object $result The result of a successful Mattermost API call.
	 */
	public function user_deleted( $item, $result ) {

		// Bail if not the action we're after.
		if ( 'user_delete' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Updates the password for a Mattermost User.
	 *
	 * @see WPCV_CiviCRM_Mattermost_CiviCRM_Contact::user_password_update()
	 * @see https://api.mattermost.com/#tag/users/operation/UpdateUserPassword
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The ID of the Mattermost User.
	 * @param string $password The new password for the Mattermost User.
	 * @return string|bool $success The Mattermost status if successful, false otherwise.
	 */
	public function password_update( $user_id, $password ) {

		// Init return.
		$success = false;

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $success;
		}

		// Bail if the required fields have not been passed in.
		if ( empty( $user_id ) || empty( $password ) ) {
			return $success;
		}

		// Now "delete" the User.
		$response = $this->mattermost->remote->user_password_update( $user_id, $password );

		/*
		 * Return should be something like:
		 *
		 * stdClass Object (
		 *   "status": "ok",
		 * )
		 *
		 * When there is an error, we could add this query to the query cache.
		 * However we may not need caching for this particular method.
		 */
		if ( false === $response ) {

			// Build data.
			$data = [
				'new_password' => $password,
			];

			// Build query.
			$query = [
				'action'   => 'user_password_update',
				'endpoint' => 'users/' . $user_id . '/password',
				'body'     => $data,
				'method'   => 'PUT',
			];

			/*
			// Add to cache queue.
			// Disabled, but shows how this would be done.
			$this->mattermost->remote->cache->queue_add( $query );
			*/

			// --<
			return $success;

		}

		// Sanity check.
		if ( empty( $response->status ) ) {
			return $success;
		}

		// Grab the status string.
		$success = $response->status;

		// --<
		return $success;

	}

	/**
	 * Called when a queue item has been moved off the stack.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item The queue item.
	 * @param object $result The result of a successful Mattermost API call.
	 */
	public function user_password_updated( $item, $result ) {

		// Bail if not the action we're after.
		if ( 'user_password_update' !== $item['action'] ) {
			return;
		}

		/*
		// Maybe do something.
		*/

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Generates a unique username for a Mattermost User from CiviCRM Contact data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $username The previously-generated Mattermost User username.
	 * @param array  $contact The CiviCRM Contact data.
	 * @return string|bool $new_username The unique Mattermost User username, or false on failure.
	 */
	public function username_unique_get( $username, $contact ) {

		// Check if this username exists.
		$exists = $this->username_exists( $username );

		// Bail if there is a failure of some kind.
		if ( 'error' === $exists ) {
			return false;
		}

		// Return early if this is already unique.
		if ( false === $exists ) {
			return $username;
		}

		// Init flags.
		$count       = 1;
		$user_exists = 1;

		do {

			// Construct a new username with a numeric suffix.
			$new_username = sanitize_title( sanitize_user( $contact['display_name'] . ' ' . $count ) );

			// Check if this username exists.
			$user_exists = $this->username_exists( $new_username );

			// Skip to next if there is a failure of some kind.
			if ( 'error' === $user_exists ) {
				$user_exists = true;
			}

			// Try the next integer.
			$count++;

		} while ( true === $user_exists );

		// --<
		return $new_username;

	}

	/**
	 * Checks if a Mattermost User with the given username exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $username The Mattermost User username to check.
	 * @return string|bool $exists False when the username does not exist, true if it does, string "error" otherwise.
	 */
	public function username_exists( $username ) {

		// Init return.
		$exists = 'error';

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $exists;
		}

		// Bail if the required fields have not been passed in.
		if ( empty( $username ) ) {
			return $exists;
		}

		// Now check for Mattermost User(s).
		$response = $this->mattermost->remote->users_get_by_username( [ $username ] );

		// We don't try and queue failures - just report as error.
		if ( false === $response ) {
			return $exists;
		}

		// If there is anything but an empty response, then a Mattermost User exists.
		if ( ! empty( $response ) ) {
			return true;
		}

		// No Mattermost User with the given username exists.
		return false;

	}

	/**
	 * Checks if a Mattermost User with the given email exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email The Mattermost User email to check.
	 * @return string|bool $exists False when the email does not exist, true if it does, string "error" otherwise.
	 */
	public function email_exists( $email ) {

		// Init return.
		$exists = 'error';

		// Bail if we can't connect to Mattermost.
		$credentials = $this->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return $exists;
		}

		// Bail if the required fields have not been passed in.
		if ( empty( $email ) ) {
			return $exists;
		}

		// Now check for Mattermost User(s).
		$response = $this->mattermost->remote->user_get_by_email( $email );

		// We don't try and queue failures - just report as error.
		if ( false === $response ) {
			return $exists;
		}

		// If there is anything but an empty response, then a Mattermost User exists.
		if ( ! empty( $response ) ) {
			return true;
		}

		// No Mattermost User with the given email exists.
		return false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets a CiviCRM Contact for a given Mattermost User ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The Mattermost User ID.
	 * @return stdClass|bool $contact The CiviCRM Contact object, or false on failure.
	 */
	public function contact_get( $user_id ) {

		// Init return.
		$contact = false;

		// Bail if no CiviCRM.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $contact;
		}

		// Get the Custom Field for the Mattermost User ID.
		$custom_field = $this->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::get( false )
				->addSelect( '*' )
				->addWhere( $custom_field, '=', $user_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $contact;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $contact;
		}

		// We want the first result.
		$contact = $result->first();

		// --<
		return $contact;

	}

	/**
	 * Gets a CiviCRM Contact ID for a given Mattermost User ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_id The Mattermost User ID.
	 * @return integer|bool $contact_id The CiviCRM Contact ID, or false on failure.
	 */
	public function contact_id_get( $user_id ) {

		// Init return.
		$contact_id = false;

		// Bail if no CiviCRM.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $contact_id;
		}

		// Get the Custom Field for the Mattermost User ID.
		$custom_field = $this->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::get( false )
				->addSelect( 'id' )
				->addWhere( $custom_field, '=', $user_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $contact_id;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $contact_id;
		}

		// We want the first result.
		$contact = $result->first();

		// We only want the ID of the first result.
		$contact_id = (int) $contact['id'];

		// --<
		return $contact_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the Mattermost User ID Field in Contact Meta.
	 *
	 * This is used when querying the CiviCRM API for values in the Custom Field.
	 *
	 * @since 1.0.0
	 *
	 * @return string The API code for the Mattermost User ID Field.
	 */
	public function meta_field_id_get() {

		// Join Custom Group slug and Custom Field slug.
		return $this->group_slug . '.' . $this->field_id_slug;

	}

	/**
	 * Gets the Mattermost User username Field in Contact Meta.
	 *
	 * This is used when querying the CiviCRM API for values in the Custom Field.
	 *
	 * @since 1.0.0
	 *
	 * @return string The API code for the Mattermost User username Field.
	 */
	public function meta_field_username_get() {

		// Join Custom Group slug and Custom Field slug.
		return $this->group_slug . '.' . $this->field_username_slug;

	}

	/**
	 * Gets the Mattermost User password Field in Contact Meta.
	 *
	 * This is used when querying the CiviCRM API for values in the Custom Field.
	 *
	 * @since 1.0.0
	 *
	 * @return string The API code for the Mattermost User password Field.
	 */
	public function meta_field_password_get() {

		// Join Custom Group slug and Custom Field slug.
		return $this->group_slug . '.' . $this->field_password_slug;

	}

	/**
	 * Installs the Mattermost User data as Contact Meta.
	 *
	 * @since 1.0.0
	 */
	public function meta_install() {

		// Maybe create a "Mattermost" Custom Group.
		$custom_group = $this->plugin->civicrm->meta->custom_group_get_by_slug( $this->group_slug );
		if ( false !== $custom_group && empty( $custom_group ) ) {
			$title        = __( 'Mattermost User', 'wpcv-civicrm-mattermost' );
			$extends      = 'Individual';
			$custom_group = $this->plugin->civicrm->meta->custom_group_create( $title, $this->group_slug, $extends );
		}

		// Maybe create Custom Fields.
		if ( false !== $custom_group ) {

			// Cast Group ID as integer.
			$group_id = (int) $custom_group['id'];

			// Maybe create the "Mattermost User ID" Custom Field.
			$custom_field = $this->plugin->civicrm->meta->custom_field_get_by_slug( $this->field_id_slug );
			if ( false !== $custom_field && empty( $custom_field ) ) {
				$label        = __( 'User ID', 'wpcv-civicrm-mattermost' );
				$html_type    = 'Text';
				$read_only    = false;
				$custom_field = $this->plugin->civicrm->meta->custom_field_create( $group_id, $label, $this->field_id_slug, $html_type, $read_only );
			}

			// Maybe create the "Mattermost Username" Custom Field.
			$custom_field = $this->plugin->civicrm->meta->custom_field_get_by_slug( $this->field_username_slug );
			if ( false !== $custom_field && empty( $custom_field ) ) {
				$label        = __( 'Username', 'wpcv-civicrm-mattermost' );
				$html_type    = 'Text';
				$read_only    = false;
				$custom_field = $this->plugin->civicrm->meta->custom_field_create( $group_id, $label, $this->field_username_slug, $html_type, $read_only );
			}

			// Maybe create the "Mattermost Password" Custom Field.
			$custom_field = $this->plugin->civicrm->meta->custom_field_get_by_slug( $this->field_password_slug );
			if ( false !== $custom_field && empty( $custom_field ) ) {
				$label        = __( 'Password', 'wpcv-civicrm-mattermost' );
				$html_type    = 'Text';
				$read_only    = true;
				$custom_field = $this->plugin->civicrm->meta->custom_field_create( $group_id, $label, $this->field_password_slug, $html_type, $read_only );
			}

		}

		/**
		 * Fires when the Mattermost User data has been installed.
		 *
		 * @since 1.0.0
		 *
		 * @param array|bool $custom_group The Custom Group data, or false on failure to install.
		 */
		do_action( 'wpcvmm/mattermost/user/meta/installed', $custom_group );

	}

	/**
	 * Uninstalls the Mattermost User data from Contact Meta.
	 *
	 * @since 1.0.0
	 */
	public function meta_uninstall() {

		// TODO: Delete the "Mattermost" Custom Group.
		$result = $this->plugin->civicrm->meta->custom_group_delete( $this->slug );

		/**
		 * Fires when the Mattermost User data has been uninstalled.
		 *
		 * @since 1.0.0
		 *
		 * @param array|bool $custom_group The Custom Group data, or false on failure to uninstall.
		 */
		do_action( 'wpcvmm/mattermost/user/meta/uninstalled', $custom_group );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Adds the HTTP routes for our Forms.
	 *
	 * @since 1.0.0
	 *
	 * @param array $items The array of HTTP routes, keyed by relative path.
	 */
	public function action_menu_route_add( &$items ) {

		// Add Mattermost Create User route.
		$items['civicrm/mattermost/add'] = [
			'page_callback'    => 'CRM_Mattermost_Contact_Form_Add',
			'access_arguments' => [
				[
					'administer CiviCRM',
				],
				'and',
			],
		];

		// Add Mattermost Deactivate User route.
		$items['civicrm/mattermost/remove'] = [
			'page_callback'    => 'CRM_Mattermost_Contact_Form_Remove',
			'access_arguments' => [
				[
					'administer CiviCRM',
				],
				'and',
			],
		];

	}

	/**
	 * Adds a Menu Item to the "Actions" Menu on the CiviCRM Contact Summary screen.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $actions The array of actions from which the Menu is rendered.
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 */
	public function action_menu_item_add( &$actions, $contact_id ) {

		// Bail if there's no Contact ID.
		if ( empty( $contact_id ) ) {
			return;
		}

		// Bail if there's no sub-menu.
		if ( empty( $actions['otherActions'] ) ) {
			return;
		}

		// Check if the Contact already has a Mattermost User.
		$user_id = $this->plugin->civicrm->contact->user_id_get( $contact_id );

		// Choose Action.
		if ( empty( $user_id ) ) {

			// Build "Create Mattermost User" link.
			$actions['otherActions']['mattermost_contact'] = [
				'title'       => __( 'Create Mattermost User', 'wpcv-civicrm-mattermost' ),
				'weight'      => 40,
				'ref'         => 'mattermost-contact',
				'key'         => 'mattermost_contact',
				'class'       => 'crm-popup',
				'icon'        => 'crm-i fa-comments',
				'href'        => CRM_Utils_System::url( 'civicrm/mattermost/add', 'reset=1' ),
				'permissions' => [ 'administer CiviCRM' ],
			];

		} else {

			// Build "Deactivate Mattermost User" link.
			$actions['otherActions']['mattermost_contact'] = [
				'title'       => __( 'Deactivate Mattermost User', 'wpcv-civicrm-mattermost' ),
				'weight'      => 40,
				'ref'         => 'mattermost-contact',
				'key'         => 'mattermost_contact',
				'class'       => 'crm-popup small-popup',
				'icon'        => 'crm-i fa-comments',
				'href'        => CRM_Utils_System::url( 'civicrm/mattermost/remove', 'reset=1' ),
				'permissions' => [ 'administer CiviCRM' ],
			];

		}

	}

}
