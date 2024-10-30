<?php // phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Plugin Name: Login IP & Country Restriction
 * Plugin URI:  https://iuliacazan.ro/login-ip-country-restriction/
 * Description: This plugin hooks in the authenticate filter. By default, the plugin is set to allow all access and you can configure the plugin to allow the login only from some specified IPs or the specified countries. PLEASE MAKE SURE THAT YOU CONFIGURE THE PLUGIN TO ALLOW YOUR OWN ACCESS. If you set a restriction by IP, then you have to add your own IP (if you are using the plugin in a local setup the IP is 127.0.0.1 or ::1, this is added in your list by default). If you set a restriction by country, then you have to select from the list of countries at least your country. The both types of restrictions work independent, so you can set only one type of restriction or both if you want.
 * Text Domain: slicr
 * Domain Path: /langs
 * Version:     6.6.0
 * Author:      Iulia Cazan
 * Author URI:  https://profiles.wordpress.org/iulia-cazan
 * Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ
 * License:     GPL2
 *
 * @package ic-devops
 *
 * Copyright (C) 2014-2024 Iulia Cazan
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Define the plugin version.
define( 'SISANU_RCIL_DB_OPTION', 'sisanu_rcil' );
define( 'SISANU_RCIL_CURRENT_DB_VERSION', 6.6 );
define( 'SISANU_RCIL_SLUG', 'slicr' );
define( 'SISANU_RCIL_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'SISANU_RCIL_URL', trailingslashit( plugins_url( '/', plugin_basename( __FILE__ ) ) ) );

/**
 * Class for Login IP & Country Restriction.
 */
class SISANU_Restrict_Country_IP_Login {
	const PLUGIN_NAME        = 'Login IP & Country Restriction';
	const PLUGIN_SUPPORT_URL = 'https://wordpress.org/support/plugin/login-ip-country-restriction/';
	const PLUGIN_TRANSIENT   = 'sislrc-plugin-notice';
	const CHAR_IPPIN         = '&#x1F4CD;';
	const CHAR_ALLOW         = '&#x2714;';
	const CHAR_BLOCK         = '&#x2716;';

	/**
	 * Other settings.
	 *
	 * @var array
	 */
	public static $settings = [];

	/**
	 * Allowed countries.
	 *
	 * @var array
	 */
	public static $allowed_countries = [];

	/**
	 * Blocked countries.
	 *
	 * @var array
	 */
	public static $blocked_countries = [];

	/**
	 * Allowed IPs.
	 *
	 * @var array
	 */
	public static $allowed_ips = [];

	/**
	 * Blocked IPs.
	 *
	 * @var array
	 */
	public static $blocked_ips = [];

	/**
	 * Allowed Roles.
	 *
	 * @var array
	 */
	public static $bypass_roles = [];

	/**
	 * All countries.
	 *
	 * @var boolean
	 */
	public static $all_countries = false;

	/**
	 * All IPs.
	 *
	 * @var boolean
	 */
	public static $all_ips = false;

	/**
	 * No roles bypass.
	 *
	 * @var boolean
	 */
	public static $no_roles_bypass = true;

	/**
	 * Restriction rules.
	 *
	 * @var null
	 */
	public static $rules = null;

	/**
	 * Maybe redirect the URLs.
	 *
	 * @var array
	 */
	public static $custom_redirects = [
		'status'   => 0,
		'login'    => 0,
		'register' => 0,
		'urls'     => [],
	];

	/**
	 * If he current user restriction was assessed.
	 *
	 * @var boolean
	 */
	private static $curent_user_assessed = false;

	/**
	 * If he current user has restriction.
	 *
	 * @var boolean
	 */
	private static $curent_user_restriction = false;

	/**
	 * The plugin URL.
	 *
	 * @var string
	 */
	private static $plugin_url = '';

	/**
	 * The plugin debug.
	 *
	 * @var boolean
	 */
	private static $is_pro = false;

	/**
	 * Maybe simulate restriction.
	 *
	 * @var array
	 */
	public static $simulate;

	/**
	 * Maybe auth user ID.
	 *
	 * @var integer
	 */
	public static $user_id = 0;

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Get active object instance
	 *
	 * @return object
	 */
	public static function get_instance() { // phpcs:ignore
		if ( ! self::$instance ) {
			self::$instance = new SISANU_Restrict_Country_IP_Login();
		}
		return self::$instance;
	}

	/**
	 * Class constructor. Includes constants, includes and init method.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Run action and filter hooks.
	 */
	private function init() {
		self::load_settings();

		$ob_class = get_called_class();
		add_action( 'wp_loaded', [ $ob_class, 'l10n' ], 20 );

		if ( file_exists( __DIR__ . '/pro-settings.php' ) ) {
			self::$is_pro = true;
			include_once __DIR__ . '/pro-settings.php';
		}

		if ( empty( self::$settings['temp_disable'] ) ) {
			if ( self::$is_pro && function_exists( 'RCIL\Pro\maybe_simulate_restriction' ) ) {
				self::$simulate = RCIL\Pro\maybe_simulate_restriction();
				self::hookup_the_custom_restrictions();
			} elseif ( false === self::$all_countries || false === self::$all_ips ) {
				self::hookup_the_custom_restrictions();
			}
		}

		self::$plugin_url = admin_url( 'options-general.php?page=login-ip-country-restriction-settings' );

		add_action( 'admin_init', [ $ob_class, 'maybe_upgrade_version' ], 1 );
		add_action( 'admin_init', [ $ob_class, 'maybe_save_settings' ], 1 );
		add_action( 'admin_menu', [ $ob_class, 'admin_menu' ] );
		add_action( 'admin_notices', [ $ob_class, 'admin_notices' ] );
		add_action( 'admin_enqueue_scripts', [ $ob_class, 'load_assets' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $ob_class, 'plugin_action_links' ] );

		if ( is_admin() ) {
			add_filter( 'check_rule_type_save', [ $ob_class, 'check_rule_type_save' ] );
		}

		add_filter( 'assess_rule_by_type', [ $ob_class, 'assess_rule_by_type' ], 10, 3 );
		add_action( 'admin_notices', [ $ob_class, 'plugin_admin_notices' ] );
		add_action( 'wp_ajax_plugin-deactivate-notice-' . SISANU_RCIL_SLUG, [ $ob_class, 'plugin_admin_notices_cleanup' ] );
		add_action( 'plugins_loaded', [ $ob_class, 'plugin_ver_check' ] );
	}

	/**
	 * Load text domain for internalization.
	 */
	public static function l10n() {
		load_plugin_textdomain( 'slicr', false, basename( __DIR__ ) . '/langs' );
	}

	/**
	 * Hookup the custom restrictions filters and actions.
	 */
	public static function hookup_the_custom_restrictions() {
		$ob_class = get_called_class();
		add_filter( 'authenticate', [ $ob_class, 'sisanu_restrict_country' ], 30, 3 );
		add_filter( 'xmlrpc_enabled', [ $ob_class, 'xmlrpc_auth_methods_enabled' ], 30, 3 );

		// Maybe hookup redirects.
		if ( ! empty( self::$custom_redirects['status'] ) ) {
			if ( ! empty( self::$custom_redirects['register'] ) ) {
				add_action( 'wp_loaded', [ $ob_class, 'maybe_restrict_register_url' ] );
			}
			if ( ! empty( self::$custom_redirects['login'] ) ) {
				add_filter( 'wp_loaded', [ $ob_class, 'maybe_restrict_login_url' ] );
			}
			if ( ! empty( self::$custom_redirects['urls'] ) ) {
				add_filter( 'template_redirect', [ $ob_class, 'maybe_restrict_custom_url' ], 0 );
			}
		}
	}

	/**
	 * This is login script.
	 *
	 * @return bool
	 */
	public static function this_is_login() {
		if ( function_exists( 'is_login' ) ) {
			return is_login();
		} elseif ( ! empty( $_SERVER['SCRIPT_NAME'] ) ) {
			return false !== stripos( wp_login_url(), $_SERVER['SCRIPT_NAME'] ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * Current page is login or register.
	 *
	 * @param  string $page_type Login or register page.
	 * @return bool
	 */
	public static function current_page_is( $page_type ) {
		global $pagenow;

		if ( empty( $pagenow ) || empty( $page_type ) ) {
			return false;
		}

		if ( 'login' === $page_type ) {
			if ( self::this_is_login() ) {
				return true;
			}

			// Fallback to page now test.
			return ! empty( $pagenow ) && 'wp-login.php' === $pagenow;
		} elseif ( 'register' === $page_type ) {
			if ( self::this_is_login() ) {
				return true;
			}

			// Fallback to page now test.
			return ! empty( $pagenow ) && 'wp-login.php' === $pagenow
				&& ! empty( $_REQUEST['action'] ) && 'register' === $_REQUEST['action']; // phpcs:ignore
		}

		return false;
	}

	/**
	 * Current request if possible.
	 *
	 * @return string
	 */
	public static function current_uri() {
		global $wp;
		return ! empty( $_SERVER['REQUEST_URI'] )
			? wp_unslash( $_SERVER['REQUEST_URI'] ) //phpcs:ignore
			: home_url( $wp->request );
	}

	/**
	 * Redirect the login URL.
	 */
	public static function maybe_restrict_login_url() {
		if ( self::current_page_is( 'login' ) || get_permalink() === wp_login_url()
			|| substr_count( self::current_uri(), 'wp-login' )
		) {
			$restrict = self::user_has_restriction();
			if ( $restrict ) {
				status_header( 403 );
				wp_redirect( home_url() ); // phpcs:ignore
				exit();
			}
		}
	}

	/**
	 * Redirect the register URL.
	 */
	public static function maybe_restrict_register_url() {
		$curent_uri = self::current_uri();
		if ( self::current_page_is( 'register' ) || get_permalink() === wp_registration_url()
			|| ( substr_count( $curent_uri, 'wp-login' ) && substr_count( $curent_uri, 'action=register' ) )
		) {
			$restrict = self::user_has_restriction();
			if ( $restrict ) {
				status_header( 403 );
				wp_redirect( home_url() ); // phpcs:ignore
				exit();
			}
		}
	}

	/**
	 * Redirect the custom URL.
	 */
	public static function maybe_restrict_custom_url() {
		global $wp;
		$curent_url = home_url( $wp->request );
		if ( empty( $curent_url ) ) {
			// Fail-fast.
			return;
		}

		if ( in_array( $curent_url, self::$custom_redirects['urls'], true )
			|| in_array( trailingslashit( $curent_url ), self::$custom_redirects['urls'], true ) ) {
			$restrict = self::user_has_restriction();
			if ( $restrict ) {
				status_header( 403 );
				wp_redirect( home_url() ); // phpcs:ignore
				exit();
			}
		}
	}

	/**
	 * Load the plugin settings.
	 */
	public static function load_settings() {
		self::$allowed_ips       = maybe_unserialize( get_option( SISANU_RCIL_DB_OPTION . '_allow_ips', [ '*' ] ) );
		self::$blocked_ips       = maybe_unserialize( get_option( SISANU_RCIL_DB_OPTION . '_block_ips', [] ) );
		self::$allowed_countries = maybe_unserialize( get_option( SISANU_RCIL_DB_OPTION . '_allow_countries', [ '*' ] ) );
		self::$blocked_countries = maybe_unserialize( get_option( SISANU_RCIL_DB_OPTION . '_block_countries', [] ) );

		if ( ! is_array( self::$allowed_countries ) ) {
			self::$allowed_countries = [ '*' ];
		}
		if ( ! is_array( self::$blocked_countries ) ) {
			self::$blocked_countries = [];
		}
		if ( ! is_array( self::$allowed_ips ) ) {
			self::$allowed_ips = [ '*' ];
		}
		if ( ! is_array( self::$blocked_ips ) ) {
			self::$blocked_ips = [];
		}

		self::$all_countries = ( in_array( '*', self::$allowed_countries, true ) ) ? true : false;
		self::$all_ips       = ( in_array( '*', self::$allowed_ips, true ) ) ? true : false;

		if ( ! empty( self::$blocked_ips ) ) {
			self::$all_ips = false;
		}
		if ( ! empty( self::$blocked_countries ) ) {
			self::$all_countries = false;
		}

		$redirects              = maybe_unserialize( get_option( SISANU_RCIL_DB_OPTION . '_custom_redirects', [] ) );
		self::$custom_redirects = wp_parse_args( $redirects, self::$custom_redirects );
		self::$bypass_roles     = maybe_unserialize( get_option( SISANU_RCIL_DB_OPTION . '_bypass_roles', [] ) );
		self::$no_roles_bypass  = empty( self::$bypass_roles ) ? true : false;

		$default = [
			'keep_settings'       => true,
			'temp_disable'        => false,
			'lockout_duration'    => 60, // * MINUTE_IN_SECONDS,
			'redirect_404'        => false,
			'users_lockout'       => false,
			'wc_customer_country' => false,
			'user_login_ip'       => [],
			'simulate_ip'         => '',
			'simulate_country'    => '',
			'simulate_token'      => '',
			'forbidden_text'      => __( 'For some reason the authentication for your account is restricted. Please contact the administrator.', 'slicr' ),
			'xmlrpc_auth_filter'  => '',
			'rule_type'           => 0,
			'bypass_php_geoip'    => false,
			'force_remove_local'  => false,
			'include_forward_ip'  => false,
		];

		self::$settings = maybe_unserialize( get_option( SISANU_RCIL_DB_OPTION . '_settings', [] ) );
		self::$settings = wp_parse_args( self::$settings, $default );

		$ips = implode( ',', array_merge( self::$allowed_ips, self::$blocked_ips ) );
		$cos = implode( ',', array_merge( self::$allowed_countries, self::$blocked_countries ) );

		self::$rules = (object) [
			'type'     => self::$settings['rule_type'],
			'restrict' => (object) [
				'ip' => ( empty( self::$all_ips ) ) ? true : false,
				'co' => ( empty( self::$all_countries ) ) ? true : false,
			],
			'block'    => (object) [
				'ip' => array_diff( self::$blocked_ips, [ '*' ] ),
				'co' => array_diff( self::$blocked_countries, [ '*' ] ),
			],
			'allow'    => (object) [
				'ip' => array_diff( self::$allowed_ips, [ '*' ] ),
				'co' => array_diff( self::$allowed_countries, [ '*' ] ),
			],
		];

		if ( ! empty( self::$settings['force_remove_local'] ) ) {
			self::$rules->allow->ip = array_diff( self::$rules->allow->ip, [ '127.0.0.1', '::1' ] );
		}

		self::$rules->wildcard = (object) [
			'ip' => ( substr_count( $ips, '*' ) ) ? true : false,
			'co' => ( empty( $cos ) || substr_count( $cos, '*' ) ) ? true : false,
		];
	}

	/**
	 * The actions to be executed when the plugin is activated.
	 */
	public static function maybe_upgrade_version() {
		$db_version = get_option( SISANU_RCIL_DB_OPTION . '_db_ver', 0 );
		if ( empty( $db_version ) || (float) SISANU_RCIL_CURRENT_DB_VERSION !== (float) $db_version ) {
			// Preserve the previous settings if possible.
			$get_prev_ip  = get_option( SISANU_RCIL_DB_OPTION . '_allow_ips', [ '*' ] );
			$get_prev_co  = get_option( SISANU_RCIL_DB_OPTION . '_allow_countries', [ '*' ] );
			$get_prev_bco = get_option( SISANU_RCIL_DB_OPTION . '_block_countries', [] );
			$get_prev_ro  = get_option( SISANU_RCIL_DB_OPTION . '_bypass_roles', [] );
			$get_prev_se  = get_option( SISANU_RCIL_DB_OPTION . '_settings', self::$settings );

			update_option( SISANU_RCIL_DB_OPTION . '_allow_countries', $get_prev_co );
			update_option( SISANU_RCIL_DB_OPTION . '_allow_ips', $get_prev_ip );
			update_option( SISANU_RCIL_DB_OPTION . '_block_countries', $get_prev_bco );
			update_option( SISANU_RCIL_DB_OPTION . '_bypass_roles', $get_prev_ro );
			update_option( SISANU_RCIL_DB_OPTION . '_settings', $get_prev_se );
			update_option( SISANU_RCIL_DB_OPTION . '_db_ver', SISANU_RCIL_CURRENT_DB_VERSION );
		}
	}

	/**
	 * The actions to be executed when the plugin is activated.
	 */
	public static function activate_plugin() {
		self::maybe_upgrade_version();
		set_transient( self::PLUGIN_TRANSIENT, true );
	}

	/**
	 * The actions to be executed when the plugin is deactivated.
	 */
	public static function deactivate_plugin() {
		if ( empty( self::$settings['keep_settings'] ) ) {
			delete_option( SISANU_RCIL_DB_OPTION . '_db_ver' );
			delete_option( SISANU_RCIL_DB_OPTION . '_allow_countries' );
			delete_option( SISANU_RCIL_DB_OPTION . '_allow_ips' );
			delete_option( SISANU_RCIL_DB_OPTION . '_block_countries' );
			delete_option( SISANU_RCIL_DB_OPTION . '_block_ips' );
			delete_option( SISANU_RCIL_DB_OPTION . '_custom_redirects' );
			delete_option( SISANU_RCIL_DB_OPTION . '_bypass_roles' );
			delete_option( SISANU_RCIL_DB_OPTION . '_settings' );
			delete_option( SISANU_RCIL_DB_OPTION . '_actions_notices' );
		}
		self::plugin_admin_notices_cleanup( false );
	}

	/**
	 * Load the plugin assets.
	 */
	public static function load_assets() {
		$current_screen = \get_current_screen();
		if ( empty( $current_screen->id )
			|| 'settings_page_login-ip-country-restriction-settings' !== $current_screen->id ) {
			// Fail-fast, we only add assets to this page.
			return;
		}

		$deps = [
			'dependencies' => [],
			'version'      => time(),
		];

		if ( file_exists( SISANU_RCIL_DIR . 'build/index.asset.php' ) ) {
			$deps = require_once SISANU_RCIL_DIR . 'build/index.asset.php';
		}

		if ( file_exists( SISANU_RCIL_DIR . 'build/index.js' ) && ! wp_script_is( SISANU_RCIL_SLUG ) ) {
			wp_register_script( SISANU_RCIL_SLUG, SISANU_RCIL_URL . 'build/index.js', $deps['dependencies'], $deps['version'], true );
			wp_localize_script( SISANU_RCIL_SLUG, str_replace( '-', '', SISANU_RCIL_SLUG ) . 'Settings', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			] );
			wp_enqueue_script( SISANU_RCIL_SLUG );
		}

		if ( file_exists( SISANU_RCIL_DIR . 'build/index.css' ) && ! wp_style_is( SISANU_RCIL_SLUG ) ) {
			wp_enqueue_style( SISANU_RCIL_SLUG, SISANU_RCIL_URL . 'build/index.css', [], $deps['version'], false );
			wp_add_inline_style( SISANU_RCIL_SLUG, self::preset_colors() );
		}
	}

	/**
	 * Make preset colors tokens.
	 *
	 * @return string
	 */
	public static function preset_colors() {
		global $_wp_admin_css_colors;

		$user_id = get_current_user_id();
		$scheme  = get_user_option( 'admin_color', $user_id );
		$colors  = $_wp_admin_css_colors[ $scheme ]->colors ?? [];
		$dark    = $colors[0] ?? '#1e1e1e';
		$main    = $colors[2] ?? '#2271b1';
		if ( 'light' === $scheme ) {
			$main = $colors[3] ?? '#2271b1';
		} elseif ( 'modern' === $scheme ) {
			$main = $colors[1] ?? '#2271b1';
		} elseif ( 'blue' === $scheme ) {
			$main = '#e1a948';
		} elseif ( 'midnight' === $scheme ) {
			$main = $colors[3] ?? '#2271b1';
		}

		// Return the minified string.
		$style = ':root { --slicr--color-main: ' . $main . '; --slicr--color-border: color-mix(in srgb, ' . $main . ' 30%, transparent); }';
		$style = ! empty( $style ) ? trim( preg_replace( '/\s\s+/', ' ', $style ) ) : '';
		return $style;
	}

	/**
	 * Add the new menu in settings section that allows to configure the restriction.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'options-general.php',
			'<div class="dashicons dashicons-admin-site"></div> ' . esc_html__( 'Login IP & Country Restriction Settings', 'slicr' ),
			'<div class="dashicons dashicons-admin-site"></div> ' . esc_html__( 'Login IP & Country Restriction Settings', 'slicr' ),
			'manage_options',
			'login-ip-country-restriction-settings',
			[ get_called_class(), 'login_ip_country_restriction_settings' ]
		);
	}

	/**
	 * Reset all options.
	 */
	public static function reset_all_settings() {
		$setup = [ '_allow_countries', '_allow_ips', '_block_countries', '_block_ips', '_custom_redirects', '_bypass_roles', '_settings' ];

		foreach ( $setup as $item ) {
			delete_option( SISANU_RCIL_DB_OPTION . $item );
		}

		// Reset the plugin cache.
		self::reset_plugin_transients();
		delete_option( SISANU_RCIL_DB_OPTION . '_db_ver' );

		// Refresh the plugin object properties.
		self::load_settings();

		// Add admin notice on flushed transients.
		self::add_admin_notice( esc_html__( 'The settings were reset to default.', 'slicr' ) );
	}

	/**
	 * Import settings.
	 *
	 * @param string $import Import setting JSON string.
	 */
	public static function import_settings( $import ) { //phpcs:ignore
		$data = json_decode( $import, true );
		if ( empty( $data ) || ! is_array( $data ) ) {
			// Add admin notice on flushed transients.
			self::add_admin_notice( esc_html__( 'The settings were not imported.', 'slicr' ), 'error' );
			return;
		}

		$setup = [ '_allow_countries', '_allow_ips', '_block_countries', '_block_ips', '_custom_redirects', '_bypass_roles', '_settings' ];

		foreach ( $data as $slug => $item ) {
			if ( in_array( $slug, $setup, true ) ) {
				update_option( SISANU_RCIL_DB_OPTION . $slug, $item );
			}
		}

		// Reset the plugin cache.
		self::reset_plugin_transients();
		delete_option( SISANU_RCIL_DB_OPTION . '_db_ver' );

		// Refresh the plugin object properties.
		self::load_settings();

		// Add admin notice on flushed transients.
		self::add_admin_notice( esc_html__( 'The settings were imported.', 'slicr' ) );
	}

	/**
	 * Remove the transients set when verifying the restrictions.
	 */
	public static function reset_plugin_transients() {
		global $wpdb;
		// Remove all the transients records in one query.
		$tmp_query = $wpdb->prepare(
			' DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE %s OR option_name LIKE %s ',
			$wpdb->esc_like( '_transient_rcil-geo' ) . '%',
			$wpdb->esc_like( '_transient_timeout_rcil-geo' ) . '%'
		);
		$wpdb->query( $tmp_query ); // phpcs:ignore

		if ( is_multisite() ) {
			// Attempt to flush transient also on multisite.
			$tmp_query = $wpdb->prepare(
				' DELETE FROM ' . $wpdb->sitemeta . ' WHERE meta_key LIKE %s OR option_name LIKE %s ',
				$wpdb->esc_like( '_transient_rcil-geo' ) . '%',
				$wpdb->esc_like( '_transient_timeout_rcil-geo' ) . '%'
			);
			$wpdb->query( $tmp_query ); // phpcs:ignore
		}
	}

	/**
	 * Maybe execute the options update if the nonce is valid, then redirect.
	 */
	public static function maybe_save_settings() {
		$nonce = filter_input( INPUT_POST, '_login_ip_country_restriction_settings_nonce', FILTER_DEFAULT );
		if ( ! empty( $nonce ) ) {
			if ( ! wp_verify_nonce( $nonce, '_login_ip_country_restriction_settings_save' ) ) {
				wp_die( esc_html__( 'Action not allowed.', 'slicr' ), esc_html__( 'Security Breach', 'slicr' ) );
			}

			$tab = filter_input( INPUT_POST, 'tab', FILTER_DEFAULT );
			$tab = ( empty( $tab ) ) ? 0 : (int) $tab;
			$tab = ( $tab < 0 || $tab > 5 ) ? 0 : $tab;
			$url = admin_url( 'options-general.php?page=login-ip-country-restriction-settings' );

			// Reset the plugin cache.
			self::reset_plugin_transients();

			self::load_settings();
			$opt = self::$settings;
			$sel = filter_input(
				INPUT_POST,
				'_login_ip_country_restriction_settings',
				FILTER_DEFAULT, FILTER_REQUIRE_ARRAY
			);

			switch ( $tab ) {
				case 0:
					$opt['xmlrpc_auth_filter'] = filter_input( INPUT_POST, 'xmlrpc_auth_filter' );

					$opt['rule_type'] = filter_input( INPUT_POST, 'rule_type', FILTER_VALIDATE_INT );
					$opt['rule_type'] = apply_filters( 'check_rule_type_save', $opt['rule_type'] );
					update_option( SISANU_RCIL_DB_OPTION . '_settings', $opt );

					// Refresh the plugin object properties.
					self::load_settings();

					// Add admin notice on flushed transients.
					self::add_admin_notice( esc_html__( 'The settings were updated.', 'slicr' ) );
					do_action( 'sislrc_after_save_settings' );

					wp_safe_redirect( $url );
					exit;

				case 1:
					$_allow_ip_all      = sanitize_text_field( $sel['allow_ip_all'] );
					$_allow_ip_restrict = sanitize_text_field( $sel['allow_ip_restrict'] );
					$_allow_ip_block    = sanitize_text_field( $sel['allow_ip_block'] );

					if ( 'all' === $sel['allow_ip_all'] ) {
						$allow_ip = [ '*' ];
						$block_ip = [];

						// Not using the local removal option.
						$opt['force_remove_local'] = false;
						update_option( SISANU_RCIL_DB_OPTION . '_settings', $opt );

						$include_forward_ip        = ! empty( $sel['include_forward_ip'] );
						$opt['include_forward_ip'] = $include_forward_ip;
						update_option( SISANU_RCIL_DB_OPTION . '_settings', $opt );

						self::load_settings();
					} else {

						$force_remove_local        = ! empty( $sel['force_remove_local'] );
						$opt['force_remove_local'] = $force_remove_local;
						update_option( SISANU_RCIL_DB_OPTION . '_settings', $opt );

						$include_forward_ip        = ! empty( $sel['include_forward_ip'] );
						$opt['include_forward_ip'] = $include_forward_ip;
						update_option( SISANU_RCIL_DB_OPTION . '_settings', $opt );

						self::load_settings();

						$allow_ip = [];
						$block_ip = [];

						if ( ! empty( $_allow_ip_restrict ) ) {
							$_allow   = preg_replace( '/\s/', '', $_allow_ip_restrict );
							$allow_ip = explode( ',', $_allow );
							if ( false === $force_remove_local ) {
								$allow_ip[] = '127.0.0.1';
								$allow_ip[] = '::1';
							}
							$allow_ip = array_unique( $allow_ip );
							asort( $allow_ip );
						}

						if ( ! empty( $_allow_ip_block ) ) {
							$_allow_ip_block = preg_replace( '/\s/', '', $_allow_ip_block );
							$block_ip        = explode( ',', $_allow_ip_block );
							$block_ip        = array_unique( $block_ip );
							asort( $block_ip );
						}
					}
					update_option( SISANU_RCIL_DB_OPTION . '_allow_ips', $allow_ip );
					update_option( SISANU_RCIL_DB_OPTION . '_block_ips', $block_ip );

					// Add admin notice on flushed transients.
					self::add_admin_notice( esc_html__( 'The settings were updated.', 'slicr' ) );
					do_action( 'sislrc_after_save_settings' );

					wp_safe_redirect( $url . '&tab=1' );
					exit;

				case 2:
					if ( 'restrict' !== $sel['allow_country_all'] ) {
						$_allow_country_restrict = [ '*' ];
						$_allow_country_block    = [];
					} else {
						$_allow_country_restrict = ( ! empty( $sel['allow_country_restrict'] ) ) ? $sel['allow_country_restrict'] : [];
						$_allow_country_block    = ( ! empty( $sel['allow_country_block'] ) ) ? $sel['allow_country_block'] : [];
					}

					if ( ! empty( $sel['countries_filter'] ) && 'restrict' === $sel['allow_country_all'] ) {
						$sel['countries_filter'] = array_filter( $sel['countries_filter'] );
						if ( ! empty( $sel['countries_filter'] ) ) {
							foreach ( $sel['countries_filter'] as $key => $value ) {
								if ( 'allow' === $value ) {
									$_allow_country_restrict[] = $key;
								} elseif ( 'block' === $value ) {
									$_allow_country_block[] = $key;
								}
							}
						}

						if ( empty( $_allow_country_restrict ) && empty( $_allow_country_block ) ) {
							$_allow_country_restrict = [ '*' ];
						}
					}
					update_option( SISANU_RCIL_DB_OPTION . '_allow_countries', $_allow_country_restrict );
					update_option( SISANU_RCIL_DB_OPTION . '_block_countries', $_allow_country_block );

					// Add admin notice on flushed transients.
					self::add_admin_notice( esc_html__( 'The settings were updated.', 'slicr' ) );
					do_action( 'sislrc_after_save_settings' );

					wp_safe_redirect( $url . '&tab=2' );
					exit;

				case 3:
					// Process redirects settings.
					$_urls = [];
					if ( ! empty( $sel['redirect_urls'] ) ) {
						$_urls = preg_replace( '/\s/', '', $sel['redirect_urls'] );
						$_urls = explode( ',', $_urls );
						$_urls = array_unique( $_urls );
						asort( $_urls );
					}

					$custom_redirects             = self::$custom_redirects;
					$custom_redirects['status']   = ( ! empty( $sel['use_redirect'] ) ) ? 1 : 0;
					$custom_redirects['login']    = ( ! empty( $sel['redirect_login'] ) ) ? 1 : 0;
					$custom_redirects['register'] = ( ! empty( $sel['redirect_register'] ) ) ? 1 : 0;
					$custom_redirects['urls']     = $_urls;
					update_option( SISANU_RCIL_DB_OPTION . '_custom_redirects', $custom_redirects );

					// Add admin notice on flushed transients.
					self::add_admin_notice( esc_html__( 'The settings were updated.', 'slicr' ) );
					do_action( 'sislrc_after_save_settings' );

					wp_safe_redirect( $url . '&tab=3' );
					exit;

				case 4:
					do_action( 'sislrc_save_pro_settings' );
					do_action( 'sislrc_after_save_settings' );
					wp_safe_redirect( $url . '&tab=4' );
					exit;

				case 5:
					$maybe_reset = filter_input( INPUT_POST, 'reset-all-settings' );
					if ( ! empty( $maybe_reset ) ) {
						// Execute the reset.
						self::reset_all_settings();
					}
					$maybe_import = filter_input( INPUT_POST, 'import-all-settings' );
					$import       = filter_input( INPUT_POST, 'import' );
					if ( ! empty( $maybe_import ) && ! empty( $import ) ) {
						// Execute the reset.
						self::import_settings( $import );
					}

					$maybe_test = filter_input( INPUT_POST, 'test-ip' );
					$test_ip    = filter_input( INPUT_POST, 'test_ip' );
					if ( ! empty( $maybe_test ) && ! empty( $test_ip ) ) {
						global $country_code_detected_api;
						$trans_id  = 'rcil-test-' . md5( gmdate( 'Y-m-d' ) );
						$test_info = [
							'ip'  => $test_ip,
							'co'  => self::get_user_country_name( $test_ip, true ),
							'api' => $country_code_detected_api,
						];
						set_transient( $trans_id, $test_info, 1 * HOUR_IN_SECONDS );
					}

					$maybe_set_bypass = filter_input( INPUT_POST, 'disable-geoip-function' );
					if ( ! empty( $maybe_set_bypass ) ) {
						self::load_settings();
						$opt = self::$settings;

						$opt['bypass_php_geoip'] = true;
						update_option( SISANU_RCIL_DB_OPTION . '_settings', $opt );
					}

					$maybe_unset_bypass = filter_input( INPUT_POST, 'enable-geoip-function' );
					if ( ! empty( $maybe_unset_bypass ) ) {
						self::load_settings();
						$opt = self::$settings;

						$opt['bypass_php_geoip'] = false;
						update_option( SISANU_RCIL_DB_OPTION . '_settings', $opt );
					}

					do_action( 'sislrc_after_save_settings' );
					wp_safe_redirect( $url . '&tab=5' );
					exit;
			}
		}
	}

	/**
	 * Check rule type save.
	 *
	 * @param  int $type Rule type.
	 * @return int
	 */
	public static function check_rule_type_save( $type ) { // phpcs:ignore
		if ( ! self::$is_pro && ! in_array( $type, [ 0, 1, 6, 7, 8, 9 ], true ) ) {
			return 0;
		}
		if ( ! in_array( $type, [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ], true ) ) {
			return 0;
		}
		return $type;
	}

	/**
	 * Add admin notices.
	 *
	 * @param string $text  The text to be outputted as the admin notice.
	 * @param string $class The admin notice class (notice-success is-dismissible, notice-error).
	 */
	public static function add_admin_notice( $text, $class = 'notice-success is-dismissible' ) { //phpcs:ignore
		$items   = get_option( SISANU_RCIL_DB_OPTION . '_actions_notices', [] );
		$items[] = [
			'type' => $class,
			'text' => $text,
		];
		update_option( SISANU_RCIL_DB_OPTION . '_actions_notices', $items );
	}

	/**
	 * Outputs custom admin notices.
	 */
	public static function admin_notices() {
		$items = get_option( SISANU_RCIL_DB_OPTION . '_actions_notices', [] );
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				?>
				<div class="notice <?php echo esc_attr( $item['type'] ); ?>">
					<p><?php echo wp_kses_post( $item['text'] ); ?></p>
				</div>
				<?php
			}
		}
		update_option( SISANU_RCIL_DB_OPTION . '_actions_notices', [] );
	}

	/**
	 * Display the current status in terms of restrictions.
	 */
	public static function current_restriction_notice_card() {
		if ( self::$is_pro
			&& ( ! empty( self::$settings['simulate_ip'] ) || ! empty( self::$settings['simulate_country'] ) ) ) {
			if ( function_exists( 'RCIL\Pro\key_is_active' ) && \RCIL\Pro\key_is_active() ) {
				$res2  = self::current_user_has_restriction( self::$settings['simulate_ip'], self::$settings['simulate_country'] );
				$icon2 = ( true === $res2 ) ? '<span class="dashicons dashicons-warning"></span>' : '<span class="dashicons dashicons-yes-alt"></span>';
				?>
				<div class="card">
					<?php echo wp_kses_post( $icon2 ); ?>
					<ul>
						<li>
							<?php
							echo wp_kses_post( sprintf(
								// Translators: %1$s - list of IPs.
								__( 'You currently enabled a simulation with IP %1$s and country code %2$s.', 'slicr' ),
								'<b>' . self::$settings['simulate_ip'] . '</b> (' . self::get_user_country_name( self::$settings['simulate_ip'] ) . ')',
								'<b>' . self::$settings['simulate_country'] . '</b>'
							) );
							?>
						</li>
						<li>
							<?php if ( false === $res2 ) : ?>
								<?php esc_html_e( 'The login is allowed, based on assessing the current combination of IPs + country codes + rule type.', 'slicr' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'The login is blocked, based on assessing the current combination of IPs + country codes + rule type.', 'slicr' ); ?>
							<?php endif; ?>
							<?php
							if ( function_exists( 'RCIL\Pro\sislrc_pro_simulate_info' ) ) {
								\RCIL\Pro\sislrc_pro_simulate_info( false );
							}
							?>
						</li>
					</ul>
				</div>
				<?php
			}
		}

		$res  = self::current_user_has_restriction( self::get_current_ip(), self::get_user_country_name() );
		$icon = ( true === $res ) ? '<span class="dashicons dashicons-warning"></span>' : '<span class="dashicons dashicons-yes-alt"></span>';
		?>
		<div class="card">
			<?php echo wp_kses_post( $icon ); ?>
			<ul>
				<?php if ( true === $res ) : ?>
					<li class="info notice-error">
						<?php esc_html_e( 'The restriction will apply to your user as well! Please make sure you change the restriction to allow your own access.', 'slicr' ); ?>
					</li>
				<?php endif; ?>
				<li>
					<?php
					$text = '';
					if ( true === self::$settings['temp_disable'] ) {
						$text .= esc_html__( 'Based on the current setup all settings are temporarily disabled.', 'slicr' );
					} else {
						$text = self::describe_rule_by_type();
					}
					echo wp_kses_post( $text );

					if ( ! empty( self::$rules->wildcard->ip )
						&& in_array( self::$rules->type, [ 0, 1, 2, 3, 4, 5, 6, 8 ], true ) ) {
						echo ' <b>' . esc_html__( 'Please note that there is no IP specified or you have * in the IPs list, meaning there is no IP filter to apply.', 'slicr' ) . '</b>';
					}
					if ( ! empty( self::$rules->wildcard->co )
						&& in_array( self::$rules->type, [ 0, 1, 2, 3, 4, 5, 7, 9 ], true ) ) {
						echo ' <b>' . esc_html__( 'Please note that there is no country filter to apply.', 'slicr' ) . '</b>';
					}
					?>
				</li>
				<li>
					<?php
					echo wp_kses_post( sprintf(
						// Translators: %1$s - IP, %2$s - country code.
						__( 'Your current IP is %1$s and the country code is %2$s.', 'slicr' ),
						'<b>' . self::get_current_ip() . '</b>',
						'<b>' . self::get_user_country_name() . '</b>'
					) );
					?>
					<a id="sislrc-toggle-debug"><?php esc_html_e( 'Debug', 'slicr' ); ?></a><span id="sislrc-debug-ip" class="is-hidden">:
						<?php
						// phpcs:disable
						$forward = ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) : '';
						echo wp_kses_post( sprintf(
							// Translators: %1$s - IP, %2$s - country code.
							__( 'SERVER_ADDR %1$s / REMOTE_ADDR %2$s / HTTP_CF_IPCOUNTRY %3$s / HTTP_CF_CONNECTING_IP %4$s / HTTP_CLIENT_IP %5$s%6$s', 'slicr' ),
							( ! empty( $_SERVER['SERVER_ADDR'] ) ) ? '<b>' . wp_unslash( $_SERVER['SERVER_ADDR'] ) . '</b>' : '',
							( ! empty( $_SERVER['REMOTE_ADDR'] ) ) ? '<b>' . wp_unslash( $_SERVER['REMOTE_ADDR'] ) . '</b>' : '',
							( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ? '<b>' . wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) . '</b>' : '',
							( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) ? '<b>' . wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) . '</b>' : '',
							( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) ? '<b>' . wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) . '</b>' : '',
							! empty( self::$settings['include_forward_ip'] )
								? ' / HTTP_X_FORWARDED_FOR <b>' . $forward . '</b>' : ''
						) );
						// phpcs:enable
						?>
					</span>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Get current IP.
	 *
	 * @return string
	 */
	public static function get_current_ip() { //phpcs:ignore
		$ip = '';
		// phpcs:disable
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
		}

		if ( '127.0.0.1' === $ip || '::1' === $ip ) {
			if ( ! empty( self::$settings['include_forward_ip'] )
				&& ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
			}
		}

		// phpcs:enable
		if ( '127.0.0.1' === $ip || '::1' === $ip ) {
			return apply_filters( 'sislrc_the_user_ip', (string) $ip );
		}

		return (string) $ip;
	}

	/**
	 * Show the current settings and allow you to change the settings.
	 */
	public static function login_ip_country_restriction_settings() {
		// Verify user capabilities in order to deny the access if the user does not have the capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Action not allowed.', 'slicr' ) );
		}

		include_once __DIR__ . '/inc/setup-page.php';
	}

	/**
	 * First tab content.
	 *
	 * @param array $rules Custom rules.
	 */
	public static function tab0_content( $rules ) { //phpcs:ignore
		include_once __DIR__ . '/inc/setup-rules.php';
	}

	/**
	 * Second tab content.
	 *
	 * @param array $rules Custom rules.
	 */
	public static function tab1_content( $rules ) { //phpcs:ignore
		include_once __DIR__ . '/inc/setup-ips.php';
	}

	/**
	 * Third tab content.
	 *
	 * @param array $all_countries Countries list.
	 */
	public static function tab2_content( $all_countries ) { //phpcs:ignore
		include_once __DIR__ . '/inc/setup-countries.php';
	}

	/**
	 * Redirects tab content.
	 */
	public static function tab3_content() {
		include_once __DIR__ . '/inc/setup-redirect.php';
	}

	/**
	 * Pro tab content.
	 */
	public static function tab4_content() {
		if ( ! self::$is_pro ) {
			self::pro_teaser();
		}
		do_action( 'sislrc_display_pro_tabs_content' );
	}

	/**
	 * PRO teaser.
	 *
	 * @param string $type Teaser type.
	 */
	public static function pro_teaser( $type = 'regular' ) { //phpcs:ignore
		include_once __DIR__ . '/inc/setup-teaser.php';
	}

	/**
	 * Setup debug.
	 */
	public static function setup_debug_output() {
		include_once __DIR__ . '/inc/setup-debug.php';
	}

	/**
	 * Return the countries list.
	 *
	 * @return array
	 */
	public static function get_countries_list() { //phpcs:ignore
		include_once __DIR__ . '/inc/countries-list.php';
		return $all_countries;
	}

	/**
	 * Maybe fetch url content with cUrl.
	 *
	 * @param  string $url URL to be crawled.
	 * @return string
	 */
	public static function maybe_fetch_url( $url = '' ) { // phpcs:ignore
		$result = '';
		if ( function_exists( 'curl_setopt' ) ) {
			// phpcs:disable
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_AUTOREFERER, false );
			curl_setopt( $ch, CURLOPT_FAILONERROR, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
			curl_setopt( $ch, CURLOPT_HEADER, false );
			$result = @curl_exec( $ch );
			$code   = @curl_getinfo( $ch );
			curl_close( $ch );
			if ( ! empty( $code['http_code'] ) && '404' === $code['http_code'] ) {
				$result = '';
			}
			// phpcs:enable
		}
		return (string) $result;
	}

	/**
	 * Maybe a country code by cUrl.
	 *
	 * @param  string $url URL to be crawled.
	 * @return string
	 */
	public static function country_code_by_curl( $url = '' ) { //phpcs:ignore
		$code = '';
		$body = self::maybe_fetch_url( $url );
		if ( ! empty( $body ) ) {
			$user = @json_decode( $body ); // phpcs:ignore
			$code = ( ! empty( $user->geoplugin_countryCode ) ) ? $user->geoplugin_countryCode : ''; // PHPCS:ignore WordPress.NamingConventions.ValidVariableName
		}
		return (string) $code;
	}

	/**
	 * Maybe a country code by JSON fetch.
	 *
	 * @param  string $url URL to be crawled.
	 * @return string
	 */
	public static function country_code_by_json( $url = '' ) { //phpcs:ignore
		$code = '';
		$body = wp_remote_get( $url, [ 'timeout' => 120 ] );
		if ( ! is_wp_error( $body ) && ! empty( $body['body'] ) ) {
			$body = @json_decode( $body['body'] ); // phpcs:ignore
			$code = ( ! empty( $body->geoplugin_countryCode ) ) ? $body->geoplugin_countryCode : ''; // PHPCS:ignore WordPress.NamingConventions.ValidVariableName
		}
		return (string) $code;
	}

	/**
	 * Maybe a country code by php fetch.
	 *
	 * @param  string $url URL to be crawled.
	 * @return string
	 */
	public static function country_code_by_php( $url = '' ) { //phpcs:ignore
		$code = '';
		$body = maybe_unserialize( @file_get_contents( $url ) ); // phpcs:ignore
		if ( ! empty( $body['geoplugin_countryCode'] ) ) {
			$code = (string) $body['geoplugin_countryCode'];
		}
		return $code;
	}

	/**
	 * Retrieves the current user country code based on the user IP.
	 *
	 * @param  string $ip           Maybe an explicit IP.
	 * @param  bool   $bypass_cache Bypass or not the cache (defaults to false).
	 * @return string
	 */
	public static function get_user_country_name( $ip = '', $bypass_cache = false ) { //phpcs:ignore
		global $country_code_detected_api;
		$country_code = '!NA';
		$user_ip      = ( ! empty( $ip ) ) ? $ip : self::get_current_ip();
		$trans_id     = 'rcil-geo-' . md5( $user_ip );
		$country_code = get_transient( $trans_id );
		if ( true === $bypass_cache || false === $country_code ) {
			$duration = ( ! empty( self::$settings['lockout_duration'] ) ) ? (int) self::$settings['lockout_duration'] : 60;
			$duration = $duration * MINUTE_IN_SECONDS;
			if ( function_exists( 'geoip_record_by_name' ) ) {
				if ( empty( self::$settings['bypass_php_geoip'] ) ) {
					// If GeoIP library is available, then let's use this.
					$user_details = geoip_record_by_name( $user_ip );
					$country_code = ( ! empty( $user_details['country_code'] ) ) ? $user_details['country_code'] : $country_code;

					$country_code_detected_api = 'PHP `geoip_record_by_name`';
					set_transient( $trans_id, $country_code, $duration );
					return $country_code;
				}
			}

			// First attempt by cUrl.
			$country_code = self::country_code_by_curl( 'http://www.geoplugin.net/json.gp?ip=' . $user_ip );
			if ( ! empty( $country_code ) && '!NA' !== $country_code ) {
				// Fail-fast, we found it.
				$country_code_detected_api = 'CURL';
				set_transient( $trans_id, $country_code, $duration );
				return $country_code;
			}

			// The GeoIP library is not available, so we are trying to use the public GeoPlugin.
			$country_code = self::country_code_by_json( 'http://www.geoplugin.net/json.gp?ip=' . $user_ip );
			if ( ! empty( $country_code ) && '!NA' !== $country_code ) {
				// Fail-fast, we found it.
				$country_code_detected_api = 'JSON';
				set_transient( $trans_id, $country_code, $duration );
				return $country_code;
			}

			$country_code = self::country_code_by_php( 'http://www.geoplugin.net/php.gp?ip=' . $user_ip );
			if ( ! empty( $country_code ) && '!NA' !== $country_code ) {
				// Fail-fast, we found it.
				$country_code_detected_api = 'PHP';
				set_transient( $trans_id, $country_code, $duration );
				return $country_code;
			}

			$country_code = '!NA';
			set_transient( $trans_id, $country_code, $duration );
		}

		return (string) $country_code;
	}

	/**
	 * Check bypass single login.
	 *
	 * @param  int    $forbid Current count.
	 * @param  string $ip     Check IP.
	 * @return int
	 */
	public static function check_bypass_single_login( $forbid, $ip ) { // phpcs:ignore
		if ( ! empty( self::$user_id ) && self::$is_pro && function_exists( 'RCIL\Pro\user_bypass_single_login' ) ) {
			$bypass = RCIL\Pro\user_bypass_single_login( self::$user_id, $ip );
			if ( false === $bypass ) {
				++$forbid;
			}
		}
		return $forbid;
	}

	/**
	 * Forbidden screen.
	 */
	public static function forbidden_screen() {
		if ( self::$is_pro && function_exists( 'RCIL\Pro\forbidden_custom_splash' ) ) {
			RCIL\Pro\forbidden_custom_splash();
		} else {
			// This is the default forbidden screen for all cases.
			status_header( 403 );
			wp_die( esc_html__( 'Forbidden!', 'slicr' ) );
		}
	}

	/**
	 * Assess if the current user has restrictions.
	 *
	 * @return bool
	 */
	public static function user_has_restriction() { //phpcs:ignore
		if ( false === self::$curent_user_assessed || ! empty( self::$simulate ) ) {
			// Proceed with the computation.
			if ( ! empty( self::$simulate ) ) {
				// This is a simulation.
				$code_co = self::$simulate['simulate_country'];
				$user_ip = self::$simulate['simulate_ip'];
				if ( empty( $code_co ) ) {
					$code_co = self::get_user_country_name( $user_ip );
				}
			} else {
				// This is real check, no simulation.
				$user_ip = self::get_current_ip();
				$code_co = self::get_user_country_name();
			}

			self::$curent_user_restriction = self::current_user_has_restriction( $user_ip, $code_co );
			self::$curent_user_assessed    = true;
		}

		// If we got this far, the user restriction was assessed.
		return self::$curent_user_restriction;
	}

	/**
	 * Returns the IP range string.
	 *
	 * @param  string $ip Initial IP.
	 * @return string
	 */
	public static function ip_range( $ip ) {
		$range = explode( '.', $ip );
		array_pop( $range );
		return implode( '.', $range ) . '.~';
	}

	/**
	 * Country code is whitelisted.
	 *
	 * @param  string $code Country code.
	 * @return bool
	 */
	public static function country_is_whitelisted( $code = '' ) { // phpcs:ignore
		if ( false === self::$rules->restrict->co || '!NA' === $code ) {
			// Fail-fast, no country restriction or code not identified.
			return true;
		}
		if ( ! in_array( $code, self::$rules->allow->co, true ) ) {
			// There is a restriction but the country is not in the allowed list.
			return false;
		}
		return true;
	}

	/**
	 * Country code is blacklisted.
	 *
	 * @param  string $code Country code.
	 * @return bool
	 */
	public static function country_is_blacklisted( $code = '' ) { // phpcs:ignore
		if ( false === self::$rules->restrict->co || '!NA' === $code ) {
			// Fail-fast, no country restriction or code not identified.
			return false;
		}
		if ( in_array( $code, self::$rules->block->co, true ) ) {
			// There is a restriction but the country is in the blocked list.
			return true;
		}
		return false;
	}

	/**
	 * IP is whitelisted.
	 *
	 * @param  string $ip IP code.
	 * @return bool
	 */
	public static function ip_is_whitelisted( $ip = '' ) { // phpcs:ignore
		if ( 0 === self::$rules->type ) {
			if ( in_array( $ip, self::$rules->allow->ip, true )
				|| in_array( self::ip_range( $ip ), self::$rules->allow->ip, true ) ) {
				// There is a restriction and the IP or IP range is in the allowed list.
				return true;
			} else {
				// Break here for type 0.
				return false;
			}
		}

		if ( false === self::$rules->restrict->ip ) {
			// Fail-fast, no IP restriction.
			return true;
		}

		if ( in_array( $ip, self::$rules->allow->ip, true )
			|| in_array( self::ip_range( $ip ), self::$rules->allow->ip, true ) ) {
			// There is a restriction and the IP or IP range is in the allowed list.
			return true;
		}

		return false;
	}

	/**
	 * IP is blacklisted.
	 *
	 * @param  string $ip IP code.
	 * @return bool
	 */
	public static function ip_is_blacklisted( $ip = '' ) { // phpcs:ignore
		if ( false === self::$rules->restrict->ip ) {
			// Fail-fast, no IP restriction.
			return false;
		}

		if ( in_array( $ip, self::$rules->block->ip, true )
			|| in_array( self::ip_range( $ip ), self::$rules->block->ip, true ) ) {
			// There is a restriction and the IP or IP range is in the blocked list.
			return true;
		}

		return false;
	}

	/**
	 * Assess rule by type.
	 *
	 * @param  int    $forbid Forbidden rules matched.
	 * @param  string $co     Maybe a country code.
	 * @param  string $ip     Maybe an IP code.
	 * @return bool
	 */
	public static function assess_rule_by_type( $forbid, $co = '', $ip = '' ) { // phpcs:ignore
		$ip = ! empty( $ip ) ? $ip : self::get_current_ip();
		$co = ! empty( $co ) ? $co : self::get_user_country_name( $ip );

		$forbid   = self::check_bypass_single_login( $forbid, $ip );
		$ip_white = self::ip_is_whitelisted( $ip );
		$ip_black = self::ip_is_blacklisted( $ip );
		$co_white = self::country_is_whitelisted( $co );
		$co_black = self::country_is_blacklisted( $co );

		if ( 0 === self::$rules->type ) {
			// Allow login only for allowed countries or allowed IPs.
			if ( ! ( $ip_white || $co_white ) ) {
				++$forbid;
			}
		} elseif ( 1 === self::$rules->type ) {
			// Block login only for blocked countries or blocked IPs.
			if ( $ip_black || $co_black ) {
				++$forbid;
			}
		} elseif ( 6 === self::$rules->type ) {
			// Allow login only for allowed IPs.
			if ( ! $ip_white ) {
				++$forbid;
			}
		} elseif ( 7 === self::$rules->type ) {
			// Allow login only from allowed countries.
			if ( ! $co_white ) {
				++$forbid;
			}
		} elseif ( 8 === self::$rules->type ) {
			// Block login only for blocked IPs.
			if ( $ip_black ) {
				++$forbid;
			}
		} elseif ( 9 === self::$rules->type ) {
			// Block login only from blocked countries.
			if ( $co_black ) {
				++$forbid;
			}
		}

		return $forbid;
	}

	/**
	 * Assess if the specified user has restrictions.
	 *
	 * @param  string $ip           IP address.
	 * @param  string $country_code Country code.
	 * @return bool
	 */
	public static function current_user_has_restriction( $ip, $country_code ) { //phpcs:ignore
		$forbid = 0;
		$forbid = apply_filters( 'assess_rule_by_type', $forbid, $country_code, $ip );

		return ! empty( $forbid ) ? true : false;
	}

	/**
	 * Describe rule by type.
	 *
	 * @return string
	 */
	public static function describe_rule_by_type() { //phpcs:ignore
		$text = '';
		if ( ! self::$rules->restrict->ip && ! self::$rules->restrict->co ) {
			return esc_html__( 'Based on the current options there is no login restriction.', 'slicr' );
		} else {
			switch ( self::$rules->type ) {
				case 6:
					$text = wp_kses_post( sprintf(
						// Translators: %1$s - list of country names.
						__( 'Based on the current options there is a login restriction, this is allowed only for these IPs: %1$s.', 'slicr' ),
						empty( self::$rules->allow->ip )
							? __( 'any', 'slicr' )
							: '<span class="allow-list">' . self::CHAR_ALLOW . ' ' . implode( ', ' . self::CHAR_ALLOW . ' ', self::$rules->allow->ip ) . '</span>'
					) );
					break;

				case 7:
					$text = wp_kses_post( sprintf(
						// Translators: %1$s - list of country names.
						__( 'Based on the current options there is a login restriction, this is allowed only from these countries: %1$s.', 'slicr' ),
						empty( self::$rules->allow->co )
							? __( 'none', 'slicr' )
							: '<span class="allow-list">' . self::CHAR_ALLOW . ' ' . implode( ', ' . self::CHAR_ALLOW . ' ', self::$rules->allow->co ) . '</span>'
					) );
					break;

				case 8:
					$text = wp_kses_post( sprintf(
						// Translators: %1$s - list of country names.
						__( 'Based on the current options there is a login restriction, this is blocked for these IPs: %1$s.', 'slicr' ),
						empty( self::$rules->block->ip )
							? __( 'none', 'slicr' )
							: '<span class="block-list">' . self::CHAR_BLOCK . ' ' . implode( ', ' . self::CHAR_BLOCK . ' ', self::$rules->block->ip ) . '</span>'
					) );
					break;

				case 9:
					$text = wp_kses_post( sprintf(
						// Translators: %1$s - list of country names.
						__( 'Based on the current options there is a login restriction, this is blocked from these countries: %1$s.', 'slicr' ),
						empty( self::$rules->block->co )
							? __( 'none', 'slicr' )
							: '<span class="block-list">' . self::CHAR_BLOCK . ' ' . implode( ', ' . self::CHAR_BLOCK . ' ', self::$rules->block->co ) . '</span>'
					) );
					break;

				case 1:
					$text = wp_kses_post( sprintf(
						// Translators: %1$s - list of country names.
						__( 'Based on the current options there is a login restriction, this is blocked for these IPs: %1$s and from these countries: %2$s.', 'slicr' ),
						empty( self::$rules->block->ip )
							? __( 'none', 'slicr' )
							: '<span class="block-list">' . self::CHAR_BLOCK . ' ' . implode( ', ' . self::CHAR_BLOCK . ' ', self::$rules->block->ip ) . '</span>',
						empty( self::$rules->block->co )
							? __( 'none', 'slicr' )
							: '<span class="block-list">' . self::CHAR_BLOCK . ' ' . implode( ', ' . self::CHAR_BLOCK . ' ', self::$rules->block->co ) . '</span>'
					) );
					break;

				case 0:
				default:
					$text = wp_kses_post( sprintf(
						// Translators: %1$s - list of country names.
						__( 'Based on the current options there is a login restriction, this is allowed from these IPs: %1$s and from these countries: %2$s.', 'slicr' ),
						( self::$rules->wildcard->ip )
							? __( 'any', 'slicr' )
							: '<span class="allow-list">' . self::CHAR_ALLOW . ' ' . implode( ', ' . self::CHAR_ALLOW . ' ', self::$rules->allow->ip ) . '</span>',
						empty( self::$rules->allow->co )
							? __( 'none', 'slicr' )
							: '<span class="allow-list">' . self::CHAR_ALLOW . ' ' . implode( ', ' . self::CHAR_ALLOW . ' ', self::$rules->allow->co ) . '</span>'
					) );
					break;
			}
		}

		$text = apply_filters( 'describe_rule_by_type', $text );
		return $text;
	}

	/**
	 * Returns the current user if this is allowed (hence defaults to WordPress functionality)
	 * or forbid access to authentication.
	 *
	 * @param  \WP_User $user     Potential WP_User instance.
	 * @param  string   $username Username.
	 * @param  string   $password Passeword.
	 * @return object
	 */
	public static function sisanu_restrict_country( $user, $username, $password ) { // phpcs:ignore
		self::$user_id = ! empty( $user->ID ) ? $user->ID : 0;

		$role_bypass = apply_filters( 'sislrc_maybe_role_bypass', false, $user );
		if ( true === $role_bypass ) {
			// This is probably a customer as his role is in the list of bypassed.
			if ( ! empty( self::$user_id ) && self::$is_pro && function_exists( 'RCIL\Pro\sislrc_pro_collect_first_ip' ) ) {
				RCIL\Pro\sislrc_pro_collect_first_ip( self::$user_id );
			}
			return $user;
		}

		$restrict = self::user_has_restriction();
		if ( ! empty( $restrict ) ) {
			// The user country based on the user IP is not in the list of allowed countries and also the user IP is not in the allowed IPs list.
			wp_logout();
			do_action( 'sislrc_maybe_404_redirect' );
			self::forbidden_screen();
		} else {
			// If we got this far, the user seems legit.
			if ( ! empty( self::$settings['users_lockout'] ) && ! empty( self::$user_id ) ) {
				$lockout = get_user_meta( self::$user_id, 'rcil-user-lockout', true );
				if ( ! empty( $lockout ) ) {
					wp_logout();
					do_action( 'sislrc_maybe_404_redirect' );
					self::forbidden_screen();
				}
			}

			if ( ! empty( self::$user_id ) && self::$is_pro && function_exists( 'RCIL\Pro\sislrc_pro_collect_first_ip' ) ) {
				RCIL\Pro\sislrc_pro_collect_first_ip( self::$user_id );
			}
			return $user;
		}
	}

	/**
	 * Disable or not the XML-RPC methods that require authentication,
	 * based on the current visitor restriction or not.
	 *
	 * @param  bool $enabled True if the XML-RPC methods that require authentication are enabled.
	 * @return bool
	 */
	public static function xmlrpc_auth_methods_enabled( $enabled ) { //phpcs:ignore
		if ( empty( self::$settings['xmlrpc_auth_filter'] ) ) {
			// Fallback to the initial state.
			return $enabled;
		} elseif ( 'all' === self::$settings['xmlrpc_auth_filter'] ) {
			// Disable all the time.
			return false;
		} else {
			$restrict = self::user_has_restriction();
			if ( ! empty( $restrict ) ) {
				// Disable only for a restriction.
				return false;
			}
		}

		// Fallback to the initial state.
		return $enabled;
	}

	/**
	 * Add the plugin settings and plugin URL links.
	 *
	 * @param  array $links The plugin links.
	 * @return array
	 */
	public static function plugin_action_links( $links ) { //phpcs:ignore
		$all   = [];
		$all[] = '<a href="' . esc_url( self::$plugin_url ) . '">' . esc_html__( 'Settings', 'slicr' ) . '</a>';
		$all[] = '<a href="https://iuliacazan.ro/login-ip-country-restriction">' . esc_html__( 'Plugin URL', 'slicr' ) . '</a>';
		$all   = array_merge( $all, $links );
		return $all;
	}

	/**
	 * The actions to be executed when the plugin is updated.
	 *
	 * @return void
	 */
	public static function plugin_ver_check() {
		$opt = str_replace( '-', '_', self::PLUGIN_TRANSIENT ) . '_db_ver';
		$dbv = get_option( $opt, 0 );
		if ( SISANU_RCIL_CURRENT_DB_VERSION !== (float) $dbv ) {
			update_option( $opt, SISANU_RCIL_CURRENT_DB_VERSION );
			self::activate_plugin();
		}
	}

	/**
	 * Execute notices cleanup.
	 *
	 * @param bool $ajax Is AJAX call.
	 */
	public static function plugin_admin_notices_cleanup( $ajax = true ) { //phpcs:ignore
		// Delete transient, only display this notice once.
		delete_transient( self::PLUGIN_TRANSIENT );

		if ( true === $ajax ) {
			// No need to continue.
			wp_die();
		}
	}

	/**
	 * Admin notices.
	 */
	public static function plugin_admin_notices() {
		if ( apply_filters( 'slicr_filter_remove_update_info', false ) ) {
			return;
		}

		$maybe_trans = get_transient( self::PLUGIN_TRANSIENT );
		if ( ! empty( $maybe_trans ) ) {
			$slug      = md5( SISANU_RCIL_SLUG );
			$title     = __( 'Login IP & Country Restriction', 'slicr' );
			$donate    = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( self::PLUGIN_NAME ) . ')';
			$thanks    = __( 'A huge thanks in advance!', 'slicr' );
			$maybe_pro = '';

			if ( empty( self::$is_pro ) ) {
				$maybe_pro = sprintf(
					// Translators: %1$s - extensions URL.
					__( 'You are using the free version. Get the <a href="%1$s" target="_blank"><b>PRO</b> version</a>. ', 'slicr' ),
					'https://iuliacazan.ro/wordpress-extension/login-ip-country-restriction-pro/'
				) . ' ';
			} else {
				$maybe_pro = sprintf(
					// Translators: %1$s - pro version label, %2$s - PRO URL.
					__( 'Thank you for purchasing the <a href="%1$s" target="_blank"><b>PRO</b> version</a>! ', 'slicr' ),
					'https://iuliacazan.ro/wordpress-extension/login-ip-country-restriction-pro/'
				) . ' ';
			}

			$other_notice = sprintf(
				// Translators: %1$s - plugins URL, %2$s - heart icon, %3$s - extensions URL, %4$s - star icon, %5$s - maybe PRO details.
				__( '%5$sCheck out my other <a href="%1$s" target="_blank" rel="noreferrer">%2$s free plugins</a> on WordPress.org and the <a href="%3$s" target="_blank" rel="noreferrer">%4$s other extensions</a> available!', 'slicr' ),
				'https://profiles.wordpress.org/iulia-cazan/#content-plugins',
				'<span class="dashicons dashicons-heart"></span>',
				'https://iuliacazan.ro/shop/',
				'<span class="dashicons dashicons-star-filled"></span>',
				$maybe_pro
			);

			?>

			<div id="item-<?php echo esc_attr( $slug ); ?>" class="updated notice">
				<div class="icon">
					<a href="<?php echo esc_url( self::$plugin_url ); ?>"><img src="<?php echo esc_url( SISANU_RCIL_URL . 'assets/images/icon-128x128.gif' ); ?>"></a>
				</div>
				<div class="content">
					<div>
						<h3>
							<?php
							echo wp_kses_post( sprintf(
								// Translators: %1$s - plugin name.
								__( '%1$s plugin was activated!', 'slicr' ),
								'<b>' . $title . '</b>'
							) );
							?>
						</h3>
						<div class="notice-other-items"><div><?php echo wp_kses_post( $other_notice ); ?></div></div>
					</div>
					<div>
						<?php
						echo wp_kses_post( sprintf(
								// Translators: %1$s - donate URL, %2$s - rating, %3$s - thanks.
							__( 'If you find the plugin useful and would like to support my work, please consider making a <a href="%1$s" target="_blank">donation</a>. It would make me very happy if you would leave a %2$s rating. <br>%3$s', 'slicr' ),
							$donate,
							'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" rel="noreferrer" title="' . esc_attr( $thanks ) . '">★★★★★</a>',
							$thanks
						) );
						?>
					</div>
					<a class="notice-plugin-donate" href="<?php echo esc_url( $donate ); ?>" target="_blank"><img src="<?php echo esc_url( SISANU_RCIL_URL . 'assets/images/buy-me-a-coffee.png?v=' . SISANU_RCIL_CURRENT_DB_VERSION ); ?>" width="200"></a>
				</div>
				<div class="action">
					<div class="dashicons dashicons-no" onclick="dismiss_notice_for_<?php echo esc_attr( $slug ); ?>()"></div>
				</div>
			</div>
			<?php
			$style = '
			#trans123super{--color-bg:rgba(176,227,126,0.2); --color-border:rgb(176,227,126); display:grid; padding:0; gap:0; grid-template-columns:6rem auto 3rem; max-width:100%; width:100%; border-left-color: var(--color-border); box-sizing:border-box;} #trans123super .dashicons-no{font-size:2rem; cursor:pointer;} #trans123super .icon{ display:grid; align-content:start; background-color:var(--color-bg); padding: 1rem} #trans123super .icon img{object-fit:cover; object-position:center; width:100%; display:block} #trans123super .action{ display:grid; align-content:start; padding: 1rem 0.5rem} #trans123super .content{ align-items: center; display: grid; gap: 1rem; grid-template-columns: 1fr 1fr 12rem; padding: 1rem;} #trans123super .content .dashicons{color:var(--color-border);} #trans123super .content > div{color:#666;} #trans123super h3{margin:0 0 0.1rem 0;color:#666} #trans123super h3 b{color:#000} #trans123super a{color:#000;text-decoration:none;} #trans123super .notice-plugin-donate img{max-width: 100%;} @media all and (max-width: 1024px) {#trans123super .content{grid-template-columns:100%;}}';
			$style = str_replace( '#trans123super', '#item-' . esc_attr( $slug ), $style );
			echo '<style>' . $style . '</style>'; //phpcs:ignore
			?>
			<script>function dismiss_notice_for_<?php echo esc_attr( $slug ); ?>() { document.getElementById( 'item-<?php echo esc_attr( $slug ); ?>' ).style='display:none'; fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=plugin-deactivate-notice-<?php echo esc_attr( SISANU_RCIL_SLUG ); ?>' ); }</script>
			<?php
		}
	}

	/**
	 * Maybe donate or rate.
	 */
	public static function show_donate_text() {
		?>
		<div class="donate outside">
			<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '/assets/images/icon-128x128.gif' ); ?>" width="32" height="32" alt="">
			<div>
				<?php
				if ( ! self::$is_pro ) {
					echo wp_kses_post(
						sprintf(
							// Translators: %1$s - donate URL, %2$s - rating, %3$s - thanks.
							__( 'If you find the plugin useful and would like to support my work, please consider making a <a href="%1$s" target="_blank">donation</a>. It would make me very happy if you would leave a %2$s rating. <br>%3$s', 'slicr' ),
							'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( self::PLUGIN_NAME ) . ')',
							'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" title="' . esc_attr__( 'A huge thanks in advance!', 'slicr' ) . '">★★★★★</a>',
							__( 'A huge thanks in advance!', 'slicr' )
						)
					);
				} else {
					echo wp_kses_post( sprintf(
						// Translators: %1$s - 5 stars, %2$s - thanks.
						__( 'It would make me very happy if you would leave a %1$s rating. <br>%2$s', 'slicr' ),
						'<a href="' . self::PLUGIN_SUPPORT_URL . 'reviews/?rate=5#new-post" class="rating" target="_blank" title="' . esc_attr__( 'A huge thanks in advance!', 'slicr' ) . '">★★★★★</a>',
						__( 'A huge thanks in advance!', 'slicr' )
					) );
				}
				?>
			</div>
		</div>
		<?php
	}
}

$srcil = SISANU_Restrict_Country_IP_Login::get_instance();
register_activation_hook( __FILE__, [ $srcil, 'activate_plugin' ] );
register_deactivation_hook( __FILE__, [ $srcil, 'deactivate_plugin' ] );
