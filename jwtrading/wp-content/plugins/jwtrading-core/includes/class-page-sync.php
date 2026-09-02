<?php
defined( 'ABSPATH' ) || exit;

/**
 * Push theme patterns into page content, from wp-admin.
 *
 * WHY THIS EXISTS
 * Page copy lives in wp_posts.post_content, not in the theme. The pattern files
 * are only the source we copy FROM, so deploying them changes nothing on their
 * own -- the copying has to happen against the database. EasyWP has no SSH and
 * no WP-CLI, so that used to mean uploading a one-shot script over SFTP,
 * running it in a browser and remembering to delete it. That left a script that
 * rewrites page content sitting on a live server, and it was easy to drop in
 * the wrong directory and get a 404.
 *
 * This does the same job from Tools -> Sync Halaman: it ships with the normal
 * deploy, is gated behind manage_options and a nonce, and nothing has to be
 * uploaded or cleaned up afterwards.
 *
 * Writes with $wpdb->update on purpose. wp_update_post() runs content through
 * wp_unslash(), which mangles block markup -- em dashes, &amp;, and the escaped
 * quotes inside block attribute JSON.
 */
class JWT_Page_Sync {

	const CAP   = 'manage_options';
	const SLUG  = 'jwt-page-sync';
	const NONCE = 'jwt_page_sync';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_' . self::NONCE, array( __CLASS__, 'handle' ) );
	}

	/**
	 * page path => pattern filename, in the active theme's /patterns/ folder.
	 *
	 * Pages are matched by PATH, never by id: ids differ between Local and live.
	 * Filterable so a page can be added without touching this file.
	 */
	public static function map(): array {
		return (array) apply_filters(
			'jwt/page_sync_map',
			array(
				'mentorship'             => 'mentorship-optin.php',
				'mentorship/application' => 'mentorship-application.php',
				'mentorship/thank-you'   => 'mentorship-thankyou.php',
				'prop-firm'              => 'prop-firm.php',
			)
		);
	}

	public static function menu() {
		add_management_page(
			__( 'Sync Halaman', 'jwtrading' ),
			__( 'Sync Halaman', 'jwtrading' ),
			self::CAP,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/** Pattern body = everything after the PHP header's closing tag. */
	protected static function pattern_body( string $file ) {
		$path = get_stylesheet_directory() . '/patterns/' . $file;

		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'missing_pattern', __( 'Pattern tidak ditemukan.', 'jwtrading' ) );
		}

		$raw   = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$parts = explode( "?>\n", $raw, 2 );
		$body  = isset( $parts[1] ) ? rtrim( $parts[1] ) : '';

		// Never let a malformed pattern blank a live page.
		if ( '' === $body || false === strpos( $body, '<!-- wp:' ) ) {
			return new WP_Error( 'empty_pattern', __( 'Pattern kosong / tanpa blok.', 'jwtrading' ) );
		}

		return $body;
	}

	/** Current state of one row, for the table and for the sync itself. */
	protected static function inspect( string $path, string $file ): array {
		$page = get_page_by_path( $path );
		$body = self::pattern_body( $file );

		return array(
			'path'    => $path,
			'file'    => $file,
			'page'    => $page,
			'body'    => is_wp_error( $body ) ? '' : $body,
			'error'   => is_wp_error( $body ) ? $body->get_error_message() : '',
			'missing' => ! $page,
			'same'    => $page && ! is_wp_error( $body ) && (string) $page->post_content === $body,
		);
	}

	public static function handle() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Tidak diizinkan.', 'jwtrading' ) );
		}
		check_admin_referer( self::NONCE );

		global $wpdb;

		$only    = sanitize_text_field( wp_unslash( $_POST['only'] ?? '' ) );
		$updated = 0;
		$skipped = 0;
		$failed  = array();

		foreach ( self::map() as $path => $file ) {
			if ( '' !== $only && $only !== $path ) {
				continue;
			}

			$row = self::inspect( $path, $file );

			if ( '' !== $row['error'] ) {
				$failed[] = $path . ': ' . $row['error'];
				continue;
			}
			if ( $row['missing'] ) {
				$failed[] = $path . ': ' . __( 'halaman belum ada.', 'jwtrading' );
				continue;
			}
			if ( $row['same'] ) {
				$skipped++;
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ok = $wpdb->update(
				$wpdb->posts,
				array(
					'post_content'      => $row['body'],
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', 1 ),
				),
				array( 'ID' => $row['page']->ID )
			);

			clean_post_cache( $row['page']->ID );

			// Read back: a silent truncation here would be invisible otherwise.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$after = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $row['page']->ID ) );

			if ( false === $ok || (string) $after !== $row['body'] ) {
				$failed[] = $path . ': ' . __( 'tulis gagal / isi tidak sama.', 'jwtrading' );
				continue;
			}

			$updated++;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::SLUG,
					'updated' => $updated,
					'skipped' => $skipped,
					'failed'  => rawurlencode( implode( ' | ', $failed ) ),
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$updated = isset( $_GET['updated'] ) ? (int) $_GET['updated'] : -1; // phpcs:ignore WordPress.Security.NonceVerification
		$skipped = isset( $_GET['skipped'] ) ? (int) $_GET['skipped'] : 0;  // phpcs:ignore WordPress.Security.NonceVerification
		$failed  = isset( $_GET['failed'] ) ? sanitize_text_field( wp_unslash( $_GET['failed'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sync Halaman', 'jwtrading' ); ?></h1>

			<?php if ( $updated > -1 ) : ?>
				<div class="notice notice-<?php echo $failed ? 'error' : 'success'; ?>">
					<p>
						<?php
						printf(
							/* translators: 1: pages updated, 2: pages already current. */
							esc_html__( '%1$d halaman diperbarui, %2$d sudah sama.', 'jwtrading' ),
							(int) $updated,
							(int) $skipped
						);
						?>
						<?php if ( $failed ) : ?>
							<br><strong><?php esc_html_e( 'Gagal:', 'jwtrading' ); ?></strong> <?php echo esc_html( $failed ); ?>
						<?php endif; ?>
					</p>
				</div>
				<?php if ( $updated > 0 ) : ?>
					<div class="notice notice-warning">
						<p><?php esc_html_e( 'Halaman sudah diperbarui. Bersihkan cache (Autoptimize + EasyWP), atau cek dengan ?cb=123 di akhir URL.', 'jwtrading' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<p class="description" style="max-width:46em">
				<?php esc_html_e( 'Isi halaman tersimpan di database, bukan di tema. Deploy hanya mengirim file pattern-nya, jadi halaman baru berubah setelah disalin di sini.', 'jwtrading' ); ?>
			</p>

			<table class="widefat striped" style="max-width:60em;margin-top:1em">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Halaman', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Pattern', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Status', 'jwtrading' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( self::map() as $path => $file ) : ?>
					<?php
					$row   = self::inspect( $path, $file );
					$state = '';
					if ( '' !== $row['error'] ) {
						$state = '<span style="color:#b32d2e">' . esc_html( $row['error'] ) . '</span>';
					} elseif ( $row['missing'] ) {
						$state = '<span style="color:#b32d2e">' . esc_html__( 'Halaman belum ada', 'jwtrading' ) . '</span>';
					} elseif ( $row['same'] ) {
						$state = '<span style="color:#007017">' . esc_html__( 'Sudah sama', 'jwtrading' ) . '</span>';
					} else {
						$state = '<span style="color:#996800">' . sprintf(
							/* translators: 1: current size, 2: new size, in bytes. */
							esc_html__( 'Berbeda (%1$s -> %2$s byte)', 'jwtrading' ),
							number_format_i18n( strlen( (string) $row['page']->post_content ) ),
							number_format_i18n( strlen( $row['body'] ) )
						) . '</span>';
					}
					?>
					<tr>
						<td>
							<code>/<?php echo esc_html( $path ); ?>/</code>
							<?php if ( $row['page'] ) : ?>
								<br><a href="<?php echo esc_url( get_permalink( $row['page'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'lihat', 'jwtrading' ); ?></a>
							<?php endif; ?>
						</td>
						<td><code><?php echo esc_html( $file ); ?></code></td>
						<td><?php echo $state; // phpcs:ignore WordPress.Security.EscapeOutput -- built above. ?></td>
						<td>
							<?php if ( ! $row['missing'] && '' === $row['error'] && ! $row['same'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( self::NONCE ); ?>
									<input type="hidden" name="action" value="<?php echo esc_attr( self::NONCE ); ?>">
									<input type="hidden" name="only" value="<?php echo esc_attr( $path ); ?>">
									<?php submit_button( __( 'Sync', 'jwtrading' ), 'secondary', 'submit', false ); ?>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1.5em">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::NONCE ); ?>">
				<?php submit_button( __( 'Sync semua halaman', 'jwtrading' ) ); ?>
			</form>
		</div>
		<?php
	}
}

JWT_Page_Sync::init();
