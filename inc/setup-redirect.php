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
?>

<div class="group-wrap">
	<label class="sislrc-toggle"
		data-target="#use_redirects_list"
		data-action="hide">
		<input type="radio"
			name="_login_ip_country_restriction_settings[use_redirect]"
			id="_login_ip_country_restriction_settings_use_redirect0"
			value="0" <?php checked( 0, self::$custom_redirects['status'] ); ?>/>
		<span>
			<h3 class="as-title"><?php esc_html_e( 'No redirect', 'slicr' ); ?></h3>
			<?php esc_html_e( 'No redirects', 'slicr' ); ?>
		</span>
	</label>
	</div>

	<div class="group-wrap">
	<label class="sislrc-toggle" data-target="#use_redirects_list" data-action="show">
		<input type="radio"
			name="_login_ip_country_restriction_settings[use_redirect]"
			id="_login_ip_country_restriction_settings_use_redirect1"
			value="1" <?php checked( 1, self::$custom_redirects['status'] ); ?>/>
		<span>
			<h3 class="as-title"><?php esc_html_e( 'Use redirects', 'slicr' ); ?></h3>
			<?php esc_html_e( 'Yes, use redirects to the front page when the URLs are accessed by someone that has a restriction.', 'slicr' ); ?>
		</span>
	</label>

	<div id="use_redirects_list"
		class="rcil_elem <?php echo esc_attr( ( 0 === (int) self::$custom_redirects['status'] ) ? 'is-hidden' : '' ); ?>">
		<div class="group-columns">
			<div>
				<h3 class="as-subtitle"><?php esc_html_e( 'Login & Registration native pages', 'slicr' ); ?></h3>

				<ul class="items">
					<li>
						<label>
							<input type="checkbox" name="_login_ip_country_restriction_settings[redirect_login]"
								id="_login_ip_country_restriction_settings_redirect_login"
								value="1" <?php checked( 1, (int) self::$custom_redirects['login'] ); ?>/>
							<span>
								<?php
								echo wp_kses_post( sprintf(
									// Translators: %1$s - url, %2$s - new url.
									__( 'Redirect login from %1$s to %2$s.', 'slicr' ),
									'<b><em>' . wp_login_url() . '</em></b>',
									'<b><em>' . home_url() . '</em></b>'
								) );
								?>
							</span>
						</label>
					</li>
					<li>
						<label>
							<input type="checkbox" name="_login_ip_country_restriction_settings[redirect_register]"
								id="_login_ip_country_restriction_settings_redirect_register"
								value="1" <?php checked( 1, (int) self::$custom_redirects['register'] ); ?>/>
							<span>
								<?php
								echo wp_kses_post( sprintf(
									// Translators: %1$s - url, %2$s - new url.
									__( 'Redirect registration from %1$s to %2$s.', 'slicr' ),
									'<b><em>' . wp_registration_url() . '</em></b>',
									'<b><em>' . home_url() . '</em></b>'
								) );
								?>
							</span>
						</label>
					</li>
				</ul>
				<p class="info"><?php esc_html_e( 'Please note that the restriction to the pages configured above will apply if the login restriction is matched.', 'slicr' ); ?></p>
			</div>
			<div>
				<h3 class="as-subtitle"><?php esc_html_e( 'The following specified URLs', 'slicr' ); ?></h3>
				<textarea name="_login_ip_country_restriction_settings[redirect_urls]" class="wide" rows="3"><?php echo esc_html( implode( ', ', self::$custom_redirects['urls'] ) ); ?></textarea>
				<p class="info"><?php esc_html_e( '(separate the URLs with comma)', 'slicr' ); ?></p>

				<?php
				if ( function_exists( 'RCIL\Pro\sislrc_pro_simulate_info' ) ) {
					\RCIL\Pro\sislrc_pro_simulate_info( true );
				}
				?>
			</div>
		</div>
	</div>
	</div>

	<div class="main-button-wrap">
	<?php submit_button( '', 'primary', 'submit-tab3', false ); ?>
	</div>
