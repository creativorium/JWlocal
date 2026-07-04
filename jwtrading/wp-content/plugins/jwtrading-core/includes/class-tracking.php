<?php
defined( 'ABSPATH' ) || exit;

/**
 * GTM + GA4 tracking — ported from the live site.
 * GA4 purchase event fires on the thank-you page with per-browser dedup.
 */
class JWT_Tracking {

	const GTM_ID = 'GTM-5726L37C';
	const GA4_ID = 'G-43GCQ182TL';

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'gtm_head' ), 1 );
		add_action( 'wp_body_open', array( __CLASS__, 'gtm_noscript' ) );
		add_action( 'wp_head', array( __CLASS__, 'gtag' ) );
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'purchase_datalayer' ), 10 );
	}

	public static function gtm_head() {
		if ( is_admin() ) {
			return;
		}
		?>
		<!-- Google Tag Manager -->
		<script>
		(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','<?php echo esc_js( self::GTM_ID ); ?>');
		</script>
		<!-- End Google Tag Manager -->
		<?php
	}

	public static function gtm_noscript() {
		if ( is_admin() ) {
			return;
		}
		?>
		<!-- Google Tag Manager (noscript) -->
		<noscript>
			<iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( self::GTM_ID ); ?>"
			height="0" width="0" style="display:none;visibility:hidden"></iframe>
		</noscript>
		<!-- End Google Tag Manager (noscript) -->
		<?php
	}

	public static function gtag() {
		if ( is_admin() ) {
			return;
		}
		?>
		<!-- Google tag (gtag.js) -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( self::GA4_ID ); ?>"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());

		  gtag('config', '<?php echo esc_js( self::GA4_ID ); ?>');
		</script>
		<?php
	}

	/** GA4 purchase event with sessionStorage dedup per order. */
	public static function purchase_datalayer( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$items    = array();
		$contents = array();

		foreach ( $order->get_items() as $item ) {
			$product    = $item->get_product();
			$product_id = $product ? $product->get_id() : $item->get_product_id();
			$item_price = (float) $item->get_subtotal() / max( 1, (int) $item->get_quantity() );

			$items[] = array(
				'item_id'   => (string) $product_id,
				'item_name' => $item->get_name(),
				'price'     => $item_price,
				'quantity'  => (int) $item->get_quantity(),
			);

			$contents[] = array(
				'id'         => (string) $product_id,
				'quantity'   => (int) $item->get_quantity(),
				'item_price' => $item_price,
			);
		}

		$order_total    = (float) $order->get_total();
		$currency       = $order->get_currency();
		$transaction_id = (string) $order->get_order_number();
		$shipping       = (float) $order->get_shipping_total();
		$tax            = (float) $order->get_total_tax();
		?>
		<script>
		(function() {
		  var dedupKey = 'ga4_purchase_' + <?php echo wp_json_encode( $transaction_id ); ?>;
		  if (sessionStorage.getItem(dedupKey)) return;
		  sessionStorage.setItem(dedupKey, '1');

		  var purchaseData = {
			transaction_id: <?php echo wp_json_encode( $transaction_id ); ?>,
			value:          <?php echo wp_json_encode( $order_total ); ?>,
			currency:       <?php echo wp_json_encode( $currency ); ?>,
			tax:            <?php echo wp_json_encode( $tax ); ?>,
			shipping:       <?php echo wp_json_encode( $shipping ); ?>,
			items:          <?php echo wp_json_encode( $items ); ?>
		  };

		  window.dataLayer = window.dataLayer || [];
		  window.dataLayer.push({
			event:     'purchase',
			ecommerce: purchaseData,
			contents:  <?php echo wp_json_encode( $contents ); ?>
		  });

		  if (typeof gtag === 'function') {
			gtag('event', 'purchase', purchaseData);
		  }
		})();
		</script>
		<?php
	}
}
