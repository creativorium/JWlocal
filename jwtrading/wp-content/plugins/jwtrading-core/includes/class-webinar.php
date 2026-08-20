<?php
defined( 'ABSPATH' ) || exit;

/**
 * Standalone funnel landing pages — the webinar/free-preview steps AND the lead
 * magnet pages that feed them. They all share the same chrome and the same
 * "reachable by direct link only" rule.
 *
 * Why these exist: the lead magnets deliver their PDF by email, so the visitor
 * used to leave the site the moment they submitted the form and went cold in an
 * inbox they barely read. These pages keep them on-site: the opt-in redirects
 * straight here, a short VSL warms them up, and one CTA sends them on to the
 * sales page. The PDF still arrives by email, it just stops being the end of
 * the journey.
 *
 *   Opt-in form  →  /webinar-…/  (VSL)  →  sales page
 *
 * Standalone by design: no site nav and no real footer, just the logo and a
 * one-line disclaimer, so the only ways out are the CTA or the back button.
 *
 * No settings page — these are pure landing pages. Adjust the slug list with
 * the `jwt/webinar_slugs` filter.
 */
class JWT_Webinar {

	public static function init() {
		// Logo-only header, shared with the mentorship funnel steps. BOTH filters
		// are needed: `minimal_header` picks the stripped header branch in
		// header.php, `funnel_chrome` then drops its CTA and centres the logo.
		// Setting only the second leaves the full site nav rendering.
		add_filter( 'jwt/minimal_header', array( __CLASS__, 'filter_minimal_header' ) );
		add_filter( 'jwt/funnel_chrome', array( __CLASS__, 'filter_funnel_chrome' ) );
		// Disclaimer-only footer — narrower than the funnel footer, which also
		// carries the refund + privacy links.
		add_filter( 'jwt/bare_footer', array( __CLASS__, 'filter_bare_footer' ) );

		// Reachable by URL only — same treatment as the Phase 2 pages.
		add_filter( 'jwt/hidden_page_slugs', array( __CLASS__, 'hide_from_discovery' ) );
	}

	/** Webinar / free-preview pages. */
	public static function webinar_slugs(): array {
		return self::clean( apply_filters( 'jwt/webinar_slugs', array( 'webinar-trading', 'webinar-prop-firm' ) ) );
	}

	/**
	 * Lead magnet opt-in pages that feed the webinars. Client's instruction for
	 * the Trader Roadmap page: unlisted, no header CTA, centred logo — i.e. the
	 * same standalone treatment as the webinar pages it now redirects into.
	 *
	 * NOTE: /ifvg-strategy/ is deliberately NOT here — it has not been through
	 * the same decision yet.
	 */
	public static function leadmagnet_slugs(): array {
		return self::clean( apply_filters( 'jwt/leadmagnet_slugs', array( 'trader-roadmap' ) ) );
	}

	/** Every page this module owns the chrome for. */
	public static function slugs(): array {
		return array_values( array_unique( array_merge( self::webinar_slugs(), self::leadmagnet_slugs() ) ) );
	}

	protected static function clean( $slugs ): array {
		return array_values( array_filter( array_map( 'trim', (array) $slugs ) ) );
	}

	public static function is_webinar_page(): bool {
		if ( ! function_exists( 'is_page' ) || ! is_page() ) {
			return false;
		}
		$slugs = self::slugs();
		return ! empty( $slugs ) && is_page( $slugs );
	}

	public static function filter_minimal_header( $on ) {
		return $on || self::is_webinar_page();
	}

	public static function filter_funnel_chrome( $on ) {
		return $on || self::is_webinar_page();
	}

	public static function filter_bare_footer( $on ) {
		return $on || self::is_webinar_page();
	}

	/** Ads drive traffic here directly; nothing should index them. */
	public static function hide_from_discovery( $slugs ) {
		return array_merge( (array) $slugs, self::slugs() );
	}
}
