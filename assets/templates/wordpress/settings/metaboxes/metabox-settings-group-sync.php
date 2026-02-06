<?php
/**
 * Group Sync Settings template.
 *
 * Handles markup for the Group Sync Settings meta box.
 *
 * @package WPCV_CiviCRM_Mattermost
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- <?php echo esc_html( $this->path_template . $this->path_metabox ); ?>metabox-settings-group-sync.php -->
<table class="form-table mm-credentials">
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $this->form_group_sync_id ); ?>"><?php esc_html_e( 'Synced Groups', 'wpcv-civicrm-mattermost' ); ?></label></th>
		<td>
			<select class="settings-select" name="<?php echo esc_attr( $this->form_group_sync_id ); ?>" id="<?php echo esc_attr( $this->form_group_sync_id ); ?>">
				<option value="yes" <?php selected( $group_sync, 'yes' ); ?>><?php echo esc_html__( 'Yes', 'wpcv-civicrm-mattermost' ); ?></option>
				<option value="no" <?php selected( $group_sync, 'no' ); ?>><?php echo esc_html__( 'No', 'wpcv-civicrm-mattermost' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Choose whether or not to enable Synced Groups.', 'wpcv-civicrm-mattermost' ); ?></p>
		</td>
	</tr>
</table>
