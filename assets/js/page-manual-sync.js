/**
 * Javascript for the Manual Sync Page.
 *
 * Implements functionality on the plugin's Manual Sync Page.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

/**
 * Create Integrate CiviCRM with Mattermost "Manual Sync" object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 1.0.0
 */
var WPCV_CiviCRM_Mattermost_Manual_Sync = WPCV_CiviCRM_Mattermost_Manual_Sync || {};

/**
 * Pass the jQuery shortcut in.
 *
 * @since 1.0.0
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Singleton.
	 *
	 * @since 1.0.0
	 */
	WPCV_CiviCRM_Mattermost_Manual_Sync.settings = new function() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0.0
		 */
		this.init = function() {

			// Init localisation.
			me.init_localisation();

			// Init settings.
			me.init_settings();

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0.0
		 */
		this.dom_ready = function() {

		};

		// Init localisation array.
		me.localisation = [];

		/**
		 * Init localisation from settings object.
		 *
		 * @since 1.0.0
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof WPCV_CiviCRM_Mattermost_Manual_Sync_Vars ) {
				me.localisation = WPCV_CiviCRM_Mattermost_Manual_Sync_Vars.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 1.0.0
		 *
		 * @param {String} key The key for the desired localisation group.
		 * @param {String} identifier The identifier for the desired localisation string.
		 * @return {String} The localised string.
		 */
		this.get_localisation = function( key, identifier ) {
			return me.localisation[key][identifier];
		};

		// Init settings array.
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 1.0.0
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof WPCV_CiviCRM_Mattermost_Manual_Sync_Vars ) {
				me.settings = WPCV_CiviCRM_Mattermost_Manual_Sync_Vars.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 1.0.0
		 *
		 * @param {String} The identifier for the desired setting.
		 * @return The value of the setting.
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

	};

	/**
	 * Create Progress Bar Class.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} options The setup options for the object.
	 */
	function CVGRP_ProgressBar( options ) {

		// Private var prevents reference collisions.
		var me = this;

		// Assign properties.
		me.bar = $(options.bar);
		me.label = $(options.label);

		// Assign labels.
		me.label_init = WPCV_CiviCRM_Mattermost_Manual_Sync.settings.get_localisation( options.key, 'total' );
		me.label_current = WPCV_CiviCRM_Mattermost_Manual_Sync.settings.get_localisation( options.key, 'current' );
		me.label_complete = WPCV_CiviCRM_Mattermost_Manual_Sync.settings.get_localisation( options.key, 'complete' );
		me.label_done = WPCV_CiviCRM_Mattermost_Manual_Sync.settings.get_localisation( 'common', 'done' );

		// Get count.
		me.count = options.count;

		// The triggering button.
		me.button = $(options.button);

		// The step setting.
		me.step_count = options.step_count;

		// The WordPress AJAX method token.
		me.action = options.action;

		// The AJAX nonce.
		me.ajax_nonce = me.button.data( 'security' );

		/**
		 * Add a click event listener to start sync.
		 *
		 * @param {Object} event The event object.
		 */
		me.button.on( 'click', function( event ) {

			// Prevent form submission.
			if ( event.preventDefault ) {
				event.preventDefault();
			}

			// Prevent further clicks until complete.
			me.button.prop( 'disabled', true );

			// Initialise progress bar.
			me.bar.progressbar({
				value: false,
				max: me.count
			});

			// Show progress bar if not already shown.
			me.bar.show();

			// Initialise progress bar label.
			me.label.html( me.label_init.replace( '{{total}}', me.count ) );

			// Send.
			me.send();

		});

		/**
		 * Send AJAX request.
		 *
		 * @since 1.0.0
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.update = function( data ) {

			// Declare vars.
			var val;

			// Are we still in progress?
			if ( data.finished == 'false' ) {

				// Get current value of progress bar.
				val = me.bar.progressbar( 'value' ) || 0;

				// Update progress bar label.
				me.label.html(
					me.label_complete.replace( '{{from}}', data.from ).replace( '{{to}}', data.to ).replace( '{{batch}}', data.batch )
				);

				// Update progress bar.
				me.bar.progressbar( 'value', val + me.step_count );

				// Trigger next batch.
				me.send();

			} else {

				// Update progress bar label.
				me.label.html( me.label_done );

				// Delay and then finish up.
				setTimeout(function () {

					// Hide the progress bar.
					me.bar.hide();

					// Allow clicks agaim.
					me.button.prop( 'disabled', false );

				}, 2000 );

			}

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 1.0.0
		 */
		this.send = function() {

			// Define vars.
			var url, data;

			// URL to post to.
			url = WPCV_CiviCRM_Mattermost_Manual_Sync.settings.get_setting( 'ajax_url' );

			// Data received by WordPress.
			data = {
				action: me.action,
				_ajax_nonce: me.ajax_nonce
			};

			// Use jQuery post method.
			$.post( url, data,

				// Callback.
				function( data, textStatus ) {

					// If success.
					if ( textStatus == 'success' ) {

						// Update progress bar.
						me.update( data );

					} else {

						// Show error.
						if ( console.log ) {
							console.log( textStatus );
						}

					}

				},

				// Expected format.
				'json'

			);

		};

	};

	/**
	 * Create Progress Bar Singleton.
	 *
	 * @since 1.0.0
	 */
	WPCV_CiviCRM_Mattermost_Manual_Sync.progress_bar = new function() {

		// Prevent reference collisions.
		var me = this;

		// Init bars array.
		me.bars = [];

		/**
		 * Initialise Progress Bar.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0.0
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 1.0.0
		 */
		this.dom_ready = function() {

			// Set up instance.
			me.setup();

		};

		/**
		 * Set up Progress Bar instances.
		 *
		 * @since 1.0.0
		 */
		this.setup = function() {

			// Define vars.
			var data, identifier, prop, obj;

			// Mattermost Channels to CiviCRM Groups.
			data = WPCV_CiviCRM_Mattermost_Manual_Sync.settings.get_setting( 'mattermost_to_civicrm' );

			identifier = data.submit_id;
			obj = new CVGRP_ProgressBar({
				bar: '#progress-bar-' + identifier,
				label: '#progress-bar-' + identifier + ' .progress-label',
				key: data.key,
				button: '#' + identifier,
				step_count: data.step_count,
				action: identifier,
				count: data.count
			});
			me.bars.push( obj );

			// CiviCRM Groups to Mattermost Channels.
			data = WPCV_CiviCRM_Mattermost_Manual_Sync.settings.get_setting( 'civicrm_to_mattermost' );

			identifier = data.submit_id;
			obj = new CVGRP_ProgressBar({
				bar: '#progress-bar-' + identifier,
				label: '#progress-bar-' + identifier + ' .progress-label',
				key: data.key,
				button: '#' + identifier,
				step_count: data.step_count,
				action: identifier,
				count: data.count
			});
			me.bars.push( obj );

		};

	};

	// Init settings.
	WPCV_CiviCRM_Mattermost_Manual_Sync.settings.init();

	// Init Progress Bar.
	WPCV_CiviCRM_Mattermost_Manual_Sync.progress_bar.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 1.0.0
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	WPCV_CiviCRM_Mattermost_Manual_Sync.settings.dom_ready();

	// The DOM is loaded now.
	WPCV_CiviCRM_Mattermost_Manual_Sync.progress_bar.dom_ready();

});
