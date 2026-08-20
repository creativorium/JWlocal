<?php
/**
 * Custom PHP/HTML form integration for JW Kit Auto Tagger.
 *
 * For forms not built with Elementor, call the hook from your form handler:
 *
 *   do_action( 'jw_kit_tag_subscriber', array(
 *       'email'      => 'user@example.com',
 *       'form_id'    => 'webinar_registration',
 *       'first_name' => 'John',
 *       'last_name'  => 'Doe',
 *       // Optional: Kit tag IDs chosen on the form itself. When present these
 *       // REPLACE whatever the form_id maps to below.
 *       'tags'       => array( 16939171, 16939366 ),
 *       'fields'     => array( 'roadmap_link' => 'https://…' ),
 *   ) );
 *
 * Supported form_id values (or add via jw_kit_custom_form_map filter):
 * - free_preview_gate_keep -> Preview_Optin + Stage_Warm
 * - checkout_started       -> Checkout_Started + Stage_High_Intent
 * - webinar_registration   -> Webinar_Registrant + Stage_Warm
 *
 * @package JW_Kit_Auto_Tagger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_Kit_Custom_Form_Hook
 */
class JW_Kit_Custom_Form_Hook {

	/**
	 * Form ID to tags mapping.
	 * form_id is normalized via sanitize_key (e.g. "Checkout_Started" -> "checkout_started").
	 *
	 * @var array<string, array{ tags: string[], stage: string }>
	 */
	private $form_map = array(
		'free_preview_gate_keep' => array(
			'tags'  => array( 'Preview_Optin', 'Stage_Warm' ),
			'stage' => 'Stage_Warm',
		),
		'checkout_started'       => array(
			'tags'  => array( 'Checkout_Started', 'Stage_High_Intent' ),
			'stage' => 'Stage_High_Intent',
		),
		'webinar_registration'   => array(
			'tags'  => array( 'Webinar_Registrant', 'Stage_Warm' ),
			'stage' => 'Stage_Warm',
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'jw_kit_tag_subscriber', array( $this, 'on_tag_request' ), 10, 1 );
	}

	/**
	 * Handle tag request from custom form.
	 *
	 * @param array $args Must include: email, form_id. Optional: first_name, last_name.
	 */
	public function on_tag_request( $args ) {
		if ( ! is_array( $args ) ) {
			jw_kit_auto_tagger()->logger->error( 'Custom form: args must be array' );
			return;
		}

		$email   = isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '';
		$form_id = isset( $args['form_id'] ) ? sanitize_key( $args['form_id'] ) : '';

		// Log incoming request (helps debug if hook is firing).
		jw_kit_auto_tagger()->logger->info( 'Custom form: hook received', array( 'form_id' => $form_id, 'email' => $email ) );

		if ( empty( $email ) || ! is_email( $email ) ) {
			jw_kit_auto_tagger()->logger->error( 'Custom form: invalid or missing email', array( 'form_id' => $form_id ) );
			return;
		}

		if ( empty( $form_id ) ) {
			jw_kit_auto_tagger()->logger->error( 'Custom form: missing form_id', array( 'email' => $email ) );
			return;
		}

		$form_map = apply_filters( 'jw_kit_custom_form_map', $this->form_map );

		// A form may carry its own tags (Kit tag IDs chosen in the block's
		// Inspector). When it does they REPLACE the form_map entry, so the page
		// decides its tags without a code change. Falls back to the map when
		// the form sends nothing, which keeps every existing caller working.
		$explicit = array_values( array_filter( array_map( 'trim', (array) ( $args['tags'] ?? array() ) ) ) );

		if ( ! empty( $explicit ) ) {
			$mapping = array(
				'tags'  => $explicit,
				'stage' => isset( $args['stage'] ) ? sanitize_text_field( $args['stage'] ) : ( $form_map[ $form_id ]['stage'] ?? '' ),
			);
		} elseif ( isset( $form_map[ $form_id ] ) ) {
			$mapping = $form_map[ $form_id ];
		} else {
			jw_kit_auto_tagger()->logger->debug( 'Custom form: form_id not in map and no explicit tags, skipping', array( 'form_id' => $form_id ) );
			return;
		}

		$event_key = 'custom_' . $form_id;
		$order_id  = isset( $args['order_id'] ) ? absint( $args['order_id'] ) : 0;
		$idem_key  = jw_kit_auto_tagger()->idempotency->get_key( $email, $event_key, $order_id );

		if ( jw_kit_auto_tagger()->idempotency->was_processed( $idem_key ) ) {
			jw_kit_auto_tagger()->logger->debug( 'Custom form: already processed (idempotent)', array( 'email' => $email, 'form_id' => $form_id ) );
			return;
		}

		$first_name = isset( $args['first_name'] ) ? sanitize_text_field( $args['first_name'] ) : '';
		$last_name  = isset( $args['last_name'] ) ? sanitize_text_field( $args['last_name'] ) : '';
		$fields     = array();
		if ( ! empty( $last_name ) ) {
			$fields['Last name'] = $last_name;
		}

		// Custom fields supplied by the caller (e.g. a per-subscriber download
		// link). These are set on the subscriber BEFORE tags are applied, so a
		// tag-triggered automation can already reference them in its email.
		foreach ( (array) ( $args['fields'] ?? array() ) as $field_key => $field_value ) {
			$field_key = sanitize_text_field( (string) $field_key );
			if ( '' !== $field_key ) {
				$fields[ $field_key ] = sanitize_text_field( (string) $field_value );
			}
		}

		$client = jw_kit_auto_tagger()->kit_client;
		if ( ! $client->is_configured() ) {
			jw_kit_auto_tagger()->logger->error( 'Custom form: Kit API not configured' );
			return;
		}

		$result = $client->process_tagging(
			$email,
			$mapping['tags'],
			$mapping['stage'],
			$first_name,
			$fields
		);

		if ( $result['success'] ) {
			jw_kit_auto_tagger()->idempotency->mark_processed( $idem_key );
			jw_kit_auto_tagger()->logger->info( 'Custom form: tagged successfully', array( 'email' => $email, 'form_id' => $form_id ) );
		} else {
			jw_kit_auto_tagger()->logger->error( 'Custom form: tagging failed', array( 'email' => $email, 'form_id' => $form_id, 'error' => isset( $result['error'] ) ? $result['error'] : '' ) );
		}
	}
}
