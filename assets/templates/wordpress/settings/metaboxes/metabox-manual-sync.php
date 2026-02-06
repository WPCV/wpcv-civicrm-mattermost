<?php
/**
 * Groups "Manual Sync" template.
 *
 * Handles markup for the Groups "Manual Sync" meta box.
 *
 * @package WPCV_CiviCRM_Mattermost
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- <?php echo esc_html( $this->path_template . $this->path_metabox ); ?>metabox-manual-sync.php -->
<div class="cwps_acf_wrapper">

	<p><?php echo esc_html( $description ); ?></p>

	<p>
		<?php submit_button( $stop_value, 'secondary' . $stop_visibility, $stop_id, false ); ?>
		<?php submit_button( $submit_value, 'primary', $submit_id, false, $submit_attributes ); ?>
	</p>

	<div class="progress-bar progress-bar-hidden">
		<div id="progress-bar-<?php echo esc_attr( $submit_id ); ?>"><div class="progress-label"></div></div>
	</div>

</div>
