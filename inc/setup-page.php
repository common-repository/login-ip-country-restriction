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

$all_countries = self::get_countries_list();

// phpcs:disable
$tab = filter_input( INPUT_GET, 'tab', FILTER_DEFAULT );
$tab = ( empty( $tab ) ) ? 0 : (int) $tab;
$tab = ( $tab < 0 || $tab > 5 ) ? 0 : $tab;
// phpcs:enable

$rules = [
	6 => [
		'is_pro' => false,
		'title'  => __( 'Allow login only for allowed IPs', 'slicr' ),
	],
	7 => [
		'is_pro' => false,
		'title'  => __( 'Allow login only for allowed countries', 'slicr' ),
	],
	0 => [
		'is_pro' => false,
		'title'  => __( 'Allow login only for allowed countries or allowed IPs', 'slicr' ),
	],
	8 => [
		'is_pro' => false,
		'title'  => __( 'Block login only for blocked IPs', 'slicr' ),
	],
	9 => [
		'is_pro' => false,
		'title'  => __( 'Block login only for blocked countries', 'slicr' ),
	],
	1 => [
		'is_pro' => false,
		'title'  => __( 'Block login only for blocked countries or blocked IPs', 'slicr' ),
	],
	2 => [
		'is_pro' => true,
		'title'  => __( 'Allow login only for allowed countries or allowed IPs, but not from blocked IPs', 'slicr' ),
	],
	3 => [
		'is_pro' => true,
		'title'  => __( 'Allow login only for allowed countries or allowed IPs, but not from blocked IPs or blocked countries', 'slicr' ),
	],
	4 => [
		'is_pro' => true,
		'title'  => __( 'Block login only for blocked countries or blocked IPs, but not for allowed IPs', 'slicr' ),
	],
	5 => [
		'is_pro' => true,
		'title'  => __( 'Block login only for blocked countries or blocked IPs, but not for allowed IPs or allowed countries', 'slicr' ),
	],
];
?>

<div class="wrap licr-feature" id="start" name="start">
	<h1 class="plugin-title">
		<span class="dashicons dashicons-admin-site"></span>
		<?php esc_html_e( 'Login IP & Country Restriction Settings', 'slicr' ); ?>
	</h1>

	<div class="intro-next outside">
		<?php self::current_restriction_notice_card(); ?>
	</div>

	<div class="intro-next outside menu-wrap">
	<?php $url = admin_url( 'options-general.php?page=login-ip-country-restriction-settings' ); ?>
		<details>
		<summary class="tabs-wrap">
			<a href="<?php echo esc_url( $url ); ?>"
				class="button<?php echo esc_attr( 0 === $tab ? ' button-primary on' : '' ); ?>">
				<div class="dashicons dashicons-admin-tools"></div>
				<?php esc_html_e( 'Rule Type', 'slicr' ); ?>
			</a>
			<a href="<?php echo esc_url( $url . '&tab=1' ); ?>"
				class="button<?php echo esc_attr( 1 === $tab ? ' button-primary on' : '' ); ?>">
				<div class="dashicons dashicons-shield"></div>
				<?php esc_html_e( 'IP Restriction', 'slicr' ); ?>
			</a>
			<a href="<?php echo esc_url( $url . '&tab=2' ); ?>"
				class="button<?php echo esc_attr( 2 === $tab ? ' button-primary on' : '' ); ?>">
				<div class="dashicons dashicons-shield-alt"></div>
				<?php esc_html_e( 'Country Restriction', 'slicr' ); ?>
			</a>
			<a href="<?php echo esc_url( $url . '&tab=3' ); ?>"
				class="button<?php echo esc_attr( 3 === $tab ? ' button-primary on' : '' ); ?>">
				<span class="dashicons dashicons-randomize"></span>
				<?php esc_html_e( 'Redirects', 'slicr' ); ?>
			</a>
			<?php
			if ( ! self::$is_pro ) {
				?>
				<a href="<?php echo esc_url( $url . '&tab=4' ); ?>" class="button pro-item disabled">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Other Settings', 'slicr' ); ?>
				</a>
				<?php
			}
			do_action( 'sislrc_display_pro_tabs' );
			?>
			<a href="<?php echo esc_url( $url . '&tab=5' ); ?>"
				class="button<?php echo esc_attr( 5 === $tab ? ' button-primary on' : '' ); ?>">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Debug', 'slicr' ); ?>
			</a>
		</summary>
	</details>
	</div>

	<div class="tab-wrap-content">
		<form action="<?php echo esc_url( self::$plugin_url ); ?>" method="POST">
			<?php wp_nonce_field( '_login_ip_country_restriction_settings_save', '_login_ip_country_restriction_settings_nonce' ); ?>
			<input type="hidden" name="tab" id="tab" value="<?php echo (int) $tab; ?>">

			<?php
			switch ( $tab ) {
				case 1:
					// IP restriction.
					self::tab1_content( $rules );
					break;

				case 2:
					// Country restriction.
					self::tab2_content( $all_countries );
					break;

				case 3:
					// Redirects.
					self::tab3_content();
					break;

				case 4:
					// Other Settings.
					self::tab4_content( $rules );
					break;

				case 5:
					// Debug.
					self::setup_debug_output();
					break;

				case 0:
				default:
					// Rule type.
					self::tab0_content( $rules );
					break;
			}
			?>
		</form>
	</div>

	<?php self::show_donate_text(); ?>
</div>
