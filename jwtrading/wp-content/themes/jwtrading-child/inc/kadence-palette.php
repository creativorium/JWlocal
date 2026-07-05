<?php
defined( 'ABSPATH' ) || exit;

/**
 * Points Kadence's own Global Palette at our brand colors, instead of us
 * fighting its generated CSS with one-off overrides (see the .entry /
 * .entry-content-wrap saga in main.scss — that fix stays as a safety net,
 * but this is the actual root-cause fix). Every native Kadence /
 * WooCommerce / Gutenberg-core surface we haven't explicitly reskinned
 * (comments, pagination, tables, calendar widget, core Search block,
 * "has-X-color" editor swatches, WooCommerce notices) reads
 * `var(--global-paletteN)`, so setting the palette here is the single
 * source of truth — don't ALSO hardcode --global-paletteN overrides
 * elsewhere; change swatches here instead.
 *
 * Kadence's default palette assumes a light theme (dark text on a white
 * background); ours is inverted (near-white text on near-black), so the
 * text/background slots are flipped accordingly, not just recolored.
 *
 * Storage: the `kadence_global_palette` OPTION (not a theme_mod) — a JSON
 * blob with three interchangeable palettes ("palette", "second-palette",
 * "third-palette") plus an "active" key. We only populate "palette" (the
 * active one) with our real values; the two spares are filled identically
 * so switching "active" in the Customizer never lands on a half-set palette.
 */

/**
 * The 15 palette swatches, in Kadence's slug order.
 */
function jwt_kadence_palette_swatches(): array {
	return array(
		array(
			'slug'  => 'palette1',
			'name'  => 'Accent',
			'color' => '#7C4DFF',
		),
		array(
			'slug'  => 'palette2',
			'name'  => 'Accent — hover/alt',
			'color' => '#5B35C4',
		),
		array(
			'slug'  => 'palette3',
			'name'  => 'Strongest text',
			'color' => '#EEF1F6',
		),
		array(
			'slug'  => 'palette4',
			'name'  => 'Strong text',
			'color' => '#D7D4E3',
		),
		array(
			'slug'  => 'palette5',
			'name'  => 'Medium text',
			'color' => '#9AA3B2',
		),
		array(
			'slug'  => 'palette6',
			'name'  => 'Subtle text',
			'color' => '#5B6473',
		),
		array(
			'slug'  => 'palette7',
			'name'  => 'Subtle background',
			'color' => '#0C0A14',
		),
		array(
			'slug'  => 'palette8',
			'name'  => 'Lighter background',
			'color' => '#100E1A',
		),
		array(
			'slug'  => 'palette9',
			'name'  => 'Base background',
			'color' => '#08070E',
		),
		array(
			'slug'  => 'palette10',
			'name'  => 'Accent — complement',
			// Sentinel value (Kadence's own convention, not a real color):
			// keeps this slot on Kadence's auto oklch-derived complement of
			// palette1 forever, so it self-updates if palette1 ever changes
			// — leave it exactly as-is, do not replace with a literal hex.
			'color' => '#FfFfFf',
		),
		array(
			'slug'  => 'palette11',
			'name'  => 'Notice — success',
			'color' => '#3ECF8E',
		),
		array(
			'slug'  => 'palette12',
			'name'  => 'Notice — info',
			'color' => '#4DA3FF',
		),
		array(
			'slug'  => 'palette13',
			'name'  => 'Notice — alert',
			'color' => '#F5657A',
		),
		array(
			'slug'  => 'palette14',
			'name'  => 'Notice — warning',
			'color' => '#F79009',
		),
		array(
			'slug'  => 'palette15',
			'name'  => 'Rating',
			'color' => '#F5A524',
		),
	);
}

/**
 * Write the palette into Kadence's option. Safe to call more than once —
 * it always (re)asserts our definition; nothing else writes this option
 * except a human manually using the Customizer's Global Palette control.
 */
function jwt_set_kadence_palette(): void {
	$swatches = jwt_kadence_palette_swatches();

	update_option(
		'kadence_global_palette',
		wp_json_encode(
			array(
				'palette'        => $swatches,
				'second-palette' => $swatches,
				'third-palette'  => $swatches,
				'active'         => 'palette',
			)
		)
	);
}
add_action( 'after_switch_theme', 'jwt_set_kadence_palette' );
