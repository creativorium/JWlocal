<?php
defined( 'ABSPATH' ) || exit;

/**
 * Performance tweaks for WooCommerce.
 */

// Disable cart fragments (AJAX cart refresh) everywhere except cart/checkout.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_cart() && ! is_checkout() ) {
		wp_dequeue_script( 'wc-cart-fragments' );
	}
}, 99 );

// Remove Woo styles/scripts on non-shop pages.
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'is_woocommerce' ) && ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
	}
}, 99 );
