<?php
/**
 * Main plugin class for JW WooCommerce Google Sheet Sync.
 *
 * @package JW_GSheet_Sync
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_GSheet_Sync
 */
class JW_GSheet_Sync {

	/**
	 * Single instance of the class.
	 *
	 * @var JW_GSheet_Sync|null
	 */
	private static $instance = null;

	/**
	 * Settings instance.
	 *
	 * @var JW_GSheet_Sync_Settings|null
	 */
	private $settings = null;

	/**
	 * Order sync instance.
	 *
	 * @var JW_GSheet_Sync_Order_Sync|null
	 */
	private $order_sync = null;

	/**
	 * Order metabox instance.
	 *
	 * @var JW_GSheet_Sync_Order_Metabox|null
	 */
	private $order_metabox = null;

	/**
	 * Get single instance.
	 *
	 * @return JW_GSheet_Sync
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
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
		require_once JW_GSHEET_SYNC_PLUGIN_DIR . 'includes/class-jw-gsheet-sync-settings.php';
		require_once JW_GSHEET_SYNC_PLUGIN_DIR . 'includes/class-jw-gsheet-sync-payload.php';
		require_once JW_GSHEET_SYNC_PLUGIN_DIR . 'includes/class-jw-gsheet-sync-webhook.php';
		require_once JW_GSHEET_SYNC_PLUGIN_DIR . 'includes/class-jw-gsheet-sync-order-sync.php';
		require_once JW_GSHEET_SYNC_PLUGIN_DIR . 'includes/class-jw-gsheet-sync-order-metabox.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		$this->settings     = new JW_GSheet_Sync_Settings();
		$this->order_sync   = new JW_GSheet_Sync_Order_Sync();
		$this->order_metabox = new JW_GSheet_Sync_Order_Metabox();
	}

	/**
	 * Get settings instance.
	 *
	 * @return JW_GSheet_Sync_Settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get order sync instance.
	 *
	 * @return JW_GSheet_Sync_Order_Sync
	 */
	public function get_order_sync() {
		return $this->order_sync;
	}
}
