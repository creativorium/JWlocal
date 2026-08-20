<?php
defined( 'ABSPATH' ) || exit;

/**
 * Per-subscriber e-book download links.
 *
 * The PDF used to sit at a public uploads URL, so one person could paste that
 * link into a Discord or WhatsApp group and everyone else skipped the opt-in.
 * Now the file lives in an unguessable folder that is never linked directly,
 * and each subscriber gets their OWN link carrying a token that expires and
 * has a download cap.
 *
 *   opt-in -> mint token -> pass the link to Kit as a custom field
 *          -> Kit email uses {{ subscriber.<field> }}
 *          -> /?jwt_ebook=<slug>&t=<token> validates and streams the file
 *
 * Honest limit: this protects the LINK, not the file. Anyone who downloads the
 * PDF can still forward the PDF itself; nothing server-side prevents that.
 * What it stops is one URL circulating indefinitely.
 *
 * EasyWP runs nginx with no config access, so "deny direct access to
 * /uploads/*.pdf" is not available to us — hiding the path replaces it.
 */
class JWT_Ebook {

	const OPT       = 'jwt_ebook';
	const DB_OPT    = 'jwt_ebook_db_version';
	const DB_VER    = '1';
	const QUERY_VAR = 'jwt_ebook';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_table' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve' ), 1 );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_jwt_ebook_secure', array( __CLASS__, 'handle_secure_file' ) );
		add_action( 'admin_post_jwt_ebook_create_field', array( __CLASS__, 'handle_create_field' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
	}

	// --- Settings ---------------------------------------------------------------

	public static function settings(): array {
		$defaults = array(
			'file'          => '',   // Randomised name on disk.
			'download_name' => 'ebook.pdf', // Friendly name the visitor sees.
			'expiry_days'   => 7,
			'max_downloads' => 5,
			'kit_field'     => 'roadmap_link',
			'slug'          => 'roadmap',
		);
		$saved = get_option( self::OPT, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	public static function register_settings() {
		register_setting(
			'jwt_ebook_group',
			self::OPT,
			array(
				'sanitize_callback' => static function ( $in ) {
					$in  = is_array( $in ) ? $in : array();
					$cur = self::settings();
					return array(
						// `file` is set only by handle_secure_file(), never posted,
						// so a bad path cannot be typed into it.
						'file'          => $cur['file'],
						'download_name' => $cur['download_name'],
						'slug'          => sanitize_key( $in['slug'] ?? 'roadmap' ),
						'kit_field'     => sanitize_key( $in['kit_field'] ?? 'roadmap_link' ),
						'expiry_days'   => max( 1, min( 365, (int) ( $in['expiry_days'] ?? 7 ) ) ),
						'max_downloads' => max( 1, min( 100, (int) ( $in['max_downloads'] ?? 5 ) ) ),
					);
				},
			)
		);
	}

	// --- Storage ----------------------------------------------------------------

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'jwt_ebook_tokens';
	}

	public static function create_table() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = self::table();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta(
			"CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			token VARCHAR(64) NOT NULL,
			email VARCHAR(191) NULL,
			slug VARCHAR(50) NOT NULL,
			downloads INT UNSIGNED NOT NULL DEFAULT 0,
			max_downloads INT UNSIGNED NOT NULL DEFAULT 5,
			expires_at DATETIME NOT NULL,
			created_at DATETIME NOT NULL,
			last_download_at DATETIME NULL,
			last_ip VARCHAR(100) NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY token (token),
			KEY email (email)
		) {$charset};"
		);

		update_option( self::DB_OPT, self::DB_VER );
	}

	public static function maybe_create_table() {
		if ( get_option( self::DB_OPT ) !== self::DB_VER ) {
			self::create_table();
		}
	}

	/**
	 * The private folder the PDF is moved into. Unguessable, created once, and
	 * never linked to directly.
	 */
	public static function private_dir(): string {
		$uploads = wp_upload_dir();
		$name    = get_option( 'jwt_ebook_dir', '' );

		if ( '' === $name ) {
			$name = 'jwt-private-' . wp_generate_password( 20, false, false );
			update_option( 'jwt_ebook_dir', $name, false );
		}

		$path = trailingslashit( $uploads['basedir'] ) . $name;

		if ( ! is_dir( $path ) ) {
			wp_mkdir_p( $path );
			// Stops directory listing on servers that have it enabled.
			file_put_contents( trailingslashit( $path ) . 'index.php', "<?php // Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		return $path;
	}

	// --- Issuing a link ----------------------------------------------------------

	/**
	 * Mint a download link for one subscriber.
	 *
	 * @param string $email Subscriber email.
	 * @return string Full URL, or '' when no file has been secured yet.
	 */
	public static function issue_link( string $email ): string {
		$s = self::settings();

		if ( '' === $s['file'] ) {
			return '';
		}

		global $wpdb;
		self::maybe_create_table();

		$token = wp_generate_password( 48, false, false );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			self::table(),
			array(
				'token'         => $token,
				'email'         => sanitize_email( $email ),
				'slug'          => $s['slug'],
				'downloads'     => 0,
				'max_downloads' => (int) $s['max_downloads'],
				'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + ( (int) $s['expiry_days'] * DAY_IN_SECONDS ) ),
				'created_at'    => current_time( 'mysql' ),
			)
		);

		return add_query_arg(
			array(
				self::QUERY_VAR => $s['slug'],
				't'             => $token,
			),
			home_url( '/' )
		);
	}

	// --- Serving the file --------------------------------------------------------

	public static function maybe_serve() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ self::QUERY_VAR ] ) || empty( $_GET['t'] ) ) {
			return;
		}
		$token = sanitize_text_field( wp_unslash( $_GET['t'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE token = %s', $token ) );

		if ( ! $row ) {
			self::deny( __( 'Link ini tidak valid. Silakan daftar ulang untuk mendapatkan e-book.', 'jwtrading' ) );
		}

		if ( strtotime( $row->expires_at ) < time() ) {
			self::deny( __( 'Link ini sudah kedaluwarsa. Daftar ulang untuk mendapatkan link baru.', 'jwtrading' ) );
		}

		if ( (int) $row->downloads >= (int) $row->max_downloads ) {
			self::deny( __( 'Link ini sudah mencapai batas unduhan. Daftar ulang untuk mendapatkan link baru.', 'jwtrading' ) );
		}

		$s = self::settings();
		// basename() on the stored value: the path is ours, but this guarantees
		// nothing can escape the private folder even if the option is tampered with.
		$file = trailingslashit( self::private_dir() ) . basename( (string) $s['file'] );

		if ( ! is_readable( $file ) ) {
			self::deny( __( 'File belum tersedia. Hubungi kami lewat WhatsApp.', 'jwtrading' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			self::table(),
			array(
				'downloads'        => (int) $row->downloads + 1,
				'last_download_at' => current_time( 'mysql' ),
				'last_ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			),
			array( 'id' => (int) $row->id )
		);

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . ( $s['download_name'] ?: basename( $file ) ) . '"' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'X-Robots-Tag: noindex, nofollow' );

		// Drop any buffered output first — stray bytes corrupt the PDF.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	protected static function deny( string $message ) {
		wp_die(
			esc_html( $message ),
			esc_html__( 'Link tidak berlaku', 'jwtrading' ),
			array(
				'response'  => 403,
				'back_link' => false,
			)
		);
	}

	// --- Admin --------------------------------------------------------------------

	public static function admin_menu() {
		// Top-level on purpose: the e-book belongs to the lead magnet funnel,
		// not to Mentorship, and hanging it there sent the client looking in
		// the wrong menu.
		add_menu_page(
			__( 'E-Book Links', 'jwtrading' ),
			__( 'E-Book Links', 'jwtrading' ),
			'manage_options',
			'jwt-ebook',
			array( __CLASS__, 'render_admin' ),
			'dashicons-pdf',
			58
		);
	}

	/** Media modal, so the PDF is picked instead of an ID being typed. */
	public static function enqueue_admin( $hook ) {
		if ( 'toplevel_page_jwt-ebook' !== $hook ) {
			return;
		}
		wp_enqueue_media();
	}

	/**
	 * Create the custom field in Kit so the merge tag exists.
	 *
	 * Kit derives the field KEY from the LABEL ("Roadmap Link" -> roadmap_link),
	 * and the email merge tag uses the key — creating it here guarantees the two
	 * match instead of relying on someone typing the label the same way.
	 */
	public static function handle_create_field() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'jwt_ebook_create_field' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		$s     = self::settings();
		$label = trim( (string) ( $_POST['field_label'] ?? '' ) );
		$label = '' !== $label ? sanitize_text_field( $label ) : 'Roadmap Link';

		// The API key lives in the kit-tagger plugin's settings; read it directly
		// rather than duplicating credentials.
		$api_key = get_option( 'jw_kit_api_key', '' );
		$notice  = 'field_error';

		if ( '' !== $api_key ) {
			$res = wp_remote_post(
				'https://api.kit.com/v4/custom_fields',
				array(
					'timeout' => 20,
					'headers' => array(
						'X-Kit-Api-Key' => $api_key,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode( array( 'label' => $label ) ),
				)
			);

			if ( ! is_wp_error( $res ) ) {
				$body = json_decode( wp_remote_retrieve_body( $res ), true );
				$key  = $body['custom_field']['key'] ?? '';

				if ( '' !== $key ) {
					$s['kit_field'] = sanitize_key( $key );
					update_option( self::OPT, $s );
					$notice = 'field_created';
				} elseif ( 422 === (int) wp_remote_retrieve_response_code( $res ) ) {
					// Already exists — not an error worth alarming anyone about.
					$notice = 'field_exists';
				}
			}
		}

		wp_safe_redirect( add_query_arg( 'jwt_ebook_notice', $notice, admin_url( 'admin.php?page=jwt-ebook' ) ) );
		exit;
	}

	/**
	 * Move a Media Library file into the private folder.
	 *
	 * Moving rather than copying is deliberate: leaving the original in place
	 * keeps its public URL alive and defeats the whole exercise.
	 */
	public static function handle_secure_file() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'jwt_ebook_secure' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		$attachment_id = absint( $_POST['attachment_id'] ?? 0 );
		$src           = $attachment_id ? get_attached_file( $attachment_id ) : '';
		$notice        = 'error';

		if ( $src && is_readable( $src ) ) {
			// Random on-disk name as well as a random folder: with directory
			// listing denied, an attacker would have to guess BOTH to reach the
			// file directly. nginx on EasyWP will still serve a path it is given,
			// so obscurity is the only lever available — this doubles it.
			$ext   = pathinfo( $src, PATHINFO_EXTENSION );
			$stored = wp_generate_password( 24, false, false ) . ( $ext ? '.' . strtolower( $ext ) : '' );
			$dest  = trailingslashit( self::private_dir() ) . $stored;

			if ( rename( $src, $dest ) ) {
				$s                  = self::settings();
				$s['file']          = $stored;
				$s['download_name'] = sanitize_file_name( basename( $src ) );
				update_option( self::OPT, $s );

				// The attachment now points at a file that has moved, so drop
				// the record rather than leave a dead URL behind.
				wp_delete_attachment( $attachment_id, true );
				$notice = 'secured';
			}
		}

		wp_safe_redirect( add_query_arg( 'jwt_ebook_notice', $notice, admin_url( 'admin.php?page=jwt-ebook' ) ) );
		exit;
	}

	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$s = self::settings();
		self::maybe_create_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 50' );

		$notice = isset( $_GET['jwt_ebook_notice'] ) ? sanitize_key( wp_unslash( $_GET['jwt_ebook_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'E-Book Links', 'jwtrading' ); ?></h1>

			<?php if ( 'secured' === $notice ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'File moved into private storage. Its old public URL no longer works.', 'jwtrading' ); ?></p></div>
			<?php elseif ( 'error' === $notice ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Could not move that file. Pick the PDF again and retry.', 'jwtrading' ); ?></p></div>
			<?php elseif ( 'field_created' === $notice ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Custom field created in Kit.', 'jwtrading' ); ?></p></div>
			<?php elseif ( 'field_exists' === $notice ) : ?>
				<div class="notice notice-info"><p><?php esc_html_e( 'That field already exists in Kit — nothing to do.', 'jwtrading' ); ?></p></div>
			<?php elseif ( 'field_error' === $notice ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Could not create the field in Kit. Check the API key in JW Kit Auto Tagger.', 'jwtrading' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( '1. Secure the PDF', 'jwtrading' ); ?></h2>
			<?php if ( '' !== $s['file'] ) : ?>
				<p>
					<span style="color:#008a20">&#10003;</span>
					<?php
					printf(
						/* translators: %s: file name. */
						esc_html__( '%s is in private storage — reachable only through a token link.', 'jwtrading' ),
						'<code>' . esc_html( $s['download_name'] ) . '</code>'
					);
					?>
				</p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Pick the PDF you already uploaded to the Media Library. It is MOVED out of the public uploads folder into private storage, so its current public URL stops working — that is the point.', 'jwtrading' ); ?></p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'jwt_ebook_secure' ); ?>
				<input type="hidden" name="action" value="jwt_ebook_secure">
				<input type="hidden" name="attachment_id" id="jwt-ebook-attachment-id" value="">
				<button type="button" class="button" id="jwt-ebook-pick"><?php esc_html_e( 'Choose PDF from Media Library', 'jwtrading' ); ?></button>
				<span id="jwt-ebook-picked" style="margin-left:8px"></span>
				<?php submit_button( __( 'Move into private storage', 'jwtrading' ), 'primary', 'submit', false, array( 'id' => 'jwt-ebook-move', 'disabled' => 'disabled' ) ); ?>
				<script>
				jQuery(function ($) {
					var frame;
					$('#jwt-ebook-pick').on('click', function (e) {
						e.preventDefault();
						if (frame) { frame.open(); return; }
						frame = wp.media({
							title: <?php echo wp_json_encode( __( 'Choose the e-book PDF', 'jwtrading' ) ); ?>,
							library: { type: 'application/pdf' },
							button: { text: <?php echo wp_json_encode( __( 'Use this file', 'jwtrading' ) ); ?> },
							multiple: false
						});
						frame.on('select', function () {
							var file = frame.state().get('selection').first().toJSON();
							$('#jwt-ebook-attachment-id').val(file.id);
							$('#jwt-ebook-picked').text(file.filename + ' (ID ' + file.id + ')');
							$('#jwt-ebook-move').prop('disabled', false);
						});
						frame.open();
					});
				});
				</script>
			</form>

			<h2 style="margin-top:2em"><?php esc_html_e( '2. Settings', 'jwtrading' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'jwt_ebook_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Link expires after', 'jwtrading' ); ?></th>
						<td>
							<input type="number" name="<?php echo esc_attr( self::OPT ); ?>[expiry_days]" value="<?php echo esc_attr( $s['expiry_days'] ); ?>" class="small-text" min="1" max="365">
							<?php esc_html_e( 'days', 'jwtrading' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max downloads per link', 'jwtrading' ); ?></th>
						<td><input type="number" name="<?php echo esc_attr( self::OPT ); ?>[max_downloads]" value="<?php echo esc_attr( $s['max_downloads'] ); ?>" class="small-text" min="1" max="100"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Kit custom field', 'jwtrading' ); ?></th>
						<td>
							<input type="text" name="<?php echo esc_attr( self::OPT ); ?>[kit_field]" value="<?php echo esc_attr( $s['kit_field'] ); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Paste this as the button URL in your Kit email:', 'jwtrading' ); ?>
								<code>{{ subscriber.<?php echo esc_html( $s['kit_field'] ); ?> }}</code>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2 style="margin-top:2em"><?php esc_html_e( '3. Create the field in Kit', 'jwtrading' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Kit builds the field key from the label ("Roadmap Link" becomes roadmap_link), and the email merge tag uses the key. Creating it from here keeps the two in step.', 'jwtrading' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'jwt_ebook_create_field' ); ?>
				<input type="hidden" name="action" value="jwt_ebook_create_field">
				<input type="text" name="field_label" value="Roadmap Link" class="regular-text">
				<?php submit_button( __( 'Create custom field in Kit', 'jwtrading' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2 style="margin-top:2em"><?php esc_html_e( 'Recent links', 'jwtrading' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Email', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Downloads', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Expires', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Last download', 'jwtrading' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No links issued yet.', 'jwtrading' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<?php $jwt_expired = strtotime( $r->expires_at ) < time(); ?>
						<tr>
							<td><?php echo esc_html( (string) $r->email ); ?></td>
							<td><?php echo esc_html( $r->downloads . ' / ' . $r->max_downloads ); ?></td>
							<td<?php echo $jwt_expired ? ' style="color:#d63638"' : ''; ?>><?php echo esc_html( $r->expires_at ); ?></td>
							<td><?php echo esc_html( $r->last_download_at ? $r->last_download_at : '—' ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
