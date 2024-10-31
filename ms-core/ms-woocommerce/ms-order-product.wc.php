<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

class WC_Order_Item_Product_MS extends WC_Order_Item_Product {

	public function set_product_id( $value ) {
		$this->data['product_id'] = absint( $value );
	} // End set_product_id

	public function get_name( $context = 'view' ) {
		global $wpdb;
		$product_name = $wpdb->get_var( $wpdb->prepare( 'SELECT post_title FROM ' . $wpdb->posts . ' WHERE ID=%d', $this->get_product_id() ) );
		return ! empty( $product_name ) ? $product_name : $this->get_product_id();
	} // End get_name

	public function get_item_downloads() {
		global $wpdb;
		require_once dirname( __FILE__ ) . '/../ms-download.php';
		$order = $this->get_order();
		if ( 'completed' == $order->get_status() ) {
			$post = get_post($this->data['product_id']);
			if ( ! empty( $post ) && 'ms_song' == $post->post_type ) {
				$song = new MSSong_WC( $post->ID );
				$downloads_obj = $song->get_downloads();
				$downloads = array();
				foreach ( $downloads_obj as $download_obj ) {

					$purchase_id = sanitize_key( $order->get_order_key() );

					// Delete purchases with same purchase id
					$wpdb->delete(
						$wpdb->prefix . MSDB_PURCHASE,
						array( 'purchase_id' => $purchase_id ),
						array( '%s' )
					);

					// Register the purchase
					$buyer = get_userdata($order->user_id);
					$buyer_email = $buyer ? $buyer->user_email : '';

					$wpdb->insert(
						$wpdb->prefix . MSDB_PURCHASE,
						array(
							'product_id'  => $post->ID,
							'purchase_id' => $purchase_id,
							'buyer_id'    => $order->user_id,
							'date'        => gmdate( 'Y-m-d H:i:s' ),
							'email'       => $buyer_email,
							'amount'      => $order->get_total(),
							'paypal_data' => 'WooCommerce purchase',
							'note'        => '',
						),
						array( '%d', '%s', '%d', '%s', '%s', '%f', '%s', '%s' )
					);

					$download_link = ms_copy_download_links( $download_obj['file'] );
					$download_link = MS_H_URL . '?ms-action=f-download' . ( ( isset( $GLOBALS[ MS_SESSION_NAME ]['ms_user_email'] ) ) ? '&ms_user_email=' . $GLOBALS[ MS_SESSION_NAME ]['ms_user_email'] : '' ) . '&f=' . $download_link . '&purchase_id=' . sanitize_key( $order->get_order_key() );
					$download = $download_obj->get_data();

					$download['download_url'] = $download_link;

					$downloads[] = $download;
				}
				return $downloads;
			}
		}
	} // End get_item_downloads

	public function set_name( $value ) {
		$this->data['product_name'] = $value;
	} // End set_name

	public function set_subtotal( $value ) {
		$value = wc_format_decimal( $value );
		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->data['subtotal'] = $value;
	} // End set_subtotal

	public function set_total( $value ) {
		$value = wc_format_decimal( $value );
		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->data['total'] = $value;

		// Subtotal cannot be less than total.
		if ( '' === $this->get_subtotal() || $this->get_subtotal() < $this->get_total() ) {
			$this->set_subtotal( $value );
		}
	} // End set_total

	public function set_product( $product ) {
		$this->set_product_id( $product->get_id() );
		$this->set_name( $product->get_name() );
		$this->set_tax_class( $product->get_tax_class() );
	} // End set_product

} // End WC_Order_Item_MS
