<?php
/**
 * Job command class.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Run CiviCRM Groups Sync cron jobs.
 *
 * ## EXAMPLES
 *
 *     $ wp wpcvmm job sync_to_mm
 *     Success: Executed 'sync_to_mm' job.
 *
 * @since 1.0.0
 *
 * @package WPCV_CiviCRM_Mattermost
 */
class WPCV_CiviCRM_Mattermost_CLI_Command_Job extends WPCV_CiviCRM_Mattermost_CLI_Command {

	/**
	 * Sync CiviCRM Group Contacts to Mattermost Channels.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wpcvmm job sync_to_mm
	 *     Success: Executed 'sync_to_mm' job.
	 *
	 * @alias sync-to-mm
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function sync_to_mm( $args, $assoc_args ) {

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		$plugin = wpcv_civicrm_mattermost();

		// Get the Custom Field that stores the Mattermost Channel ID.
		$custom_field = $plugin->mattermost->channel->meta_field_id_get();

		// Get all synced CiviCRM Groups.
		$groups = $plugin->civicrm->group->synced_get();
		if ( $groups instanceof CRM_Core_Exception ) {
			WP_CLI::error( sprintf( 'Could not fetch CiviCRM Groups: %s.', $groups->getMessage() ) );
		}

		foreach ( $groups as $group ) {
			WP_CLI::log( '' );
			WP_CLI::log( sprintf( WP_CLI::colorize( '%gSyncing CiviCRM Group%n %Y%s%n %y(ID: %d)%n' ), $group['title'], (int) $group['id'] ) );
			$this->group_sync_to_mm( $group, $custom_field );
		}

		WP_CLI::log( '' );
		WP_CLI::success( "Executed 'sync_to_mm' job." );

	}

	/**
	 * Syncs a given CiviCRM Group to a Mattermost Channel.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $group The array of CiviCRM Group data.
	 * @param string $custom_field The Custom Field that stores the Mattermost Channel ID.
	 */
	private function group_sync_to_mm( $group, $custom_field ) {

		// Avoid nonsense requests.
		if ( empty( $group['id'] ) ) {
			return;
		}

		$plugin = wpcv_civicrm_mattermost();

		// Bail if there is no Mattermost Channel ID.
		$channel_id = ! empty( $group[ $custom_field ] ) ? $group[ $custom_field ] : false;
		if ( empty( $channel_id ) ) {
			return;
		}

		WP_CLI::log( sprintf( WP_CLI::colorize( '%gSyncing with Mattermost Channel%n %y(ID: %s)%n' ), $channel_id ) );

		// Get the list of all Contact IDs in the Group.
		$civicrm_group_contact_ids = $plugin->civicrm->group->contact->group_contact_ids_get( (int) $group['id'] );
		if ( $civicrm_group_contact_ids instanceof CRM_Core_Exception ) {
			WP_CLI::log( sprintf( WP_CLI::colorize( '%rCould not fetch Contact IDs:%n %s' ), $civicrm_group_contact_ids->getMessage() ) );
			return;
		}

		// Build the args to retrieve all members - review if this is not enough.
		$args = [
			'per_page' => 10000,
		];

		// Get all Members in the Mattermost Channel.
		$members = $plugin->mattermost->channel->members_get_by_channel_id( $channel_id, $args );

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
			$data = $plugin->mattermost->channel->user_ids_to_add( $civicrm_group_contact_ids, $channel_id );
			if ( ! empty( $data['has-user-id'] ) ) {

				WP_CLI::log( WP_CLI::colorize( '%gAdding Contacts from CiviCRM Group...%n' ) );

				$feedback = $plugin->mattermost->channel->user_ids_add( $data['has-user-id'], $channel_id );
				if ( ! empty( $feedback['added'] ) ) {
					foreach ( $feedback['added'] as $contact_id => $user_id ) {
						WP_CLI::log( sprintf( WP_CLI::colorize( '%gAdded Mattermost User%n %y(ID: %s) (Contact ID: %d)%n' ), $user_id, (int) $contact_id ) );
					}
				}
				if ( ! empty( $feedback['failed'] ) ) {
					foreach ( $feedback['failed'] as $contact_id => $user_id ) {
						WP_CLI::log( sprintf( WP_CLI::colorize( '%rFailed to add Mattermost User%n %y(ID: %s) (Contact ID: %d)%n' ), $user_id, (int) $contact_id ) );
					}
				}
				if ( ! empty( $data['no-user-id'] ) ) {
					foreach ( $data['no-user-id'] as $contact_id ) {
						WP_CLI::log( sprintf( WP_CLI::colorize( '%bNo Mattermost User ID found for Contact%n %y(ID: %d)%n' ), (int) $contact_id ) );
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
			$mm_group_contact_ids = $plugin->civicrm->group->contact->group_contact_ids_for_user_ids_get( $user_ids );

			// Get all Contact IDs to remove from the Mattermost Channel.
			$contact_ids_to_remove = array_diff( $mm_group_contact_ids, $civicrm_group_contact_ids );
			if ( ! empty( $contact_ids_to_remove ) ) {

				WP_CLI::log( WP_CLI::colorize( '%gRemoving Mattermost Users with no Contact in CiviCRM Group...%n' ) );

				// Process Contact IDs.
				foreach ( $contact_ids_to_remove as $contact_id ) {

					// Skip if no Contact ID.
					if ( empty( $contact_id ) ) {
						continue;
					}

					// Find the corresponding User ID.
					$user_id = $plugin->civicrm->contact->user_id_get( $contact_id );
					if ( false === $user_id ) {
						continue;
					}

					// Remove User from Mattermost Channel.
					$success = $plugin->mattermost->channel->member_delete( $channel_id, $user_id );
					if ( true === $success ) {
						WP_CLI::log( sprintf( WP_CLI::colorize( '%gRemoved Mattermost User%n %y(ID: %s) (Contact ID: %d)%n' ), $user_id, (int) $contact_id ) );
					} else {
						WP_CLI::log( sprintf( WP_CLI::colorize( '%rFailed to remove Mattermost User%n %y((ID: %s) (Contact ID: %d)%n' ), $user_id, (int) $contact_id ) );
					}

				}

				$did_sync = true;

			}

		}

		// Show feedback when no sync has taken place.
		if ( false === $did_sync ) {
			WP_CLI::log( WP_CLI::colorize( '%gGroups are already in sync.%n' ) );
		}

	}

	/**
	 * Sync WordPress Mattermost Channel Users to CiviCRM Groups.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wpcvmm job sync_to_civicrm
	 *     Success: Executed 'sync_to_civicrm' job.
	 *
	 * @alias sync-to-civicrm
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function sync_to_civicrm( $args, $assoc_args ) {

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		$plugin = wpcv_civicrm_mattermost();

		// Get all Channel Members.
		$channel_members = $plugin->mattermost->channel->members_in_synced_groups_get();
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
					$civicrm_group = $plugin->mattermost->channel->group_get( $channel_id );
					if ( ! empty( $civicrm_group ) ) {
						$civicrm_group_id                         = (int) $civicrm_group['id'];
						$correspondences['groups'][ $channel_id ] = $civicrm_group_id;
						WP_CLI::log( '' );
						WP_CLI::log( sprintf( WP_CLI::colorize( '%gAdding Users from Mattermost Channel%n %y(ID: %s)%n' ), $channel_id ) );
						WP_CLI::log( sprintf( WP_CLI::colorize( '%gSyncing with CiviCRM Group%n %Y%s%n %y(ID: %d)%n' ), $civicrm_group['title'], $civicrm_group_id ) );
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
					$contact_id = $plugin->mattermost->user->contact_id_get( $user_id );
					if ( ! empty( $contact_id ) ) {
						$correspondences['users'][ $user_id ] = (int) $contact_id;
					} else {
						$correspondences['users'][ $user_id ] = false;
					}
				}

				// Skip if there is no Contact for this Mattermost User ID.
				if ( empty( $contact_id ) || false === $contact_id ) {
					WP_CLI::log( sprintf( WP_CLI::colorize( '%bNo CiviCRM Contact found for Mattermost User%n %y(ID: %s)%n' ), $user_id ) );
					continue;
				}

				// Skip if there is an existing GroupContact entry.
				$exists = $plugin->civicrm->group->contact->get( $civicrm_group_id, $contact_id );
				if ( ! empty( $exists ) && 'Added' === $exists['status'] ) {
					continue;
				}

				// Create an active CiviCRM Group Contact.
				if ( empty( $exists ) ) {
					$success = $plugin->civicrm->group->contact->create( $civicrm_group_id, $contact_id );
				} else {
					$success = $plugin->civicrm->group->contact->activate( $exists['id'] );
				}

				// Feedback.
				if ( false !== $success ) {
					WP_CLI::log( sprintf( WP_CLI::colorize( '%gAdded Mattermost User%n %y(ID: %s) (Contact ID: %d)%n' ), $user_id, $contact_id ) );
				} else {
					WP_CLI::log( sprintf( WP_CLI::colorize( '%rFailed to add Mattermost User%n %y(ID: %s) (Contact ID: %d)%n' ), $user_id, $contact_id ) );
				}

			}

		}

		// Get all the Group Contacts in the Synced Groups.
		$group_contacts = $plugin->civicrm->group->contact->group_contacts_get();
		if ( ( $group_contacts instanceof CRM_Core_Exception ) ) {
			$group_contacts = [];
		}

		// Get the Custom Fields that store the Mattermost Channel ID and User ID.
		$channel_field = $plugin->mattermost->channel->meta_field_id_get();
		$user_field    = $plugin->mattermost->user->meta_field_id_get();

		// Delete each Group Contact where the User no longer exists in the Mattermost Channel.
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
					WP_CLI::log( '' );
					WP_CLI::log( sprintf( WP_CLI::colorize( '%gDeleting Contacts not in Mattermost Channel%n %y(ID: %s)%n' ), $channel_id ) );
					WP_CLI::log( sprintf( WP_CLI::colorize( '%gSyncing with CiviCRM Group%n %Y%s%n %y(ID: %d)%n' ), $group_contact['group.title'], (int) $group_contact['group_id'] ) );
					$channel_ids[] = $channel_id;
				}

				// Skip if the Channel Member exists or (for safety) an error is encountered.
				$exists = $plugin->mattermost->channel->member_exists( $user_id, $channel_id );
				if ( false !== $exists ) {
					continue;
				}

				// Delete the Group Contact.
				$contact_id = (int) $group_contact['contact_id'];
				$success    = $plugin->civicrm->group->contact->delete( (int) $group_contact['group_id'], $contact_id );
				if ( false !== $success ) {
					WP_CLI::log( sprintf( WP_CLI::colorize( '%gRemoved Contact%n %y(ID: %d) (User ID: %s)%n' ), $contact_id, $user_id ) );
				} else {
					WP_CLI::log( sprintf( WP_CLI::colorize( '%rFailed to remove Contact%n %y(ID: %d) (User ID: %s)%n' ), $contact_id, $user_id ) );
				}

			}
		}

		WP_CLI::log( '' );
		WP_CLI::success( "Executed 'sync_to_civicrm' job." );

	}

}
