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

		if ( ! isset( $form_map[ $form_id ] ) ) {
			jw_kit_auto_tagger()->logger->debug( 'Custom form: form_id not in map, skipping', array( 'form_id' => $form_id ) );
			return;
		}

		$mapping   = $form_map[ $form_id ];
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
