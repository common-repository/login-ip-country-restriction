<?php // phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Login IP & Country Restriction.
 * Text Domain: slicr
 *
 * @package ic-devops
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$setup = [
	'_ver'              => SISANU_RCIL_CURRENT_DB_VERSION,
	'_db_ver'           => get_option( SISANU_RCIL_DB_OPTION . '_db_ver', '' ),
	'_allow_countries'  => get_option( SISANU_RCIL_DB_OPTION . '_allow_countries', [] ),
	'_allow_ips'        => get_option( SISANU_RCIL_DB_OPTION . '_allow_ips', [] ),
	'_block_countries'  => get_option( SISANU_RCIL_DB_OPTION . '_block_countries', [] ),
	'_block_ips'        => get_option( SISANU_RCIL_DB_OPTION . '_block_ips', [] ),
	'_custom_redirects' => get_option( SISANU_RCIL_DB_OPTION . '_custom_redirects', [] ),
	'_bypass_roles'     => get_option( SISANU_RCIL_DB_OPTION . '_bypass_roles', [] ),
	'_settings'         => get_option( SISANU_RCIL_DB_OPTION . '_settings', [] ),
];
?>
<div class="group-columns">
	<div class="group-wrap">
		<h3 class="as-title"><?php esc_html_e( 'Export Settings', 'slicr' ); ?></h3>
		<p>
			<?php esc_html_e( 'Here are some details about the current settings of this plugin, these can be reset or exported into another instance.', 'slicr' ); ?>
		</p>
		<textarea name="export" rows="5" readonly><?php echo wp_json_encode( $setup ); ?></textarea>
		<p>
			<input type="submit" class="button" name="reset-all-settings" value="<?php esc_attr_e( 'Reset to default', 'slicr' ); ?>">
		</p>
		<p>
			<?php esc_html_e( 'Please note that reset to default is not requiring for a confirmation, so be careful with clicking this button.', 'slicr' ); ?>
		</p>
	</div>
	<div class="group-wrap">
		<h3 class="as-title"><?php esc_html_e( 'Import Settings', 'slicr' ); ?></h3>
		<p>
			<?php esc_html_e( 'You can paste here the settings you want to import from another instance. This is a string in JSON format.', 'slicr' ); ?>
		</p>
		<textarea name="import" rows="5" placeholder="<?php esc_attr_e( 'Paste here the JSON code.', 'slicr' ); ?>"></textarea>
		<p>
			<input type="submit" class="button button-primary" name="import-all-settings" value="<?php esc_attr_e( 'Import settings', 'slicr' ); ?>">
		</p>
		<p>
			<?php esc_html_e( 'Please note that this will override all the existing settings.', 'slicr' ); ?>
		</p>
	</div>
	<div class="group-wrap">
		<?php
		if ( ! class_exists( 'WP_Debug_Data' ) && file_exists( ABSPATH . 'wp-admin/includes/class-wp-debug-data.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
		}
		if ( class_exists( 'WP_Debug_Data' ) ) {
			$info = \WP_Debug_Data::debug_data();
		}

		$allow = [
			'wp-core'           => [ 'version', 'site_language', 'timezone', 'home_url', 'site_url', 'permalink', 'https_status', 'multisite', 'environment_type', 'dotorg_communication' ],
			'wp-paths-sizes'    => [ 'wordpress_path', 'uploads_path', 'themes_path', 'plugins_path' ],
			'wp-active-theme'   => [ 'name', 'version', 'author', 'author_website', 'parent_theme', 'theme_features', 'theme_path', 'auto_update' ],
			'wp-parent-theme'   => [ 'name', 'version' ],
			'wp-plugins-active' => '*',
			'wp-media'          => '*',
			'wp-server'         => '*',
			'wp-database'       => [ 'extension', 'server_version', 'client_version' ],
			'wp-constants'      => '*',
			'wp-filesystem'     => '*',
		];

		$details = '';
		if ( ! empty( $info ) ) {
			foreach ( $info as $section => $item ) {
				if ( ! empty( $allow[ $section ] ) && ! empty( $item['fields'] ) ) {
					$details .= PHP_EOL . '*************************************';
					$details .= PHP_EOL . esc_html( $item['label'] );
					$details .= PHP_EOL . '-------------------------------------';

					if ( '*' === $allow[ $section ] ) {
						$keys = array_keys( $item['fields'] );
					} else {
						$keys = $allow[ $section ];
					}

					foreach ( $keys as $key ) {
						$str = ( ! empty( $item['fields'][ $key ]['label'] ) ) ? $item['fields'][ $key ]['label'] : '';
						if ( is_scalar( $item['fields'][ $key ]['value'] ) ) {
							$str .= ': ' . $item['fields'][ $key ]['value'];
						} else {
							$str .= ': ' . print_r( $item['fields'][ $key ]['value'], true ); //phpcs:ignore
						}
						if ( ! empty( $str ) ) {
							$details .= PHP_EOL . '- ' . esc_html( $str );
						}
					}
					$details .= PHP_EOL;
				}
			}

			if ( isset( $info['wp-paths-sizes']['fields']['wordpress_path']['value'] ) ) {
				$details = str_replace(
					$info['wp-paths-sizes']['fields']['wordpress_path']['value'], '{{ROOT}}', $details
				);
			}
		}
		$details .= PHP_EOL . '*************************************';
		$details .= PHP_EOL . esc_html__( 'Debug', 'slicr' );
		$details .= PHP_EOL . '-------------------------------------';
		$details .= PHP_EOL . '- ' . sprintf(
			// Translators: %1$s - IP, %2$s - country code.
			__( 'Your current IP is %1$s and the country code is %2$s.', 'slicr' ),
			self::get_current_ip(),
			self::get_user_country_name()
		);

		$details .= PHP_EOL . '- SERVER_ADDR: ';
		$details .= ( ! empty( $_SERVER['SERVER_ADDR'] ) ) ? wp_unslash( $_SERVER['SERVER_ADDR'] ) : ''; //phpcs:ignore

		$details .= PHP_EOL . '- REMOTE_ADDR: ';
		$details .= ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : ''; //phpcs:ignore

		$details .= PHP_EOL . '- HTTP_CF_IPCOUNTRY: ';
		$details .= ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ? wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) : ''; //phpcs:ignore

		$details .= PHP_EOL . '- HTTP_CF_CONNECTING_IP: ';
		$details .= ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) ? wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) : ''; //phpcs:ignore

		$details .= PHP_EOL . '- HTTP_CLIENT_IP: ';
		$details .= ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) ? wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) : ''; //phpcs:ignore

		if ( ! empty( self::$settings['include_forward_ip'] ) ) {
			$details .= PHP_EOL . '- HTTP_X_FORWARDED_FOR: ';
			$details .= ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ? wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) : ''; //phpcs:ignore
		}

		if ( ! empty( $details ) ) {
			?>
			<h3 class="as-title"><?php esc_html_e( 'Status/Debug', 'slicr' ); ?></h3>
			<p>
				<?php esc_html_e( 'Here are some details about your current WordPress instance and the services versions that run currently in this environment.', 'slicr' ); ?>
			</p>
			<textarea rows="5" readonly><?php echo esc_html( $details ); ?></textarea>
			<?php
		}
		?>
	</div>

	<?php
	$test_info = get_transient( 'rcil-test-' . md5( gmdate( 'Y-m-d' ) ) );
	$test_ip   = ( ! empty( $test_info['ip'] ) ) ? $test_info['ip'] : '';
	$test_co   = ( ! empty( $test_info['co'] ) ) ? $test_info['co'] : '';
	$test_api  = ( ! empty( $test_info['api'] ) ) ? $test_info['api'] : '';
	?>
	<div class="group-wrap">
		<h3 class="as-title"><?php esc_html_e( 'Test country code for IP', 'slicr' ); ?></h3>
		<div class="group-columns columns-1">
			<div>
				<?php esc_html_e( 'IP', 'slicr' ); ?>
				<input type="text" name="test_ip" value="<?php echo esc_attr( $test_ip ); ?>">
				<input type="submit" class="button" name="test-ip" value="<?php esc_attr_e( 'Test', 'slicr' ); ?>">
				<p>
					<?php
					if ( ! empty( $test_ip ) ) {
						echo wp_kses_post( sprintf(
							// Translators: %1$s - IP, %2$s - code, %3$s - method.
							__( 'The country code detected for the IP %1$s is %2$s. The detection was done through the %3$s method.', 'slicr' ),
							'<b>' . $test_ip . '</b>',
							'<code>' . $test_co . '</code>',
							'<b>' . $test_api . '</b>'
						) );
					}
					?>
				</p>
			</div>
		</div>
		<p>
			<?php
			if ( ! empty( $test_ip ) && ( 'PHP `geoip_record_by_name`' === $test_api ) ) {
				echo wp_kses_post( sprintf(
					// Translators: %s - method.
					__( 'Please note that the %s function is part of the PHP service used on your server, and this is used as the default detection method. If this does not return the expected country code for the test IP, then you can try to bypass it and allow for other detection methods to run.', 'slicr' ),
					'<b>' . $test_api . '</b>'
				) );
				?>
				<br>
				<?php
				if ( empty( self::$settings['bypass_php_geoip'] ) ) {
					?>
					<input type="submit" class="button" name="disable-geoip-function" value="<?php esc_attr_e( 'Bypass the PHP `geoip_record_by_name` function', 'slicr' ); ?>">
					<?php
				} else {
					?>
					<input type="submit" class="button" name="enable-geoip-function" value="<?php esc_attr_e( 'Enable the PHP `geoip_record_by_name` function', 'slicr' ); ?>">
					<?php
				}
			}
			?>
		</p>
	</div>
</div>
