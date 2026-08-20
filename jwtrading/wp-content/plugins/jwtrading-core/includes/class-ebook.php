<?php
defined( 'ABSPATH' ) || exit;

/**
 * Per-subscriber e-book download links — any number of e-books.
 *
 * A PDF sitting at a public uploads URL can be pasted into a Discord or
 * WhatsApp group, and everyone after that skips the opt-in. Here each PDF is
 * moved into an unguessable folder under a randomised filename, and every
 * subscriber gets their OWN link carrying a token that expires and has a
 * download cap.
 *
 *   opt-in -> mint token -> link handed to Kit as a custom field
 *          -> Kit email uses {{ subscriber.<field> }}
 *          -> /?jwt_ebook=<slug>&t=<token> validates and streams
 *
 * Each e-book is a slug with its own file, expiry, cap and Kit field, so a
 * second magnet (prop firm, IFVG…) is added from the admin without code. The
 * opt-in block chooses which e-book it delivers.
 *
 * Honest limits, worth knowing before promising anything:
 *  - This protects the LINK, not the file. A subscriber can still forward the
 *    PDF itself and nothing server-side can stop that.
 *  - EasyWP runs nginx with no config access, so "deny direct access to
 *    /uploads/*.pdf" is unavailable. The private path is obscurity: a random
 *    folder AND a random filename, neither ever linked anywhere.
 */
class JWT_Ebook {

	const OPT       = 'jwt_ebook';
	const DB_OPT    = 'jwt_ebook_db_version';
	const DB_VER    = '1';
	const QUERY_VAR = 'jwt_ebook';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_table' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve' ), 1 );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
		add_action( 'admin_post_jwt_ebook_secure', array( __CLASS__, 'handle_secure_file' ) );
		add_action( 'admin_post_jwt_ebook_create_field', array( __CLASS__, 'handle_create_field' ) );
		add_action( 'admin_post_jwt_ebook_add', array( __CLASS__, 'handle_add' ) );
		add_action( 'admin_post_jwt_ebook_delete', array( __CLASS__, 'handle_delete' ) );
	}

	// --- Data -------------------------------------------------------------------

	public static function defaults(): array {
		return array(
			'label'         => '',
			'file'          => '',          // Randomised name on disk.
			'download_name' => 'ebook.pdf', // Friendly name the visitor sees.
			'expiry_days'   => 7,
			'max_downloads' => 5,
			'kit_field'     => '',
		);
	}

	/**
	 * All configured e-books, keyed by slug.
	 *
	 * Migrates the original single-e-book option shape on read, so an existing
	 * install keeps its secured file and settings with no manual step.
	 */
	public static function all(): array {
		$opt = get_option( self::OPT, array() );
		$opt = is_array( $opt ) ? $opt : array();

		if ( isset( $opt['ebooks'] ) && is_array( $opt['ebooks'] ) ) {
			$books = $opt['ebooks'];
		} elseif ( ! empty( $opt['file'] ) || ! empty( $opt['kit_field'] ) ) {
			// Old flat shape: a single e-book stored at the top level.
			$slug                    = ! empty( $opt['slug'] ) ? sanitize_key( $opt['slug'] ) : 'roadmap';
			$books                   = array( $slug => $opt );
			$books[ $slug ]['label'] = $opt['label'] ?? 'Trader Roadmap';
			update_option( self::OPT, array( 'ebooks' => $books ) );
		} else {
			$books = array();
		}

		$out = array();
		foreach ( $books as $slug => $book ) {
			$slug = sanitize_key( $slug );
			if ( '' === $slug ) {
				continue;
			}
			$out[ $slug ] = wp_parse_args( is_array( $book ) ? $book : array(), self::defaults() );
			if ( '' === $out[ $slug ]['label'] ) {
				$out[ $slug ]['label'] = $slug;
			}
		}

		return $out;
	}

	public static function get( string $slug ): array {
		$all = self::all();
		return $all[ sanitize_key( $slug ) ] ?? array();
	}

	protected static function save( array $books ) {
		update_option( self::OPT, array( 'ebooks' => $books ) );
	}

	/** slug => label, for the block editor dropdown. */
	public static function choices(): array {
		$out = array();
		foreach ( self::all() as $slug => $book ) {
			$out[ $slug ] = $book['label'];
		}
		return $out;
	}

	public static function register_settings() {
		register_setting(
			'jwt_ebook_group',
			self::OPT,
			array(
				'sanitize_callback' => static function ( $in ) {
					$current = self::all();
					$posted  = ( is_array( $in ) && isset( $in['ebooks'] ) && is_array( $in['ebooks'] ) ) ? $in['ebooks'] : array();
					$out     = array();

					// Only editable fields come from the form. `file` and
					// `download_name` are written by handle_secure_file() alone,
					// so a path can never be typed in.
					foreach ( $current as $slug => $book ) {
						$p                     = is_array( $posted[ $slug ] ?? null ) ? $posted[ $slug ] : array();
						$book['label']         = sanitize_text_field( $p['label'] ?? $book['label'] );
						$book['kit_field']     = sanitize_key( $p['kit_field'] ?? $book['kit_field'] );
						$book['expiry_days']   = max( 1, min( 365, (int) ( $p['expiry_days'] ?? $book['expiry_days'] ) ) );
						$book['max_downloads'] = max( 1, min( 100, (int) ( $p['max_downloads'] ?? $book['max_downloads'] ) ) );
						$out[ $slug ]          = $book;
					}

					return array( 'ebooks' => $out );
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
			KEY email (email),
			KEY slug (slug)
		) {$charset};"
		);

		update_option( self::DB_OPT, self::DB_VER );
	}

	public static function maybe_create_table() {
		if ( get_option( self::DB_OPT ) !== self::DB_VER ) {
			self::create_table();
		}
	}

	/** Unguessable folder holding every secured PDF. Created once. */
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
			// Blocks directory listing where the server has it enabled.
			file_put_contents( trailingslashit( $path ) . 'index.php', "<?php // Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		return $path;
	}

	// --- Issuing a link ----------------------------------------------------------

	/**
	 * Mint a download link for one subscriber.
	 *
	 * @param string $email Subscriber email.
	 * @param string $slug  Which e-book.
	 * @return string Full URL, or '' when that e-book has no file yet.
	 */
	public static function issue_link( string $email, string $slug ): string {
		$book = self::get( $slug );

		if ( empty( $book ) || '' === $book['file'] ) {
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
				'slug'          => sanitize_key( $slug ),
				'downloads'     => 0,
				'max_downloads' => (int) $book['max_downloads'],
				'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + ( (int) $book['expiry_days'] * DAY_IN_SECONDS ) ),
				'created_at'    => current_time( 'mysql' ),
			)
		);

		return add_query_arg(
			array(
				self::QUERY_VAR => sanitize_key( $slug ),
				't'             => $token,
			),
			home_url( '/' )
		);
	}

	// --- Serving -----------------------------------------------------------------

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

		$book = self::get( (string) $row->slug );

		if ( empty( $book ) || '' === $book['file'] ) {
			self::deny( __( 'File belum tersedia. Hubungi kami lewat WhatsApp.', 'jwtrading' ) );
		}

		// basename() guarantees nothing escapes the private folder even if the
		// stored option were tampered with.
		$file = trailingslashit( self::private_dir() ) . basename( (string) $book['file'] );

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
		header( 'Content-Disposition: inline; filename="' . ( $book['download_name'] ? $book['download_name'] : basename( $file ) ) . '"' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'X-Robots-Tag: noindex, nofollow' );

		// Drop buffered output — stray bytes corrupt the PDF.
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

	// --- Admin actions ------------------------------------------------------------

	protected static function guard( string $nonce ) {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $nonce ) ) {
			wp_die( 'Unauthorized', 403 );
		}
	}

	protected static function back( string $notice ) {
		wp_safe_redirect( add_query_arg( 'jwt_ebook_notice', $notice, admin_url( 'admin.php?page=jwt-ebook' ) ) );
		exit;
	}

	public static function handle_add() {
		self::guard( 'jwt_ebook_add' );

		$slug  = sanitize_key( $_POST['slug'] ?? '' );
		$label = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );

		if ( '' === $slug ) {
			self::back( 'add_error' );
		}

		$books = self::all();

		if ( isset( $books[ $slug ] ) ) {
			self::back( 'add_exists' );
		}

		$books[ $slug ]              = self::defaults();
		$books[ $slug ]['label']     = '' !== $label ? $label : $slug;
		$books[ $slug ]['kit_field'] = sanitize_key( $slug . '_link' );
		self::save( $books );

		self::back( 'added' );
	}

	public static function handle_delete() {
		self::guard( 'jwt_ebook_delete' );

		$slug  = sanitize_key( $_POST['slug'] ?? '' );
		$books = self::all();

		if ( isset( $books[ $slug ] ) ) {
			// The PDF itself is left on disk on purpose — removing a config row
			// should never destroy a file that might still be in use.
			unset( $books[ $slug ] );
			self::save( $books );
		}

		self::back( 'deleted' );
	}

	/**
	 * Move a Media Library file into private storage.
	 *
	 * Moving rather than copying is the point: leaving the original keeps its
	 * public URL alive. The stored name is randomised so that, with listing
	 * denied, both the folder and the filename would have to be guessed.
	 */
	public static function handle_secure_file() {
		self::guard( 'jwt_ebook_secure' );

		$slug          = sanitize_key( $_POST['slug'] ?? '' );
		$attachment_id = absint( $_POST['attachment_id'] ?? 0 );
		$books         = self::all();

		if ( ! isset( $books[ $slug ] ) || ! $attachment_id ) {
			self::back( 'error' );
		}

		$src = get_attached_file( $attachment_id );

		if ( ! $src || ! is_readable( $src ) ) {
			self::back( 'error' );
		}

		$ext    = pathinfo( $src, PATHINFO_EXTENSION );
		$stored = wp_generate_password( 24, false, false ) . ( $ext ? '.' . strtolower( $ext ) : '' );
		$dest   = trailingslashit( self::private_dir() ) . $stored;

		if ( ! rename( $src, $dest ) ) {
			self::back( 'error' );
		}

		$books[ $slug ]['file']          = $stored;
		$books[ $slug ]['download_name'] = sanitize_file_name( basename( $src ) );
		self::save( $books );

		// The attachment now points at a moved file — drop it so no dead URL remains.
		wp_delete_attachment( $attachment_id, true );

		self::back( 'secured' );
	}

	/**
	 * Create the custom field in Kit.
	 *
	 * Kit derives the field KEY from the LABEL ("Roadmap Link" -> roadmap_link)
	 * and the email merge tag uses the key, so creating it here keeps the two in
	 * step rather than relying on the label being typed identically.
	 */
	public static function handle_create_field() {
		self::guard( 'jwt_ebook_create_field' );

		$slug  = sanitize_key( $_POST['slug'] ?? '' );
		$label = sanitize_text_field( wp_unslash( $_POST['field_label'] ?? '' ) );
		$books = self::all();

		if ( ! isset( $books[ $slug ] ) || '' === $label ) {
			self::back( 'field_error' );
		}

		// The API key lives in the kit-tagger plugin's settings; read it rather
		// than keeping a second copy of the credential.
		$api_key = get_option( 'jw_kit_api_key', '' );

		if ( '' === $api_key ) {
			self::back( 'field_error' );
		}

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

		if ( is_wp_error( $res ) ) {
			self::back( 'field_error' );
		}

		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		$key  = $body['custom_field']['key'] ?? '';

		if ( '' !== $key ) {
			$books[ $slug ]['kit_field'] = sanitize_key( $key );
			self::save( $books );
			self::back( 'field_created' );
		}

		// 422 = already exists, which is not a failure worth alarming about.
		self::back( 422 === (int) wp_remote_retrieve_response_code( $res ) ? 'field_exists' : 'field_error' );
	}

	// --- Admin screen -------------------------------------------------------------

	public static function admin_menu() {
		// Top-level: e-books belong to the lead magnet funnels generally, not to
		// Mentorship, and nesting them there sent people to the wrong menu.
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

	public static function enqueue_admin( $hook ) {
		if ( 'toplevel_page_jwt-ebook' === $hook ) {
			wp_enqueue_media();
		}
	}

	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		self::maybe_create_table();

		$books  = self::all();
		$notice = isset( $_GET['jwt_ebook_notice'] ) ? sanitize_key( wp_unslash( $_GET['jwt_ebook_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$messages = array(
			'secured'       => array( 'success', __( 'File moved into private storage. Its old public URL no longer works.', 'jwtrading' ) ),
			'error'         => array( 'error', __( 'Could not move that file. Pick the PDF again and retry.', 'jwtrading' ) ),
			'added'         => array( 'success', __( 'E-book added.', 'jwtrading' ) ),
			'add_exists'    => array( 'error', __( 'That slug is already in use.', 'jwtrading' ) ),
			'add_error'     => array( 'error', __( 'Give the e-book a slug.', 'jwtrading' ) ),
			'deleted'       => array( 'success', __( 'E-book removed. The PDF file itself was left on disk.', 'jwtrading' ) ),
			'field_created' => array( 'success', __( 'Custom field created in Kit.', 'jwtrading' ) ),
			'field_exists'  => array( 'info', __( 'That field already exists in Kit — nothing to do.', 'jwtrading' ) ),
			'field_error'   => array( 'error', __( 'Could not create the field in Kit. Check the API key in JW Kit Auto Tagger.', 'jwtrading' ) ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'E-Book Links', 'jwtrading' ); ?></h1>

			<?php if ( isset( $messages[ $notice ] ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( $messages[ $notice ][0] ); ?>"><p><?php echo esc_html( $messages[ $notice ][1] ); ?></p></div>
			<?php endif; ?>

			<p class="description" style="max-width:820px">
				<?php esc_html_e( 'Each e-book has its own PDF, expiry, download cap and Kit field. Choose which one an opt-in delivers in that page\'s block settings.', 'jwtrading' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'jwt_ebook_group' ); ?>

				<?php if ( empty( $books ) ) : ?>
					<p><em><?php esc_html_e( 'No e-books yet — add one below.', 'jwtrading' ); ?></em></p>
				<?php endif; ?>

				<?php foreach ( $books as $slug => $book ) : ?>
					<?php
					$file  = '' !== $book['file'] ? trailingslashit( self::private_dir() ) . basename( $book['file'] ) : '';
					$ready = $file && is_readable( $file );
					?>
					<div class="card" style="max-width:820px;margin:1em 0;padding:1em 1.5em">
						<h2 style="margin-top:0">
							<?php echo esc_html( $book['label'] ); ?>
							<code style="font-size:12px"><?php echo esc_html( $slug ); ?></code>
						</h2>

						<p>
							<?php if ( $ready ) : ?>
								<span style="color:#008a20">&#10003;</span>
								<?php
								printf(
									/* translators: 1: file name, 2: file size. */
									esc_html__( '%1$s secured in private storage (%2$s).', 'jwtrading' ),
									'<code>' . esc_html( $book['download_name'] ) . '</code>',
									esc_html( size_format( filesize( $file ) ) )
								);
								?>
							<?php else : ?>
								<span style="color:#d63638">&#10007;</span>
								<?php esc_html_e( 'No PDF secured yet — links for this e-book will not be issued.', 'jwtrading' ); ?>
							<?php endif; ?>
						</p>

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php esc_html_e( 'Name', 'jwtrading' ); ?></th>
								<td><input type="text" name="<?php echo esc_attr( self::OPT ); ?>[ebooks][<?php echo esc_attr( $slug ); ?>][label]" value="<?php echo esc_attr( $book['label'] ); ?>" class="regular-text"></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Link expires after', 'jwtrading' ); ?></th>
								<td>
									<input type="number" name="<?php echo esc_attr( self::OPT ); ?>[ebooks][<?php echo esc_attr( $slug ); ?>][expiry_days]" value="<?php echo esc_attr( $book['expiry_days'] ); ?>" class="small-text" min="1" max="365">
									<?php esc_html_e( 'days', 'jwtrading' ); ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Max downloads per link', 'jwtrading' ); ?></th>
								<td><input type="number" name="<?php echo esc_attr( self::OPT ); ?>[ebooks][<?php echo esc_attr( $slug ); ?>][max_downloads]" value="<?php echo esc_attr( $book['max_downloads'] ); ?>" class="small-text" min="1" max="100"></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Kit custom field', 'jwtrading' ); ?></th>
								<td>
									<input type="text" name="<?php echo esc_attr( self::OPT ); ?>[ebooks][<?php echo esc_attr( $slug ); ?>][kit_field]" value="<?php echo esc_attr( $book['kit_field'] ); ?>" class="regular-text">
									<?php if ( '' !== $book['kit_field'] ) : ?>
										<p class="description">
											<?php esc_html_e( 'Paste this as the button URL in the Kit email:', 'jwtrading' ); ?>
											<code>{{ subscriber.<?php echo esc_html( $book['kit_field'] ); ?> }}</code>
										</p>
									<?php endif; ?>
								</td>
							</tr>
						</table>
					</div>
				<?php endforeach; ?>

				<?php if ( ! empty( $books ) ) : ?>
					<?php submit_button( __( 'Save all e-book settings', 'jwtrading' ) ); ?>
				<?php endif; ?>
			</form>

			<?php foreach ( $books as $slug => $book ) : ?>
				<div class="card" style="max-width:820px;margin:1em 0;padding:1em 1.5em">
					<h3 style="margin-top:0"><?php echo esc_html( $book['label'] ); ?> — <?php esc_html_e( 'actions', 'jwtrading' ); ?></h3>

					<p>
						<strong><?php esc_html_e( '1. Secure the PDF', 'jwtrading' ); ?></strong><br>
						<span class="description"><?php esc_html_e( 'Pick a PDF already in the Media Library. It is MOVED out of the public uploads folder, so its public URL stops working.', 'jwtrading' ); ?></span>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:1.5em">
						<?php wp_nonce_field( 'jwt_ebook_secure' ); ?>
						<input type="hidden" name="action" value="jwt_ebook_secure">
						<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
						<input type="hidden" name="attachment_id" class="jwt-ebook-attachment-id" value="">
						<button type="button" class="button jwt-ebook-pick"><?php esc_html_e( 'Choose PDF', 'jwtrading' ); ?></button>
						<span class="jwt-ebook-picked" style="margin-left:8px"></span>
						<button type="submit" class="button button-primary jwt-ebook-move" disabled><?php esc_html_e( 'Move into private storage', 'jwtrading' ); ?></button>
					</form>

					<p>
						<strong><?php esc_html_e( '2. Create the field in Kit', 'jwtrading' ); ?></strong><br>
						<span class="description"><?php esc_html_e( 'Kit builds the field key from the label, and the merge tag uses the key — creating it here keeps them in step.', 'jwtrading' ); ?></span>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'jwt_ebook_create_field' ); ?>
						<input type="hidden" name="action" value="jwt_ebook_create_field">
						<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
						<input type="text" name="field_label" value="<?php echo esc_attr( $book['label'] . ' Link' ); ?>" class="regular-text">
						<button type="submit" class="button"><?php esc_html_e( 'Create custom field in Kit', 'jwtrading' ); ?></button>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1.5em">
						<?php wp_nonce_field( 'jwt_ebook_delete' ); ?>
						<input type="hidden" name="action" value="jwt_ebook_delete">
						<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
						<button type="submit" class="button-link delete" onclick="return confirm('<?php esc_attr_e( 'Remove this e-book from the list? The PDF file itself stays on disk.', 'jwtrading' ); ?>')"><?php esc_html_e( 'Remove this e-book', 'jwtrading' ); ?></button>
					</form>
				</div>
			<?php endforeach; ?>

			<h2 style="margin-top:2em"><?php esc_html_e( 'Add an e-book', 'jwtrading' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'jwt_ebook_add' ); ?>
				<input type="hidden" name="action" value="jwt_ebook_add">
				<input type="text" name="label" placeholder="<?php esc_attr_e( 'Name, e.g. Prop Firm Guide', 'jwtrading' ); ?>" class="regular-text">
				<input type="text" name="slug" placeholder="<?php esc_attr_e( 'slug, e.g. propfirm', 'jwtrading' ); ?>">
				<button type="submit" class="button"><?php esc_html_e( 'Add', 'jwtrading' ); ?></button>
			</form>

			<h2 style="margin-top:2em"><?php esc_html_e( 'Recent links', 'jwtrading' ); ?></h2>
			<?php
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 50' );
			?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'E-book', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Email', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Downloads', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Expires', 'jwtrading' ); ?></th>
						<th><?php esc_html_e( 'Last download', 'jwtrading' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No links issued yet.', 'jwtrading' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<?php $jwt_expired = strtotime( $r->expires_at ) < time(); ?>
						<tr>
							<td><code><?php echo esc_html( (string) $r->slug ); ?></code></td>
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

		<script>
		jQuery(function ($) {
			$('.jwt-ebook-pick').on('click', function (e) {
				e.preventDefault();
				var $form = $(this).closest('form');
				var frame = wp.media({
					title: <?php echo wp_json_encode( __( 'Choose the e-book PDF', 'jwtrading' ) ); ?>,
					library: { type: 'application/pdf' },
					button: { text: <?php echo wp_json_encode( __( 'Use this file', 'jwtrading' ) ); ?> },
					multiple: false
				});
				frame.on('select', function () {
					var file = frame.state().get('selection').first().toJSON();
					$form.find('.jwt-ebook-attachment-id').val(file.id);
					$form.find('.jwt-ebook-picked').text(file.filename);
					$form.find('.jwt-ebook-move').prop('disabled', false);
				});
				frame.open();
			});
		});
		</script>
		<?php
	}
}
