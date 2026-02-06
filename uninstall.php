<?php
/**
 * Uninstaller.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Kick out if uninstall not called from WordPress.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete plugin version.
delete_option( 'wpcv_civicrm_mattermost_version' );

// Delete settings.
delete_option( 'wpcv_civicrm_mattermost_settings' );

// TODO: Remove traces of plugin from CiviCRM.
