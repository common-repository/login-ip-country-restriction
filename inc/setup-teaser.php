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

if ( 'regular' === $type ) : ?>
	<h3 class="as-subtitle no-border no-top"><?php esc_html_e( 'You are using the free version.', 'slicr' ); ?></h3>
	<p>
		<?php
		echo wp_kses_post( sprintf(
			// Translators: %1$s - extensions URL.
			__( 'Click the button to see more and get the <a class="pro-item button button-primary" href="%1$s" target="_blank">version</a> of the plugin!', 'slicr' ),
			'https://iuliacazan.ro/wordpress-extension/login-ip-country-restriction-pro/'
		) );
		?>
	</p>
<?php else : ?>
	<h3 class="as-subtitle no-border"><?php esc_html_e( 'You are using the PRO version.', 'slicr' ); ?></h3>
	<p>
		<?php esc_html_e( 'It seems that you either did not input yet your license key, or that is not valid or has expired already.', 'slicr' ); ?>
		<?php
		echo wp_kses_post( sprintf(
			// Translators: %1$s - extensions URL.
			__( 'Click the button to get a valid license key for the <a class="pro-item button button-primary" href="%1$s" target="_blank">version</a> of the plugin!', 'slicr' ),
			'https://iuliacazan.ro/wordpress-extension/login-ip-country-restriction-pro/'
		) );
		?>
	</p>
<?php endif; ?>

<div class="group-columns">
	<div>
		<h3 class="as-subtitle"><?php esc_html_e( 'Login IP & Country Restriction', 'slicr' ); ?></h3>
		<p>
			<?php esc_html_e( 'This plugin allows you to restrict the login on your website, based on the custom rules you apply. This helps with tightening your website security and fights against dictionary bot attacks originating from other countries, by denying access.', 'slicr' ); ?>
		</p>
		<img src="<?php echo esc_url( SISANU_RCIL_URL . 'assets/images/banner-772x250.png' ); ?>" width="100%">
	</div>

	<div>
		<h3 class="as-subtitle"><?php esc_html_e( 'The PRO version includes additional useful features', 'slicr' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Additional Rule Types', 'slicr' ); ?></li>
			<li><?php esc_html_e( 'Redirect Restricted Login', 'slicr' ); ?></li>
			<li><?php esc_html_e( 'Lockout duration', 'slicr' ); ?></li>
			<li><?php esc_html_e( 'Individual lockout', 'slicr' ); ?></li>
			<li><?php esc_html_e( 'WooCommerce Integration', 'slicr' ); ?></li>
			<li><?php esc_html_e( 'Bypass the IP and country restriction for the specified roles', 'slicr' ); ?></li>
			<li><?php esc_html_e( 'Single IP Login Per User', 'slicr' ); ?></li>
			<li><?php esc_html_e( 'Simulate IP and Country', 'slicr' ); ?></li>
			<li><?php esc_html_e( 'Temporarily disable all settings', 'slicr' ); ?></li>
		</ol>
	</div>
</div>
