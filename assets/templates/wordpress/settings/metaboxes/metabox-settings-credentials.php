<?php
/**
 * Mattermost Credentials template.
 *
 * Handles markup for the Mattermost Credentials meta box.
 *
 * @package WPCV_CiviCRM_Mattermost
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- <?php echo esc_html( $this->path_template . $this->path_metabox ); ?>metabox-settings-credentials.php -->
<p>
	<?php

	echo sprintf(
		/* translators: 1: The opening anchor tag, 2: The closing anchor tag. */
		esc_html__( 'You will first need to set up a %1$sBot Account%2$s on your Mattermost instance with "System Admin" permissions.', 'wpcv-civicrm-mattermost' ),
		'<a href="https://developers.mattermost.com/integrate/reference/bot-accounts/">',
		'</a>'
	);

	?>
</p>

<table class="form-table mm-credentials">
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $this->form_mm_url ); ?>"><?php esc_html_e( 'API URL', 'wpcv-civicrm-mattermost' ); ?></label></th>
		<td>
			<input type="text" class="widefat" name="<?php echo esc_attr( $this->form_mm_url ); ?>" id="<?php echo esc_attr( $this->form_mm_url ); ?>" value="<?php echo esc_attr( $url ); ?>" />
			<p class="description"><?php esc_html_e( 'The Mattermost API URL. Do not include the trailing slash.', 'wpcv-civicrm-mattermost' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $this->form_mm_token ); ?>"><?php esc_html_e( 'API Access Token', 'wpcv-civicrm-mattermost' ); ?></label></th>
		<td>
			<input type="password" class="widefat" name="<?php echo esc_attr( $this->form_mm_token ); ?>" id="<?php echo esc_attr( $this->form_mm_token ); ?>" value="<?php echo esc_attr( $token ); ?>" />
			<p class="description"><?php esc_html_e( 'The Mattermost API Access Token.', 'wpcv-civicrm-mattermost' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $this->form_mm_team_id ); ?>"><?php esc_html_e( 'Team', 'wpcv-civicrm-mattermost' ); ?></label></th>
		<td>
			<?php if ( ! empty( $message ) ) : ?>
				<?php echo esc_html( $message ); ?>
			<?php endif; ?>
			<?php if ( is_object( $team ) ) : ?>
				<p style="font-weight: bold;"><?php echo esc_html( $team->display_name ); ?></p>
				<p class="description"><?php esc_html_e( 'It looks like you have only one Mattermost Team. You are all set.', 'wpcv-civicrm-mattermost' ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $teams ) ) : ?>
				<select class="settings-select" name="<?php echo esc_attr( $this->form_mm_team_id ); ?>" id="<?php echo esc_attr( $this->form_mm_team_id ); ?>">
					<?php foreach ( $teams as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $team_id, $key ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Choose the Mattermost Team you want to integrate with CiviCRM.', 'wpcv-civicrm-mattermost' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
</table>

