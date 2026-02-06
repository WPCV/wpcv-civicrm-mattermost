<?php
/**
 * CiviCRM Group Sync class.
 *
 * Handles CiviCRM Group Sync-related functionality.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Group Sync Class.
 *
 * A class that encapsulates CiviCRM Group Sync functionality.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_CiviCRM_Group_Sync {

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
	 * Bridging array.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array
	 */
	private $bridging_array = [];

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
		do_action( 'wpcvmm/civicrm/group/sync/loaded' );

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

		// Bail if we can't connect to Mattermost.
		$credentials = $this->plugin->mattermost->remote->api_credentials_get();
		if ( empty( $credentials ) ) {
			return;
		}

		// Modify CiviCRM Group Create form.
		add_action( 'civicrm_buildForm', [ $this, 'form_modify' ], 10, 2 );

		// Add Symfony hooks.
		add_action( 'civicrm_config', [ $this, 'config_callback' ], 10 );

		// Check our form element before creating a Group.
		add_action( 'civicrm_pre', [ $this, 'group_created_pre' ], 100, 4 );

		// Intercept before CiviCRM updates a Group.
		add_action( 'civicrm_pre', [ $this, 'group_updated_pre' ], 10, 4 );

	}

	/**
	 * Adds callbacks for Symfony events.
	 *
	 * @since 1.0.0
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function config_callback( &$config ) {

		// Add callback for CiviCRM "preInsert" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preInsert',
			[ $this, 'group_pre_insert' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "postInsert" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.postInsert',
			[ $this, 'group_post_insert' ],
			-100 // Default priority.
		);

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Enables a Mattermost Channel to be created when creating a CiviCRM Group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_modify( $form_name, &$form ) {

		// Is this the Group Edit form?
		if ( 'CRM_Group_Form_Edit' !== $form_name ) {
			return;
		}

		// Get CiviCRM Group.
		$group = $form->getVar( '_group' );

		// Assign template depending on whether we have a Group ID.
		if ( ! empty( $group ) && ! empty( $group->id ) ) {

			// It's the Edit Group form.

			// Skip if there is no Mattermost Channel ID.
			$channel_id = $this->group->channel_id_get( (int) $group->id );
			if ( empty( $channel_id ) ) {
				return;
			}

			// Add translated text.
			$form->assign( 'wpcvmm_label', esc_html__( 'Mattermost Channel', 'wpcv-civicrm-mattermost' ) );
			$form->assign( 'wpcvmm_description', esc_html__( 'This is a Synced Group and already has an associated Mattermost Channel', 'wpcv-civicrm-mattermost' ) );

			// Maybe add Channel URL.
			// TODO: Check if Contact's User is a Channel Member.
			$channel_url = '';
			$url         = $this->group->channel_url_get( (int) $group->id );
			if ( ! empty( $url ) ) {
				$channel_url = ' &rarr; <a href="' . esc_url( $url ) . '">' . esc_html__( 'Visit Channel', 'wpcv-civicrm-mattermost' ) . '</a>';
			}
			$form->assign( 'wpcvmm_url', $channel_url );

			// Insert template block into the page.
			CRM_Core_Region::instance( 'page-body' )->add( [ 'template' => 'groups/civicrm-group-edit.tpl' ] );

		} else {

			// It's the New Group form.

			// Add the checkbox to the form.
			$form->add(
				'checkbox',
				'wpcvmm_channel_create',
				esc_html__( 'Mattermost Channel', 'wpcv-civicrm-mattermost' )
			);

			$form->assign( 'wpcvmm_channel_description', esc_html__( 'Create a Mattermost Channel that is synced with this Group. You only need to fill out the "Group Title" field (and optionally the "Group Description" field) above. The "Access Control" Group Type will be added automatically.', 'wpcv-civicrm-mattermost' ) );

			// Add the select to the form.
			$form->add(
				'select',
				'wpcvmm_channel_type',
				esc_html__( 'Channel Type', 'wpcv-civicrm-mattermost' ),
				[
					'O' => __( 'Public', 'wpcv-civicrm-mattermost' ),
					'P' => __( 'Private', 'wpcv-civicrm-mattermost' ),
				]
			);

			$form->assign( 'wpcvmm_type_description', esc_html__( 'Choose the type of Mattermost Channel to create.', 'wpcv-civicrm-mattermost' ) );

			// Insert template block into the page.
			CRM_Core_Region::instance( 'page-body' )->add( [ 'template' => 'groups/civicrm-group-create.tpl' ] );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Acts when a CiviCRM Group is about to be created.
	 *
	 * We somehow need to pass information on to a "post" hook if our checkbox has been
	 * checked so that the Group ID can be linked with a Mattermost Channel. To do this,
	 * we append a string to one of the params by which the CiviCRM Group is created.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $group The array of CiviCRM Group data.
	 */
	public function group_created_pre( $op, $object_name, $group_id, &$group ) {

		// Target our operation.
		if ( 'create' !== $op ) {
			return;
		}

		// Bail if this isn't the type of object we're after.
		if ( 'Group' !== $object_name ) {
			return;
		}

		// Was our checkbox ticked?
		if ( ! isset( $group['wpcvmm_channel_create'] ) ) {
			return;
		}
		if ( 1 !== (int) $group['wpcvmm_channel_create'] ) {
			return;
		}

		// Sanity check the Channel Type.
		if ( ! isset( $group['wpcvmm_channel_type'] ) ) {
			$group['wpcvmm_channel_type'] = '0';
		}
		if ( ! in_array( $group['wpcvmm_channel_type'], [ 'O', 'P' ], true ) ) {
			return;
		}

		// Always make the Group of type "Access Control".
		$group['group_type'] = $this->group_ensure_acl_type( $group );

		// Use the "source" field to denote a "Synced Group".
		if ( empty( $group['source'] ) ) {
			$group['source'] = 'wpcvmm-' . $group['wpcvmm_channel_type'];
		} else {
			$group['source'] .= 'wpcvmm-' . $group['wpcvmm_channel_type'];
		}

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.preInsert' hook.
	 *
	 * @since 1.0.0
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function group_pre_insert( $event, $hook ) {

		// Extract Group for this hook.
		$group =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $group instanceof CRM_Contact_BAO_Group ) ) {
			return;
		}

		// Bail if this isn't one of our synced Groups.
		if ( empty( $group->source ) || false === strstr( $group->source, 'wpcvmm-' ) ) {
			return;
		}

		// Extract Channel Type.
		$start  = strpos( $group->source, 'wpcvmm-' );
		$string = substr( $group->source, $start, 8 );
		$type   = substr( $string, -1, 1 );

		// Add Channel Type to mark the Group as needing to be processed after insertion.
		$this->bridging_array[ $event->eventID ] = $type;

		// No need to save our identifier.
		$group->source = str_replace( 'wpcvmm-' . $type, '', $group->source );

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.postInsert' hook.
	 *
	 * @since 1.0.0
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function group_post_insert( $event, $hook ) {

		// Clone the Group object because we don't want to affect the original.
		$group = clone $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $group instanceof CRM_Contact_BAO_Group ) ) {
			return;
		}

		// Bail if the Group does not need to be processed after insertion.
		if ( ! isset( $this->bridging_array[ $event->eventID ] ) ) {
			return;
		}

		// Read and remove this item.
		$type = $this->bridging_array[ $event->eventID ];
		unset( $this->bridging_array[ $event->eventID ] );

		// Use CiviCRM's logic for building the unique Group "name".
		$group->name = substr( $group->name, 0, -4 ) . "_{$group->id}";

		// Create a Mattermost Channel from CiviCRM Group data.
		$channel = $this->group->channel_create( $group, $type );

	}

	/**
	 * Intercept when a CiviCRM Group is about to be updated.
	 *
	 * We need to make sure that the CiviCRM Group remains of type "Access Control".
	 *
	 * @since 1.0.0
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $group The array of CiviCRM Group data.
	 */
	public function group_updated_pre( $op, $object_name, $group_id, &$group ) {

		// Target our operation.
		if ( 'edit' !== $op ) {
			return;
		}

		// Target our object type.
		if ( 'Group' !== $object_name ) {
			return;
		}

		// Bail if there is no Mattermost Channel ID.
		$channel_id = $this->group->channel_id_get( (int) $group_id );
		if ( empty( $channel_id ) ) {
			return;
		}

		// Always make the Group of type "Access Control".
		$group['group_type'] = $this->group_ensure_acl_type( $group );

	}

	/**
	 * Ensures that a Synced CiviCRM Group is always of type "ACL".
	 *
	 * Note that these integer values can be modified or deleted since they are editable
	 * Option Values that can be accessed via:
	 *
	 * /wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fadmin%2Foptions&gid=22&reset=1
	 *
	 * In future, these values might need to be looked up to make sure they exist.
	 * For now, we assume that people haven't messed with the defaults.
	 *
	 * Also note that CiviCRM 5.79.0 switched the "Group Type" array from:
	 *
	 * array(
	 *     group_type_id => enabled_flag,
	 *     group_type_id => enabled_flag,
	 * );
	 *
	 * To:
	 *
	 * array(
	 *     group_type_id,
	 *     group_type_id,
	 * );
	 *
	 * @see https://github.com/civicrm/civicrm-core/pull/31095
	 *
	 * @since 1.0.0
	 *
	 * @param array $group The existing array of CiviCRM Group data.
	 * @return array $group_types The array of CiviCRM Group types.
	 */
	public function group_ensure_acl_type( $group ) {

		// Make sure we have an array.
		if ( empty( $group['group_type'] ) || ! is_array( $group['group_type'] ) ) {
			$group_types = [];
		} else {
			$group_types = $group['group_type'];
		}

		// Check for watershed version.
		$version = CRM_Utils_System::version();
		if ( version_compare( $version, '5.79.0', '>=' ) ) {

			// Make sure array contains integers.
			array_walk(
				$group_types,
				function( &$item ) {
					$item = (int) $item;
				}
			);

			// Add "Access Control" type to array if not present.
			if ( ! in_array( 1, $group_types, true ) ) {
				$group_types[] = 1;
			}

			// Sort by value for consistency.
			sort( $group_types );

		} else {

			// Legacy way to make the Group of type "Access Control".
			if ( ! empty( $group_types ) ) {
				$group_types[1] = 1;
			} else {
				$group_types = [ 1 => 1 ];
			}

			// Sort by key for consistency.
			ksort( $group_types );

		}

		// --<
		return $group_types;

	}

}
