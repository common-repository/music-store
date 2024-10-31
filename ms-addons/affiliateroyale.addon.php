<?php
if ( ! class_exists( 'MS_AffiliateRoyale' ) ) {
	class MS_AffiliateRoyale {

		private static $_instance;
		private function __construct() {
			if ( is_admin() ) {
				add_action( 'musicstore_settings_page', array( &$this, 'show_settings' ), 11 );
				add_action( 'musicstore_save_settings', array( &$this, 'save_settings' ), 11 );
			} else {
				add_action( 'musicstore_paypal_form_html_before_submit', array( &$this, 'paypal_form_html_output' ), 11, 2 );
				add_action( 'musicstore_payment_received', array( &$this, 'capture_ipn' ), 11, 2 );
			}
		}

		private function is_active() {
			return get_option( 'ms_affiliate_royale_active' );
		}

		public function paypal_form_html_output( $products, $purchase_id ) {
			if (
				$this->is_active() &&
				( $wafp_custom_args = do_shortcode( '[wafp_custom_args]' ) ) != '[wafp_custom_args]' // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
			) {
				echo wp_kses_post( $wafp_custom_args );
			}
		} // End paypal_form_html_output

		public function capture_ipn( $ipn_post, $products ) {
			if ( $this->is_active() ) {
				$custom_array = array();

				// Load up the custom vals if they're there
				if ( isset( $ipn_post['custom'] ) && ! empty( $ipn_post['custom'] ) ) {
					$custom_array = wp_parse_args( $ipn_post['custom'] );
				}

				// Make sure we have what we need to track this payment
				if (
					isset( $custom_array['aff_id'] ) &&
					class_exists( 'WafpTransaction' ) &&
					isset( $ipn_post['txn_id'] ) &&
					isset( $ipn_post['mc_gross'] )
				) {
					$products_titles = '';
					if ( is_array( $products ) ) {
						$separator = '';
						foreach ( $products as $product ) {
							if ( isset( $product->post_title ) ) {
								$products_titles .= $separator . $product->post_title;
								$separator        = '|';
							}
						}
					} elseif ( isset( $products->post_title ) ) {
						$products_titles = $products->post_title;
					}
					$_COOKIE['wafp_click'] = $custom_array['aff_id'];
					WafpTransaction::track(
						(float) $ipn_post['mc_gross'],
						$ipn_post['txn_id'],
						( ! empty( $products_titles ) ) ? $products_titles : 'Music Store Purchase'
					);
				}
			}
		} // End capture_ipn

		public function show_settings() {
			$ms_affiliate_royale_active = get_option( 'ms_affiliate_royale_active' );
			?>
			<div class="postbox">
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Affiliate Royale Integration', 'music-store' ); ?></span></h3>
				<div class="inside">
					<?php esc_html_e( 'If the Affiliate Royale plugin is installed on the website, and you want integrate it with the Music Store, tick the checkbox:', 'music-store' ); ?>
					<input type="checkbox" name="ms_affiliate_royale_active" <?php print( ( $ms_affiliate_royale_active ) ? 'CHECKED' : '' ); ?> />
				</div>
			</div>
			<?php
		} // End show_settings

		public function save_settings() {
			update_option( 'ms_affiliate_royale_active', ( isset( $_REQUEST['ms_affiliate_royale_active'] ) ) ? true : false );
		} // End save_settings

		public static function init() {
			if ( null == self::$_instance ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		} // End init
	} // End MS_AffiliateRoyale
}

MS_AffiliateRoyale::init();
?>
