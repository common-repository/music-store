<?php
if ( ! class_exists( 'MUSIC_STORE_MOLLIE_ADDON' ) ) {
	class MUSIC_STORE_MOLLIE_ADDON {

		private static $_instance;
		private $_licenses;
		private $_default_settings;
		private $_settings;

		public static function init() {
			if ( null == self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		} // End init

		public function __construct() {
			 $this->_default_settings = array(
				 'enabled' => 0,
				 'api_key' => '',
				 'label'   => 'iDeal - Mollie',
			 );

			 if ( ! is_admin() ) {
				 add_action( 'musicstore_calling_payment_gateway', array( $this, 'send_to_mollie' ), 10, 2 );
				 add_action( 'musicstore_checking_payment', array( $this, 'check_payment' ), 10 );
				 add_filter( 'musicstore_payment_gateway_enabled', array( $this, 'enabled' ), 10 );
				 add_filter( 'musicstore_payment_gateway_list', array( $this, 'populate_list' ), 10 );
				 add_action( 'wp_footer', array( $this, 'mollie_form' ), 10 );
			 } else {
				 add_action( 'musicstore_settings_page', array( &$this, 'show_settings' ), 11 );
				 add_action( 'musicstore_save_settings', array( &$this, 'save_settings' ), 11 );
			 }
		} // End __construct

		/************************************** PRIVATE METHODS **************************************/
		private function settings( $attr, $reload = false ) {
			if ( empty( $this->_settings ) || $reload ) {
				$this->_settings = get_option( 'ms_mollie_settings', $this->_default_settings );
			}

			return $this->_settings[ $attr ];
		} // End settings


		private function get_mollie() {
			 // require_once dirname(__FILE__)."/mollie-addon/src/Mollie/API/Autoloader.php";
			require_once dirname( __FILE__ ) . '/mollie-addon/vendor/autoload.php';
			if ( '' == ( $api_key = $this->settings( 'api_key' ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				return false;
			}
			$mollie = new \Mollie\Api\MollieApiClient();
			$mollie->setApiKey( $api_key );
			return $mollie;
		} // End get_mollie

		/************************************** PUBLIC METHODS  **************************************/

		public function enabled( $enabled = false ) {
			return $this->settings( 'enabled' ) || $enabled;
		} // End enabled

		public function show_settings() {           ?>
			<div id="metabox_basic_settings" class="postbox" >
				<h3 class='hndle' style="padding:5px;"><span>iDeal - Mollie <?php esc_html_e( 'Payment Gateway', 'music-store' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Mollie?', 'music-store' ); ?></th>
							<td>
								<input type="checkbox" name="mollie_enabled" <?php print $this->enabled() ? 'checked' : ''; ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Label', 'music-store' ); ?></th>
							<td><input type="text" name="mollie_label" style="width:100%;" value="<?php print esc_attr( $this->settings( 'label' ) ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row">Mollie <?php esc_html_e( 'API Key', 'music-store' ); ?></th>
							<td>
								<input type="text" name="mollie_api_key" style="width:100%;" value="<?php echo esc_attr( $this->settings( 'api_key' ) ); ?>" />
							</td>
						</tr>
					</table>
				</div>
			</div>
			<?php
		} // End show_settings

		public function save_settings() {
			$this->_settings = array(
				'enabled' => isset( $_REQUEST['mollie_enabled'] ) ? 1 : 0,
				'api_key' => isset( $_REQUEST['mollie_api_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mollie_api_key'] ) ) : '',
				'label'   => isset( $_REQUEST['mollie_label'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mollie_label'] ) ) : '',
			);
			update_option( 'ms_mollie_settings', $this->_settings );
		} // End save_settings

		public function populate_list( $payment_gateways ) {
			if ( $this->enabled() ) {
				$payment_gateways['mollie'] = __( $this->settings( 'label' ), 'music-store' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			}
			return $payment_gateways;
		} // End populate_list

		public function mollie_form() {
			 global $music_store_settings;
			if ( $this->enabled() ) {
				?>
				<link rel="stylesheet" type="text/css" href="<?php print esc_url( plugins_url( '/mollie-addon/css/style.css', __FILE__ ) ); // phpcs:ignore WordPress.WP.EnqueuedResources ?>">
				<script>
					var ms_mollie_form_template = ''+
					'<div class="ms-mollie-email-form">'+
					'	<div class="ms-mollie-close-form"><a href="javascript:void(0);">X</a></div>'+
					'	<div class="ms-mollie-field">'+
					'	  	<label class="ms-mollie-label"><?php print esc_js( __( 'Email address where to receive the download link', 'music-store' ) ); ?></label>'+
					'	  	<div id="ms-mollie-email"><input type="email" id="ms_email" name="ms_email" class="ms-mollie-input required" required placeholder="<?php esc_attr_e( 'Required email', 'music-store' ); ?>" value="<?php
						$current_user = wp_get_current_user();
						print esc_attr( ! empty( $current_user ) ? $current_user->user_email : '' );
					?>" /></div>'+
					'	  	<div id="ms-mollie-button"><input type="button" class="ms-mollie-button" value="<?php esc_attr_e( 'Go to Pay', 'music-store' ); ?>" /></div>'+
					'	</div>'+
					'</div>';

					jQuery(window).on('load', function(){
						var $ = jQuery,
							ms_buy_now_mollie = ('ms_buy_now' in window) ? ms_buy_now : function(){return true;};

						$(document).on('click', '.ms-mollie-close-form', function(){$(this).closest('.ms-mollie-email-form').hide();});

						window['ms_buy_now'] = function(e, p){
							var o = $(e),
								f = o.closest('form'),
								l = f.find('select'),
								w;

							if(!l.length || l.val() == 'mollie')
							{
								if(ms_buy_now_mollie(this, p))
								{
									$('.ms-mollie-email-form').hide();
									w = $('.ms-mollie-email-form', f);
									if(!w.length)
									{
										w = $(ms_mollie_form_template);
										w.find('[type="button"]').click(function(){
											if(w.find('#ms_email')[0].checkValidity()) f.submit();
										});
										w.appendTo(f);
									}
									w.show();
								}
								return false;
							}
							else
							{
								return ms_buy_now_mollie(e, p);
							}
						};
						jQuery(document).on('click', '.ms-purchase-button', function(){return ms_buy_now(this);});
					});
				</script>
				<?php
			}
		} // End mollie_form


		public function send_to_mollie( $amount, $purchase_settings ) {
			if (
				! $this->enabled() ||
				! isset( $_REQUEST['ms_payment_gateway'] ) ||
				'mollie' != $_REQUEST['ms_payment_gateway'] ||
				false == ( $mollie = $this->get_mollie() ) // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
			) {
				return;
			}
			global $music_store_settings;

			$currency  = $music_store_settings['ms_paypal_currency'];
			$baseurl   = $purchase_settings['baseurl'];
			$returnurl = $purchase_settings['returnurl'];

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
				$purchase_id = ( ! empty( $purchase_settings['id'] ) ) ? $purchase_settings['id'] : '';
				$coupon_code = ( ! empty( $purchase_settings['coupon'] ) ) ? '|ms_coupon_code=' . $purchase_settings['coupon'] : '';
				$buyer       = ( 0 != ( $user_id = get_current_user_id() ) ) ? '|buyer_id=' . $user_id : ''; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

				$item_name = html_entity_decode( $item_name, ENT_COMPAT, 'UTF-8' );
				if ( function_exists( 'music_store_apply_taxes' ) ) {
					$amount = music_store_apply_taxes( $amount );
				}
				$amount = number_format( $amount, 2, '.', '' );

				try {
					$payment = $mollie->payments->create(
						array(
							'amount'      => array(
								'value'    => $amount,
								'currency' => $currency,
							),

							'description' => sanitize_text_field( $item_name ),

							'redirectUrl' => $returnurl . '&purchase_id=' . $purchase_id,

							'webhookUrl'  => apply_filters( 'musicstore_notify_url', $baseurl . '|pg=mollie|pid=' . $products_id . '|purchase_id=' . $purchase_id . '|rtn_act=purchased_product_music_store' . $coupon_code . $buyer ),

							'metadata'    => array(
								'order_id'    => $purchase_id,
								'buyer_email' => isset( $_REQUEST['ms_email'] ) ? sanitize_email( wp_unslash( $_REQUEST['ms_email'] ) ) : '',
							),
						)
					);

					set_transient( $purchase_id, $payment->id, 60 * 60 * 24 );
				} catch ( Exception $err ) {
					$error_message = $err->getMessage();
					error_log( $error_message );
					print wp_kses_post( $error_message );
				}
				?>
				<script>
					document.location.href="<?php print isset( $payment ) ? esc_url( $payment->getCheckoutUrl() ) : '#'; ?>";
				</script>
				<?php
				exit;
			}
		} // End send_to_mollie

		public function check_payment() {
			if (
				! $this->enabled() ||
				false == ( $mollie = $this->get_mollie() ) // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
			) {
				return;
			}

			global $music_store_settings;

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

				if ( ! empty( $ipn_parameters['pg'] ) && 'mollie' != $ipn_parameters['pg'] ) {
					return;
				}

				if ( ! isset( $ipn_parameters['purchase_id'] ) ) {
					exit;
				}
				$purchase_id = $ipn_parameters['purchase_id'];
				$payment_id  = get_transient( $purchase_id );
				$payment     = $mollie->payments->get( $payment_id );

				if ( $payment->isPaid() ) {
					// If the buyer_id is empty the result would be 0
					$GLOBALS['buyer_id'] = ( ! empty( $ipn_parameters['buyer_id'] ) && is_numeric( $ipn_parameters['buyer_id'] ) ) ? intval( $ipn_parameters['buyer_id'] ) : 0;

					$products = array();
					if ( ! empty( $music_store_settings['ms_paypal_shopping_cart'] ) ) {
						if ( ! empty( $purchase_id ) ) {
							$products = music_store_getProducts( $purchase_id );
						}
					} else {
						$product_id = isset( $ipn_parameters['pid'] ) ? $ipn_parameters['pid'] : 0;
						if ( ! empty( $product_id ) ) {
							$_post = get_post( $product_id );
							if ( is_null( $_post ) ) {
								esc_html_e( 'Non-existent product', 'music-store' );
								exit;
							}

							switch ( $_post->post_type ) {
								case 'ms_song':
									$products[] = new MSSong( $_post->ID );
									break;
								case 'ms_collection':
									$products[] = new MSCollection( $_post->ID );
									break;
								default:
									esc_html_e( 'The product is not a song or collection', 'music-store' );
									exit;
								break;
							}
						}
					}

					$percent       = 0;
					$discount_note = '';
					$base_price    = 0;
					$payer_email   = $payment->metadata->buyer_email;
					$item_name     = $payment->description;

					// Walking the products list to get the determine the price applied
					foreach ( $products as $i => $product ) {
						$processed_product             = new stdClass();
						$processed_product->product_id = ( isset( $product->product_id ) ) ? $product->product_id : $product->id;

						if ( isset( $product->price_type ) && 'exclusive' == $product->price_type && ! empty( $product->exclusive_price ) ) {
							$processed_product->exclusive_price_applied = true;
							$processed_product->final_price             = $product->exclusive_price;
							$this->_set_license( 'exclusive' );
						} else {
							if ( function_exists( 'music_store_getValidProductDiscount' ) ) {
								$discount = music_store_getValidProductDiscount( $product->product_id );
							}

							$processed_product->exclusive_price_applied = false;
							$processed_product->final_price             = ( ! empty( $discount ) ) ? $discount->discount : $product->price;
							$processed_product->discount_applied        = ( ! empty( $discount ) && ! empty( $discount->note ) ) ? ' - ' . $discount->note : '';
							$this->_set_license( 'regular' );
						}
						$products[ $i ] = $processed_product;
						$base_price    += $processed_product->final_price;
					}

					// Coupon
					if ( '' != ( $coupon_code = ( ! empty( $ipn_parameters['coupon'] ) ) ? $ipn_parameters['coupon'] : '' ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
						$coupon = $wpdb->get_row(
							$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . MSDB_COUPON . ' WHERE coupon=%s AND (onetime=0 OR times=0)', $coupon_code ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						);

						if ( ! empty( $coupon ) ) {
							$percent = $coupon->discount;
							$wpdb->query(
								$wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_COUPON . ' SET times=times+1 WHERE coupon=%s', $coupon_code ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							);
							$discount_note = ' - Coupon code: ' . $coupon->coupon . ' discount: ' . $percent . '%';
						}
					}

					// Checks the store discount
					if ( function_exists( 'music_store_getValidStoreDiscount' ) ) {
						$store_discount = music_store_getValidStoreDiscount( $base_price );
						if ( $store_discount && $percent < $store_discount->discount ) {
							$percent       = $store_discount->discount;
							$discount_note = ' - ' . $store_discount->note;
							$coupon_code   = '';
						}
					}

					// Register the purchase
					foreach ( $products as $product ) {
						$note = strip_tags( ( ( ! empty( $product->discount_applied ) ) ? $product->discount_applied : '' ) . $discount_note );

						$register_price = $product->final_price * ( 100 - $percent ) / 100;
						if ( function_exists( 'music_store_apply_taxes' ) ) {
							$register_price = music_store_apply_taxes( $register_price );
						}

						if (
						function_exists( 'music_store_register_purchase' ) &&
						music_store_register_purchase(
							$product->product_id,
							$purchase_id,
							( ! empty( $payer_email ) ) ? $payer_email : $result->payment->id,
							round( $register_price, 2 ),
							$payment_gateway_data,
							$note
						)
						) {
							if ( $product->exclusive_price_applied ) {
								$wpdb->query(
									$wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_POST_DATA . ' SET purchases=purchases+1, purchased_exclusively=1 WHERE id=%d', $product->product_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
								);
								$wpdb->query(
									$wpdb->prepare( 'UPDATE ' . $wpdb->posts . " SET post_status='pexclusively' WHERE ID=%d", $product->product_id )
								);
							} else {
								$wpdb->query(
									$wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_POST_DATA . ' SET purchases=purchases+1 WHERE id=%d', $product->product_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
								);
							}
						}
					}
					if ( function_exists( 'music_store_removeCart' ) ) {
						music_store_removeCart( $purchase_id );
					}
					if ( ! empty( $payer_email ) && function_exists( 'music_store_send_emails' ) ) {
						music_store_send_emails(
							array(
								'item_name'   => $item_name,
								'currency'    => $payment->amount->currency,
								'purchase_id' => $purchase_id,
								'amount'      => $payment->amount->value,
								'payer_email' => $payer_email,
							),
							$this->licenses
						);
					}

					$_POST['ms_purchase_id']    = $purchase_id;
					$_POST['ms_payment_amount'] = $payment->amount->value;

					do_action( 'musicstore_payment_received', $_POST, $products ); // phpcs:ignore WordPress.Security.NonceVerification

					echo 'Payment received.';
					exit;
				}
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
MUSIC_STORE_MOLLIE_ADDON::init();
