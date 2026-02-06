<?php
/**
 * Remote API Class.
 *
 * Handles Remote API connections.
 *
 * @package WPCV_CiviCRM_Mattermost
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Remote API Class.
 *
 * A class that encapsulates Remote API connections.
 *
 * @since 1.0.0
 */
class WPCV_CiviCRM_Mattermost_Remote_API {

	/**
	 * The API Base URL.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $url;

	/**
	 * The API Token.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $token;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The API Base URL.
	 * @param string $token The API Token.
	 */
	public function __construct( $url = '', $token = '' ) {

		// Initialise this instance when the credentials exist.
		$credentials = wpcv_civicrm_mattermost()->mattermost->remote->api_credentials_get();
		if ( ! empty( $credentials ) ) {
			$this->initialise( $credentials['url'], $credentials['token'] );
		}

	}

	/**
	 * Initialises this Remote API instance.
	 *
	 * This method can be used to override the Remote API instance if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The API Base URL.
	 * @param string $token The API token.
	 * @return bool $success True if successful, false otherwise.
	 */
	public function initialise( $url, $token ) {

		// Sanity check params.
		if ( empty( $url ) || empty( $token ) ) {
			return false;
		}

		// Store params.
		$this->url   = trailingslashit( $url );
		$this->token = $token;

		// --<
		return true;

	}

	/**
	 * Sends a GET request to the Remote API and returns the response.
	 *
	 * Note: Some GET operations will respond with HTTP status 404 when the resource
	 * does not exist - e.g. checking for a User by username or email address.
	 * This is not strictly an error, but confirmation that the User does not exist.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint The Remote API endpoint.
	 * @param array  $body The params to send.
	 * @param bool   $auth True if authentication is required. Default false.
	 * @param array  $headers Any extra headers to send.
	 * @param bool   $allow_404 True if a HTTP 404 response is valid. Default false.
	 * @return stdClass|bool $result The response object, or false on failure.
	 */
	public function get( $endpoint, $body = [], $auth = false, $headers = [], $allow_404 = false ) {

		// Init return.
		$result = false;

		// Some GET requests require authentication.
		if ( true === $auth ) {

			// Construct authentication string.
			$auth = 'Bearer ' . $this->token;

			// Build headers array.
			$http_headers = [
				'Authorization' => $auth,
			] + $headers;

		} else {

			// Use supplied headers array.
			$http_headers = $headers;

		}

		// Build GET arguments.
		$args = [
			'headers' => $http_headers,
			'body'    => $body,
		];

		// Fire the GET request.
		$response = wp_remote_get( $this->url . $endpoint, $args );

		// Build success codes array.
		$success_codes = [ 200 ];
		if ( true === $allow_404 ) {
			$success_codes[] = 404;
		}

		// Post-request checks.
		$result = $this->post_request( $response, $success_codes, $this->url . $endpoint, $args );

		// --<
		return $result;

	}

	/**
	 * Sends a POST request to the Remote API and returns the response.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint The Remote API endpoint.
	 * @param array  $body The PHP array of params to send.
	 * @param array  $headers The headers to send.
	 * @param bool   $json Whether or not to JSON-encode the payload.
	 * @return stdClass|bool $result The response object, or false on failure.
	 */
	public function post( $endpoint, $body = [], $headers = [], $json = false ) {

		// Init return.
		$result = false;

		// POST always requires authentication.
		$auth = 'Bearer ' . $this->token;

		// Build headers array.
		$http_headers = [
			'Authorization' => $auth,
		] + $headers;

		// Maybe JSON-encode payload.
		if ( true === $json ) {
			$http_headers['Content-Type'] = 'application/json; charset=utf-8';
			$body                         = wp_json_encode( $body );
		}

		// Build POST arguments.
		$args = [
			'headers' => $http_headers,
			'body'    => $body,
		];

		// Fire the POST request.
		$response = wp_remote_post( $this->url . $endpoint, $args );

		/*
		 * Post-request checks.
		 *
		 * * Create requests are successful with code 201.
		 * * Update requests are successful with code 200.
		 *
		 * We're happy with either.
		 */
		$result = $this->post_request( $response, [ 200, 201 ], $this->url . $endpoint, $args );

		// --<
		return $result;

	}

	/**
	 * Sends a DELETE request to the Remote API and returns the response.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint The Remote API endpoint.
	 * @param array  $body The params to send.
	 * @param array  $headers The headers to send.
	 * @return stdClass|bool $result The response object, or false on failure.
	 */
	public function delete( $endpoint, $body = [], $headers = [] ) {

		// Init return.
		$result = false;

		// DELETE always requires authentication.
		$auth = 'Bearer ' . $this->token;

		// Build headers array.
		$http_headers = [
			'Authorization' => $auth,
		] + $headers;

		// Build DELETE arguments.
		$args = [
			'method'  => 'DELETE',
			'headers' => $http_headers,
			'body'    => $body,
		];

		// Fire the DELETE request.
		$response = wp_remote_request( $this->url . $endpoint, $args );

		// Post-request checks.
		$result = $this->post_request( $response, [ 200 ], $this->url . $endpoint, $args );

		// --<
		return $result;

	}

	/**
	 * Sends a configurable request to the Remote API and returns the response.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint The Remote API endpoint.
	 * @param array  $body The PHP array of params to send.
	 * @param string $method The request method. Default GET.
	 * @param array  $headers The headers to send.
	 * @return stdClass|bool $result The response object, or false on failure.
	 */
	public function request( $endpoint, $body = [], $method = 'GET', $headers = [] ) {

		// Init return.
		$result = false;

		// Always add authentication.
		$auth = 'Bearer ' . $this->token;

		// Build headers array.
		$http_headers = [
			'Authorization' => $auth,
		] + $headers;

		// Always capitalise method.
		$method = strtoupper( $method );

		// Declare as JSON and encode data when request is POST or PUT.
		if ( 'POST' === $method || 'PUT' === $method ) {
			$http_headers['Content-Type'] = 'application/json; charset=utf-8';
			$body                         = wp_json_encode( $body );
		}

		// Build arguments.
		$args = [
			'method'  => $method,
			'headers' => $http_headers,
			'body'    => $body,
		];

		// Fire the request.
		$response = wp_remote_request( $this->url . $endpoint, $args );

		// Post-request checks.
		$result = $this->post_request( $response, [ 200, 201 ], $this->url . $endpoint, $args );

		// --<
		return $result;

	}

	/**
	 * Post-request checks and response parsing.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $response The request response.
	 * @param array  $success_codes The anticipated success codes. Default 200.
	 * @param string $url The URL.
	 * @param array  $args The request args.
	 * @return stdClass|bool $result The response object, or false on failure.
	 */
	public function post_request( $response, $success_codes = [ 200 ], $url = '', $args = [] ) {

		// Init return.
		$result = false;

		// Log what we can if there's an error.
		if ( is_wp_error( $response ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			wpcv_civicrm_mattermost()->log_error(
				[
					'method'    => __METHOD__,
					'message'   => $response->get_error_message(),
					'response'  => $response,
					'url'       => $url,
					'args'      => $args,
					'backtrace' => $trace,
				]
			);
			return $result;
		}

		// Log something if the response isn't what we expect.
		if ( ! is_array( $response ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			wpcv_civicrm_mattermost()->log_error(
				[
					'method'    => __METHOD__,
					'error'     => __( 'Response is not an array.', 'wpcv-civicrm-mattermost' ),
					'response'  => $response,
					'url'       => $url,
					'args'      => $args,
					'backtrace' => $trace,
				]
			);
			return $result;
		}

		// Log something if the response isn't an expected success code.
		if ( empty( $response['response']['code'] ) || ! in_array( (int) $response['response']['code'], $success_codes, true ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			wpcv_civicrm_mattermost()->log_error(
				[
					'method'    => __METHOD__,
					'error'     => __( 'Request was not successful.', 'wpcv-civicrm-mattermost' ),
					'response'  => $response,
					'url'       => $url,
					'args'      => $args,
					'backtrace' => $trace,
				]
			);
			return $result;
		}

		// Try and format the result.
		$result = json_decode( $response['body'], false );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			wpcv_civicrm_mattermost()->log_error(
				[
					'method'    => __METHOD__,
					'error'     => __( 'Failed to decode JSON.', 'wpcv-civicrm-mattermost' ),
					'message'   => json_last_error_msg(),
					'response'  => $response,
					'url'       => $url,
					'args'      => $args,
					'backtrace' => $trace,
				]
			);
			$result = false;
		}

		// --<
		return $result;

	}

}
