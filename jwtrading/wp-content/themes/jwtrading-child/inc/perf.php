<?php
/**
 * Front-end performance — purely subtractive. Drops assets that the current
 * page does not use. On non-WooCommerce pages (blog, home, content pages) no
 * WooCommerce UI is ever rendered, so removing Woo CSS/JS there has zero visual
 * or functional effect; it just trims render-blocking requests.
 *
 * @package jwtrading-child
 */

defined( 'ABSPATH' ) || exit;

/** True only on pages that actually render WooCommerce (shop/product/cart/checkout/account). */
function jwt_is_woo_context() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}
	return ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
		|| ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() )
		|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() );
}

/** Dequeue WooCommerce + Duitku scripts on non-Woo pages. */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_admin() || jwt_is_woo_context() ) {
			return;
		}

		// Cart-fragments fires an AJAX request on every page load; we have no
		// mini-cart in the custom header, so it is pure overhead off-Woo.
		wp_dequeue_script( 'wc-cart-fragments' );

		// Duitku's DOM tweak is only needed on the checkout payment screen.
		wp_dequeue_script( 'duitku-dom-manipulate-js' );
	},
	99
);

/**
 * Dequeue WooCommerce / block / Kadence-woo stylesheets on non-Woo pages. Run on
 * wp_print_styles so it also catches the block styles (wc-blocks-style) that are
 * registered late. These only style WooCommerce UI, absent on these pages.
 */
add_action(
	'wp_print_styles',
	function () {
		if ( is_admin() || jwt_is_woo_context() ) {
			return;
		}
		foreach ( array(
			'woocommerce-general',
			'woocommerce-layout',
			'woocommerce-smallscreen',
			'wc-blocks-style',
			'wc-blocks-vendors-style',
			'kadence-woocommerce',
		) as $jwt_handle ) {
			wp_dequeue_style( $jwt_handle );
		}
	},
	100
);

/**
 * Drop jquery-migrate (legacy-API compat shim) on the front end — one fewer
 * render-blocking request. Revert this block if a plugin needs deprecated jQuery.
 */
add_action(
	'wp_default_scripts',
	function ( $scripts ) {
		if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
			return;
		}
		$deps = $scripts->registered['jquery']->deps;
		$scripts->registered['jquery']->deps = array_values( array_diff( $deps, array( 'jquery-migrate' ) ) );
	}
);
