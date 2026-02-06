<?php
/**
 * WP-CLI integration for this plugin.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Set up WP-CLI commands for this plugin.
 *
 * @since 1.0.0
 */
function wpcv_civicrm_mattermost_cli_bootstrap() {

	// Include files.
	require_once __DIR__ . '/commands/command-base.php';
	require_once __DIR__ . '/commands/command-wpcvmm.php';
	require_once __DIR__ . '/commands/command-job.php';

	// ----------------------------------------------------------------------------
	// Add commands.
	// ----------------------------------------------------------------------------

	// Add top-level command.
	WP_CLI::add_command( 'wpcvmm', 'WPCV_CiviCRM_Mattermost_CLI_Command' );

	// Add Job command.
	WP_CLI::add_command( 'wpcvmm job', 'WPCV_CiviCRM_Mattermost_CLI_Command_Job', [ 'before_invoke' => 'WPCV_CiviCRM_Mattermost_CLI_Command_Job::check_dependencies' ] );

}

// Set up commands.
WP_CLI::add_hook( 'before_wp_load', 'wpcv_civicrm_mattermost_cli_bootstrap' );
