<?php
/**
 * Elementor Forms integration for JW Kit Auto Tagger.
 *
 * Hooks into elementor_pro/forms/new_record to capture form submissions.
 * Maps form names to Kit tags:
 * - LM Roadmap          -> LM_Roadmap + Stage_Cold
 * - LM IFVG             -> LM_IFVG + Stage_Cold
 * - Free Preview        -> Preview_Optin + Stage_Warm (exclusive)
 * - Webinar Registration -> Webinar_Registrant + Stage_Warm
 *
 * @package JW_Kit_Auto_Tagger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_Kit_Elementor_Hook
 */
class JW_Kit_Elementor_Hook {

	/**
	 * Form name to tags mapping.
	 *
	 * @var array<string, array{ tags: string[], stage: string }>
	 */
	private $form_map = array(
		'LM Roadmap'           => array(
			'tags'  => array( 'LM_Roadmap', 'Stage_Cold' ),
			'stage' => 'Stage_Cold',
		),
		'LM IFVG'              => array(
			'tags'  => array( 'LM_IFVG', 'Stage_Cold' ),
			'stage' => 'Stage_Cold',
		),
		'Free Preview'         => array(
			'tags'  => array( 'Preview_Optin', 'Stage_Warm' ),
			'stage' => 'Stage_Warm',
		),
		'Webinar'              => array(
			'tags'  => array( 'Webinar_Registrant', 'Stage_Warm' ),
			'stage' => 'Stage_Warm',
		),
		'Webinar Registration' => array(
			'tags'  => array( 'Webinar_Registrant', 'Stage_Warm' ),
			'stage' => 'Stage_Warm',
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Use new_record - fires after form actions run, we have full record.
		add_action( 'elementor_pro/forms/new_record', array( $this, 'on_form_submit' ), 10, 2 );
	}

	/**
	 * Handle form submission.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record Form record.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler Ajax handler.
	 */
	public function on_form_submit( $record, $ajax_handler ) {
		// Skip if in Elementor editor/preview.
		if ( $this->is_editor_or_preview() ) {
			return;
		}

		$form_name = $record->get_form_settings( 'form_name' );
		if ( empty( $form_name ) ) {
			$form_name = $record->get_form_settings( 'form_id' );
		}

		if ( ! isset( $this->form_map[ $form_name ] ) ) {
			jw_kit_auto_tagger()->logger->debug( 'Elementor form not in map, skipping', array( 'form_name' => $form_name ) );
			return;
		}

		$email = $this->extract_email( $record );
		if ( empty( $email ) || ! is_email( $email ) ) {
			jw_kit_auto_tagger()->logger->error( 'Elementor form: no valid email found', array( 'form_name' => $form_name ) );
			return;
		}

		$mapping   = $this->form_map[ $form_name ];
		$event_key = 'elementor_' . sanitize_key( str_replace( ' ', '_', $form_name ) );
		$idem_key  = jw_kit_auto_tagger()->idempotency->get_key( $email, $event_key );

		if ( jw_kit_auto_tagger()->idempotency->was_processed( $idem_key ) ) {
			jw_kit_auto_tagger()->logger->debug( 'Elementor form: already processed (idempotent)', array( 'email' => $email, 'form' => $form_name ) );
			return;
		}

		$first_name = $this->extract_first_name( $record );
		$last_name  = $this->extract_last_name( $record );
		$fields     = array();
		if ( ! empty( $last_name ) ) {
			$fields['Last name'] = $last_name;
		}

		$client = jw_kit_auto_tagger()->kit_client;
		if ( ! $client->is_configured() ) {
			jw_kit_auto_tagger()->logger->error( 'Elementor form: Kit API not configured' );
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
			jw_kit_auto_tagger()->logger->info( 'Elementor form: tagged successfully', array( 'email' => $email, 'form' => $form_name ) );
		} else {
			jw_kit_auto_tagger()->logger->error( 'Elementor form: tagging failed', array( 'email' => $email, 'form' => $form_name, 'error' => isset( $result['error'] ) ? $result['error'] : '' ) );
		}
	}

	/**
	 * Extract email from form record.
	 *
	 * Supports field ID "email" and fields with type=email.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record Form record.
	 * @return string Empty string if not found.
	 */
	private function extract_email( $record ) {
		// Try field ID "email" first.
		$fields = $record->get_field( array( 'id' => 'email' ) );
		if ( ! empty( $fields ) && is_array( $fields ) ) {
			$field = reset( $fields );
			if ( isset( $field['value'] ) && is_email( $field['value'] ) ) {
				return sanitize_email( $field['value'] );
			}
		}

		// Scan all fields: keyed by id, each has 'value' and possibly 'type'.
		$raw = $record->get( 'fields' );
		if ( is_array( $raw ) ) {
			// Direct id check.
			if ( isset( $raw['email']['value'] ) && is_email( $raw['email']['value'] ) ) {
				return sanitize_email( $raw['email']['value'] );
			}
			foreach ( $raw as $id => $field ) {
				$val = isset( $field['value'] ) ? $field['value'] : '';
				if ( ! is_email( $val ) ) {
					continue;
				}
				// Match by id or type.
				if ( 'email' === $id || ( isset( $field['type'] ) && 'email' === $field['type'] ) ) {
					return sanitize_email( $val );
				}
			}
		}

		return '';
	}

	/**
	 * Extract first name from form record.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record Form record.
	 * @return string
	 */
	private function extract_first_name( $record ) {
		$raw = $record->get( 'fields' );
		if ( ! is_array( $raw ) ) {
			return '';
		}

		// Try first_name, name.
		$ids = array( 'first_name', 'name' );
		foreach ( $ids as $id ) {
			if ( isset( $raw[ $id ]['value'] ) ) {
				return sanitize_text_field( $raw[ $id ]['value'] );
			}
		}

		// Scan for type=text with name containing "first".
		foreach ( $raw as $field ) {
			if ( isset( $field['type'] ) && 'text' === $field['type'] && isset( $field['value'] ) ) {
				$label = isset( $field['title'] ) ? strtolower( $field['title'] ) : '';
				if ( false !== strpos( $label, 'first' ) || false !== strpos( $label, 'name' ) ) {
					return sanitize_text_field( $field['value'] );
				}
			}
		}

		return '';
	}

	/**
	 * Extract last name from form record.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record Form record.
	 * @return string
	 */
	private function extract_last_name( $record ) {
		$raw = $record->get( 'fields' );
		if ( ! is_array( $raw ) ) {
			return '';
		}

		if ( isset( $raw['last_name']['value'] ) ) {
			return sanitize_text_field( $raw['last_name']['value'] );
		}

		foreach ( $raw as $field ) {
			if ( isset( $field['type'] ) && 'text' === $field['type'] && isset( $field['value'] ) ) {
				$label = isset( $field['title'] ) ? strtolower( $field['title'] ) : '';
				if ( false !== strpos( $label, 'last' ) ) {
					return sanitize_text_field( $field['value'] );
				}
			}
		}

		return '';
	}

	/**
	 * Check if we're in Elementor editor or preview mode.
	 *
	 * @return bool
	 */
	private function is_editor_or_preview() {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$plugin = \Elementor\Plugin::$instance;
			if ( isset( $plugin->editor ) && $plugin->editor->is_edit_mode() ) {
				return true;
			}
			if ( isset( $plugin->preview ) && $plugin->preview->is_preview_mode() ) {
				return true;
			}
		}
		return false;
	}
}
