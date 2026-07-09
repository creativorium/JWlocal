<?php
/**
 * Admin settings page for JW Kit Auto Tagger.
 *
 * Settings under: WP Admin > Settings > JW Kit Auto Tagger
 *
 * @package JW_Kit_Auto_Tagger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_Kit_Admin_Settings
 */
class JW_Kit_Admin_Settings {

	/**
	 * Settings option group.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'jw_kit_auto_tagger_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_jw_kit_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_jw_kit_resync_order', array( $this, 'ajax_resync_order' ) );
		add_filter( 'plugin_action_links_' . JW_KIT_AUTO_TAGGER_BASENAME, array( $this, 'add_plugin_links' ) );
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'JW Kit Auto Tagger', 'jw-kit-auto-tagger' ),
			__( 'JW Kit Auto Tagger', 'jw-kit-auto-tagger' ),
			'manage_options',
			'jw-kit-auto-tagger',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			'jw_kit_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback'  => 'sanitize_text_field',
				'show_in_rest'       => false,
			)
		);

		foreach ( JW_KIT_TAG_KEYS as $tag_key ) {
			register_setting(
				self::OPTION_GROUP,
				'jw_kit_tag_' . $tag_key,
				array(
					'type'              => 'string',
					'sanitize_callback'  => array( $this, 'sanitize_tag_id' ),
					'show_in_rest'       => false,
				)
			);
		}

		register_setting(
			self::OPTION_GROUP,
			'jw_kit_elementor_enabled',
			array(
				'type'              => 'string',
				'default'           => '1',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'jw_kit_woo_enabled',
			array(
				'type'              => 'string',
				'default'           => '1',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'jw_kit_debug_enabled',
			array(
				'type'              => 'string',
				'default'           => '0',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			)
		);
	}

	/**
	 * Sanitize tag ID (must be numeric).
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public function sanitize_tag_id( $value ) {
		$value = sanitize_text_field( $value );
		if ( '' !== $value && ! is_numeric( $value ) ) {
			return '';
		}
		return $value;
	}

	/**
	 * Sanitize checkbox (1 or 0).
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public function sanitize_checkbox( $value ) {
		return '1' === $value ? '1' : '0';
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'settings_page_jw-kit-auto-tagger' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'jw-kit-admin',
			JW_KIT_AUTO_TAGGER_URL . 'assets/admin.css',
			array(),
			JW_KIT_AUTO_TAGGER_VERSION
		);

		wp_enqueue_script(
			'jw-kit-admin',
			JW_KIT_AUTO_TAGGER_URL . 'assets/admin.js',
			array( 'jquery' ),
			JW_KIT_AUTO_TAGGER_VERSION,
			true
		);

		wp_localize_script( 'jw-kit-admin', 'jwKitAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'jw_kit_admin' ),
			'i18n'    => array(
				'testing'   => __( 'Testing...', 'jw-kit-auto-tagger' ),
				'success'   => __( 'Connection successful!', 'jw-kit-auto-tagger' ),
				'error'    => __( 'Connection failed: ', 'jw-kit-auto-tagger' ),
				'resync'   => __( 'Re-syncing...', 'jw-kit-auto-tagger' ),
				'resyncOk' => __( 'Order re-synced successfully.', 'jw-kit-auto-tagger' ),
				'resyncErr'=> __( 'Re-sync failed: ', 'jw-kit-auto-tagger' ),
				'orderId'  => __( 'Please enter an order ID.', 'jw-kit-auto-tagger' ),
			),
		) );
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api_key = get_option( 'jw_kit_api_key', '' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php" id="jw-kit-settings-form">
				<?php settings_fields( self::OPTION_GROUP ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="jw_kit_api_key"><?php esc_html_e( 'Kit API Key', 'jw-kit-auto-tagger' ); ?></label>
						</th>
						<td>
							<input type="password" id="jw_kit_api_key" name="jw_kit_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" autocomplete="off" />
							<p class="description"><?php esc_html_e( 'Your Kit (ConvertKit) API key. Find it in Kit Settings > Advanced > API & Webhooks.', 'jw-kit-auto-tagger' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Test Connection', 'jw-kit-auto-tagger' ); ?></th>
						<td>
							<button type="button" id="jw-kit-test-connection" class="button button-secondary"><?php esc_html_e( 'Test Connection', 'jw-kit-auto-tagger' ); ?></button>
							<span id="jw-kit-test-result"></span>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Tag ID Mappings', 'jw-kit-auto-tagger' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Enter the Kit Tag ID for each tag. Create these tags in Kit first, then copy their IDs here. Tags without IDs will be skipped.', 'jw-kit-auto-tagger' ); ?></p>

				<table class="form-table" role="presentation">
					<?php foreach ( JW_KIT_TAG_KEYS as $tag_key ) : ?>
						<tr>
							<th scope="row">
								<label for="jw_kit_tag_<?php echo esc_attr( $tag_key ); ?>"><?php echo esc_html( $tag_key ); ?></label>
							</th>
							<td>
								<input type="text" id="jw_kit_tag_<?php echo esc_attr( $tag_key ); ?>" name="jw_kit_tag_<?php echo esc_attr( $tag_key ); ?>" value="<?php echo esc_attr( get_option( 'jw_kit_tag_' . $tag_key, '' ) ); ?>" class="small-text" placeholder="<?php esc_attr_e( 'Tag ID', 'jw-kit-auto-tagger' ); ?>" />
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Integrations', 'jw-kit-auto-tagger' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Elementor Forms', 'jw-kit-auto-tagger' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="jw_kit_elementor_enabled" value="0" />
								<input type="checkbox" name="jw_kit_elementor_enabled" value="1" <?php checked( get_option( 'jw_kit_elementor_enabled', '1' ), '1' ); ?> />
								<?php esc_html_e( 'Enable Elementor Forms integration', 'jw-kit-auto-tagger' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Requires Elementor Pro. Form names: "LM Roadmap", "LM IFVG", "Free Preview", "Webinar", or "Webinar Registration".', 'jw-kit-auto-tagger' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Custom PHP/HTML Forms', 'jw-kit-auto-tagger' ); ?></th>
						<td>
							<p class="description"><?php esc_html_e( 'Call this hook from your form handler after validating the submission:', 'jw-kit-auto-tagger' ); ?></p>
							<pre style="background:#f0f0f1;padding:12px;overflow:auto;max-width:600px;font-size:12px;"><code>do_action( 'jw_kit_tag_subscriber', array(
    'email'      => sanitize_email( $_POST['email'] ),
    'form_id'    => 'free_preview_gate_keep',
    'first_name' => sanitize_text_field( $_POST['first_name'] ?? '' ),
    'last_name'  => sanitize_text_field( $_POST['last_name'] ?? '' ),
) );</code></pre>
							<p class="description"><?php esc_html_e( 'Supported form_id values: free_preview_gate_keep, checkout_started, webinar_registration. Add more via jw_kit_custom_form_map filter.', 'jw-kit-auto-tagger' ); ?></p>
					<p class="description"><?php esc_html_e( 'Quick single-tag shortcut (no stage exclusivity): jw_kit_add_tag( $email, \'Webinar_Registrant\', $first_name );', 'jw-kit-auto-tagger' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'WooCommerce', 'jw-kit-auto-tagger' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="jw_kit_woo_enabled" value="0" />
								<input type="checkbox" name="jw_kit_woo_enabled" value="1" <?php checked( get_option( 'jw_kit_woo_enabled', '1' ), '1' ); ?> />
								<?php esc_html_e( 'Enable WooCommerce integration', 'jw-kit-auto-tagger' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Tags on checkout started, payment started, and order completed.', 'jw-kit-auto-tagger' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug Logging', 'jw-kit-auto-tagger' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="jw_kit_debug_enabled" value="0" />
								<input type="checkbox" name="jw_kit_debug_enabled" value="1" <?php checked( get_option( 'jw_kit_debug_enabled', '0' ), '1' ); ?> />
								<?php esc_html_e( 'Enable debug logging', 'jw-kit-auto-tagger' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Logs to WooCommerce log (if available) or PHP error_log.', 'jw-kit-auto-tagger' ); ?></p>
						</td>
					</tr>
				</table>

				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<h2 class="title"><?php esc_html_e( 'Manual Re-sync', 'jw-kit-auto-tagger' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="jw_kit_resync_order_id"><?php esc_html_e( 'Order ID', 'jw-kit-auto-tagger' ); ?></label>
						</th>
						<td>
							<input type="number" id="jw_kit_resync_order_id" class="small-text" min="1" placeholder="<?php esc_attr_e( 'Order ID', 'jw-kit-auto-tagger' ); ?>" />
							<button type="button" id="jw-kit-resync-order" class="button button-secondary"><?php esc_html_e( 'Re-sync Order to Kit', 'jw-kit-auto-tagger' ); ?></button>
							<span id="jw-kit-resync-result"></span>
							<p class="description"><?php esc_html_e( 'Re-apply Kit tags for a completed order. Use for orders that failed to sync.', 'jw-kit-auto-tagger' ); ?></p>
						</td>
					</tr>
				</table>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * AJAX: Test Kit API connection.
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'jw_kit_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'jw-kit-auto-tagger' ) ) );
		}

		$result = jw_kit_auto_tagger()->kit_client->test_connection();
		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		}
		wp_send_json_error( array( 'message' => $result['message'] ) );
	}

	/**
	 * AJAX: Re-sync order to Kit.
	 */
	public function ajax_resync_order() {
		check_ajax_referer( 'jw_kit_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'jw-kit-auto-tagger' ) ) );
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'jw-kit-auto-tagger' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Please enter an order ID.', 'jw-kit-auto-tagger' ) ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'jw-kit-auto-tagger' ) ) );
		}

		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Order has no valid billing email.', 'jw-kit-auto-tagger' ) ) );
		}

		$status = $order->get_status();
		$tags_to_add = array();
		$new_stage   = '';

		if ( in_array( $status, array( 'processing', 'completed' ), true ) ) {
			$tags_to_add = array( 'Bootcamp_Buyer', 'Stage_Buyer' );
			$new_stage   = 'Stage_Buyer';
		} else {
			$tags_to_add = array( 'Checkout_Started', 'Stage_High_Intent' );
			$new_stage   = 'Stage_High_Intent';
		}

		$first_name = $order->get_billing_first_name();
		$result     = jw_kit_auto_tagger()->kit_client->process_tagging( $email, $tags_to_add, $new_stage, $first_name );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => __( 'Order re-synced successfully.', 'jw-kit-auto-tagger' ) ) );
		}
		wp_send_json_error( array( 'message' => isset( $result['error'] ) ? $result['error'] : __( 'Re-sync failed.', 'jw-kit-auto-tagger' ) ) );
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_plugin_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=jw-kit-auto-tagger' ) ) . '">' . __( 'Settings', 'jw-kit-auto-tagger' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
