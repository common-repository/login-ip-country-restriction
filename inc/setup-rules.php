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

$true_pro = self::$is_pro && function_exists( '\RCIL\Pro\key_is_active' ) && true === \RCIL\Pro\key_is_active();
?>

<div class="group-columns">
	<div class="group-wrap">
		<h3 class="as-title"><?php esc_html_e( 'Login Restriction Rules', 'slicr' ); ?></h3>
		<ol class="items">
			<?php
			foreach ( $rules as $key => $value ) {
				$class = '';
				if ( true === $value['is_pro'] ) {
					$class = ( ! $true_pro ) ? 'pro-item-after disabled' : 'pro-item-after';
					if ( ! $true_pro ) {
						?>
						<li>
							<label class="pro-item-after disabled">
								<span><?php echo esc_html( $value['title'] ); ?></span>
							</label>
						</li>
						<?php
					} else {
						?>
						<li>
							<label class="pro-item-after">
								<input type="radio" name="rule_type" value="<?php echo (int) $key; ?>"
								<?php checked( self::$rules->type, $key ); ?>>
								<span><?php echo esc_html( $value['title'] ); ?></span>
							</label>
						</li>
						<?php
					}
				} else {
					?>
					<li>
						<label>
							<input type="radio" name="rule_type" value="<?php echo (int) $key; ?>"
							<?php checked( self::$rules->type, $key ); ?>>
							<span><?php echo esc_html( $value['title'] ); ?></span>
						</label>
					</li>
					<?php
				}
			}
			?>
		</ol>

		<p class="info">
			<?php esc_html_e( 'The login filter can be configured to work in a different way, depending on what type of rules to be assessed and in which order.', 'slicr' ); ?>
		</p>
	</div>

	<div class="group-wrap">
		<h3 class="as-title"><?php esc_html_e( 'Filter XML-RPC Authenticated Methods', 'slicr' ); ?></h3>
		<ul class="items">
			<li>
				<label>
					<input type="radio" name="xmlrpc_auth_filter"
						id="xmlrpc_auth_filter"
						value=""
						<?php checked( '', self::$settings['xmlrpc_auth_filter'] ); ?>/>
					<span><?php esc_html_e( 'Default', 'slicr' ); ?></span>
				</label>
			</li>
			<li>
				<label>
					<input type="radio" name="xmlrpc_auth_filter"
						id="xmlrpc_auth_filter_all"
						value="all"
						<?php checked( 'all', self::$settings['xmlrpc_auth_filter'] ); ?>/>
					<span><?php esc_html_e( 'Disable all', 'slicr' ); ?></span>
				</label>
			</li>
			<li>
				<label>
					<input type="radio" name="xmlrpc_auth_filter"
						id="xmlrpc_auth_filter_restriction"
						value="restriction"
						<?php checked( 'restriction', self::$settings['xmlrpc_auth_filter'] ); ?>/>
					<span><?php esc_html_e( 'Disable only when matching a restriction rule', 'slicr' ); ?></span>
				</label>
			</li>
		</ul>

		<p class="info">
			<?php esc_html_e( 'The option above controls whether XML-RPC methods requiring authentication (such as for publishing purposes) are enabled and does not interfere with pingbacks or other custom endpoints that don\'t require authentication.', 'slicr' ); ?>
		</p>
	</div>
</div>

<div class="main-button-wrap">
	<?php submit_button( '', 'primary', '', false ); ?>
</div>
