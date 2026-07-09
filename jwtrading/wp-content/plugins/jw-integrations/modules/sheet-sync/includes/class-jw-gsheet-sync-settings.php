<?php
/**
 * Plugin settings page for JW WooCommerce Google Sheet Sync.
 *
 * @package JW_GSheet_Sync
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class JW_GSheet_Sync_Settings
 */
class JW_GSheet_Sync_Settings {

	/**
	 * Option name for plugin settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'jw_gsheet_sync_settings';

	/**
	 * Default trigger statuses.
	 *
	 * @var array
	 */
	const DEFAULT_TRIGGER_STATUSES = array( 'processing', 'completed' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . JW_GSHEET_SYNC_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Google Sheet Sync', 'jw-gsheet-sync' ),
			__( 'Google Sheet Sync', 'jw-gsheet-sync' ),
			'manage_woocommerce',
			'jw-gsheet-sync',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Add settings link on plugins page.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=jw-gsheet-sync' ) ),
			__( 'Settings', 'jw-gsheet-sync' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register settings and fields.
	 */
	public function register_settings() {
		register_setting(
			'jw_gsheet_sync_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'jw_gsheet_sync_main_section',
			__( 'Webhook Configuration', 'jw-gsheet-sync' ),
			array( $this, 'render_section_callback' ),
			'jw-gsheet-sync'
		);

		add_settings_field(
			'webhook_url',
			__( 'Google Apps Script Webhook URL', 'jw-gsheet-sync' ),
			array( $this, 'render_webhook_url_field' ),
			'jw-gsheet-sync',
			'jw_gsheet_sync_main_section',
			array( 'label_for' => 'jw_gsheet_webhook_url' )
		);

		add_settings_field(
			'secret_token',
			__( 'Secret Token', 'jw-gsheet-sync' ),
			array( $this, 'render_secret_token_field' ),
			'jw-gsheet-sync',
			'jw_gsheet_sync_main_section',
			array( 'label_for' => 'jw_gsheet_secret_token' )
		);

		add_settings_field(
			'site_label',
			__( 'Website Label', 'jw-gsheet-sync' ),
			array( $this, 'render_site_label_field' ),
			'jw-gsheet-sync',
			'jw_gsheet_sync_main_section',
			array( 'label_for' => 'jw_gsheet_site_label' )
		);

		add_settings_field(
			'enable_logging',
			__( 'Enable Logging', 'jw-gsheet-sync' ),
			array( $this, 'render_enable_logging_field' ),
			'jw-gsheet-sync',
			'jw_gsheet_sync_main_section',
			array( 'label_for' => 'jw_gsheet_enable_logging' )
		);

		add_settings_field(
			'trigger_statuses',
			__( 'Trigger Statuses', 'jw-gsheet-sync' ),
			array( $this, 'render_trigger_statuses_field' ),
			'jw-gsheet-sync',
			'jw_gsheet_sync_main_section',
			array( 'label_for' => 'jw_gsheet_trigger_statuses' )
		);
	}

	/**
	 * Sanitize settings before save.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return $this->get_defaults();
		}

		$sanitized = array();

		$sanitized['webhook_url'] = ! empty( $input['webhook_url'] )
			? esc_url_raw( trim( $input['webhook_url'] ) )
			: '';

		$sanitized['secret_token'] = ! empty( $input['secret_token'] )
			? sanitize_text_field( $input['secret_token'] )
			: '';

		$sanitized['site_label'] = ! empty( $input['site_label'] )
			? sanitize_text_field( $input['site_label'] )
			: get_bloginfo( 'name' );

		$sanitized['enable_logging'] = ! empty( $input['enable_logging'] ) ? 'yes' : 'no';

		$valid_statuses = array( 'processing', 'completed', 'on-hold' );
		$sanitized['trigger_statuses'] = array();
		if ( ! empty( $input['trigger_statuses'] ) && is_array( $input['trigger_statuses'] ) ) {
			foreach ( $input['trigger_statuses'] as $status ) {
				if ( in_array( $status, $valid_statuses, true ) ) {
					$sanitized['trigger_statuses'][] = $status;
				}
			}
		}
		if ( empty( $sanitized['trigger_statuses'] ) ) {
			$sanitized['trigger_statuses'] = self::DEFAULT_TRIGGER_STATUSES;
		}

		return $sanitized;
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'webhook_url'       => '',
			'secret_token'     => '',
			'site_label'       => get_bloginfo( 'name' ),
			'enable_logging'    => 'no',
			'trigger_statuses' => self::DEFAULT_TRIGGER_STATUSES,
		);
	}

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		$saved = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $saved, $this->get_defaults() );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get( $key ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Check if logging is enabled.
	 *
	 * @return bool
	 */
	public function is_logging_enabled() {
		return 'yes' === $this->get( 'enable_logging' );
	}

	/**
	 * Check if a status should trigger sync.
	 *
	 * @param string $status Order status.
	 * @return bool
	 */
	public function is_trigger_status( $status ) {
		$trigger_statuses = $this->get( 'trigger_statuses' );
		return is_array( $trigger_statuses ) && in_array( $status, $trigger_statuses, true );
	}

	/**
	 * Render section description.
	 */
	public function render_section_callback() {
		echo '<p>' . esc_html__( 'Configure the connection to your Google Apps Script webhook. Orders will be sent when they reach the selected statuses.', 'jw-gsheet-sync' ) . '</p>';
	}

	/**
	 * Render webhook URL field.
	 */
	public function render_webhook_url_field() {
		$value = $this->get( 'webhook_url' );
		?>
		<input type="url"
			id="jw_gsheet_webhook_url"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[webhook_url]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="https://script.google.com/macros/s/.../exec"
			autocomplete="url" />
		<p class="description"><?php esc_html_e( 'Paste your Google Apps Script web app URL (Deploy as Web app).', 'jw-gsheet-sync' ); ?></p>
		<?php
	}

	/**
	 * Render secret token field.
	 */
	public function render_secret_token_field() {
		$value = $this->get( 'secret_token' );
		?>
		<input type="password"
			id="jw_gsheet_secret_token"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[secret_token]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="off" />
		<p class="description"><?php esc_html_e( 'Must match the secret token in your Google Apps Script for verification.', 'jw-gsheet-sync' ); ?></p>
		<?php
	}

	/**
	 * Render site label field.
	 */
	public function render_site_label_field() {
		$value = $this->get( 'site_label' );
		?>
		<input type="text"
			id="jw_gsheet_site_label"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[site_label]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
		<p class="description"><?php esc_html_e( 'Label to identify this site in the spreadsheet (e.g. store name).', 'jw-gsheet-sync' ); ?></p>
		<?php
	}

	/**
	 * Render enable logging field.
	 */
	public function render_enable_logging_field() {
		$value = $this->get( 'enable_logging' );
		?>
		<label for="jw_gsheet_enable_logging">
			<input type="checkbox"
				id="jw_gsheet_enable_logging"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_logging]"
				value="yes"
				<?php checked( $value, 'yes' ); ?> />
			<?php esc_html_e( 'Log sync activity to WooCommerce logs', 'jw-gsheet-sync' ); ?>
		</label>
		<?php
	}

	/**
	 * Render trigger statuses field.
	 */
	public function render_trigger_statuses_field() {
		$selected = $this->get( 'trigger_statuses' );
		if ( ! is_array( $selected ) ) {
			$selected = self::DEFAULT_TRIGGER_STATUSES;
		}

		$statuses = array(
			'processing' => __( 'Processing', 'jw-gsheet-sync' ),
			'completed'  => __( 'Completed', 'jw-gsheet-sync' ),
			'on-hold'    => __( 'On Hold', 'jw-gsheet-sync' ),
		);
		?>
		<fieldset>
			<?php foreach ( $statuses as $status => $label ) : ?>
				<label style="display: block; margin-bottom: 4px;">
					<input type="checkbox"
						name="<?php echo esc_attr( self::OPTION_NAME ); ?>[trigger_statuses][]"
						value="<?php echo esc_attr( $status ); ?>"
						<?php checked( in_array( $status, $selected, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Send order data when order reaches any of these statuses.', 'jw-gsheet-sync' ); ?></p>
		<?php
	}

	/**
	 * Render the full settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'jw-gsheet-sync' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'jw_gsheet_sync_settings_group' );
				do_settings_sections( 'jw-gsheet-sync' );
				submit_button( __( 'Save Settings', 'jw-gsheet-sync' ) );
				?>
			</form>
		</div>
		<?php
	}
}
