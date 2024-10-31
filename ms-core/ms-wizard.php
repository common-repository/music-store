<?php
global $music_store_settings;
if ( isset( $_POST['ms_wizard'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ms_wizard'] ) ), plugin_basename( __FILE__ ) ) ) {
	$ms_paypal_email = ( ! empty( $_POST['ms_paypal_email'] ) ) ? sanitize_text_field( wp_unslash( $_POST['ms_paypal_email'] ) ) : '';
	$ms_items_page   = ( ! empty( $_POST['ms_items_page'] ) && is_numeric( $_POST['ms_items_page'] ) && 0 < ( $ms_items_page = intval( $_POST['ms_items_page'] ) ) ) ? $ms_items_page : 10; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

	$music_store_settings['ms_paypal_email'] = $ms_paypal_email;
	$music_store_settings['ms_items_page']   = $ms_items_page;

	update_option( 'ms_paypal_email', $ms_paypal_email );
	update_option( 'ms_items_page', $ms_items_page );

	$columns               = ( ! empty( $_POST['ms_columns'] ) && is_numeric( $_POST['ms_columns'] ) && 0 < ( $columns = intval( $_POST['ms_columns'] ) ) ) ? $columns : 1; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
	$music_store_shortcode = '[music_store columns="' . $columns . '"]';

	if ( ! empty( $_POST['ms_shop_page_title'] ) && ( $ms_shop_page_title = sanitize_text_field( wp_unslash( $_POST['ms_shop_page_title'] ) ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
		$page_id                              = wp_insert_post(
			array(
				'comment_status' => 'closed',
				'post_title'     => $ms_shop_page_title,
				'post_content'   => $music_store_shortcode,
				'post_status'    => 'publish',
				'post_type'      => 'page',
			)
		);
		$music_store_settings['ms_main_page'] = get_permalink( $page_id );
		update_option( 'ms_main_page', $music_store_settings['ms_main_page'] );
	}
	print '<div class="updated notice">' . esc_html__( 'Music Store Wizard Completed', 'music-store' ) . '</div>';
	if ( isset( $_POST['ms_wizard_goto'] ) && 'songs' == $_POST['ms_wizard_goto'] ) {
		?>
	<script>document.location.href="<?php print esc_js( admin_url( 'post-new.php?post_type=ms_song' ) ); ?>";</script>
		<?php
	}
}

$ms_has_been_configured = get_option( 'ms_has_been_configured', false );
if ( MS_PAYPAL_EMAIL == $music_store_settings['ms_paypal_email'] && ! $ms_has_been_configured ) {
	?>
	<h1 style="text-align:center;"><?php esc_html_e( 'Music Store Wizard', 'music-store' ); ?></h1>
	<form id="ms_wizard" method="post" action="<?php echo esc_attr( admin_url( 'admin.php?page=music-store-menu-settings' ) ); ?>">
		<div>
			<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Step 1 of 2', 'music-store' ); ?>: <?php esc_html_e( 'Payment Gateway', 'music-store' ); ?></span></h3>
			<hr />
			<table class="form-table">
				<tr valign="top">
					<th scope="row" style="white-space:nowrap;">
						<?php esc_html_e( 'Enter the email address associated to your PayPal account', 'music-store' ); ?>
					</th>
					<td>
						<input type="text" name="ms_paypal_email" size="40" placeholder="<?php esc_attr_e( 'Email address', 'music-store' ); ?>" /><br />
						<i style="font-weight:normal;"><?php esc_html_e( 'Leave in blank if you want distribute your songs for free.', 'music-store' ); ?></i>
					</td>
				</tr>
			</table>
			<div style="border:1px dotted #333333; margin-top:10px; margin-bottom:10px; padding: 10px;">Please, remember that the Instant Payment Notification (IPN) must be enabled in your PayPal account, because if the IPN is disabled PayPal does not notify the payments to your website. Please, visit the following link: <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#link-settingupipnnotificationsonpaypal" target="_blank">How to enable the IPN?</a>. PayPal needs the URL to the IPN Script in your website, however, you simply should enter the URL to the home page.</div>
			<input type="button" class="button" value="<?php esc_attr_e( 'Next step', 'music-store' ); ?>" onclick="jQuery(this).closest('div').hide().next('div').show();">
		</div>
		<div style="display:none;">
			<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Step 2 of 2', 'music-store' ); ?>: <?php esc_html_e( 'Store Page', 'music-store' ); ?></span></h3>
			<hr />
			<table class="form-table">
				<tr valign="top">
					<th><?php esc_html_e( 'Enter the shop page\'s title', 'music-store' ); ?></th>
					<td>
						<input type="text" name="ms_shop_page_title" size="40" /><br />
						<i><?php esc_html_e( 'Leave in blank if you want to configure the shop\'s page after.', 'music-store' ); ?></i>
					</td>
				</tr>
				<tr valign="top">
					<th><?php esc_html_e( 'Products per page', 'music-store' ); ?></th>
					<td><input type="text" name="ms_items_page" value="<?php echo is_numeric( $music_store_settings['ms_items_page'] ) ? intval( $music_store_settings['ms_items_page'] ) : ''; ?>" /></td>
				</tr>
				<tr valign="top">
					<th><?php esc_html_e( 'Number of columns', 'music-store' ); ?></th>
					<td><input type="text" name="ms_columns" value="3" /></td>
				</tr>
			</table>
			<input type="hidden" id="ms_wizard_goto" name="ms_wizard_goto" value="settings" />
			<input type="button" class="button" value="<?php esc_attr_e( 'Previous step', 'music-store' ); ?>" onclick="jQuery(this).closest('div').hide().prev('div').show();" />
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save wizard and create my first song', 'music-store' ); ?>" onclick="jQuery('#ms_wizard_goto').val('songs');" />
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save wizad and go to the store\'s settings', 'music-store' ); ?>" />
		</div>
		<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ms_wizard' ); ?>
	</form>
	<script>jQuery(document).on('keydown', '#ms_wizard input[type="text"]', function(e){var code = e.keyCode || e.which;if(code == 13) {e.preventDefault();e.stopPropagation();return false;}});</script>
	<?php
	update_option( 'ms_has_been_configured', true );
	$wizard_active = true;
}
