<?php
defined( 'ABSPATH' ) || exit;

/**
 * Restricts sensitive plugins to the owner account only.
 *
 * For every wp-admin user EXCEPT the allowed logins, the guarded plugins are:
 *  - removed from the Plugins list screen,
 *  - stripped from the admin menu (top-level + submenus),
 *  - blocked on direct URL access (403),
 *  - protected from activate/deactivate/delete requests.
 *
 * Escape hatch: define( 'JWT_PLUGIN_GUARD_DISABLE', true ) in wp-config.php
 * (e.g. if the owner account is ever renamed/removed).
 */
class JWT_Plugin_Guard {

	/** Only these user logins can see/use the guarded plugins. */
	const ALLOWED_LOGINS = array( 'it.cular' );

	/** Plugin basenames to guard (folder/file as shown by `wp plugin list`). */
	const GUARDED_PLUGINS = array(
		'loginpress/loginpress.php',
		'wp-security-audit-log/wp-security-audit-log.php',
		'duitku-social-payment-gateway/woocommerce-gateway-duitku.php',
		'disable-xml-rpc-api/disable-xml-rpc-api.php',
		'all-in-one-wp-security-and-firewall/wp-security.php',
		'all-in-one-wp-migration/all-in-one-wp-migration.php',
	);

	/** Admin page slug prefixes owned by the guarded plugins. */
	const GUARDED_PAGE_PREFIXES = array(
		'loginpress',           // LoginPress (loginpress-settings, loginpress-...)
		'wsal-',                // WP Activity Log
		'aiowpsec',             // All-In-One Security
		'ai1wm_',               // All-in-One WP Migration (export/import/backups)
		'disable-xml-rpc-api',  // Disable XML-RPC-API settings
	);

	public static function init() {
		if ( defined( 'JWT_PLUGIN_GUARD_DISABLE' ) && JWT_PLUGIN_GUARD_DISABLE ) {
			return;
		}

		add_filter( 'all_plugins', array( __CLASS__, 'filter_plugin_list' ) );
		add_action( 'admin_menu', array( __CLASS__, 'strip_menus' ), 999 );
		add_action( 'admin_init', array( __CLASS__, 'block_direct_access' ) );
	}

	/**
	 * Is the current user allowed to manage the guarded plugins?
	 */
	protected static function is_allowed(): bool {
		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		$allowed = apply_filters( 'jwt/plugin_guard_allowed_logins', self::ALLOWED_LOGINS );

		return in_array( $user->user_login, $allowed, true );
	}

	/**
	 * Does an admin page slug belong to a guarded plugin?
	 */
	protected static function is_guarded_slug( string $slug ): bool {
		foreach ( self::GUARDED_PAGE_PREFIXES as $prefix ) {
			if ( 0 === strpos( $slug, $prefix ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Hide guarded plugins from the Plugins list screen.
	 */
	public static function filter_plugin_list( $plugins ) {
		if ( self::is_allowed() ) {
			return $plugins;
		}

		foreach ( self::GUARDED_PLUGINS as $basename ) {
			unset( $plugins[ $basename ] );
		}

		return $plugins;
	}

	/**
	 * Remove guarded top-level and submenu entries from the admin menu.
	 */
	public static function strip_menus() {
		if ( self::is_allowed() ) {
			return;
		}

		global $menu, $submenu;

		if ( is_array( $menu ) ) {
			foreach ( $menu as $index => $item ) {
				if ( isset( $item[2] ) && self::is_guarded_slug( (string) $item[2] ) ) {
					unset( $menu[ $index ] );
				}
			}
		}

		if ( is_array( $submenu ) ) {
			foreach ( $submenu as $parent => $items ) {
				if ( self::is_guarded_slug( (string) $parent ) ) {
					unset( $submenu[ $parent ] );
					continue;
				}
				foreach ( (array) $items as $index => $item ) {
					if ( isset( $item[2] ) && self::is_guarded_slug( (string) $item[2] ) ) {
						unset( $submenu[ $parent ][ $index ] );
					}
				}
			}
		}
	}

	/**
	 * 403 on direct URL access to guarded plugin pages, their Duitku settings
	 * section, and plugin management actions targeting guarded basenames.
	 */
	public static function block_direct_access() {
		if ( self::is_allowed() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only routing checks.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( '' !== $page && self::is_guarded_slug( $page ) ) {
			wp_die( esc_html__( 'Halaman ini dibatasi.', 'jwtrading' ), 403 );
		}

		// Duitku has no page of its own — its settings live in Woo → Payments.
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		if ( 'wc-settings' === $page && 0 === strpos( $section, 'duitku' ) ) {
			wp_die( esc_html__( 'Halaman ini dibatasi.', 'jwtrading' ), 403 );
		}

		// Block activate/deactivate/delete attempts on guarded basenames.
		$target = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';

		if ( '' !== $target && in_array( $target, self::GUARDED_PLUGINS, true ) ) {
			wp_die( esc_html__( 'Plugin ini dibatasi.', 'jwtrading' ), 403 );
		}
		// phpcs:enable
	}
}
