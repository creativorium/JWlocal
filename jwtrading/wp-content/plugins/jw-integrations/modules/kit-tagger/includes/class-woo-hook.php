<?php
/**
 * WooCommerce integration for JW Kit Auto Tagger.
 *
 * Events:
 * A) Checkout Started - when order is created during checkout
 * B) Payment Started - when order goes to pending/on-hold
 * C) Purchase Completed - when order is processing or completed
 *
 * @package JW_Kit_Auto_Tagger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_Kit_Woo_Hook
 */
class JW_Kit_Woo_Hook {

	/**
	 * Event: Checkout started.
	 *
	 * @var string
	 */
	const EVENT_CHECKOUT_STARTED = 'woo_checkout_started';

	/**
	 * Event: Payment started.
	 *
	 * @var string
	 */
	const EVENT_PAYMENT_STARTED = 'woo_payment_started';

	/**
	 * Event: Purchase completed.
	 *
	 * @var string
	 */
	const EVENT_PURCHASE_COMPLETED = 'woo_purchase_completed';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Scheduled action handler (for async tagging).
		add_action( 'jw_kit_process_woo_tagging', array( $this, 'execute_tagging' ), 10, 1 );

		// A) Checkout started - when order is created (has billing email).
		add_action( 'woocommerce_checkout_order_created', array( $this, 'on_checkout_order_created' ), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'on_block_checkout_processed' ), 10, 1 );

		// B) Payment started - order goes to pending/on-hold (payment attempted).
		add_action( 'woocommerce_order_status_pending', array( $this, 'on_order_pending' ), 10, 2 );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'on_order_on_hold' ), 10, 2 );
		add_action( 'woocommerce_order_status_failed', array( $this, 'on_order_failed' ), 10, 2 );

		// C) Purchase completed - processing or completed.
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_order_processing' ), 10, 2 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ), 10, 2 );
	}

	/**
	 * Handle checkout order created (classic checkout).
	 *
	 * @param WC_Order $order Order object.
	 */
	public function on_checkout_order_created( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->maybe_tag_checkout_started( $order );
	}

	/**
	 * Handle block checkout processed.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function on_block_checkout_processed( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->maybe_tag_checkout_started( $order );
	}

	/**
	 * Tag for checkout started (Checkout_Started + Stage_High_Intent).
	 *
	 * @param WC_Order $order Order object.
	 */
	private function maybe_tag_checkout_started( $order ) {
		$email = $order->get_billing_email();
		if ( empty( $email ) || ! is_email( $email ) ) {
			return;
		}

		$order_id = $order->get_id();
		$idem_key = jw_kit_auto_tagger()->idempotency->get_key( $email, self::EVENT_CHECKOUT_STARTED, $order_id );

		if ( jw_kit_auto_tagger()->idempotency->was_processed( $idem_key ) ) {
			jw_kit_auto_tagger()->logger->debug( 'Woo checkout started: already processed', array( 'order_id' => $order_id, 'email' => $email ) );
			return;
		}

		$this->schedule_tagging( array(
			'email'       => $email,
			'tags_to_add' => array( 'Checkout_Started', 'Stage_High_Intent' ),
			'new_stage'   => 'Stage_High_Intent',
			'first_name'  => $order->get_billing_first_name(),
			'event_type'  => self::EVENT_CHECKOUT_STARTED,
			'order_id'    => $order_id,
		) );
	}

	/**
	 * Handle order status pending.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public function on_order_pending( $order_id, $order = null ) {
		$order = $order ?: wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->maybe_tag_payment_started( $order );
	}

	/**
	 * Handle order status on-hold.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public function on_order_on_hold( $order_id, $order = null ) {
		$order = $order ?: wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->maybe_tag_payment_started( $order );
	}

	/**
	 * Handle order status failed (payment was attempted).
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public function on_order_failed( $order_id, $order = null ) {
		$order = $order ?: wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->maybe_tag_payment_started( $order );
	}

	/**
	 * Tag for payment started (same as checkout started).
	 *
	 * @param WC_Order $order Order object.
	 */
	private function maybe_tag_payment_started( $order ) {
		$email = $order->get_billing_email();
		if ( empty( $email ) || ! is_email( $email ) ) {
			return;
		}

		$order_id = $order->get_id();
		$idem_key = jw_kit_auto_tagger()->idempotency->get_key( $email, self::EVENT_PAYMENT_STARTED, $order_id );

		if ( jw_kit_auto_tagger()->idempotency->was_processed( $idem_key ) ) {
			jw_kit_auto_tagger()->logger->debug( 'Woo payment started: already processed', array( 'order_id' => $order_id, 'email' => $email ) );
			return;
		}

		$this->schedule_tagging( array(
			'email'       => $email,
			'tags_to_add' => array( 'Checkout_Started', 'Stage_High_Intent' ),
			'new_stage'   => 'Stage_High_Intent',
			'first_name'  => $order->get_billing_first_name(),
			'event_type'  => self::EVENT_PAYMENT_STARTED,
			'order_id'    => $order_id,
		) );
	}

	/**
	 * Handle order status processing.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public function on_order_processing( $order_id, $order = null ) {
		$order = $order ?: wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->maybe_tag_purchase_completed( $order );
	}

	/**
	 * Handle order status completed.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public function on_order_completed( $order_id, $order = null ) {
		$order = $order ?: wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->maybe_tag_purchase_completed( $order );
	}

	/**
	 * Tag for purchase completed (Bootcamp_Buyer + Stage_Buyer).
	 *
	 * @param WC_Order $order Order object.
	 */
	private function maybe_tag_purchase_completed( $order ) {
		$email = $order->get_billing_email();
		if ( empty( $email ) || ! is_email( $email ) ) {
			return;
		}

		$order_id = $order->get_id();
		$idem_key = jw_kit_auto_tagger()->idempotency->get_key( $email, self::EVENT_PURCHASE_COMPLETED, $order_id );

		if ( jw_kit_auto_tagger()->idempotency->was_processed( $idem_key ) ) {
			jw_kit_auto_tagger()->logger->debug( 'Woo purchase completed: already processed', array( 'order_id' => $order_id, 'email' => $email ) );
			return;
		}

		$this->schedule_tagging( array(
			'email'       => $email,
			'tags_to_add' => array( 'Bootcamp_Buyer', 'Stage_Buyer' ),
			'new_stage'   => 'Stage_Buyer',
			'first_name'  => $order->get_billing_first_name(),
			'event_type'  => self::EVENT_PURCHASE_COMPLETED,
			'order_id'    => $order_id,
		) );
	}

	/**
	 * Schedule tagging in background (async) or run immediately.
	 *
	 * Uses wp_schedule_single_event when Action Scheduler or WP-Cron is available.
	 *
	 * @param array $args Tagging arguments.
	 */
	private function schedule_tagging( $args ) {
		$args = wp_parse_args( $args, array(
			'email'       => '',
			'tags_to_add' => array(),
			'new_stage'   => '',
			'first_name'  => '',
			'event_type'  => '',
			'order_id'    => 0,
		) );

		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			return;
		}

		// Use Action Scheduler if available (WooCommerce ships with it).
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				time(),
				'jw_kit_process_woo_tagging',
				array( $args ),
				'jw_kit_auto_tagger'
			);
		} else {
			wp_schedule_single_event( time(), 'jw_kit_process_woo_tagging', array( $args ) );
		}
	}

	/**
	 * Execute tagging (called by scheduled action).
	 *
	 * @param array $args Tagging arguments.
	 */
	public function execute_tagging( $args ) {
		$email       = isset( $args['email'] ) ? $args['email'] : '';
		$tags_to_add = isset( $args['tags_to_add'] ) ? $args['tags_to_add'] : array();
		$new_stage   = isset( $args['new_stage'] ) ? $args['new_stage'] : '';
		$first_name  = isset( $args['first_name'] ) ? $args['first_name'] : '';
		$event_type  = isset( $args['event_type'] ) ? $args['event_type'] : '';
		$order_id    = isset( $args['order_id'] ) ? (int) $args['order_id'] : 0;

		if ( empty( $email ) || ! is_email( $email ) ) {
			return;
		}

		$idem_key = jw_kit_auto_tagger()->idempotency->get_key( $email, $event_type, $order_id );
		if ( jw_kit_auto_tagger()->idempotency->was_processed( $idem_key ) ) {
			return;
		}

		$client = jw_kit_auto_tagger()->kit_client;
		if ( ! $client->is_configured() ) {
			jw_kit_auto_tagger()->logger->error( 'Woo tagging: Kit API not configured', array( 'event' => $event_type ) );
			return;
		}

		$result = $client->process_tagging( $email, $tags_to_add, $new_stage, $first_name );

		if ( $result['success'] ) {
			jw_kit_auto_tagger()->idempotency->mark_processed( $idem_key );
			jw_kit_auto_tagger()->logger->info( 'Woo tagging: success', array( 'email' => $email, 'event' => $event_type, 'order_id' => $order_id ) );
		} else {
			jw_kit_auto_tagger()->logger->error( 'Woo tagging: failed', array( 'email' => $email, 'event' => $event_type, 'error' => isset( $result['error'] ) ? $result['error'] : '' ) );
		}
	}
}
