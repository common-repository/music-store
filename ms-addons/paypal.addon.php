<?php
if ( ! class_exists( 'MUSIC_STORE_PAYPAL_ADDON' ) ) {
	class MUSIC_STORE_PAYPAL_ADDON {

		private static $_instance;
		private $_licenses;

		public static function init() {
			if ( null == self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		} // End init

		public function __construct() {
			if ( ! is_admin() ) {
				add_action( 'musicstore_calling_payment_gateway', array( $this, 'send_to_paypal' ), 10, 2 );
				add_action( 'musicstore_checking_payment', array( $this, 'check_payment' ), 10 );
				add_filter( 'musicstore_payment_gateway_enabled', array( $this, 'paypal_enabled' ), 10 );
				add_filter( 'musicstore_payment_gateway_list', array( $this, 'populate_list' ), 10 );
			}
		} // End __construct

		/************************************** PUBLIC METHODS  **************************************/

		public function paypal_enabled( $is_enabled ) {
			global $music_store_settings;

			if ( $music_store_settings['ms_paypal_enabled'] && ! empty( $music_store_settings['ms_paypal_email'] ) ) {
				return true;
			}
			return $is_enabled;
		} // End paypal_enabled

		public function populate_list( $payment_gateways ) {
			if ( $this->paypal_enabled( false ) ) {
				$payment_gateways['paypal'] = __( 'PayPal', 'music-store' );
			}
			return $payment_gateways;
		} // End paypal_enabled

		public function send_to_paypal( $amount, $purchase_settings ) {
			global $music_store_settings;

			if (
				! $this->paypal_enabled( false ) ||
				( isset( $_REQUEST['ms_payment_gateway'] ) &&
				'paypal' != $_REQUEST['ms_payment_gateway'] )
			) {
				return;
			}

			$currency        = $music_store_settings['ms_paypal_currency'];
			$language        = $music_store_settings['ms_paypal_language'];
			$ms_paypal_email = $music_store_settings['ms_paypal_email'];

			$baseurl   = $purchase_settings['baseurl'];
			$returnurl = $purchase_settings['returnurl'];
			$cancelurl = $purchase_settings['cancelurl'];

			if ( ! empty( $ms_paypal_email ) ) {
				if ( $amount > 0 ) {
					mt_srand( intval( $this->_make_seed() ) );
					$randval = mt_rand( 1, 999999 );

					$products_id = '';
					if ( ! empty( $purchase_settings['products'] ) ) {
						foreach ( $purchase_settings['products'] as $product ) {
							$products_id .= ( isset( $product->product_id ) ) ? $product->product_id : $product->ID;
						}
					}
					$item_name   = ( ! empty( $purchase_settings['item_name'] ) ) ? $purchase_settings['item_name'] : '';
					$item_number = ( ! empty( $purchase_settings['item_number'] ) ) ? $purchase_settings['item_number'] : '';
					$purchase_id = ( ! empty( $purchase_settings['id'] ) ) ? $purchase_settings['id'] : '';
					$coupon_code = ( ! empty( $purchase_settings['coupon'] ) ) ? '|ms_coupon_code=' . $purchase_settings['coupon'] : '';
					$buyer       = ( 0 != ( $user_id = get_current_user_id() ) ) ? '|buyer_id=' . $user_id : ''; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

					$item_name = html_entity_decode( $item_name, ENT_COMPAT, 'UTF-8' );
					?>
					<form action="https://www.<?php print( ( $music_store_settings['ms_paypal_sandbox'] ) ? 'sandbox.' : '' ); ?>paypal.com/cgi-bin/webscr" name="ppform<?php print esc_attr( $randval ); ?>" method="post">
					<input type="hidden" name="charset" value="utf-8" />
					<input type="hidden" name="business" value="<?php print esc_attr( $ms_paypal_email ); ?>" />
					<input type="hidden" name="item_name" value="<?php print esc_attr( sanitize_text_field( $item_name ) ); ?>" />
					<input type="hidden" name="item_number" value="Item Number <?php print esc_attr( sanitize_text_field( $item_number ) ); ?>" />
					<input type="hidden" name="amount" value="<?php print esc_attr( $amount ); ?>" />
					<input type="hidden" name="currency_code" value="<?php print esc_attr( $currency ); ?>" />
					<input type="hidden" name="lc" value="<?php print esc_attr( $language ); ?>" />
					<input type="hidden" name="return" value="<?php print esc_url( $returnurl . '&purchase_id=' . $purchase_id ); ?>" />
					<input type="hidden" name="cancel_return" value="<?php print esc_url( $cancelurl ); ?>" />
					<input type="hidden" name="notify_url" value="<?php print esc_url( apply_filters( 'musicstore_notify_url', $baseurl . '|pg=paypal|pid=' . $products_id . '|purchase_id=' . $purchase_id . '|rtn_act=purchased_product_music_store' . $coupon_code . $buyer ) ); ?>" />
					<input type="hidden" name="cmd" value="_xclick" />
					<input type="hidden" name="page_style" value="Primary" />
					<input type="hidden" name="no_shipping" value="1" />
					<input type="hidden" name="no_note" value="1" />
					<input type="hidden" name="bn" value="NetFactorSL_SI_Custom" />
					<input type="hidden" name="ipn_test" value="1" />
					<?php
					if ( ! empty( $music_store_settings['ms_tax'] ) ) {
						print '<input type="hidden" name="tax_rate" value="' . esc_attr( $music_store_settings['ms_tax'] ) . '" />';
					}
					if ( ! empty( $purchase_settings['products'] ) ) {
						do_action( 'musicstore_paypal_form_html_before_submit', $purchase_settings['products'], $purchase_id );
					}
					?>
					</form>
					<script type="text/javascript">document.ppform<?php print esc_js( $randval ); ?>.submit();</script>
					<?php
					exit;
				}
			}
		} // End send_to_paypal

		public function check_payment() {
			global $music_store_settings;
			if ( ! $this->paypal_enabled( false ) ) {
				return;
			}

			$this->_licenses   = array();
			$purchase_settings = array();

			global $wpdb;
			echo 'Start IPN';

			$ipn_parameters = array();
			if ( ! empty( $_GET['ms-action'] ) ) {
				$_parameters = explode( '|', sanitize_text_field( wp_unslash( $_GET['ms-action'] ) ) );
				foreach ( $_parameters as $_parameter ) {
					$_parameter_parts = explode( '=', $_parameter );
					if ( count( $_parameter_parts ) == 2 ) {
						$ipn_parameters[ $_parameter_parts[0] ] = sanitize_text_field( $_parameter_parts[1] );
					}
				}

				if ( ! empty( $ipn_parameters['pg'] ) && 'paypal' != $ipn_parameters['pg'] ) {
					return;
				}

				if ( ! isset( $ipn_parameters['purchase_id'] ) ) {
					exit;
				}
				$purchase_id = $ipn_parameters['purchase_id'];

				// If the buyer_id is empty the result would be 0
				$GLOBALS['buyer_id'] = ( ! empty( $ipn_parameters['buyer_id'] ) && is_numeric( $ipn_parameters['buyer_id'] ) ) ? intval( $ipn_parameters['buyer_id'] ) : 0;

				$item_name = ( ! empty( $_POST['item_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['item_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				// $item_number 		= (!empty($_POST['item_number'])) ? $_POST['item_number'] : '';
				$payment_status = ( ! empty( $_POST['payment_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$payment_amount = ( ! empty( $_POST['mc_gross'] ) && is_numeric( $_POST['mc_gross'] ) ) ? floatval( $_POST['mc_gross'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
				$tax            = ( ! empty( $_POST['tax'] ) && is_numeric( $_POST['tax'] ) ) ? floatval( $_POST['tax'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
				$payment_amount  -= $tax;
				$payment_currency = ( ! empty( $_POST['mc_currency'] ) ) ? sanitize_text_field( wp_unslash( $_POST['mc_currency'] ) ) : 'USD'; // phpcs:ignore WordPress.Security.NonceVerification
				// $txn_id 			= (!empty($_POST['txn_id'])) ? $_POST['txn_id'] : '';
				// $receiver_email 	= (!empty($_POST['receiver_email'])) ? $_POST['receiver_email'] : '';
				$payer_email  = ( ! empty( $_POST['payer_email'] ) ) ? sanitize_email( wp_unslash( $_POST['payer_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$payment_type = ( ! empty( $_POST['payment_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['payment_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

				if ( 'Completed' != $payment_status && 'echeck' != $payment_type ) {
					exit;
				}
				if ( 'echeck' == $payment_type && 'Completed' == $payment_status ) {
					exit;
				}

				$paypal_data = '';
				foreach ( $_POST as $item => $value ) { // phpcs:ignore WordPress.Security.NonceVerification
					$paypal_data .= sanitize_key( $item ) . '=' . sanitize_text_field( wp_unslash( $value ) ) . "\r\n";
				}

				$variable_price = ( ! empty( $music_store_settings['ms_variable_price'] ) ) ? $music_store_settings['ms_variable_price'] : 0;

				$percent       = 0;
				$discount_note = '';

				// Available coupons
				if (
					! $variable_price &&
					isset( $ipn_parameters['ms_coupon_code'] )
				) {
					$coupon = $wpdb->get_row(
						$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . MSDB_COUPON . ' WHERE coupon=%s AND (onetime=0 OR times=0)', $ipn_parameters['ms_coupon_code'] ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					);

					if ( ! empty( $coupon ) ) {
						$percent = $coupon->discount;
						$wpdb->query(
							$wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_COUPON . ' SET times=times+1 WHERE coupon=%s', $ipn_parameters['ms_coupon_code'] ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						);
						$discount_note = ' - Coupon code: ' . $coupon->coupon . ' discount: ' . $percent . '%';
					}
				}

				// It is enabled the shopping cart
				if ( ! empty( $music_store_settings['ms_paypal_shopping_cart'] ) ) {
					$products = music_store_getProducts( $purchase_id );
					$total    = 0;
					foreach ( $products as $product ) {
						if (
							! $variable_price &&
							isset( $product->price_type ) &&
							'exclusive' == $product->price_type &&
							! empty( $product->exclusive_price )
						) {
							$total               += $product->exclusive_price;
							$product->final_price = $product->exclusive_price;
							$this->_set_license( 'exclusive' );
						} else {
							if (
								$variable_price &&
								isset( $product->price_type ) &&
								is_numeric( $product->price_type ) &&
								$product->price <= ( $variable_price = floatval( $product->price_type ) ) // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
							) {
								$total                    += $variable_price;
								$product->final_price      = $variable_price;
								$product->discount_applied = '';
							} else {
								if ( function_exists( 'music_store_getValidProductDiscount' ) ) {
									$discount = music_store_getValidProductDiscount( $product->product_id );
								}

								$total                    += ( ! empty( $discount ) ) ? $discount->discount : $product->price;
								$product->final_price      = ( ! empty( $discount ) ) ? $discount->discount : $product->price;
								$product->discount_applied = ( ! empty( $discount ) && ! empty( $discount->note ) ) ? ' - ' . strip_tags( $discount->note ) : '';
							}
							$this->_set_license( 'regular' );
						}
					}

					if (
						! $variable_price &&
						0 == $percent
					) {
						if ( function_exists( 'music_store_getValidStoreDiscount' ) ) {
							$store_discount = music_store_getValidStoreDiscount( $total );
						}

						if ( ! empty( $store_discount ) ) {
							$percent       = $store_discount->discount;
							$discount_note = ' - ' . $store_discount->note;
						}
					}

					$total = round( $total * ( 100 - $percent ) / 100, 2 );

					if ( $payment_amount - $total < -0.5 ) {
						exit;
					}

					$discount_note = strip_tags( $discount_note );
					foreach ( $products as $key => $product ) {
						if ( ! isset( $product->post_title ) ) {
							$product->post_title = '';
						}
						$products[ $key ] = $product;

						$note = ( ( ! empty( $product->discount_applied ) ) ? $product->discount_applied : '' ) . $discount_note;
						if (
							music_store_register_purchase(
								$product->product_id,
								$purchase_id,
								$payer_email,
								round( $product->final_price * ( 100 - $percent ) / 100, 2 ) + $tax,
								$paypal_data,
								$note
							)
						) {
							if (
								isset( $product->price_type ) &&
								'exclusive' == $product->price_type &&
								! empty( $product->exclusive_price )
							) {
								$wpdb->query(
									$wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_POST_DATA . ' SET purchases=purchases+1, purchased_exclusively=1 WHERE id=%d', $product->product_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
								);
								$wpdb->query(
									$wpdb->prepare( 'UPDATE ' . $wpdb->posts . " SET post_status='pexclusively' WHERE ID=%d", $product->product_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
								);
								$this->_set_license( 'exclusive' );
							} else {
								$wpdb->query(
									$wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_POST_DATA . ' SET purchases=purchases+1 WHERE id=%d', $product->product_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
								);
								$this->_set_license( 'regular' );
							}
						}
					}
					if ( function_exists( 'music_store_removeCart' ) ) {
						music_store_removeCart( $purchase_id );
					}
				} else { // The shopping cart is disabled
					if ( isset( $ipn_parameters['pid'] ) ) {
						$id = $ipn_parameters['pid'];
					} elseif ( isset( $ipn_parameters['id'] ) ) {
						$id = $ipn_parameters['id'];
					} else {
						exit;
					}

					$_post = get_post( $id );
					if ( is_null( $_post ) ) {
						exit;
					}

					switch ( $_post->post_type ) {
						case 'ms_song':
							$obj = new MSSong( $id );
							break;
						case 'ms_collection':
							$obj = new MSCollection( $id );
							break;
						default:
							exit;
						break;
					}

					if (
						isset( $ipn_parameters['price_type'] ) &&
						'exclusive' == $ipn_parameters['price_type'] &&
						! empty( $obj->exclusive_price )
					) {
						$price = $obj->exclusive_price;
						$this->_set_license( 'exclusive' );
						$exclusive = true;
					} else {
						if (
							$variable_price &&
							isset( $obj->price_type ) &&
							is_numeric( $obj->price_type ) &&
							$obj->price <= ( $variable_price = floatval( $obj->price_type ) ) // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
						) {
							$price         = $variable_price;
							$discount_note = '';
						} else {
							if ( function_exists( 'music_store_getValidProductDiscount' ) ) {
								$discount = music_store_getValidProductDiscount( $id );
							}

							$price         = ( ! empty( $discount ) ) ? $discount->discount : $obj->price;
							$discount_note = ( ( ! empty( $discount ) && ! empty( $discount->note ) ) ? ' - ' . strip_tags( $discount->note ) : '' ) . $discount_note;
						}
						$this->_set_license( 'regular' );
					}

					if (
						! $variable_price &&
						0 == $percent
					) {
						if ( function_exists( 'music_store_getValidStoreDiscount' ) ) {
							$store_discount = music_store_getValidStoreDiscount( $price );
						}

						if ( ! empty( $store_discount ) ) {
							$percent        = $store_discount->discount;
							$discount_note .= ( ! empty( $store_discount->note ) ) ? ' - ' . strip_tags( $store_discount->note ) : '';
						}
					}

					$price = round( $price * ( 100 - $percent ) / 100, 2 );
					if ( $payment_amount - $price < -0.5 ) {
						exit;
					}
					if (
						music_store_register_purchase(
							$id,
							$purchase_id,
							$payer_email,
							$payment_amount + $tax,
							$paypal_data,
							$discount_note
						)
					) {
						$obj->purchases++;
						if ( ! empty( $exclusive ) ) {
							$obj->purchased_exclusively = 1;
							$obj->post_status           = 'pexclusively';
							$wpdb->query(
								$wpdb->prepare( 'UPDATE ' . $wpdb->posts . " SET post_status='pexclusively' WHERE ID=%d", $obj->id )
							);
						}
					}
				}
				do_action(
					'musicstore_send_notification_emails',
					array(
						'item_name'   => $item_name,
						'currency'    => $payment_currency,
						'purchase_id' => $purchase_id,
						'amount'      => $payment_amount + $tax,
						'payer_email' => $payer_email,
						'tax'         => $tax,
					),
					$this->_licenses
				);

				$_POST['ms_purchase_id']    = $purchase_id;
				$_POST['ms_payment_amount'] = $payment_amount;

				do_action( 'musicstore_payment_received', $_POST, ( isset( $products ) ) ? $products : $obj ); // phpcs:ignore WordPress.Security.NonceVerification
				echo 'OK';
				exit;
			}
		} // End check_payment

		/************************************** PRIVATE METHODS **************************************/

		private function _make_seed() {
			 list($usec, $sec) = explode( ' ', microtime() );
			return intval( (float) $sec + ( (float) $usec * 1000000 ) );
		} // End _make_seed

		private function _set_license( $license_type ) {
			global $music_store_settings;
			if ( function_exists( 'music_store_set_license' ) ) {
				music_store_set_license( $license_type, $this->_licenses );
			}
		} // End _set_license

	} // End  MS_PayPal
}
MUSIC_STORE_PAYPAL_ADDON::init();
