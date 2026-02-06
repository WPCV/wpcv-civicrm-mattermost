<?php
/**
 * CiviCRM class.
 *
 * Handles CiviCRM-related functionality.
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
class WPCV_CiviCRM_Mattermost_CiviCRM {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost
	 */
	public $plugin;

	/**
	 * Metadata object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_Meta
	 */
	public $meta;

	/**
	 * Contact object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_Contact
	 */
	public $contact;

	/**
	 * Group object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_Group
	 */
	public $group;

	/**
	 * CiviRules object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules
	 */
	public $civirules;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Add action for init.
		add_action( 'wpcvmm/loaded', [ $this, 'initialise' ] );

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
		do_action( 'wpcvmm/civicrm/loaded' );

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
		include WPCVMM_PATH . 'includes/civicrm/class-civicrm-meta.php';
		include WPCVMM_PATH . 'includes/civicrm/class-civicrm-contact.php';
		include WPCVMM_PATH . 'includes/civicrm/class-civicrm-group.php';
		include WPCVMM_PATH . 'includes/civicrm/civirules/class-civicrm-civirules.php';

	}

	/**
	 * Sets up objects for this class.
	 *
	 * @since 1.0.0
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->meta      = new WPCV_CiviCRM_Mattermost_CiviCRM_Meta( $this );
		$this->contact   = new WPCV_CiviCRM_Mattermost_CiviCRM_Contact( $this );
		$this->group     = new WPCV_CiviCRM_Mattermost_CiviCRM_Group( $this );
		$this->civirules = new WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		// Register PHP directory.
		add_action( 'civicrm_config', [ $this, 'directory_register_php' ] );

		// Register template directory.
		add_action( 'civicrm_config', [ $this, 'directory_register_template' ] );

	}

	/**
	 * Checks if CiviCRM is initialised.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if CiviCRM initialised, false otherwise.
	 */
	public function is_initialised() {

		// Bail if CiviCRM is not fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) || ! CIVICRM_INSTALLED ) {
			return false;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return false;
		}

		// Try and initialise CiviCRM.
		return civi_wp()->initialize();

	}

	/**
	 * Finds out if an Extension is installed and enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param string $extension The "name" of the CiviCRM Extension, e.g. 'org.civicoop.emailapi'.
	 * @return bool $active True if the Extension is active, false otherwise.
	 */
	public function is_extension_enabled( $extension = '' ) {

		// Init return.
		$active = false;

		// Get the Extensions array.
		$extensions = $this->extensions_get_enabled();

		// Override if Extension is active.
		if ( in_array( $extension, $extensions, true ) ) {
			$active = true;
		}

		// --<
		return $active;

	}

	/**
	 * Gets the Extensions that are enabled in CiviCRM.
	 *
	 * The return array contains the unique 'key' of each enabled Extension.
	 *
	 * @since 1.0.0
	 *
	 * @return array $enabled_extensions The array of enabled Extensions.
	 */
	public function extensions_get_enabled() {

		// Only do this once per page load.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$enabled_extensions = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $enabled_extensions;
		}

		// Define params to query for enabled Extensions.
		$params = [
			'version'     => 3,
			'sequential'  => 1,
			'status'      => 'installed',
			'statusLabel' => 'Enabled',
			'options'     => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Extension', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $enabled_extensions;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $enabled_extensions;
		}

		// Build return array.
		foreach ( $result['values'] as $key => $extension ) {
			$enabled_extensions[] = $extension['key'];
		}

		// Maybe populate to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $enabled_extensions;
		}

		// --<
		return $enabled_extensions;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Registers an additional directory that CiviCRM searches for PHP files.
	 *
	 * This only works with *new* PHP files. One cannot override existing PHP
	 * with this technique - instead, the file must be placed in the path:
	 * defined in `$config->customPHPPathDir`.
	 *
	 * @since 1.0.0
	 *
	 * @param CRM_Core_Config $config The CiviCRM config object.
	 */
	public function directory_register_php( &$config ) {

		// Define our custom path.
		$custom_path = WPCVMM_PATH . 'includes/civicrm/custom';

		// Add to include path.
		$include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
		set_include_path( $include_path );

	}

	/**
	 * Registers a directory that CiviCRM searches in for template files.
	 *
	 * @since 1.0.0
	 *
	 * @param CRM_Core_Config $config The CiviCRM config object.
	 */
	public function directory_register_template( &$config ) {

		// Get template instance.
		$template = CRM_Core_Smarty::singleton();

		// Define our custom path.
		$custom_path = WPCVMM_PATH . 'assets/templates/civicrm';

		// Add template directory.
		$template->addTemplateDir( $custom_path );

		// Register template directory.
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
		set_include_path( $template_include_path );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Encrypts a string using CiviCRM's crypto method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plaintext The plaintext string.
	 * @return string|bool $encrypted The encrypted string, or false on failure.
	 */
	public function string_encrypt( $plaintext ) {

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		$encrypted = \Civi::service( 'crypto.token' )->encrypt( $plaintext, 'CRED' );

		// --<
		return $encrypted;

	}

	/**
	 * Tries to decrypt a string using CiviCRM's crypto method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $encrypted The encrypted string.
	 * @return string|bool $decrypted The decrypted string, or false on failure.
	 */
	public function string_decrypt( $encrypted ) {

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		try {

			$decrypted = \Civi::service( 'crypto.token' )->decrypt( $encrypted, 'CRED' );

		} catch ( \Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'message'   => __( 'Unable to retrieve the encrypted string. Please check your configured encryption keys.', 'wpcv-civicrm-mattermost' ),
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			$decrypted = false;
		}

		// --<
		return $decrypted;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Syncs all CiviCRM Groups to their corresponding Mattermost Channels.
	 *
	 * @since 1.0.0
	 */
	public function sync_to_mattermost() {

		// Feedback at the beginning of the process.
		$this->plugin->log_message( '' );
		$this->plugin->log_message( '--------------------------------------------------------------------------------' );
		$this->plugin->log_message( __( 'Syncing all CiviCRM Groups to their corresponding Mattermost Channels...', 'wpcv-civicrm-mattermost' ) );
		$this->plugin->log_message( '--------------------------------------------------------------------------------' );

		// Get the Custom Field that stores the Mattermost Channel ID.
		$custom_field = $this->plugin->mattermost->channel->meta_field_id_get();

		// Get all synced CiviCRM Groups.
		$groups = $this->plugin->civicrm->group->synced_get();

		// Trap errors.
		if ( $groups instanceof CRM_Core_Exception ) {
			$data = [
				'method'    => __METHOD__,
				'message'   => $groups->getMessage(),
				'backtrace' => $groups->getTraceAsString(),
			];
			$this->plugin->log_error( $data );
			return;
		}

		foreach ( $groups as $group ) {
			$this->plugin->log_message( '' );
			$this->plugin->log_message(
				/* translators: 1: The name of the Group, 2: The ID of the Group. */
				sprintf( __( 'Syncing CiviCRM Group %1$s (ID: %2$d)', 'wpcv-civicrm-mattermost' ), $group['title'], (int) $group['id'] )
			);
			$this->group_sync_to_mattermost( $group, $custom_field );
		}

		$this->plugin->log_message( '' );

	}

	/**
	 * Syncs a given CiviCRM Group to a Mattermost Channel.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $group The array of CiviCRM Group data.
	 * @param string $custom_field The Custom Field that stores the Mattermost Channel ID.
	 */
	public function group_sync_to_mattermost( $group, $custom_field ) {

		// Avoid nonsense requests.
		if ( empty( $group['id'] ) ) {
			return;
		}

		// Bail if there is no Mattermost Channel ID.
		$channel_id = ! empty( $group[ $custom_field ] ) ? $group[ $custom_field ] : false;
		if ( empty( $channel_id ) ) {
			return;
		}

		$this->plugin->log_message(
			/* translators: %d: The ID of the Group. */
			sprintf( __( 'Syncing with Mattermost Channel (ID: %d)', 'wpcv-civicrm-mattermost' ), (int) $channel_id )
		);

		// Get the list of Contact IDs in the Group.
		$civicrm_group_contact_ids = $this->group->contact->group_contact_ids_get( (int) $group['id'] );
		if ( $civicrm_group_contact_ids instanceof CRM_Core_Exception ) {
			$this->plugin->log_message(
				/* translators: %s: The error message. */
				sprintf( __( 'Could not fetch Contact IDs: %s', 'wpcv-civicrm-mattermost' ), $civicrm_group_contact_ids->getMessage() )
			);
			return;
		}

		// Build the args to retrieve all members - review if this is not enough.
		$args = [
			'per_page' => 10000,
		];

		// Get all Members in the Mattermost Channel.
		$members = $this->plugin->mattermost->channel->members_get_by_channel_id( $channel_id, $args );

		// Extract the User IDs.
		$user_ids = [];
		foreach ( $members as $member ) {
			$user_ids[] = $member->user_id;
		}

		// Set a feedback flag.
		$did_sync = false;

		// Add Contacts to the Group if they are missing.
		if ( ! empty( $civicrm_group_contact_ids ) ) {

			// Get the Users to add to the Mattermost Channel.
			$data = $this->plugin->mattermost->channel->user_ids_to_add( $civicrm_group_contact_ids, $channel_id );
			if ( ! empty( $data['has-user-id'] ) ) {

				$this->plugin->log_message( __( 'Adding Contacts from CiviCRM Group...', 'wpcv-civicrm-mattermost' ) );

				$feedback = $this->plugin->mattermost->channel->user_ids_add( $data['has-user-id'], $channel_id );

				if ( ! empty( $feedback['added'] ) ) {
					foreach ( $feedback['added'] as $contact_id => $user_id ) {
						$this->plugin->log_message(
							/* translators: 1: The ID of the Contact, 2: The ID of the User. */
							sprintf( __( 'Added Mattermost User (ID: %1$s) (Contact ID: %2$d)', 'wpcv-civicrm-mattermost' ), $user_id, (int) $contact_id )
						);
					}
				}
				if ( ! empty( $feedback['failed'] ) ) {
					foreach ( $feedback['failed'] as $contact_id => $user_id ) {
						$this->plugin->log_message(
							/* translators: 1: The ID of the Contact, 2: The ID of the User. */
							sprintf( __( 'Failed to add Mattermost User (ID: %1$s) (Contact ID: %2$d)', 'wpcv-civicrm-mattermost' ), $user_id, (int) $contact_id )
						);
					}
				}
				if ( ! empty( $data['no-user-id'] ) ) {
					foreach ( $data['no-user-id'] as $contact_id ) {
						$this->plugin->log_message(
							/* translators: %d: The ID of the Contact. */
							sprintf( __( 'No Mattermost User ID found for Contact (ID: %d)', 'wpcv-civicrm-mattermost' ), (int) $contact_id )
						);
					}
				}

				$did_sync = true;

			}

		}

		/*
		 * Delete any Users from the Mattermost Channel that are not in the CiviCRM Group.
		 *
		 * To allow the deletion of an entry after a User has been deleted, we don't
		 * check if the User exists.
		 */
		if ( ! empty( $user_ids ) ) {

			// Get all Contact IDs in the Group for the set of Mattermost User IDs.
			$mm_group_contact_ids = $this->group->contact->group_contact_ids_for_user_ids_get( $user_ids );

			// Get all Contact IDs to remove from the Mattermost Channel.
			$contact_ids_to_remove = array_diff( $mm_group_contact_ids, $civicrm_group_contact_ids );
			if ( ! empty( $contact_ids_to_remove ) ) {

				$this->plugin->log_message( __( 'Removing Mattermost Users with no Contact in CiviCRM Group...', 'wpcv-civicrm-mattermost' ) );

				// Process Contact IDs.
				foreach ( $contact_ids_to_remove as $contact_id ) {

					// Skip if no Contact ID.
					if ( empty( $contact_id ) ) {
						continue;
					}

					// Find the corresponding User ID.
					$user_id = $this->contact->user_id_get( $contact_id );
					if ( false === $user_id ) {
						continue;
					}

					// Remove User from Mattermost Channel.
					$success = $this->plugin->mattermost->channel->member_delete( $channel_id, $user_id );
					if ( true === $success ) {
						$this->plugin->log_message(
							/* translators: 1: The ID of the Contact, 2: The ID of the User. */
							sprintf( __( 'Removed Mattermost User (ID: %1$s) (Contact ID: %2$d)', 'wpcv-civicrm-mattermost' ), $user_id, (int) $contact_id )
						);
					} else {
						$this->plugin->log_message(
							/* translators: 1: The ID of the Contact, 2: The ID of the User. */
							sprintf( __( 'Failed to remove Mattermost User (ID: %1$s) (Contact ID: %2$d)', 'wpcv-civicrm-mattermost' ), $user_id, (int) $contact_id )
						);
					}

				}

				$did_sync = true;

			}

		}

		// Show feedback when no sync has taken place.
		if ( false === $did_sync ) {
			$this->plugin->log_message( __( 'Groups are already in sync.', 'wpcv-civicrm-mattermost' ) );
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Batch sync CiviCRM Groups to Mattermost Channels.
	 *
	 * @since 1.0.0
	 *
	 * @param string $identifier The batch identifier.
	 */
	public function batch_sync_to_mattermost( $identifier ) {

		// Get the current Batch.
		$batch        = new WPCV_CiviCRM_Mattermost_Admin_Batch( $identifier );
		$batch_number = $batch->initialise();

		// Set batch count for schedules.
		if ( false !== strpos( $identifier, 'wpcvmm_cron' ) ) {
			$batch_count = (int) $this->plugin->admin->setting_get( 'batch_count' );
			$batch->stepper->step_count_set( $batch_count );
		}

		// Call the Batches in order.
		switch ( $batch_number ) {
			case 0:
				$this->batch_sync_one( $batch );
				break;
			case 1:
				$this->batch_sync_two( $batch );
				break;
			case 2:
				$this->batch_sync_three( $batch );
				break;
		}

	}

	/**
	 * Batch sync CiviCRM Group Contacts to Mattermost Channel Users.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_Admin_Batch $batch The batch object.
	 */
	public function batch_sync_one( $batch ) {

		$limit  = $batch->stepper->step_count_get();
		$offset = $batch->stepper->initialise();

		// Get the Custom Fields that store the Mattermost Channel ID and User ID.
		$channel_field = $this->plugin->mattermost->channel->meta_field_id_get();
		$user_field    = $this->plugin->mattermost->user->meta_field_id_get();

		// Get the batch of Group Contacts for this step.
		$civicrm_batch = $this->group->contact->group_contacts_get( $limit, $offset );
		if ( ( $civicrm_batch instanceof CRM_Core_Exception ) ) {
			$civicrm_batch = [];
		}

		// Ensure each Group Contact exists in the Mattermost Channel.
		if ( ! empty( $civicrm_batch ) ) {
			foreach ( $civicrm_batch as $group_contact ) {

				// Safely get User ID and Channel ID.
				$user_id    = ! empty( $group_contact[ 'contact.' . $user_field ] ) ? $group_contact[ 'contact.' . $user_field ] : false;
				$channel_id = ! empty( $group_contact[ 'group.' . $channel_field ] ) ? $group_contact[ 'group.' . $channel_field ] : false;
				if ( false === $user_id || false === $channel_id ) {
					continue;
				}

				// Skip if the Channel Member exists or (for safety) an error is encountered.
				$exists = $this->plugin->mattermost->channel->member_exists( $user_id, $channel_id );
				if ( false !== $exists ) {
					continue;
				}

				// Finally add the Mattermost Channel membership.
				$contact_id = (int) $group_contact['contact_id'];
				$success    = $this->plugin->mattermost->channel->member_create( $channel_id, $user_id );
				if ( false !== $success ) {
					$this->plugin->log_message(
						sprintf(
							/* translators: 1: The ID of the Contact, 2: The ID of the User, 3: The ID of the Mattermost Channel. */
							__( 'Added CiviCRM Contact (ID: %1$d) (User ID: %2$s) to Mattermost Channel (ID: %3$s)', 'wpcv-civicrm-mattermost' ),
							$contact_id,
							$user_id,
							$channel_id
						)
					);
				} else {
					$this->plugin->log_message(
						sprintf(
							/* translators: 1: The ID of the Contact, 2: The ID of the User, 3: The ID of the Mattermost Channel. */
							__( 'Failed to add CiviCRM Contact (ID: %1$d) (User ID: %2$s) to Mattermost Channel (ID: %3$s)', 'wpcv-civicrm-mattermost' ),
							$contact_id,
							$user_id,
							$channel_id
						)
					);
				}

			}
		}

		// Get the next batch of Group Contacts.
		$batch->stepper->next();
		$offset        = $batch->stepper->initialise();
		$civicrm_batch = $this->group->contact->group_contacts_get( $limit, $offset );
		if ( ( $civicrm_batch instanceof CRM_Core_Exception ) ) {
			$civicrm_batch = [];
		}

		// Move batching onwards.
		if ( empty( $civicrm_batch ) ) {
			$batch->next();
		}

	}

	/**
	 * Batch delete Mattermost Channel Members where the Contact no longer exists in the CiviCRM Group.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_Admin_Batch $batch The batch object.
	 */
	public function batch_sync_two( $batch ) {

		$limit  = $batch->stepper->step_count_get();
		$offset = $batch->stepper->initialise();

		// Get the batch of Mattermost Channel Members for this step.
		$members_batch = $this->plugin->mattermost->channel->members_batch_get( $limit, $offset );

		// Save some queries by using a pseudo-cache.
		$correspondences = [
			'users'  => [],
			'groups' => [],
		];

		// Delete each Mattermost Channel Member where the Contact no longer exists in the CiviCRM Group.
		if ( ! empty( $members_batch ) ) {
			foreach ( $members_batch as $channel_member ) {

				// Cast as array.
				$channel_member = (array) $channel_member;

				// Get the CiviCRM Group ID for this Mattermost Channel ID.
				$channel_id = $channel_member['channel_id'];
				// Try the pseudo-cache first.
				if ( isset( $correspondences['groups'][ $channel_id ] ) ) {
					$civicrm_group_id = $correspondences['groups'][ $channel_id ];
				} else {
					// Check the database.
					$civicrm_group = $this->plugin->mattermost->channel->group_get( $channel_id );
					if ( ! empty( $civicrm_group ) ) {
						$civicrm_group_id                         = (int) $civicrm_group['id'];
						$correspondences['groups'][ $channel_id ] = $civicrm_group_id;
					} else {
						$correspondences['groups'][ $channel_id ] = false;
					}
				}

				// Skip if there is no CiviCRM Group for this Mattermost Channel ID.
				if ( empty( $civicrm_group_id ) ) {
					continue;
				}

				// Get the CiviCRM Contact ID for this Mattermost User ID.
				$user_id = $channel_member['user_id'];
				// Try the pseudo-cache first.
				if ( isset( $correspondences['users'][ $user_id ] ) ) {
					$contact_id = $correspondences['users'][ $user_id ];
				} else {
					// Check the database.
					$contact_id = $this->plugin->mattermost->user->contact_id_get( $user_id );
					if ( ! empty( $contact_id ) ) {
						$correspondences['users'][ $user_id ] = (int) $contact_id;
					} else {
						$correspondences['users'][ $user_id ] = false;
					}
				}

				// Skip if there is no Contact for this User ID.
				if ( empty( $contact_id ) || false === $contact_id ) {
					continue;
				}

				// Skip if there is an existing active GroupContact entry.
				$exists = $this->group->contact->get( $civicrm_group_id, $contact_id );
				if ( ! empty( $exists ) && 'Added' === $exists['status'] ) {
					continue;
				}

				// Finally delete the Mattermost Channel membership.
				$success = $this->plugin->mattermost->channel->member_delete( $channel_id, $user_id );
				if ( ! empty( $success ) ) {
					$this->plugin->log_message(
						sprintf(
							/* translators: 1: The ID of the Contact, 2: The ID of the User, 3: The ID of the Mattermost Channel. */
							__( 'Removed Contact (ID: %1$d) (User ID: %2$s) from Mattermost Channel (ID: %3$s)', 'wpcv-civicrm-mattermost' ),
							$contact_id,
							$user_id,
							$channel_id
						)
					);
				} else {
					$this->plugin->log_message(
						sprintf(
							/* translators: 1: The ID of the Contact, 2: The ID of the User, 3: The ID of the Mattermost Channel. */
							__( 'Failed to remove Contact (ID: %1$d) (User ID: %2$s) from Mattermost Channel (ID: %3$s)', 'wpcv-civicrm-mattermost' ),
							$contact_id,
							$user_id,
							$channel_id
						)
					);
				}

			}
		}

		// Get the next batch of Mattermost Channel Members.
		$batch->stepper->next();
		$offset        = $batch->stepper->initialise();
		$members_batch = $this->plugin->mattermost->channel->members_batch_get( $limit, $offset );

		// Move batching onwards.
		if ( empty( $members_batch ) ) {
			$batch->next();
		}

	}

	/**
	 * Batch done.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_Admin_Batch $batch The batch object.
	 */
	public function batch_sync_three( $batch ) {

		// We're finished.
		$batch->delete();
		unset( $batch );

	}

}
