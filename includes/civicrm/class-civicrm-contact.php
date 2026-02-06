<?php
/**
 * CiviCRM Contact class.
 *
 * Handles CiviCRM Contact-related functionality.
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
class WPCV_CiviCRM_Mattermost_CiviCRM_Contact {

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
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		/**
		 * Broadcast that this object is loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpcvmm/civicrm/contact/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0.0
	 */
	public function include_files() {}

	/**
	 * Sets up objects for this class.
	 *
	 * @since 1.0.0
	 */
	public function setup_objects() {}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		// Rotate the encryption of Mattermost User passwords.
		add_action( 'civicrm_cryptoRotateKey', [ $this, 'user_passwords_rotate' ], 10, 2 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Contact data for a given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array|bool $contact An array of Contact data, or false on failure.
	 */
	public function get_by_id( $contact_id ) {

		// Init return.
		$contact = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::get( false )
				->addSelect( '*' )
				->addWhere( 'id', '=', $contact_id )
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
	 * Gets the Primary Email for a given CiviCRM Contact ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array|bool $email An array of Email data, or false on failure.
	 */
	public function email_primary_get( $contact_id ) {

		// Init return.
		$email = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		try {

			// Call the API.
			$result = \Civi\Api4\Email::get( false )
				->addSelect( '*' )
				->addWhere( 'contact_id', '=', $contact_id )
				->addWhere( 'is_primary', '=', true )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $email;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $email;
		}

		// We want the first result.
		$email = $result->first();

		// --<
		return $email;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Creates a Mattermost User for a given CiviCRM Contact.
	 *
	 * @since 1.0.0
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @return stdClass|bool $user The Mattermost User object, or false on failure.
	 */
	public function user_create( $contact ) {

		// Bail if required Fields are not populated.
		if ( empty( $contact['id'] ) || empty( $contact['display_name'] ) ) {
			return false;
		}

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Bail if the Contact already has a Mattermost User.
		$user_id = $this->user_id_get( (int) $contact['id'] );
		if ( ! empty( $user_id ) ) {
			$user    = $this->plugin->mattermost->user->get_by_id( $user_id );
			$success = $this->user_restore( $contact, $user, false );
			return $user;
		}

		// Get the Primary Email for the Contact.
		$email = $this->email_primary_get( (int) $contact['id'] );
		if ( false === $email ) {
			return false;
		}

		// Bail if required Fields are not populated.
		if ( empty( $email['email'] ) ) {
			return false;
		}

		// Bail if there is an existing Mattermost User with this Contact's email.
		$email_exists = $this->plugin->mattermost->user->email_exists( $email['email'] );
		if ( false !== $email_exists ) {
			$user    = $this->plugin->mattermost->user->get_by_email( $email['email'] );
			$success = $this->user_restore( $contact, $user, true );
			return $user;
		}

		// Safely get First Name and Last Name.
		$first_name = ! empty( $contact['first_name'] ) ? $contact['first_name'] : '';
		$last_name  = ! empty( $contact['last_name'] ) ? $contact['last_name'] : '';

		// Use display name as a fallback.
		if ( empty( $first_name ) && empty( $last_name ) ) {
			$username = sanitize_title( sanitize_user( $contact['display_name'] ) );
		} else {
			if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
				$username = sanitize_title( sanitize_user( $first_name . '-' . $last_name ) );
			} else {
				if ( ! empty( $first_name ) ) {
					$username = sanitize_title( sanitize_user( $first_name ) );
				} else {
					$username = sanitize_title( sanitize_user( $last_name ) );
				}
			}
		}

		/**
		 * Filters the Mattermost username.
		 *
		 * @since 1.0.0
		 *
		 * @param string $username The generated username.
		 * @param array  $civi_contact The CiviCRM Contact data.
		 */
		$username = apply_filters( 'wpcvmm/civicrm/contact/user/new_username', $username, $civi_contact );

		// Bail if we can't generate a unique URL-friendly username.
		$username = $this->plugin->mattermost->user->username_unique_get( $username, $contact );
		if ( false === $username ) {
			return false;
		}

		// Generate a random password.
		$password = wp_generate_password();

		// Go ahead and create the Mattermost User.
		$user = $this->plugin->mattermost->user->create( $username, $email['email'], $password, $first_name, $last_name );
		if ( empty( $user ) ) {
			return false;
		}

		// Store Mattermost User data in Contact Meta.
		$this->user_id_set( (int) $contact['id'], $user->id );
		$this->user_username_set( (int) $contact['id'], $user->username );
		// Encrypt the password before saving.
		$encrypted = $this->civicrm->string_encrypt( $password );
		$this->user_password_set( (int) $contact['id'], $encrypted );

		// --<
		return $user;

	}

	/**
	 * Restores the Mattermost User data for a given CiviCRM Contact.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $contact The CiviCRM Contact data.
	 * @param stdClass $user The Mattermost User object.
	 * @param bool     $meta True restores the User meta data. False by default.
	 * @return string|bool $success The string "ok" on success, or false on failure.
	 */
	public function user_restore( $contact, $user, $meta = false ) {

		// Maybe reactivate User.
		if ( ! empty( $user->delete_at ) ) {
			$success = $this->plugin->mattermost->user->activate( $user->id );
		}

		// Bail if not restoring User meta.
		if ( false === $meta ) {
			if ( ! empty( $success ) ) {
				return $success;
			} else {
				return false;
			}
		}

		// Store Mattermost User data in Contact Meta.
		$this->user_id_set( (int) $contact['id'], $user->id );
		$this->user_username_set( (int) $contact['id'], $user->username );

		// Generate a random password.
		$password = wp_generate_password();

		// Update the MatterMost User's password.
		$success = $this->plugin->mattermost->user->password_update( $user->id, $password );
		if ( false === $success ) {
			return $success;
		}

		// Encrypt the password before saving.
		$encrypted = $this->civicrm->string_encrypt( $password );
		$this->user_password_set( (int) $contact['id'], $encrypted );

		// --<
		return $success;

	}

	/**
	 * Deletes the Mattermost User for a given CiviCRM Contact ID.
	 *
	 * Default behaviour is to deactivate the User and revoke all its sessions by
	 * archiving its User object. The `$permanent` param can be used if the server
	 * has been configured to allow hard-deletion of Users.
	 *
	 * @see WPCV_CiviCRM_Mattermost_Mattermost_User::delete()
	 * @see WPCV_CiviCRM_Mattermost_Remote::user_delete()
	 * @see https://api.mattermost.com/#tag/users/operation/DeleteUser

	 * @since 1.0.0
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @return string|bool $success The string "ok" on success, or false on failure.
	 */
	public function user_delete( $contact_id ) {

		// Init return.
		$success = false;

		// Bail if required Field is not populated.
		if ( empty( $contact_id ) ) {
			return $success;
		}

		// Bail if the Contact has no Mattermost User.
		$user_id = $this->user_id_get( (int) $contact_id );
		if ( empty( $user_id ) ) {
			return $success;
		}

		// Go ahead and "delete" the Mattermost User.
		$success = $this->plugin->mattermost->user->delete( $user_id );
		if ( false === $success ) {
			return $success;
		}

		/**
		 * Filters whether or not the Contact's Mattermost User data should be deleted.
		 *
		 * Default is false because Mattermost only deactivates Users by default.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $delete Default false, return true to delete Mattermost User data.
		 * @param integer $contact_id The CiviCRM Contact ID.
		 * @param string $user_id The Mattermost User ID.
		 */
		$delete = apply_filters( 'wpcvmm/civicrm/contact/user/delete_meta', false, $contact_id, $user_id );
		if ( false === $delete ) {
			return $success;
		}

		// Delete the Mattermost User data from Contact Meta.
		$this->user_id_set( (int) $contact_id, '' );
		$this->user_username_set( (int) $contact_id, '' );
		$this->user_password_set( (int) $contact_id, '' );

		// --<
		return $success;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the Mattermost User ID for a given CiviCRM Contact ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @return string|bool $user_id The Mattermost User ID, or false on failure.
	 */
	public function user_id_get( $contact_id ) {

		// Init return.
		$user_id = false;

		// Bail if no Contact ID.
		if ( empty( $contact_id ) ) {
			return $user_id;
		}

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $user_id;
		}

		// Get the Custom Field that stores the Mattermost User ID.
		$custom_field = $this->plugin->mattermost->user->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::get( false )
				->addSelect( $custom_field )
				->addWhere( 'id', '=', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $user_id;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $user_id;
		}

		// We want the first result.
		$contact = $result->first();

		// We only want the Custom Field in the first result.
		$user_id = $contact[ $custom_field ];

		// Let's always return an empty result as false.
		if ( empty( $user_id ) ) {
			return false;
		}

		// --<
		return $user_id;

	}

	/**
	 * Saves the Mattermost User ID for a CiviCRM Contact ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @param string  $user_id The Mattermost User ID.
	 * @return bool $success The Mattermost User ID, or false on failure.
	 */
	public function user_id_set( $contact_id, $user_id ) {

		// Init return.
		$success = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Get the Custom Field that stores the Mattermost User ID.
		$custom_field = $this->plugin->mattermost->user->meta_field_id_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::update( false )
				->addValue( $custom_field, $user_id )
				->addWhere( 'id', '=', $contact_id )
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
	 * Gets the Mattermost Username for a given CiviCRM Contact ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @return string|bool $username The Mattermost Username, or false on failure.
	 */
	public function user_username_get( $contact_id ) {

		// Init return.
		$username = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $username;
		}

		// Get the Custom Field that stores the Mattermost Username.
		$custom_field = $this->plugin->mattermost->user->meta_field_username_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::get( false )
				->addSelect( $custom_field )
				->addWhere( 'id', '=', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $username;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $username;
		}

		// We want the first result.
		$contact = $result->first();

		// We only want the Custom Field in the first result.
		$username = $contact[ $custom_field ];

		// Let's always return an empty result as false.
		if ( empty( $username ) ) {
			return false;
		}

		// --<
		return $username;

	}

	/**
	 * Saves the Mattermost Username for a CiviCRM Contact ID.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @param string  $username The Mattermost Username.
	 * @return bool $success The Mattermost Username, or false on failure.
	 */
	public function user_username_set( $contact_id, $username ) {

		// Init return.
		$success = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Get the Custom Field that stores the Mattermost Username.
		$custom_field = $this->plugin->mattermost->user->meta_field_username_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::update( false )
				->addValue( $custom_field, $username )
				->addWhere( 'id', '=', $contact_id )
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
	 * Gets the encrypted Mattermost User password for a given CiviCRM Contact ID.
	 *
	 * In order to decrypt the password, use something like:
	 *
	 * @code
	 * try {
	 *   $password = \Civi::service( 'crypto.token' )->decrypt( $encrypted, 'CRED' );
	 * } catch ( \Exception $e ) {
	 *   $log = [
	 *     'method'    => __METHOD__,
	 *     'message'   => __( 'Unable to retrieve the encrypted password. Please check your configured encryption keys.', 'wpcv-civicrm-mattermost' ),
	 *     'error'     => $e->getMessage(),
	 *     'backtrace' => $e->getTraceAsString(),
	 *   ];
	 *   $this->plugin->log_error( $log );
	 * }
	 * @endCode
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @return string|bool $password The encrypted Mattermost User password, or false on failure.
	 */
	public function user_password_get( $contact_id ) {

		// Init return.
		$password = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $password;
		}

		// Get the Custom Field that stores the Mattermost User passwords.
		$custom_field = $this->plugin->mattermost->user->meta_field_password_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::get( false )
				->addSelect( $custom_field )
				->addWhere( 'id', '=', $contact_id )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $password;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $password;
		}

		// We want the first result.
		$contact = $result->first();

		// We only want the Custom Field in the first result.
		$password = $contact[ $custom_field ];

		// Let's always return an empty result as false.
		if ( empty( $password ) ) {
			return false;
		}

		// --<
		return $password;

	}

	/**
	 * Saves an encrypted Mattermost User password for a CiviCRM Contact ID.
	 *
	 * In order to encrypt the password, use something like:
	 *
	 * @code
	 * $encrypted = \Civi::service( 'crypto.token' )->encrypt( $password, 'CRED' );
	 * @endCode
	 *
	 * @since 1.0.0
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @param string  $password The encrypted Mattermost Username.
	 * @return bool $success True on success or false on failure.
	 */
	public function user_password_set( $contact_id, $password ) {

		// Init return.
		$success = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Get the Custom Field that stores the Mattermost Username.
		$custom_field = $this->plugin->mattermost->user->meta_field_password_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Contact::update( false )
				->addValue( $custom_field, $password )
				->addWhere( 'id', '=', $contact_id )
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
	 * Rotates saved Mattermost User passwords with the new crypto-key.
	 *
	 * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_cryptoRotateKey/
	 *
	 * @since 1.0.0
	 *
	 * @param string                   $tag The type of crypto-key that is currently being rotated.
	 * @param \Psr\Log\LoggerInterface $log List of messages about re-keyed values.
	 */
	public function user_passwords_rotate( $tag, $log ) {

		// Bail if not the tag we're using.
		if ( 'CRED' !== $tag ) {
			return;
		}

		// Get the Contacts that need updating.
		$contacts = $this->ids_with_passwords_get();
		if ( empty( $contacts ) ) {
			return;
		}

		// Resave passwords.
		$crypto_token = \Civi::service( 'crypto.token' );
		foreach ( $contacts as $contact_id => $password ) {
			$new = $crypto_token->rekey( $password, 'CRED' );
			if ( null !== $new ) {
				$success = $this->user_password_set( $contact_id, $new );
				if ( false !== $success ) {
					$log->info( 'Updated Mattermost password for Contact #{id}', [ 'id' => $contact_id ] );
				} else {
					$log->info( 'Failed to update Mattermost password for Contact #{id}', [ 'id' => $contact_id ] );
				}
			}
		}

	}

	/**
	 * Gets the CiviCRM Contacts that have a Mattermost User passwords.
	 *
	 * @since 1.0.0
	 *
	 * @return array|bool $contacts The array of CiviCRM Contacts, or false on failure.
	 */
	private function ids_with_passwords_get() {

		// Init return.
		$contacts = false;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contacts;
		}

		// Get the Custom Field that stores the Mattermost User password.
		$custom_field = $this->plugin->mattermost->user->meta_field_password_get();

		try {

			// Call the API.
			$result = \Civi\Api4\Group::get( false )
				->addSelect( 'id', $custom_field )
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
			return $contacts;
		}

		// Bail if there are none.
		if ( 0 === $result->count() ) {
			return [];
		}

		// Let's build a simpler return array.
		$contacts = [];
		foreach ( $result as $item ) {
			$contacts[ $item['id'] ] = $item[ $custom_field ];
		}

		// --<
		return $contacts;

	}

}
