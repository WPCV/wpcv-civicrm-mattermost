<?php
/**
 * CiviRules Action Base class.
 *
 * Holds methods common to all CiviRules Action classes.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviRules Action Base Class.
 *
 * A class that is extended by CiviRules Action classes in this plugin.
 *
 * @see https://docs.civicrm.org/civirules/en/latest/create-your-own-action/
 *
 * @since 1.0.0
 */
abstract class WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules_Base {

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
	 * CiviRules object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var WPCV_CiviCRM_Mattermost_CiviCRM_CiviRules
	 */
	public $civirules;

	/**
	 * The "Name" of the CiviRule.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $civirule_name = '';

	/**
	 * The Class Name of the CiviRule.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $civirule_class = '';

	/**
	 * The URL of the CiviRules Action page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $civirule_url = '';

	/**
	 * The Class Name of the CiviRules Action page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $civirule_page = '';

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WPCV_CiviCRM_Mattermost_CiviCRM $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->plugin    = $parent->civicrm->plugin;
		$this->civicrm   = $parent->civicrm;
		$this->civirules = $parent;

		// Add action for init.
		add_action( 'wpcvmm/civicrm/civirules/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0.0
	 */
	public function initialise() {

		// Bootstrap this object.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		// Install CiviRules Action on install.
		add_action( 'wpcvmm/activated', [ $this, 'action_install' ] );

		// Uninstall CiviRules Action on uninstall.
		add_action( 'wpcvmm/deactivated', [ $this, 'action_uninstall' ] );

		// Add the URL for the CiviRules Action.
		add_action( 'civicrm_alterMenu', [ $this, 'route_add' ] );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Installs the CiviRules Action.
	 *
	 * @since 1.0.0
	 */
	abstract public function action_install();

	/**
	 * Uninstalls the CiviRules Action.
	 *
	 * @since 1.0.0
	 */
	public function action_uninstall() {

		// Uninstall CiviRules Action.
		$result = $this->action_delete( $this->civirule_name );

	}

	/**
	 * Creates a CiviRules Action.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of the Action.
	 * @param string $label The label of the Action.
	 * @param string $class_name The name of the class that handles the Action.
	 * @return array $action The array of data for the new Action.
	 */
	protected function action_create( $name, $label, $class_name ) {

		// Init return.
		$action = [];

		// Bail if we can't initialise CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $action;
		}

		// Bail if CiviRules is not installed.
		if ( ! $this->civicrm->is_extension_enabled( 'org.civicoop.civirules' ) ) {
			return $action;
		}

		try {

			// Install CiviRules Action.
			$result = \Civi\Api4\CiviRulesAction::create( false )
				->addValue( 'name', $name )
				->addValue( 'label', $label )
				->addValue( 'class_name', $class_name )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $action;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $action;
		}

		// We want the first result.
		$action = $result->first();

		// --<
		return $action;

	}

	/**
	 * Deletes a CiviRules Action.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of the Action.
	 * @return array $action The array of data for the deleted Action.
	 */
	protected function action_delete( $name ) {

		// Init return.
		$action = [];

		// Bail if we can't initialise CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $action;
		}

		// Bail if CiviRules is not installed.
		if ( ! $this->civicrm->is_extension_enabled( 'org.civicoop.civirules' ) ) {
			return $action;
		}

		try {

			// Uninstall CiviRules Action.
			$result = \Civi\Api4\CiviRulesAction::delete( false )
				->addWhere( 'name', '=', $name )
				->execute();

		} catch ( CRM_Core_Exception $e ) {
			$log = [
				'method'    => __METHOD__,
				'error'     => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );
			return $action;
		}

		// Bail if there is not exactly one result.
		if ( 1 !== $result->count() ) {
			return $action;
		}

		// We want the first result.
		$action = $result->first();

		// --<
		return $action;

	}

	/**
	 * Adds a HTTP route to CiviCRM.
	 *
	 * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterMenu/
	 *
	 * @since 1.0.0
	 *
	 * @param array $routes The array of CiviCRM routes.
	 */
	public function route_add( &$routes ) {

		// Bail if CiviRules is not installed.
		if ( ! $this->civicrm->is_extension_enabled( 'org.civicoop.civirules' ) ) {
			return;
		}

		// Add route.
		$routes[ $this->civirule_url ] = [
			'page_callback'    => $this->civirule_page,
			'access_arguments' => [
				[
					'access CiviCRM',
				],
				'and',
			],
		];

	}

	/**
	 * Gets the HTTP route for the CiviRules Action.
	 *
	 * @since 1.0.0
	 *
	 * @return string $route The CiviCRM route.
	 */
	public function route_get() {
		return $this->civirule_url;
	}

}
