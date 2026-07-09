<?php
/**
 * Kit (ConvertKit) API client for JW Kit Auto Tagger.
 *
 * Uses Kit API v4: https://api.kit.com/v4/
 * - Create/upsert subscriber by email
 * - Tag subscriber by email
 * - Remove tag from subscriber by email
 *
 * @package JW_Kit_Auto_Tagger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_Kit_Client
 */
class JW_Kit_Client {

	/**
	 * Kit API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.kit.com/v4';

	/**
	 * Logger instance.
	 *
	 * @var JW_Kit_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param JW_Kit_Logger $logger Logger instance.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Get API key from settings.
	 *
	 * @return string
	 */
	private function get_api_key() {
		return jw_kit_auto_tagger()->get_option( 'api_key', '' );
	}

	/**
	 * Get tag ID for a tag key.
	 *
	 * @param string $tag_key Tag key (e.g. LM_Roadmap, Stage_Cold).
	 * @return int|null Tag ID or null if not configured.
	 */
	public function get_tag_id( $tag_key ) {
		$id = jw_kit_auto_tagger()->get_option( 'tag_' . $tag_key, '' );
		if ( '' === $id || ! is_numeric( $id ) ) {
			return null;
		}
		return (int) $id;
	}

	/**
	 * Check if API is configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		$key = $this->get_api_key();
		return ! empty( $key );
	}

	/**
	 * Test API connection.
	 *
	 * @return array{ success: bool, message: string }
	 */
	public function test_connection() {
		if ( ! $this->is_configured() ) {
			return array(
				'success' => false,
				'message' => __( 'API key is not configured.', 'jw-kit-auto-tagger' ),
			);
		}

		// Kit API: List tags as a simple auth test.
		$response = $this->request( 'GET', '/tags?per_page=1' );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 === $code || 201 === $code ) {
			return array(
				'success' => true,
				'message' => __( 'Connection successful.', 'jw-kit-auto-tagger' ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$msg  = isset( $body['errors'][0] ) ? $body['errors'][0] : __( 'Unknown API error.', 'jw-kit-auto-tagger' );

		return array(
			'success' => false,
			'message' => $msg,
		);
	}

	/**
	 * Upsert subscriber (create or update by email).
	 *
	 * @param string $email      Email address.
	 * @param string $first_name Optional first name.
	 * @param array  $fields     Optional custom fields (e.g. last_name).
	 * @return array{ success: bool, subscriber_id?: int, error?: string }
	 */
	public function upsert_subscriber( $email, $first_name = '', $fields = array() ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return array( 'success' => false, 'error' => __( 'Invalid email address.', 'jw-kit-auto-tagger' ) );
		}

		$body = array(
			'email_address' => $email,
			'first_name'    => sanitize_text_field( $first_name ),
		);

		if ( ! empty( $fields ) ) {
			$body['fields'] = $fields;
		}

		$response = $this->request( 'POST', '/subscribers', $body );

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'Kit upsert subscriber failed', array( 'email' => $email, 'error' => $response->get_error_message() ) );
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( in_array( $code, array( 200, 201, 202 ), true ) && isset( $data['subscriber']['id'] ) ) {
			$this->logger->debug( 'Kit upsert subscriber success', array( 'email' => $email, 'subscriber_id' => $data['subscriber']['id'] ) );
			return array(
				'success'       => true,
				'subscriber_id' => (int) $data['subscriber']['id'],
			);
		}

		$error = isset( $data['errors'][0] ) ? $data['errors'][0] : __( 'Unknown API error.', 'jw-kit-auto-tagger' );
		$this->logger->error( 'Kit upsert subscriber failed', array( 'email' => $email, 'code' => $code, 'error' => $error ) );
		return array( 'success' => false, 'error' => $error );
	}

	/**
	 * Add tag to subscriber by email.
	 *
	 * @param string $email  Email address.
	 * @param int    $tag_id Kit tag ID.
	 * @return array{ success: bool, error?: string }
	 */
	public function add_tag_by_email( $email, $tag_id ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return array( 'success' => false, 'error' => __( 'Invalid email address.', 'jw-kit-auto-tagger' ) );
		}

		if ( ! $tag_id ) {
			return array( 'success' => false, 'error' => __( 'Invalid tag ID.', 'jw-kit-auto-tagger' ) );
		}

		$response = $this->request( 'POST', '/tags/' . (int) $tag_id . '/subscribers', array( 'email_address' => $email ) );

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'Kit add tag failed', array( 'email' => $email, 'tag_id' => $tag_id, 'error' => $response->get_error_message() ) );
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// 200 = already has tag, 201 = newly tagged.
		if ( in_array( $code, array( 200, 201 ), true ) ) {
			$this->logger->debug( 'Kit add tag success', array( 'email' => $email, 'tag_id' => $tag_id ) );
			return array( 'success' => true );
		}

		$data  = json_decode( wp_remote_retrieve_body( $response ), true );
		$error = isset( $data['errors'][0] ) ? $data['errors'][0] : __( 'Unknown API error.', 'jw-kit-auto-tagger' );
		$this->logger->error( 'Kit add tag failed', array( 'email' => $email, 'tag_id' => $tag_id, 'code' => $code, 'error' => $error ) );
		return array( 'success' => false, 'error' => $error );
	}

	/**
	 * Remove tag from subscriber by email.
	 *
	 * @param string $email  Email address.
	 * @param int    $tag_id Kit tag ID.
	 * @return array{ success: bool, error?: string }
	 */
	public function remove_tag_by_email( $email, $tag_id ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return array( 'success' => false, 'error' => __( 'Invalid email address.', 'jw-kit-auto-tagger' ) );
		}

		if ( ! $tag_id ) {
			return array( 'success' => false, 'error' => __( 'Invalid tag ID.', 'jw-kit-auto-tagger' ) );
		}

		$response = $this->request( 'DELETE', '/tags/' . (int) $tag_id . '/subscribers', array( 'email_address' => $email ) );

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'Kit remove tag failed', array( 'email' => $email, 'tag_id' => $tag_id, 'error' => $response->get_error_message() ) );
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// 204 = success, 404 = subscriber/tag not found (treat as success - nothing to remove).
		if ( in_array( $code, array( 204, 404 ), true ) ) {
			$this->logger->debug( 'Kit remove tag success', array( 'email' => $email, 'tag_id' => $tag_id ) );
			return array( 'success' => true );
		}

		$data  = json_decode( wp_remote_retrieve_body( $response ), true );
		$error = isset( $data['errors'][0] ) ? $data['errors'][0] : __( 'Unknown API error.', 'jw-kit-auto-tagger' );
		$this->logger->error( 'Kit remove tag failed', array( 'email' => $email, 'tag_id' => $tag_id, 'code' => $code, 'error' => $error ) );
		return array( 'success' => false, 'error' => $error );
	}

	/**
	 * Process tagging for a subscriber: upsert, add tags, remove other stage tags.
	 *
	 * @param string $email         Email address.
	 * @param array  $tags_to_add   Tag keys to add (e.g. ['LM_Roadmap', 'Stage_Cold']).
	 * @param string $new_stage     New stage tag key for exclusivity (removes other stages).
	 * @param string $first_name    Optional first name.
	 * @param array  $fields        Optional custom fields.
	 * @return array{ success: bool, error?: string }
	 */
	public function process_tagging( $email, $tags_to_add, $new_stage = '', $first_name = '', $fields = array() ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			$this->logger->error( 'process_tagging: invalid email', array( 'email' => $email ) );
			return array( 'success' => false, 'error' => __( 'Invalid email address.', 'jw-kit-auto-tagger' ) );
		}

		if ( ! $this->is_configured() ) {
			$this->logger->error( 'process_tagging: API not configured' );
			return array( 'success' => false, 'error' => __( 'Kit API is not configured.', 'jw-kit-auto-tagger' ) );
		}

		// 1. Upsert subscriber.
		$upsert = $this->upsert_subscriber( $email, $first_name, $fields );
		if ( ! $upsert['success'] ) {
			return $upsert;
		}

		// 2. Remove other stage tags if we're setting a new stage.
		if ( ! empty( $new_stage ) ) {
			$stage_tag_id = $this->get_tag_id( $new_stage );
			if ( $stage_tag_id ) {
				foreach ( JW_KIT_STAGE_TAGS as $stage_key ) {
					if ( $stage_key === $new_stage ) {
						continue;
					}
					$remove_id = $this->get_tag_id( $stage_key );
					if ( $remove_id ) {
						$this->remove_tag_by_email( $email, $remove_id );
					}
				}
			} else {
				$this->logger->info( 'process_tagging: stage tag ID not configured, skipping stage exclusivity', array( 'stage' => $new_stage ) );
			}
		}

		// 3. Add tags.
		$tags_added = 0;
		$tags_skipped = array();
		foreach ( $tags_to_add as $tag_key ) {
			$tag_id = $this->get_tag_id( $tag_key );
			if ( $tag_id ) {
				$result = $this->add_tag_by_email( $email, $tag_id );
				if ( $result['success'] ) {
					$tags_added++;
				}
			} else {
				$tags_skipped[] = $tag_key;
			}
		}

		if ( ! empty( $tags_skipped ) ) {
			$this->logger->info( 'process_tagging: some tags skipped (ID not configured)', array( 'skipped' => $tags_skipped, 'email' => $email ) );
		}

		return array( 'success' => true );
	}

	/**
	 * Make API request.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   API path (e.g. /subscribers).
	 * @param array  $body   Optional request body.
	 * @return array|WP_Error
	 */
	private function request( $method, $path, $body = array() ) {
		$api_key = $this->get_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'Kit API key is not configured.', 'jw-kit-auto-tagger' ) );
		}

		$url  = self::API_BASE . $path;
		$args = array(
			'method'  => $method,
			'headers' => array(
				'X-Kit-Api-Key' => $api_key,
				'Content-Type'   => 'application/json',
			),
			'timeout' => 15,
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		// DELETE with body: Kit API remove tag by email requires email in body.
		if ( 'DELETE' === $method && ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $response;
	}
}
