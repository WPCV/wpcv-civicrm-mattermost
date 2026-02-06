/**
 * Javascript for the Settings Page.
 *
 * Implements visibility toggles on the plugin's Settings Page.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

/**
 * Pass the jQuery shortcut in.
 *
 * @since 1.0.0
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Act on document ready.
	 *
	 * @since 1.0.0
	 */
	$(document).ready( function() {

		// Define vars.
		var group_sync = $('#mm_group_sync_id'),
			schedule_box = $('#wpcvmm_settings_settings_schedule'),
			interval = $('#wpcvmm_settings_interval_id'),
			sync_details = $('table.sync-details');

		// Initial Schedule metabox visibility toggle.
		if ( 'no' === group_sync.val() ) {
			schedule_box.hide();
		} else {
			schedule_box.show();
		}

		/**
		 * Add a change event listener to the "Synced Groups" select.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} event The event object.
		 */
		group_sync.on( 'change', function( event ) {
			if ( 'no' === group_sync.val() ) {
				schedule_box.hide();
			} else {
				schedule_box.show();
			}
		} );

		// Initial Pseudo-cron settings visibility toggle.
		if ( 'off' === interval.val() ) {
			sync_details.hide();
		} else {
			sync_details.show();
		}

		/**
		 * Add a change event listener to the "Schedule Interval" select.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} event The event object.
		 */
		interval.on( 'change', function( event ) {
			if ( 'off' === interval.val() ) {
				sync_details.hide();
			} else {
				sync_details.show();
			}
		} );

   	});

} )( jQuery );
