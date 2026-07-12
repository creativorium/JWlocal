<?php
defined( 'ABSPATH' ) || exit;

/**
 * Restrict the WordPress "Pages" admin to a single user (login: it.cular).
 * Everyone else — administrators included — has the Pages menu removed, the
 * "+ New → Page" node removed, and direct access to page list/edit/new screens
 * blocked (redirected to the dashboard).
 */
class JWT_Page_Access {

	const ALLOWED_LOGIN = 'it.cular';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'hide_menu' ), 999 );
		add_action( 'current_screen', array( __CLASS__, 'block_screens' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'hide_admin_bar' ), 999 );
	}

	/** Allow only the designated user (by login), filterable for flexibility. */
	protected static function is_allowed() {
		$user  = wp_get_current_user();
		$login = ( $user && $user->exists() ) ? $user->user_login : '';
		return (bool) apply_filters( 'jwt/pages_allowed_for_user', self::ALLOWED_LOGIN === $login, $login );
	}

	public static function hide_menu() {
		if ( self::is_allowed() ) {
			return;
		}
		remove_menu_page( 'edit.php?post_type=page' );
	}

	/** Block the page list, page editor, and "add new page" screens. */
	public static function block_screens( $screen ) {
		if ( ! $screen || self::is_allowed() ) {
			return;
		}
		if ( isset( $screen->post_type ) && 'page' === $screen->post_type ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	public static function hide_admin_bar( $bar ) {
		if ( self::is_allowed() ) {
			return;
		}
		$bar->remove_node( 'new-page' );
	}
}
