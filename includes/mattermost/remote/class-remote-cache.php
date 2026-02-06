<?php
/**
 * Remote Cache Class.
 *
 * Handles caching of queries to the Remote API.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Remote Cache Class.
 *
 * A class that encapsulates caching of queries to the Remote API.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Remote_Cache {

	/**
	 * Query Cache key.
	 *
	 * The option with this key stores the stack of requests that are being queued.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var str $query_cache_key The Query Cache key.
	 */
	public $query_cache_key = '_wpcv_civicrm_mattermost_query_cache';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Init when the Remote class is loaded.
		add_action( 'wpcvmm/mattermost/remote/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0.0
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpcvmm/mattermost/remote/cache/loaded' );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		// Maybe run cached queries when loading WordPress admin.
		add_action( 'admin_init', [ $this, 'queue_perform' ] );

	}

	/**
	 * Performs the queries held in the queue.
	 *
	 * @since 1.0.0
	 */
	public function queue_perform() {

		// Get the current queue.
		$queue = get_option( $this->query_cache_key, [] );

		// Bail if it's empty.
		if ( empty( $queue ) ) {
			return;
		}

		// Get connection instance.
		$connection = new WPCV_CiviCRM_Mattermost_Remote_API();

		// Let's perform all queries for now.
		$limit = count( $queue );

		// This may need to be limited to batches per pseudo-cron request.
		for ( $i = 1; $i <= $limit; $i++ ) {

			// Remove the top item from the stack.
			$item = array_shift( $queue );

			// Re-issue the call to the Remote API.
			$result = $connection->request( $item['endpoint'], $item['body'], $item['method'] );

			// If we've hit an error.
			if ( false === $result ) {

				// Restore item to queue.
				$queue[] = $item;

				// Skip the rest of this batch.
				break;

			}

			/**
			 * Fires when a queue item has been moved off the stack.
			 *
			 * @since 1.0.0
			 *
			 * @param array $item The queue item.
			 * @param object $result The result of a successful Remote API call.
			 */
			do_action( 'wpcvmm/queue/item', $item, $result );

		}

		// Resave queue.
		$this->queue_save( $queue );

	}

	/**
	 * Adds an item to the queue of queries to perform.
	 *
	 * Each item in the queue looks something like:
	 *
	 * [
	 *   'action' => 'pledge_create', // Name of the action.
	 *   'endpoint' => 'wp-json/wp/v2/...', // The Remote API endpoint.
	 *   'body' => [], // The array of Remote API params.
	 *   'method' => 'POST', // The Remote API method.
	 * ];
	 *
	 * The "url", "params" and "method" entries must be present for the query to
	 * be reissued. Subsequent array entries depend on the requirements of the
	 * action.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query The query to perform.
	 */
	public function queue_add( $query ) {

		// Get the current queue.
		$queue = get_option( $this->query_cache_key, [] );

		// Add query to stack and resave.
		$queue[] = $query;
		$this->queue_save( $queue );

	}

	/**
	 * Saves the queue of queries to perform.
	 *
	 * @since 1.0.0
	 *
	 * @param array $queue The array of queries to perform.
	 */
	public function queue_save( $queue ) {

		// Overwrite current stack.
		update_option( $this->query_cache_key, $queue );

	}

}
