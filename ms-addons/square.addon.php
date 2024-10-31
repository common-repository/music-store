<?php
/*
Documentation: https://github.com/square/connect-api-examples/tree/master/connect-examples/v2/php_payment
				https://docs.connect.squareup.com/payments/online-payments
*/
if ( ! class_exists( 'MUSIC_STORE_SQUARE_ADDON' ) ) {
	class MUSIC_STORE_SQUARE_ADDON {

		private static $instance;
		private $licenses;
		private $default_settings;
		private $current_settings;

		public static function init() {
			if ( null == self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		} // End init

		public function __construct() {
			$this->licenses         = array();
			$this->default_settings = array(
				'enabled'            => 0,
				'square_key'         => '',
				'square_accesstoken' => '',
				'mode'               => 1,
				'label'              => 'Square',
			);

			if ( ! is_admin() ) {
				add_action( 'musicstore_calling_payment_gateway', array( $this, 'send_to_square' ), 10, 2 );
				add_action( 'musicstore_checking_payment', array( $this, 'check_payment' ), 1 );
				add_filter( 'musicstore_payment_gateway_enabled', array( $this, 'is_enabled' ), 10 );
				add_filter( 'musicstore_payment_gateway_list', array( $this, 'populate_list' ), 10 );
			} else {
				add_action( 'musicstore_settings_page', array( &$this, 'show_settings' ), 11 );
				add_action( 'musicstore_save_settings', array( &$this, 'save_settings' ), 11 );
			}
		} // End __construct

		/**************************** PRIVATE METHODS ***************************/

		private function _settings( $reload = false ) {
			if ( empty( $this->current_settings ) || $reload ) {
				$this->current_settings = get_option( 'ms_square_settings', $this->default_settings );
			}

			return $this->current_settings;
		} // End _settings

		private function _set_license( $license_type ) {
			if ( function_exists( 'music_store_set_license' ) ) {
				music_store_set_license( $license_type, $this->licenses );
			}
		} // End _set_license

		private function _exit() {
			remove_all_actions( 'shutdown' );
			exit;
		} // End _exit

		private function _log( $message, $print = false ) {
			error_log( $message );
			if ( $print ) {
				print $message;
			}
		} // End _log

		/************************************** PUBLIC METHODS  **************************************/

		public function is_enabled( $enabled ) {
			$settings = $this->_settings();
			return $settings['enabled'] || $enabled;
		} // End is_enabled

		public function populate_list( $payment_gateways ) {
			if ( $this->is_enabled( false ) ) {
				$settings                   = $this->_settings();
				$label                      = ( ! empty( $settings['label'] ) ) ? __( $settings['label'], 'music-store' ) : $this->default_settings['label']; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				$payment_gateways['square'] = $label;
			}
			return $payment_gateways;
		} // End populate_list

		public function show_settings() {
			$settings = $this->_settings( true );
			?>
			<div id="metabox_basic_settings" class="postbox" >
				<h3 class='hndle' style="padding:5px;"><span>Square <?php esc_html_e( 'Payment Gateway', 'music-store' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Square?', 'music-store' ); ?></th>
							<td>
								<input type="checkbox" name="square_enabled" <?php print ( ! empty( $settings['enabled'] ) ? 'checked' : '' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Label', 'music-store' ); ?></th>
							<td><input type="text" name="square_label" style="width:100%;" value="<?php print esc_attr( ! empty( $settings['label'] ) ? $settings['label'] : '' ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row">Squareup.com <?php esc_html_e( 'Location ID', 'music-store' ); ?></th>
							<td>
								<input type="text" name="square_key" style="width:100%;" value="<?php echo esc_attr( $settings['square_key'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">Squareup.com <?php esc_html_e( 'Access Token', 'music-store' ); ?></th>
							<td>
								<input type="text" name="square_accesstoken" style="width:100%;" value="<?php echo esc_attr( $settings['square_accesstoken'] ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Square Mode', 'music-store' ); ?></th>
							<td>
								<select name="square_mode">
									<option value="1" <?php if ( '0' != $settings['mode'] ) {
										echo 'selected';} ?>>
										<?php esc_html_e( 'Production - real payments processed', 'music-store' ); ?>
									</option>
									<option value="0" <?php if ( '0' == $settings['mode'] ) {
										echo 'selected';} ?>>
										<?php esc_html_e( 'SandBox - Square testing sandbox area', 'music-store' ); ?>
									</option>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<?php
		} // End show_settings

		public function save_settings() {
			$settings               = array(
				'enabled'            => isset( $_REQUEST['square_enabled'] ) ? 1 : 0,
				'square_key'         => isset( $_REQUEST['square_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['square_key'] ) ) : '',
				'square_accesstoken' => isset( $_REQUEST['square_accesstoken'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['square_accesstoken'] ) ) : '',
				'mode'               => isset( $_REQUEST['square_mode'] ) && is_numeric( $_REQUEST['square_mode'] ) ? intval( $_REQUEST['square_mode'] ) : 0,
				'label'              => isset( $_REQUEST['square_label'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['square_label'] ) ) : '',
			);
			$this->current_settings = $settings;
			update_option( 'ms_square_settings', $settings );
		} // End save_settings

		/************************ PUBLIC METHODS  *****************************/

		public function send_to_square( $amount, $purchase_settings ) {
			global $music_store_settings;

			if (
				! $this->is_enabled( false ) ||
				! isset( $_REQUEST['ms_payment_gateway'] ) ||
				'square' != $_REQUEST['ms_payment_gateway']
			) {
				return;
			}

			if ( $amount > 0 ) {
				$item_name = ( ! empty( $purchase_settings['item_name'] ) ) ? $purchase_settings['item_name'] : '';
				$item_name = html_entity_decode( $item_name, ENT_COMPAT, 'UTF-8' );
				if ( function_exists( 'music_store_apply_taxes' ) ) {
					$amount = music_store_apply_taxes( $amount );
				}
				$amount   = round( $amount * 100, 0 );
				$settings = $this->_settings();
				$currency = strtoupper( ( ! empty( $music_store_settings['ms_paypal_currency'] ) ) ? $music_store_settings['ms_paypal_currency'] : 'usd' );

				$purchase_id = ( ! empty( $purchase_settings['id'] ) ) ? $purchase_settings['id'] : '';
				$products_id = '';
				$baseurl     = $purchase_settings['baseurl'];
				$cancelurl   = $purchase_settings['cancelurl'];

				if ( ! empty( $purchase_settings['products'] ) ) {
					foreach ( $purchase_settings['products'] as $product ) {
						$products_id .= ( isset( $product->product_id ) ) ? $product->product_id : $product->ID;
					}
				}
				$coupon_code = ( ! empty( $purchase_settings['coupon'] ) ) ? '|ms_coupon_code=' . $purchase_settings['coupon'] : '';
				$buyer       = ( 0 != ( $user_id = get_current_user_id() ) ) ? '|buyer_id=' . $user_id : ''; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

				error_reporting( E_ERROR | E_PARSE );

				if ( ! class_exists( '\Square\SquareClient' ) ) {
					require_once __DIR__ . '/square-addon/vendor/autoload.php';
				}

				$data = array(
					'amount'    => $amount,
					'name'      => sanitize_text_field( $item_name ),
					'notifyurl' => apply_filters( 'musicstore_notify_url', $baseurl . '|pid=' . $products_id . '|purchase_id=' . $purchase_id . '|rtn_act=purchased_product_music_store' . $coupon_code . $buyer . '&ms_square_ipncheck=1' ),
				);

				$client = new \Square\SquareClient(
					array(
						'accessToken' => $settings['square_accesstoken'],
						'environment' => $settings['mode'] ? \Square\Environment::PRODUCTION : \Square\Environment::SANDBOX,
					)
				);

				try {
					$checkout_api = $client->getCheckoutApi();

					$money = new \Square\Models\Money();
					$money->setCurrency( $currency );
					$money->setAmount( $data['amount'] );

					$item = new \Square\Models\OrderLineItem( 1 );
					$item->setName( $data['name'] );
					$item->setBasePriceMoney( $money );

					$order = new \Square\Models\Order( $settings['square_key'] );
					$order->setLineItems( array( $item ) );

					$create_order_request = new \Square\Models\CreateOrderRequest();
					$create_order_request->setOrder( $order );

					$checkout_request = new \Square\Models\CreateCheckoutRequest( uniqid(), $create_order_request );
					$checkout_request->setRedirectUrl( $data['notifyurl'] );
					$response = $checkout_api->createCheckout( $settings['square_key'], $checkout_request );
				} catch ( \Square\Exceptions\ApiException $e ) {
					$this->_log( 'Caught exception!<br/><strong>Response body:</strong><br/><pre>' . print_r( $e->getMessage(), true ) . '</pre>', true );
					$this->_exit();
				}

				if ( $response->isError() ) {
					$error_message = 'Api response has Errors<ul>';
					$errors        = $response->getErrors();
					foreach ( $errors as $error ) {
						$error_message .= '<li>❌ ' . $error->getDetail() . '</li>';
					}
					$error_message .= '</ul>';
					$this->_log( $error_message, true );
					$this->_exit();
				}

				// This redirects to the Square hosted checkout page
				if ( ! headers_sent() ) {
					header( 'Location: ' . $response->getResult()->getCheckout()->getCheckoutPageUrl() );
				} else {
					print '<script>document.location.href="' . esc_js( $response->getResult()->getCheckout()->getCheckoutPageUrl() ) . '";</script>';
				}

				$this->_exit();
			}
		} // End send_to_square

		public function check_payment() {
			global $music_store_settings, $wpdb;

			if (
				$this->is_enabled( false ) &&
				isset( $_REQUEST['ms_square_ipncheck'] ) &&
				isset( $_REQUEST['checkoutId'] ) &&
				isset( $_REQUEST['transactionId'] )
			) {
				$checkoutId    = sanitize_text_field( wp_unslash( $_GET['checkoutId'] ) );
				$transactionId = sanitize_text_field( wp_unslash( $_GET['transactionId'] ) );

				$settings = $this->_settings();
				if ( ! empty( $settings['square_key'] ) && ! empty( $settings['square_accesstoken'] ) ) {
					try {
						try {
							$_parameters = isset( $_GET['ms-action'] ) ? explode( '|', sanitize_text_field( wp_unslash( $_GET['ms-action'] ) ) ) : array();
							foreach ( $_parameters as $_parameter ) {
								$_parameter_parts = explode( '=', $_parameter );
								if ( 2 == count( $_parameter_parts ) ) {
									$ipn_parameters[ $_parameter_parts[0] ] = sanitize_text_field( $_parameter_parts[1] );
								}
							}

							$purchase_id = isset( $ipn_parameters['purchase_id'] ) ? $ipn_parameters['purchase_id'] : 0;
							$product_id  = isset( $ipn_parameters['pid'] ) ? $ipn_parameters['pid'] : 0;
							// If the buyer_id is empty the result would be 0
							$GLOBALS['buyer_id'] = ( ! empty( $ipn_parameters['buyer_id'] ) && is_numeric( $ipn_parameters['buyer_id'] ) ) ? intval( $ipn_parameters['buyer_id'] ) : 0;

							$products = array();
							if ( ! empty( $music_store_settings['ms_paypal_shopping_cart'] ) ) {
								if ( ! empty( $purchase_id ) ) {
									$products = music_store_getProducts( $purchase_id );
								}
							} elseif ( ! empty( $product_id ) ) {
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

							$percent       = 0;
							$discount_note = '';
							$base_price    = 0;

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

							$amount = $base_price * ( 100 - $percent );
							if ( function_exists( 'music_store_apply_taxes' ) ) {
								$amount = music_store_apply_taxes( $amount );
							}

							if ( ! class_exists( '\Square\SquareClient' ) ) {
								require_once dirname( __FILE__ ) . '/square-addon/vendor/autoload.php';
							}

							$access_token = $settings['square_accesstoken'];

							$client = new \Square\SquareClient(
								array(
									'accessToken' => $access_token,
									'environment' => ( $settings['mode'] ? \Square\Environment::PRODUCTION : \Square\Environment::SANDBOX ),
								)
							);

							$ordersApi = $client->getOrdersApi();

							$body_orderIds = array( $transactionId );
							$body          = new Square\Models\BatchRetrieveOrdersRequest( $body_orderIds );
							$body->setLocationId( $settings['square_key'] );

							try {
								$apiResponse = $ordersApi->batchRetrieveOrders( $body );
								if ( $apiResponse->isSuccess() ) {
									$batchRetrieveOrdersResponse = $apiResponse->getResult();
									$orders                      = $batchRetrieveOrdersResponse->getOrders();
									if ( count( $orders ) ) {
										$order = $orders[0];
										if ( $order->getState() == 'COMPLETED' && count( $lineItems = $order->getLineItems() ) ) {
											$item_name   = $lineItems[0]->getName();
											$user        = ! empty( $ipn_parameters['buyer_id'] ) && is_numeric( $ipn_parameters['buyer_id'] ) ? get_userdata( intval( $ipn_parameters['buyer_id'] ) ) : false;
											$payer_email = ( $user ) ? $user->user_email : '';
											$money       = $order->getTotalMoney();
											if ( $amount <= $money->getAmount() ) {
												$payment_gateway_data = json_encode( $order );

												// Register the purchase
												foreach ( $products as $product ) {
													$note = strip_tags( ( ( ! empty( $product->discount_applied ) ) ? $product->discount_applied : '' ) . $discount_note );

													if (
														function_exists( 'music_store_register_purchase' ) &&
														music_store_register_purchase(
															$product->product_id,
															$purchase_id,
															$payer_email,
															round( $product->final_price * ( 100 - $percent ) / 100, 2 ),
															$payment_gateway_data,
															$note
														)
													) {
														if ( $product->exclusive_price_applied ) {
															$wpdb->query(
																$wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_POST_DATA . ' SET purchases=purchases+1, purchased_exclusively=1 WHERE id=%d', $product->product_id )
															);
															$wpdb->query(
																$wpdb->prepare( 'UPDATE ' . $wpdb->posts . " SET post_status='pexclusively' WHERE ID=%d", $product->product_id )
															);
														} else {
															$wpdb->query(
																$wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_POST_DATA . ' SET purchases=purchases+1 WHERE id=%d', $product->product_id )
															);
														}
													}
												}
												if ( function_exists( 'music_store_removeCart' ) ) {
													music_store_removeCart( $purchase_id );
												}
												if ( function_exists( 'music_store_send_emails' ) ) {
													music_store_send_emails(
														array(
															'item_name'     => $item_name,
															'currency'      => $music_store_settings['ms_paypal_currency'],
															'purchase_id'   => $purchase_id,
															'amount'        => number_format( $amount / 100, 2 ),
															'payer_email'   => $payer_email,
														),
														$this->licenses
													);
												}

												$_POST['ms_purchase_id']    = $purchase_id;
												$_POST['ms_payment_amount'] = number_format( $amount / 100, 2 );

												do_action( 'musicstore_payment_received', $_POST, $products );

												// Redirects the user to the download page
												$returnurl  = $GLOBALS['music_store']->_ms_create_pages( 'ms-download-page', 'Download Page' );
												$returnurl .= ( ( strpos( $returnurl, '?' ) === false ) ? '?' : '&' ) . 'ms-action=download';
												$returnurl .= '&purchase_id=' . $purchase_id;

												if ( ! empty( $returnurl ) ) {

													if ( ! headers_sent() ) {
														header( 'Location: ' . $returnurl );
													} else {
														print '<script>document.location.href="' . str_replace( '&amp;', '&', esc_js( $returnurl ) ) . '";</script>';
													}
													$this->_exit();
												}
											}
										}
									}
								} else {
									$error_message = 'Api response has Errors<ul>';
									$errors        = $apiResponse->getErrors();
									foreach ( $errors as $error ) {
										$error_message .= '<li>❌ ' . wp_kses_post( $error->getDetail() ) . '</li>';
									}
									$error_message .= '</ul>';
									$this->_log( $error_message, true );
									$this->_exit();
								}
							} catch ( Exception $e ) {
								$this->_log( '<pre>' . print_r( wp_kses_post( $e->getMessage() ), true ) . '</pre>', true );
								$this->_exit();
							}
						} catch ( \SquareConnect\ApiException $e ) {
								echo '<p>Square: ' . esc_html__( 'Failed to process credit card. Error message', 'music-store' ) . ': <strong>' . wp_kses_post( $e->getResponseBody()->errors[0]->detail ) . '</strong></p>';
								echo '<p>' . wp_kses_post( __( 'Please <a href="javascript:window.history.back();">go back and try again</a>', 'music-store' ) ) . '</p>';
								$this->_exit();
						}
					} catch ( Exception $e ) {
						echo '<p>Square: ' . esc_html__( 'Failed to process credit card. Error message', 'music-store' ) . ': <strong>' . wp_kses_post( $e->getMessage() ) . '</strong></p>';
						echo '<p>' . wp_kses_post( __( 'Please <a href="javascript:window.history.back();">go back and try again</a>', 'music-store' ) ) . '</p>';
						$this->_exit();
					}
				}
			}
		} // end check_payment
	} // End MUSIC_STORE_SQUARE_ADDON
}
MUSIC_STORE_SQUARE_ADDON::init();
