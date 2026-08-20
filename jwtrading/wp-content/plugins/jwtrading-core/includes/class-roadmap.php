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

		// Hand the synced Kit tags to the block editor so the opt-in blocks can
		// offer them as checkboxes instead of anyone typing IDs.
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_tag_data' ), 20 );

		// Map the roadmap form_id to Kit tags (filterable — adjust to your tags).
		add_filter( 'jw_kit_custom_form_map', array( __CLASS__, 'kit_form_map' ) );
	}

	/** Add lead-magnet form_id → tags mappings for the Kit tagger. */
	public static function kit_form_map( $map ) {
		if ( ! is_array( $map ) ) {
			return $map;
		}
		// Use the tag keys the Kit tagger already exposes/configures
		// (JW_KIT_TAG_KEYS): LM_Roadmap / LM_IFVG — same as the original
		// "LM Roadmap" / "LM IFVG" opt-in forms.
		$defaults = array(
			// Roadmap_Ebook is ADDED alongside the legacy LM_Roadmap, not swapped
			// in — per the client, the old tag stays on new opt-ins too.
			//
			// ⚠️ This only achieves "new leads skip the old email" if that old
			// email is a Kit BROADCAST (a one-time send, which never reaches
			// subscribers tagged afterwards). If it is an AUTOMATION/sequence
			// triggered by LM_Roadmap, new leads WILL enter it — in that case
			// drop 'LM_Roadmap' from this array.
			'trader_roadmap' => array(
				'tags'  => array( 'LM_Roadmap', 'Roadmap_Ebook', 'Stage_Warm' ),
				'stage' => 'Stage_Warm',
			),
			'ifvg_strategy'  => array(
				'tags'  => array( 'LM_IFVG', 'Stage_Warm' ),
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

	/**
	 * Expose the Kit tag catalogue to the editor as window.jwtKitTags.
	 * Empty until someone presses "Sync Tags from Kit" in the tagger settings.
	 */
	public static function editor_tag_data() {
		$tags = class_exists( 'JW_Kit_Admin_Settings' ) ? JW_Kit_Admin_Settings::synced_tags() : array();

		$ebooks = class_exists( 'JWT_Ebook' ) ? JWT_Ebook::choices() : array();

		wp_add_inline_script(
			'jwt-blocks-editor',
			'window.jwtKitTags = ' . wp_json_encode( (object) $tags ) . ';'
			. 'window.jwtEbooks = ' . wp_json_encode( (object) $ebooks ) . ';',
			'before'
		);
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

		// Tags chosen on the block itself win; with none set this stays empty and
		// the tagger falls back to the form_id mapping below.
		$tag_ids = array_values( array_filter( array_map( 'absint', (array) ( $_POST['tags'] ?? array() ) ) ) );

		// Private, expiring download link for THIS subscriber. Handed to Kit as a
		// custom field so the email can use {{ subscriber.<field> }} — the link is
		// never the same twice, so a shared one burns the sharer's own quota.
		// Empty until a PDF has been secured in Mentorship -> E-Book Links.
		$fields = array();
		$ebook  = isset( $_POST['ebook'] ) ? sanitize_key( wp_unslash( $_POST['ebook'] ) ) : '';

		if ( '' !== $ebook && class_exists( 'JWT_Ebook' ) ) {
			$book = JWT_Ebook::get( $ebook );
			$link = JWT_Ebook::issue_link( $email, $ebook );

			if ( '' !== $link && ! empty( $book['kit_field'] ) ) {
				$fields[ $book['kit_field'] ] = $link;
			}
		}

		do_action(
			'jw_kit_tag_subscriber',
			array(
				'email'      => $email,
				'form_id'    => $form_id,
				'first_name' => $first,
				'last_name'  => $last,
				'tags'       => $tag_ids,
				'fields'     => $fields,
			)
		);

		wp_send_json_success(
			array(
				'pdf' => esc_url_raw( apply_filters( 'jwt/roadmap_pdf_url', '' ) ),
			)
		);
	}
}
