<?php
/**
 * Command class.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage CiviCRM Groups Sync through the command-line.
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
class WPCV_CiviCRM_Mattermost_CLI_Command extends WPCV_CiviCRM_Mattermost_CLI_Command_Base {

	/**
	 * Adds our description and sub-commands.
	 *
	 * @since 1.0.0
	 *
	 * @param object $command The command.
	 * @return array $info The array of information about the command.
	 */
	private function command_to_array( $command ) {

		$info = [
			'name'        => $command->get_name(),
			'description' => $command->get_shortdesc(),
			'longdesc'    => $command->get_longdesc(),
		];

		foreach ( $command->get_subcommands() as $subcommand ) {
			$info['subcommands'][] = $this->command_to_array( $subcommand );
		}

		if ( empty( $info['subcommands'] ) ) {
			$info['synopsis'] = (string) $command->get_synopsis();
		}

		return $info;

	}

}
