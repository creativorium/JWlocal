<?php
/**
 * Logger class for JW Kit Auto Tagger.
 *
 * Uses WC_Logger when WooCommerce is available, otherwise error_log.
 *
 * @package JW_Kit_Auto_Tagger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_Kit_Logger
 */
class JW_Kit_Logger {

	/**
	 * WC_Logger instance when WooCommerce is active.
	 *
	 * @var WC_Logger|null
	 */
	private $wc_logger = null;

	/**
	 * Log source/context.
	 *
	 * @var string
	 */
	private $source = 'jw-kit-auto-tagger';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_logger' ) ) {
			$this->wc_logger = wc_get_logger();
		}
	}

	/**
	 * Log a message.
	 *
	 * @param string $level   Log level: debug, info, notice, warning, error.
	 * @param string $message Message to log.
	 * @param array  $context Optional context data.
	 */
	public function log( $level, $message, $context = array() ) {
		$plugin = jw_kit_auto_tagger();
		if ( 'debug' === $level && ! $plugin->is_debug_enabled() ) {
			return;
		}

		$formatted = $this->format_message( $message, $context );

		if ( $this->wc_logger ) {
			$this->wc_logger->log( $level, $formatted, array( 'source' => $this->source ) );
		} else {
			error_log( sprintf( '[%s] [%s] %s', strtoupper( $level ), $this->source, $formatted ) );
		}
	}

	/**
	 * Log debug message.
	 *
	 * @param string $message Message.
	 * @param array  $context Optional context.
	 */
	public function debug( $message, $context = array() ) {
		$this->log( 'debug', $message, $context );
	}

	/**
	 * Log info message.
	 *
	 * @param string $message Message.
	 * @param array  $context Optional context.
	 */
	public function info( $message, $context = array() ) {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Log error message.
	 *
	 * @param string $message Message.
	 * @param array  $context Optional context.
	 */
	public function error( $message, $context = array() ) {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Format message with context.
	 *
	 * @param string $message Message.
	 * @param array  $context Context data.
	 * @return string
	 */
	private function format_message( $message, $context = array() ) {
		if ( empty( $context ) ) {
			return $message;
		}
		return $message . ' | Context: ' . wp_json_encode( $context );
	}
}
