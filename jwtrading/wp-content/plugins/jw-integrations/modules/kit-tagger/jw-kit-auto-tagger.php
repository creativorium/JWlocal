<?php
/**
 * Plugin Name: JW Kit Auto Tagger
 * Plugin URI: https://creativorium.com
 * Description: Integrates Elementor Forms and WooCommerce with Kit (ConvertKit) marketing platform. Automatically tags subscribers based on form submissions, checkout events, and purchases.
 * Version: 2.1.5
 * Author: Abetnego
 * Author URI: https://creativorium.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jw-kit-auto-tagger
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package JW_Kit_Auto_Tagger
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'JW_KIT_AUTO_TAGGER_VERSION', '2.1.5' );
define( 'JW_KIT_AUTO_TAGGER_FILE', __FILE__ );
define( 'JW_KIT_AUTO_TAGGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'JW_KIT_AUTO_TAGGER_URL', plugin_dir_url( __FILE__ ) );
define( 'JW_KIT_AUTO_TAGGER_BASENAME', plugin_basename( __FILE__ ) );

// Stage tags for exclusivity (only one at a time).
define( 'JW_KIT_STAGE_TAGS', array( 'Stage_Cold', 'Stage_Warm', 'Stage_High_Intent', 'Stage_Buyer' ) );

// All tag keys used in the plugin.
define( 'JW_KIT_TAG_KEYS', array(
	'LM_Roadmap',
	'LM_IFVG',
	'Preview_Optin',
	'Checkout_Started',
	'Bootcamp_Buyer',
	'Webinar_Registrant',
	'Stage_Cold',
	'Stage_Warm',
	'Stage_High_Intent',
	'Stage_Buyer',
) );

/**
 * Main plugin class.
 */
final class JW_Kit_Auto_Tagger {

	/**
	 * Single instance.
	 *
	 * @var JW_Kit_Auto_Tagger|null
	 */
	private static $instance = null;

	/**
	 * Kit API client.
	 *
	 * @var JW_Kit_Client|null
	 */
	public $kit_client = null;

	/**
	 * Logger instance.
	 *
	 * @var JW_Kit_Logger|null
	 */
	public $logger = null;

	/**
	 * Idempotency handler.
	 *
	 * @var JW_Kit_Idempotency|null
	 */
	public $idempotency = null;

	/**
	 * Get instance.
	 *
	 * @return JW_Kit_Auto_Tagger
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		require_once JW_KIT_AUTO_TAGGER_PATH . 'includes/class-logger.php';
		require_once JW_KIT_AUTO_TAGGER_PATH . 'includes/class-idempotency.php';
		require_once JW_KIT_AUTO_TAGGER_PATH . 'includes/class-kit-client.php';
		require_once JW_KIT_AUTO_TAGGER_PATH . 'includes/class-admin-settings.php';
		require_once JW_KIT_AUTO_TAGGER_PATH . 'includes/class-elementor-hook.php';
		require_once JW_KIT_AUTO_TAGGER_PATH . 'includes/class-woo-hook.php';
		require_once JW_KIT_AUTO_TAGGER_PATH . 'includes/class-custom-form-hook.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 5 );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Fired on plugins_loaded.
	 */
	public function on_plugins_loaded() {
		$this->logger      = new JW_Kit_Logger();
		$this->idempotency = new JW_Kit_Idempotency();
		$this->kit_client  = new JW_Kit_Client( $this->logger );

		// Admin settings (always load).
		new JW_Kit_Admin_Settings();

		// Elementor integration (only if enabled and Elementor Pro active).
		if ( $this->is_elementor_enabled() ) {
			new JW_Kit_Elementor_Hook();
		}

		// WooCommerce integration (only if enabled and WooCommerce active).
		if ( $this->is_woo_enabled() ) {
			new JW_Kit_Woo_Hook();
		}

		// Custom PHP/HTML form integration (always loaded).
		new JW_Kit_Custom_Form_Hook();
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'jw-kit-auto-tagger',
			false,
			dirname( JW_KIT_AUTO_TAGGER_BASENAME ) . '/languages'
		);
	}

	/**
	 * Check if Elementor integration is enabled and Elementor Pro is active.
	 *
	 * @return bool
	 */
	public function is_elementor_enabled() {
		$enabled = get_option( 'jw_kit_elementor_enabled', '1' );
		return '1' === $enabled && defined( 'ELEMENTOR_PRO_VERSION' );
	}

	/**
	 * Check if WooCommerce integration is enabled and WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_woo_enabled() {
		$enabled = get_option( 'jw_kit_woo_enabled', '1' );
		return '1' === $enabled && class_exists( 'WooCommerce' );
	}

	/**
	 * Check if debug logging is enabled.
	 *
	 * @return bool
	 */
	public function is_debug_enabled() {
		return '1' === get_option( 'jw_kit_debug_enabled', '0' );
	}

	/**
	 * Get option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_option( $key, $default = '' ) {
		return get_option( 'jw_kit_' . $key, $default );
	}
}

/**
 * Returns the main plugin instance.
 *
 * @return JW_Kit_Auto_Tagger
 */
function jw_kit_auto_tagger() {
	return JW_Kit_Auto_Tagger::instance();
}

/**
 * Convenience helper: add a single tag to a subscriber by email.
 *
 * Upserts the subscriber first, then applies the tag. No stage exclusivity
 * is enforced — call process_tagging() directly if you need that.
 *
 * Usage:
 *   jw_kit_add_tag( 'user@example.com', 'Webinar_Registrant' );
 *   jw_kit_add_tag( 'user@example.com', 'Webinar_Registrant', 'John' );
 *
 * @param string $email      Subscriber email.
 * @param string $tag_key    Tag key as defined in JW_KIT_TAG_KEYS (e.g. 'Webinar_Registrant').
 * @param string $first_name Optional first name to upsert on the subscriber.
 * @param array  $fields     Optional custom fields (e.g. array( 'Last name' => 'Doe' )).
 * @return array{ success: bool, error?: string }
 */
function jw_kit_add_tag( $email, $tag_key, $first_name = '', $fields = array() ) {
	$client = jw_kit_auto_tagger()->kit_client;

	if ( ! $client->is_configured() ) {
		return array( 'success' => false, 'error' => __( 'Kit API is not configured.', 'jw-kit-auto-tagger' ) );
	}

	$tag_id = $client->get_tag_id( $tag_key );
	if ( ! $tag_id ) {
		return array( 'success' => false, 'error' => sprintf( __( 'Tag ID not configured for key: %s', 'jw-kit-auto-tagger' ), $tag_key ) );
	}

	// Upsert subscriber first.
	$upsert = $client->upsert_subscriber( $email, $first_name, $fields );
	if ( ! $upsert['success'] ) {
		return $upsert;
	}

	return $client->add_tag_by_email( $email, $tag_id );
}

// Bootstrap.
jw_kit_auto_tagger();
