<?php
defined( 'ABSPATH' ) || exit;

/**
 * Settings page: JWTrading → Settings (under WooCommerce menu).
 * Fields: Kit API key, default tag, Sheets webhook URL + secret, enable toggles.
 * Also shows the last 20 sync log rows for quick debugging.
 */
class JWT_Admin {

	public static function init() {
		// This page only configures Core's own Kit/Sheets sync, which is now handled
		// by the JW Integrations plugin. Hide it (same filter as the dispatch) so it
		// doesn't cause confusion. Flip jwt/enable_core_order_sync to true to bring it
		// back if you ever switch order syncing back to Core.
		if ( ! apply_filters( 'jwt/enable_core_order_sync', false ) ) {
			return;
		}
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'JWTrading Sync', 'jwtrading' ),
			__( 'JWTrading Sync', 'jwtrading' ),
			'manage_woocommerce',
			'jwtrading-sync',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		$fields = array(
			'jwt_kit_enabled'        => 'sanitize_text_field',
			'jwt_kit_api_key'        => 'sanitize_text_field',
			'jwt_kit_default_tag'    => 'sanitize_text_field',
			'jwt_sheets_enabled'     => 'sanitize_text_field',
			'jwt_sheets_webhook_url' => 'esc_url_raw',
			'jwt_sheets_secret'      => 'sanitize_text_field',
		);

		foreach ( $fields as $option => $sanitize ) {
			register_setting( 'jwt_sync_settings', $option, array( 'sanitize_callback' => $sanitize ) );
		}
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'JWTrading Sync Settings', 'jwtrading' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'jwt_sync_settings' ); ?>

				<h2><?php esc_html_e( 'Kit (ConvertKit)', 'jwtrading' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Kit sync', 'jwtrading' ); ?></th>
						<td><input type="checkbox" name="jwt_kit_enabled" value="yes" <?php checked( get_option( 'jwt_kit_enabled', 'yes' ), 'yes' ); ?>></td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_kit_api_key"><?php esc_html_e( 'Kit API Key (v4)', 'jwtrading' ); ?></label></th>
						<td><input type="password" class="regular-text" id="jwt_kit_api_key" name="jwt_kit_api_key" value="<?php echo esc_attr( get_option( 'jwt_kit_api_key', '' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_kit_default_tag"><?php esc_html_e( 'Default Tag ID', 'jwtrading' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="jwt_kit_default_tag" name="jwt_kit_default_tag" value="<?php echo esc_attr( get_option( 'jwt_kit_default_tag', '' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Applied to every buyer. Per-product tags: add product meta _jwt_kit_tag_id.', 'jwtrading' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Google Sheets', 'jwtrading' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Sheets sync', 'jwtrading' ); ?></th>
						<td><input type="checkbox" name="jwt_sheets_enabled" value="yes" <?php checked( get_option( 'jwt_sheets_enabled', 'yes' ), 'yes' ); ?>></td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_sheets_webhook_url"><?php esc_html_e( 'Apps Script Web App URL', 'jwtrading' ); ?></label></th>
						<td><input type="url" class="large-text" id="jwt_sheets_webhook_url" name="jwt_sheets_webhook_url" value="<?php echo esc_attr( get_option( 'jwt_sheets_webhook_url', '' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="jwt_sheets_secret"><?php esc_html_e( 'Shared Secret', 'jwtrading' ); ?></label></th>
						<td>
							<input type="password" class="regular-text" id="jwt_sheets_secret" name="jwt_sheets_secret" value="<?php echo esc_attr( get_option( 'jwt_sheets_secret', '' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Must match the secret validated inside the Apps Script.', 'jwtrading' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Recent Sync Log', 'jwtrading' ); ?></h2>
			<?php self::render_log(); ?>
		</div>
		<?php
	}

	protected static function render_log() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . JWT_Sync_Log::table() . ' ORDER BY updated_at DESC LIMIT 20' );

		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'No sync activity yet.', 'jwtrading' ) . '</p>';
			return;
		}
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Order', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Target', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Status', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Attempts', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Response', 'jwtrading' ); ?></th>
					<th><?php esc_html_e( 'Updated', 'jwtrading' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $row->order_id . '&action=edit' ) ); ?>">#<?php echo (int) $row->order_id; ?></a></td>
						<td><?php echo esc_html( $row->target ); ?></td>
						<td><?php echo esc_html( $row->status ); ?></td>
						<td><?php echo (int) $row->attempts; ?></td>
						<td><code><?php echo esc_html( mb_substr( (string) $row->response, 0, 120 ) ); ?></code></td>
						<td><?php echo esc_html( $row->updated_at ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
