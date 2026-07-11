<?php
defined( 'ABSPATH' ) || exit;

/**
 * Trader Roadmap opt-in — lead form on the /trader-roadmap/ page.
 *
 * Mirrors the preview-gate contract (first_name / last_name / email →
 * `jw_kit_tag_subscriber`) so the same Kit/Thinkific integration picks it up,
 * then returns the roadmap PDF URL for the front end to open.
 */
class JWT_Roadmap {

	public static function init() {
		add_action( 'wp_ajax_jwt_roadmap_optin', array( __CLASS__, 'optin_handler' ) );
		add_action( 'wp_ajax_nopriv_jwt_roadmap_optin', array( __CLASS__, 'optin_handler' ) );

		// Map the roadmap form_id to Kit tags (filterable — adjust to your tags).
		add_filter( 'jw_kit_custom_form_map', array( __CLASS__, 'kit_form_map' ) );
	}

	/** Add lead-magnet form_id → tags mappings for the Kit tagger. */
	public static function kit_form_map( $map ) {
		if ( ! is_array( $map ) ) {
			return $map;
		}
		$defaults = array(
			'trader_roadmap' => array(
				'tags'  => array( 'Roadmap_Optin', 'Stage_Warm' ),
				'stage' => 'Stage_Warm',
			),
			'ifvg_strategy'  => array(
				'tags'  => array( 'IFVG_Optin', 'Stage_Warm' ),
				'stage' => 'Stage_Warm',
			),
		);
		foreach ( $defaults as $key => $val ) {
			if ( ! isset( $map[ $key ] ) ) {
				$map[ $key ] = $val;
			}
		}
		return $map;
	}

	/** AJAX: validate, notify admin, tag in Kit, return the PDF URL. */
	public static function optin_handler() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'jwt_roadmap_optin' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid session. Refresh page.' ), 403 );
		}

		$first   = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last    = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : 'trader_roadmap';

		if ( empty( $first ) || empty( $email ) ) {
			wp_send_json_error( array( 'message' => 'Mohon isi nama dan email.' ), 400 );
		}
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Email tidak valid.' ), 400 );
		}

		$subject  = '[JW Roadmap] Trader Roadmap Opt-in';
		$message  = "New Trader Roadmap opt-in:\n\n";
		$message .= "Name: {$first} {$last}\n";
		$message .= "Email: {$email}\n";
		$message .= 'Time: ' . current_time( 'mysql' ) . "\n";
		wp_mail( get_option( 'admin_email' ), $subject, $message );

		// Kit tagging (mapped in kit_form_map / filterable via jw_kit_custom_form_map).
		do_action(
			'jw_kit_tag_subscriber',
			array(
				'email'      => $email,
				'form_id'    => $form_id,
				'first_name' => $first,
				'last_name'  => $last,
			)
		);

		wp_send_json_success(
			array(
				'pdf' => esc_url_raw( apply_filters( 'jwt/roadmap_pdf_url', '' ) ),
			)
		);
	}
}
