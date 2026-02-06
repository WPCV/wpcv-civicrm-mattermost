<?php
/**
 * Mattermost class.
 *
 * Handles Mattermost-related functionality via a "Bot Account".
 *
 * @see https://developers.mattermost.com/integrate/reference/bot-accounts/
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Mattermost Class.
 *
 * A class that encapsulates functionality for interacting with Mattermost.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Mattermost {

	/**
	 * Plugin object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost
	 */
	public $plugin;

	/**
	 * Mattermost Team object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Team
	 */
	public $team;

	/**
	 * Mattermost User object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_User
	 */
	public $user;

	/**
	 * Mattermost Channel object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Channel
	 */
	public $channel;

	/**
	 * Mattermost API object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_Remote
	 */
	public $remote;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param object $plugin The plugin object.
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
		do_action( 'wpcvmm/mattermost/loaded' );

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
		include WPCVMM_PATH . 'includes/mattermost/class-mattermost-team.php';
		include WPCVMM_PATH . 'includes/mattermost/class-mattermost-channel.php';
		include WPCVMM_PATH . 'includes/mattermost/class-mattermost-user.php';
		include WPCVMM_PATH . 'includes/mattermost/remote/class-remote.php';

	}

	/**
	 * Sets up objects for this class.
	 *
	 * @since 1.0.0
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->team    = new WPCV_CiviCRM_Mattermost_Mattermost_Team( $this );
		$this->channel = new WPCV_CiviCRM_Mattermost_Mattermost_Channel( $this );
		$this->user    = new WPCV_CiviCRM_Mattermost_Mattermost_User( $this );
		$this->remote  = new WPCV_CiviCRM_Mattermost_Remote( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {}

	// -----------------------------------------------------------------------------------

	/**
	 * Syncs all Mattermost Channels to their corresponding CiviCRM Groups.
	 *
	 * @since 1.0.0
	 */
	public function sync_to_civicrm() {

		// Feedback at the beginning of the process.
		$this->plugin->log_message( '' );
		$this->plugin->log_message( '--------------------------------------------------------------------------------' );
		$this->plugin->log_message( __( 'Syncing all Mattermost Channels to their corresponding CiviCRM Groups...', 'wpcv-civicrm-mattermost' ) );
		$this->plugin->log_message( '--------------------------------------------------------------------------------' );

		// Get all Channel Members.
		$channel_members = $this->channel->members_in_synced_groups_get();
		if ( ! empty( $channel_members ) ) {

			// Save some queries by using a pseudo-cache.
			$correspondences = [
				'users'  => [],
				'groups' => [],
			];

			foreach ( $channel_members as $channel_member ) {

				// Cast as array.
				$channel_member = (array) $channel_member;

				// Get the CiviCRM Group ID for this Mattermost Channel ID.
				$channel_id = $channel_member['channel_id'];
				// Try the pseudo-cache first.
				if ( isset( $correspondences['groups'][ $channel_id ] ) ) {
					$civicrm_group_id = $correspondences['groups'][ $channel_id ];
				} else {
					// Check the database.
					$civicrm_group = $this->channel->group_get( $channel_id );
					if ( ! empty( $civicrm_group ) ) {
						$civicrm_group_id                         = (int) $civicrm_group['id'];
						$correspondences['groups'][ $channel_id ] = $civicrm_group_id;
						$this->plugin->log_message( '' );
						$this->plugin->log_message(
							/* translators: %s: The ID of the Mattermost Channel. */
							sprintf( __( 'Adding Users from Mattermost Channel (ID: %s)', 'wpcv-civicrm-mattermost' ), $channel_id )
						);
						$this->plugin->log_message(
							/* translators: 1: The name of the CiviCRM Group, 2: The ID of the CiviCRM Group. */
							sprintf( __( 'Syncing with CiviCRM Group: %1$s (ID: %2$d)', 'wpcv-civicrm-mattermost' ), $civicrm_group['title'], $civicrm_group_id )
						);
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

				// Skip if there is no Contact for this Mattermost User ID.
				if ( empty( $contact_id ) || false === $contact_id ) {
					$this->plugin->log_message(
						/* translators: %d: The ID of the Mattermost User. */
						sprintf( __( 'No CiviCRM Contact ID found for Mattermost User (ID: %s)', 'wpcv-civicrm-mattermost' ), $user_id )
					);
					continue;
				}

				// Skip if there is an existing active GroupContact entry.
				$exists = $this->plugin->civicrm->group->contact->get( $civicrm_group_id, $contact_id );
				if ( ! empty( $exists ) && 'Added' === $exists['status'] ) {
					continue;
				}

				// Create an active CiviCRM Group Contact.
				if ( empty( $exists ) ) {
					$success = $this->plugin->civicrm->group->contact->create( $civicrm_group_id, $contact_id );
				} else {
					$success = $this->plugin->civicrm->group->contact->activate( $exists['id'] );
				}

				// Feedback.
				if ( false !== $success ) {
					$this->plugin->log_message(
						/* translators: 1: The ID of the Contact, 2: The ID of the Mattermost User. */
						sprintf( __( 'Added Contact (Contact ID: %1$d) (User ID: %2$s)', 'wpcv-civicrm-mattermost' ), $contact_id, $user_id )
					);
				} else {
					$this->plugin->log_message(
						/* translators: 1: The ID of the Contact, 2: The ID of the Mattermost User. */
						sprintf( __( 'Failed to add Contact (Contact ID: %1$d) (User ID: %2$s)', 'wpcv-civicrm-mattermost' ), $contact_id, $user_id )
					);
				}

			}

		}

		// Get all the Group Contacts in the Synced Groups.
		$group_contacts = $this->plugin->civicrm->group->contact->group_contacts_get();
		if ( ( $group_contacts instanceof CRM_Core_Exception ) ) {
			$group_contacts = [];
		}

		// Get the Custom Fields that store the Mattermost Channel ID and User ID.
		$channel_field = $this->plugin->mattermost->channel->meta_field_id_get();
		$user_field    = $this->plugin->mattermost->user->meta_field_id_get();

		// Delete each Group Contact where the Mattermost User no longer exists in the Mattermost Channel.
		if ( ! empty( $group_contacts ) ) {
			$channel_ids = [];
			foreach ( $group_contacts as $group_contact ) {

				// Safely get User ID and Channel ID.
				$user_id    = ! empty( $group_contact[ 'contact.' . $user_field ] ) ? $group_contact[ 'contact.' . $user_field ] : false;
				$channel_id = ! empty( $group_contact[ 'group.' . $channel_field ] ) ? $group_contact[ 'group.' . $channel_field ] : false;
				if ( false === $user_id || false === $channel_id ) {
					continue;
				}

				// Show feedback each time Group changes.
				if ( ! in_array( $channel_id, $channel_ids, true ) ) {
					$this->plugin->log_message( '' );
					$this->plugin->log_message(
						/* translators: 1: The name of the Group, 2: The ID of the Group. */
						sprintf( __( 'Deleting Contacts not in Mattermost Channel (ID: %s)', 'wpcv-civicrm-mattermost' ), $channel_id )
					);
					$this->plugin->log_message(
						/* translators: %d: The ID of the Group. */
						sprintf( __( 'Syncing with CiviCRM Group: %1$s (ID: %2$d)', 'wpcv-civicrm-mattermost' ), $group_contact['group.title'], (int) $group_contact['group_id'] )
					);
					$channel_ids[] = $channel_id;
				}

				// Skip if the Channel Member exists or (for safety) an error is encountered.
				$exists = $this->channel->member_exists( $user_id, $channel_id );
				if ( false !== $exists ) {
					continue;
				}

				// Delete the Group Contact.
				$contact_id = (int) $group_contact['contact_id'];
				$success    = $this->plugin->civicrm->group->contact->delete( (int) $group_contact['group_id'], $contact_id );
				if ( false !== $success ) {
					$this->plugin->log_message(
						/* translators: 1: The ID of the Contact, 2: The ID of the User. */
						sprintf( __( 'Removed Contact (Contact ID: %1$d) (User ID: %2$s)', 'wpcv-civicrm-mattermost' ), $contact_id, $user_id )
					);
				} else {
					$this->plugin->log_message(
						/* translators: 1: The ID of the Contact, 2: The ID of the User. */
						sprintf( __( 'Failed to remove Contact (Contact ID: %1$d) (User ID: %2$s)', 'wpcv-civicrm-mattermost' ), $contact_id, $user_id )
					);
				}

			}
		}

		$this->plugin->log_message( '' );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Batch sync Mattermost Channels to CiviCRM Groups.
	 *
	 * @since 1.0.0
	 *
	 * @param string $identifier The batch identifier.
	 */
	public function batch_sync_to_civicrm( $identifier ) {

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
	 * Batch sync Mattermost Channel Members to CiviCRM Group Contacts.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_Admin_Batch $batch The batch object.
	 */
	public function batch_sync_one( $batch ) {

		$limit  = $batch->stepper->step_count_get();
		$offset = $batch->stepper->initialise();

		// Get the batch of Channel Members for this step.
		$members_batch = $this->channel->members_batch_get( $limit, $offset );

		// Save some queries by using a pseudo-cache.
		$correspondences = [
			'users'  => [],
			'groups' => [],
		];

		// Ensure each Channel Member has a CiviCRM Group Contact.
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
					$civicrm_group = $this->channel->group_get( $channel_id );
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

				// Get the CiviCRM Contact ID for this User ID.
				$user_id = $channel_member['user_id'];
				// Try the pseudo-cache first.
				if ( isset( $correspondences['users'][ $user_id ] ) ) {
					$contact_id = $correspondences['users'][ $user_id ];
				} else {
					// Check the database.
					$contact_id = $this->user->contact_id_get( $user_id );
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
				$exists = $this->plugin->civicrm->group->contact->get( $civicrm_group_id, $contact_id );
				if ( ! empty( $exists ) && 'Added' === $exists['status'] ) {
					continue;
				}

				// Create an active CiviCRM Group Contact.
				if ( empty( $exists ) ) {
					$success = $this->plugin->civicrm->group->contact->create( $civicrm_group_id, $contact_id );
				} else {
					$success = $this->plugin->civicrm->group->contact->activate( $exists['id'] );
				}

				// Feedback.
				if ( false !== $success ) {
					$this->plugin->log_message(
						sprintf(
							/* translators: 1: The ID of the User, 2: The ID of the Contact. */
							__( 'Added Mattermost User (ID: %1$s) (Contact ID: %2$d) to CiviCRM Group (Group ID: %3$d)', 'wpcv-civicrm-mattermost' ),
							$user_id,
							(int) $contact_id,
							(int) $civicrm_group_id,
						)
					);
				} else {
					$this->plugin->log_message(
						sprintf(
							/* translators: 1: The ID of the User, 2: The ID of the Contact. */
							__( 'Failed to add Mattermost User (ID: %1$s) (Contact ID: %2$d) to CiviCRM Group (Group ID: %3$d)', 'wpcv-civicrm-mattermost' ),
							$user_id,
							(int) $contact_id,
							(int) $civicrm_group_id,
						)
					);
				}

			}
		}

		// Get the next batch of Channel Members.
		$batch->stepper->next();
		$offset        = $batch->stepper->initialise();
		$members_batch = $this->channel->members_batch_get( $limit, $offset );

		// Move batching onwards.
		if ( empty( $members_batch ) ) {
			$batch->next();
		}

	}

	/**
	 * Batch delete Group Contacts where the User no longer exists in the Mattermost Channel.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_Admin_Batch $batch The batch object.
	 */
	public function batch_sync_two( $batch ) {

		$limit  = $batch->stepper->step_count_get();
		$offset = $batch->stepper->initialise();

		// Get the Custom Fields that store the Mattermost Channel ID and User ID.
		$channel_field = $this->plugin->mattermost->channel->meta_field_id_get();
		$user_field    = $this->plugin->mattermost->user->meta_field_id_get();

		// Get the batch of Group Contacts for this step.
		$civicrm_batch = $this->plugin->civicrm->group->contact->group_contacts_get( $limit, $offset );
		if ( ( $civicrm_batch instanceof CRM_Core_Exception ) ) {
			$civicrm_batch = [];
		}

		// Delete each Group Contact where the User no longer exists in the Mattermost Channel.
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

				// Delete the Group Contact.
				$contact_id = (int) $group_contact['contact_id'];
				$success    = $this->plugin->civicrm->group->contact->delete( (int) $group_contact['group_id'], $contact_id );
				if ( false !== $success ) {
					$this->plugin->log_message(
						sprintf(
							/* translators: 1: The ID of the User, 2: The ID of the Contact. */
							__( 'Removed Mattermost User (ID: %1$s) (Contact ID: %2$d) from CiviCRM Group (Group ID: %3$d)', 'wpcv-civicrm-mattermost' ),
							$user_id,
							$contact_id,
							(int) $group_contact['group_id']
						)
					);
				} else {
					$this->plugin->log_message(
						sprintf(
							/* translators: 1: The ID of the User, 2: The ID of the Contact. */
							__( 'Failed to remove Mattermost User (ID: %1$s) (Contact ID: %2$d)CiviCRM Group (Group ID: %3$d)', 'wpcv-civicrm-mattermost' ),
							$user_id,
							$contact_id,
							(int) $group_contact['group_id']
						)
					);
				}

			}
		}

		// Get the next batch of Group Contacts.
		$batch->stepper->next();
		$offset        = $batch->stepper->initialise();
		$civicrm_batch = $this->plugin->civicrm->group->contact->group_contacts_get( $limit, $offset );
		if ( ( $civicrm_batch instanceof CRM_Core_Exception ) ) {
			$civicrm_batch = [];
		}

		// Move batching onwards.
		if ( empty( $civicrm_batch ) ) {
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
