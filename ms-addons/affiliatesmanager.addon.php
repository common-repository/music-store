<?php
if ( ! class_exists( 'MS_AffiliatesManager' ) ) {
	class MS_AffiliatesManager {

		private static $_instance;
		private function __construct() {
			if ( is_admin() ) {
				add_action( 'musicstore_settings_page', array( &$this, 'show_settings' ), 11 );
				add_action( 'musicstore_save_settings', array( &$this, 'save_settings' ), 11 );
			} else {
				add_filter( 'musicstore_notify_url', array( &$this, 'musicstore_notify_url' ) );
				add_action( 'musicstore_payment_received', array( &$this, 'musicstore_payment_received' ), 11, 2 );
			}
		}

		private function is_active() {
			return get_option( 'ms_affiliates_manager_active' );
		}

		public function musicstore_notify_url( $url ) {
			if ( $this->is_active() && ! empty( $_COOKIE['wpam_id'] ) ) {
				$url .= '|wpam_id=' . sanitize_text_field( wp_unslash( $_COOKIE['wpam_id'] ) );
			}
			return $url;
		} // End musicstore_notify_url

		public function musicstore_payment_received( $ipn_post, $products ) {
			if (
				$this->is_active() &&
				! empty( $ipn_post['ms_purchase_id'] ) &&
				! empty( $ipn_post['ms_payment_amount'] )
			) {
				// For paypal as payment gateway
				if (
					! empty( $_GET['ms-action'] ) &&
					preg_match( '/\|wpam_id=(\d+)/i', sanitize_text_field( wp_unslash( $_GET['ms-action'] ) ), $wpam_id_matches )
				) {
					  $affiliate_id = $wpam_id_matches[1];
				} elseif ( ! empty( $_COOKIE['wpam_id'] ) ) { // For stripe as payment gateway
					 $affiliate_id = sanitize_text_field( wp_unslash( $_COOKIE['wpam_id'] ) );
				}

				if ( ! empty( $affiliate_id ) ) {
					$args           = array();
					$args['txn_id'] = 'Music Store - ' . $ipn_post['ms_purchase_id'];
					$args['amount'] = $ipn_post['ms_payment_amount'];
					$args['aff_id'] = $affiliate_id;
					do_action( 'wpam_process_affiliate_commission', $args );
				}
			}
		} // End musicstore_payment_received

		public function show_settings() {
			$ms_affiliates_manager_active = get_option( 'ms_affiliates_manager_active' );
			?>
			<div class="postbox">
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Affiliates Managers Integration', 'music-store' ); ?></span></h3>
				<div class="inside">
					<?php esc_html_e( 'If the Affiliates Manager plugin is installed on the website, tick the checkbox to integrate it with the Music Store:', 'music-store' ); ?>
					<input type="checkbox" name="ms_affiliates_manager_active" <?php print( ( $ms_affiliates_manager_active ) ? 'CHECKED' : '' ); ?> />
				</div>
			</div>
			<?php
		} // End show_settings

		public function save_settings() {
			update_option( 'ms_affiliates_manager_active', ( isset( $_REQUEST['ms_affiliates_manager_active'] ) ) ? true : false );
		} // End save_settings

		public static function init() {
			if ( null == self::$_instance ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		} // End init
	} // End MS_AffiliatesManager
}

MS_AffiliatesManager::init();
?>
