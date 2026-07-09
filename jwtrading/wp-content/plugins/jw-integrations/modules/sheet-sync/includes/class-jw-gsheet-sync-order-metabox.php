<?php
/**
 * Order metabox for JW WooCommerce Google Sheet Sync.
 *
 * @package JW_GSheet_Sync
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_GSheet_Sync_Order_Metabox
 */
class JW_GSheet_Sync_Order_Metabox {

	/**
	 * Nonce action for resend.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'jw_gsheet_resend';

	/**
	 * Nonce name.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'jw_gsheet_resend_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'wp_ajax_jw_gsheet_resend_order', array( $this, 'ajax_resend_order' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add metabox to order edit screen.
	 */
	public function add_metabox() {
		$screens = array( 'shop_order' );

		// HPOS (High-Performance Order Storage) support - WooCommerce 7.1+
		if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) ) {
			try {
				$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class );
				if ( $controller && $controller->custom_orders_table_usage_is_enabled() ) {
					$screens[] = wc_get_page_screen_id( 'shop-order' );
				}
			} catch ( Exception $e ) {
				// Fallback to shop_order only.
			}
		}

		foreach ( array_unique( $screens ) as $screen ) {
			add_meta_box(
				'jw_gsheet_sync_metabox',
				__( 'Google Sheet Sync', 'jw-gsheet-sync' ),
				array( $this, 'render_metabox' ),
				$screen,
				'side',
				'default'
			);
		}
	}

	/**
	 * Enqueue scripts for order edit page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		global $post;

		$is_order_edit = in_array( $hook, array( 'post.php', 'post-new.php' ), true )
			&& $post
			&& 'shop_order' === get_post_type( $post );

		// HPOS: check for order list/edit screen.
		$is_order_screen = strpos( $hook, 'woocommerce_page_wc-orders' ) !== false
			|| ( $post && 'shop_order' === ( $post->post_type ?? '' ) );

		if ( ! $is_order_edit && ! $is_order_screen ) {
			return;
		}

		wp_enqueue_style(
			'jw-gsheet-sync-admin',
			JW_GSHEET_SYNC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			JW_GSHEET_SYNC_VERSION
		);
		wp_enqueue_script(
			'jw-gsheet-sync-admin',
			JW_GSHEET_SYNC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			JW_GSHEET_SYNC_VERSION,
			true
		);
		wp_localize_script( 'jw-gsheet-sync-admin', 'jwGsheetSync', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
		) );
	}

	/**
	 * Render metabox content.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order object.
	 */
	public function render_metabox( $post_or_order ) {
		$order = $this->get_order_from_context( $post_or_order );
		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Order not found.', 'jw-gsheet-sync' ) . '</p>';
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to view this.', 'jw-gsheet-sync' ) . '</p>';
			return;
		}

		$sent     = $order->get_meta( JW_GSHEET_META_SENT );
		$sent_at  = $order->get_meta( JW_GSHEET_META_SENT_AT );
		$response = $order->get_meta( JW_GSHEET_META_RESPONSE );
		$order_id = $order->get_id();

		?>
		<div id="jw-gsheet-sync-metabox" data-order-id="<?php echo esc_attr( (string) $order_id ); ?>">
			<p>
				<strong><?php esc_html_e( 'Status:', 'jw-gsheet-sync' ); ?></strong>
				<span class="jw-gsheet-status <?php echo 'yes' === $sent ? 'jw-gsheet-success' : 'jw-gsheet-pending'; ?>">
					<?php echo 'yes' === $sent ? esc_html__( 'Sent', 'jw-gsheet-sync' ) : esc_html__( 'Not sent', 'jw-gsheet-sync' ); ?>
				</span>
			</p>
			<p class="jw-gsheet-sent-at-row" <?php echo ! $sent_at ? 'style="display:none;"' : ''; ?>>
				<strong><?php esc_html_e( 'Last sync:', 'jw-gsheet-sync' ); ?></strong><br>
				<span class="jw-gsheet-sent-at"><?php echo esc_html( $sent_at ); ?></span>
			</p>
			<p class="jw-gsheet-response-row" <?php echo ! $response ? 'style="display:none;"' : ''; ?>>
				<strong><?php esc_html_e( 'Response:', 'jw-gsheet-sync' ); ?></strong><br>
				<span class="jw-gsheet-response"><?php echo esc_html( $response ); ?></span>
			</p>
			<p>
				<button type="button" id="jw-gsheet-resend-btn" class="button button-secondary">
					<?php esc_html_e( 'Resend to Google Sheet', 'jw-gsheet-sync' ); ?>
				</button>
				<span id="jw-gsheet-resend-spinner" class="spinner" style="float: none; margin: 0 0 0 5px; display: none;"></span>
			</p>
			<div id="jw-gsheet-resend-message" style="margin-top: 8px; display: none;"></div>
		</div>
		<?php
	}

	/**
	 * Get order from post or order context (supports HPOS).
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order.
	 * @return WC_Order|null
	 */
	private function get_order_from_context( $post_or_order ) {
		if ( $post_or_order instanceof WC_Order ) {
			return $post_or_order;
		}
		if ( $post_or_order instanceof WP_Post ) {
			return wc_get_order( $post_or_order->ID );
		}
		if ( is_numeric( $post_or_order ) ) {
			return wc_get_order( (int) $post_or_order );
		}
		// HPOS fallback: order ID may be in request.
		if ( isset( $_GET['id'] ) && absint( $_GET['id'] ) > 0 ) {
			return wc_get_order( absint( $_GET['id'] ) );
		}
		return null;
	}

	/**
	 * Handle AJAX resend request.
	 */
	public function ajax_resend_order() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'jw-gsheet-sync' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'jw-gsheet-sync' ) ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'jw-gsheet-sync' ) ) );
		}

		$order_sync = JW_GSheet_Sync::instance()->get_order_sync();
		$result     = $order_sync->resend_order( $order );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message'   => $result['message'],
				'sent'      => 'yes',
				'sent_at'   => $order->get_meta( JW_GSHEET_META_SENT_AT ),
				'response'  => $order->get_meta( JW_GSHEET_META_RESPONSE ),
			) );
		}

		wp_send_json_error( array( 'message' => $result['message'] ) );
	}
}
