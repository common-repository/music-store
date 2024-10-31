<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

require_once dirname( __FILE__ ) . '/ms-store.wc.php';
require_once dirname( __FILE__ ) . '/ms-song.wc.php';
require_once dirname( __FILE__ ) . '/ms-order-product.wc.php';

function ms_woocommerce_product_class( $class, $wildcard_1, $wildcard_2, $product_id ) {
	$post = get_post( $product_id );
	if ( ! empty( $post ) && 'ms_song' == $post->post_type ) {
		$class = 'MSSong_WC';
	}
	return $class;
} // End ms_woocommerce_product_class

function ms_woocommerce_add_to_cart_btn( $button, $product_id ) {
	global $music_store_settings;
	$in_cart = false;
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product_in_cart = $cart_item['product_id'];
		if ( $product_in_cart == $product_id ) {
			$in_cart = true;
		}
	}
	if ( $in_cart ) {
		return '<a href="' . esc_attr( wc_get_cart_url() ) . '" class="button">' . esc_html__( 'View Cart', 'music-store' ) . '</a>';
	}
	// return '<form action="' . esc_attr( wc_get_cart_url() ) . '" method="post">
	$action = ! empty( $music_store_settings['ms_woocommerce_cart_redirect'] ) ? 'action="' . esc_attr( wc_get_cart_url() ) . '"' : '';
	return '<form method="post" ' . $action . '>
				<input name="add-to-cart" type="hidden" value="' . esc_attr( $product_id ) . '" />
				<input name="quantity" type="hidden" value="1" min="1"  />
				<button class="add-to-cart ms-add-to-cart" >' . esc_html__( 'Add To Cart', 'music-store' ) . '</button>
			</form>';
} // End ms_woocommerce_add_to_cart_btn

function ms_woocommerce_product_type_query( $bool, $product_id ) {
	$post = get_post( $product_id );
	if ( ! empty( $post ) && 'ms_song' == $post->post_type ) {
		$bool = 'MSSong_WC';
	}
	return $bool;
} // End ms_woocommerce_product_type_query

function ms_woocommerce_after_cart_item_name( $cart_item, $cart_item_key ) {
	$post = get_post( $cart_item['product_id'] );
	if ( ! empty( $post ) && 'ms_song' == $post->post_type ) {
		$song = new MSSong( $post->ID );
		$demo = $song->get_audio_tag( 'single', $player_style );
		if ( ! empty( $demo ) ) {
			print '<div class="music-store-song" style="margin:10px 0 0 0;padding:0;width:100%;"><div class="ms-player single ' . esc_attr( $player_style ) . '">' . $demo . '</div></div>';
			$GLOBALS['music_store']->public_resources();
		}
	}

} // End ms_woocommerce_after_cart_item_name

function ms_woocommerce_data_stores( $stores ) {
	$stores['product-ms_song']       = 'WC_Product_MS_Data_Store_CPT';
	return $stores;
} // End ms_woocommerce_data_stores

function ms_woocommerce_checkout_create_order_line_item_object( $item, $cart_item_key, $values, $order ) {
	if (
		! empty( $values['data'] ) &&
		is_object( $values['data'] ) &&
		class_exists( 'MSSong_WC' ) &&
		$values['data'] instanceof MSSong_WC
	) {
		$item = new WC_Order_Item_Product_MS();
	}
	return $item;
} // End ms_woocommerce_checkout_create_order_line_item_object

function ms_woocommerce_get_order_item_classname( $classname, $item_type, $id ) {
	global $wpdb;

	if ( 'WC_Order_Item_Product' == $classname ) {
		$post_type = $wpdb->get_var( $wpdb->prepare( 'SELECT posts.post_type FROM ' . $wpdb->prefix . 'wc_order_product_lookup product_loockup INNER JOIN ' . $wpdb->posts . ' posts ON (product_loockup.product_id=posts.ID) WHERE order_item_id=%d', $id ) );

		if ( ! empty( $post_type ) && 'ms_song' == $post_type ) {
			return 'WC_Order_Item_Product_MS';
		}
	}
	return $classname;

} // End ms_woocommerce_get_order_item_classname

function ms_woocommerce_add_cart_item_data( $cart_item_meta, $product_id, $variation_id = 0, $quantity = 0 ) {
	$post = get_post( $product_id );
	if ( ! empty( $post ) && 'ms_song' == $post->post_type ) {
		$obj = new MSSong( $product_id );
		$cart_item_meta['ms_sale_price'] = wc_format_decimal( $obj->get_price() );
	}
	return $cart_item_meta;
} // End ms_woocommerce_add_cart_item_data

function ms_woocommerce_get_cart_item_data( $values, $cart_item ) {
	if ( isset( $cart_item['ms_sale_price'] ) ) {
		$cart_item['data']->set_price( $cart_item['ms_sale_price'] );
	}
	return $values;
} // End ms_woocommerce_get_cart_item_data

function ms_woocommerce_get_cart_item_from_session( $cart_item, $values, $key = '' ) {
	if ( isset( $values['ms_sale_price'] ) ) {
		$cart_item['ms_sale_price'] = $values['ms_sale_price'];
		$cart_item                  = ms_woocommerce_add_cart_item( $cart_item );
	}
	return $cart_item;
} // End ms_woocommerce_get_cart_item_from_session

function ms_woocommerce_add_cart_item( $cart_item ) {
	if ( isset( $cart_item['ms_sale_price'] ) ) {

		$price = $cart_item['ms_sale_price'];

		/** Modifies the prices defined by FANCY PRODUCT DESIGNER */
		if ( isset( $cart_item['fpd_data'] ) && isset( $cart_item['fpd_data']['fpd_product_price'] ) ) {
			$cart_item['fpd_data']['fpd_product_price'] = $price;
		}

		/** Modifies the prices defined by WOOCOMMERCE PRODUCT ADD-ONS ULTIMATE */
		if ( isset( $cart_item['product_extras'] ) && isset( $cart_item['product_extras']['price_with_extras'] ) ) {
			$cart_item['product_extras']['price_with_extras'] = $price;
		}

		if ( isset( $cart_item['product_extras'] ) && isset( $cart_item['product_extras']['original_price'] ) ) {
			$cart_item['product_extras']['original_price'] = $price;
		}

		if ( method_exists( $cart_item['data'], 'set_regular_price' ) ) {
			$cart_item['data']->set_regular_price( $price );
		}

		if ( property_exists( $cart_item['data'], 'regular_price' ) ) {
			$cart_item['data']->regular_price = $price;
		}

		if ( method_exists( $cart_item['data'], 'set_price' ) ) {
			$cart_item['data']->set_price( $price );
		}

		if ( property_exists( $cart_item['data'], 'price' ) ) {
			$cart_item['data']->price = $price;
		}
	}

	return $cart_item;
} // End ms_woocommerce_add_cart_item

function ms_woocommerce_trash_order( $order_id ) {
	if ( 'shop_order' != get_post_type( $order_id ) ) {
		return;
	}

	$order_obj = wc_get_order( $order_id );
	$order_key = $order_obj->get_order_key();

	global $wpdb;
	$wpdb->query($wpdb->prepare(
		"DELETE FROM ".$wpdb->prefix.MSDB_PURCHASE." WHERE purchase_id=%s",
		$order_key
	));

} // End ms_woocommerce_trash_order

function ms_woocommerce_untrash_order( $order_id, $previous_status ) {
	if ( 'shop_order' != get_post_type( $order_id ) ) {
		return;
	}

	$order_obj = wc_get_order( $order_id );
	if ( 'completed' == $order_obj->get_status() ) {
		$items = $order_obj->get_items();
		foreach ( $items as $item ) {
			if ( $item instanceof WC_Order_Item_Product_MS ) {
				$item->get_item_downloads();
			}
		}
	}

} // End ms_woocommerce_untrash_order

// WOOCOMMERCE RELATED ACTIONS
add_filter( 'musicstore_shopping_cart_button', 'ms_woocommerce_add_to_cart_btn', 10, 2 );
add_filter( 'musicstore_buynow_button', 'ms_woocommerce_add_to_cart_btn', 10, 2 );

add_filter( 'woocommerce_product_class', 'ms_woocommerce_product_class', 10, 4 );
add_filter( 'woocommerce_product_type_query', 'ms_woocommerce_product_type_query', 10, 2 );

add_action( 'woocommerce_after_cart_item_name', 'ms_woocommerce_after_cart_item_name', 10, 2 );
add_filter( 'woocommerce_data_stores', 'ms_woocommerce_data_stores', 10, 1 );

add_action( 'woocommerce_checkout_create_order_line_item_object', 'ms_woocommerce_checkout_create_order_line_item_object', 10, 4 );
add_filter( 'woocommerce_get_order_item_classname', 'ms_woocommerce_get_order_item_classname', 10, 3 );

add_filter( 'woocommerce_add_cart_item', 'ms_woocommerce_add_cart_item', 99, 1 );
add_filter( 'woocommerce_add_cart_item_data', 'ms_woocommerce_add_cart_item_data', 99, 2 );
add_filter( 'woocommerce_add_cart_item_data', 'ms_woocommerce_add_cart_item_data', 99, 4 );

add_filter( 'woocommerce_get_item_data', 'ms_woocommerce_get_cart_item_data', 99, 2 );
add_filter( 'woocommerce_get_cart_item_from_session', 'ms_woocommerce_get_cart_item_from_session', 99, 2 );
add_filter( 'woocommerce_get_cart_item_from_session', 'ms_woocommerce_get_cart_item_from_session', 99, 3 );

add_action( 'wp_trash_post', 'ms_woocommerce_trash_order', 99, 1 );
add_action( 'untrashed_post', 'ms_woocommerce_untrash_order', 99, 2 );

add_filter(
	'musicstore_payment_gateway_enabled',
	function( $enabled ) {
		return true;
	},
	99
);

// EDIT GLOBAL SETTINGS

if ( ! is_admin() ) {
	global $music_store_settings;

	$music_store_settings['ms_paypal_currency'] = get_woocommerce_currency();
	$music_store_settings['ms_paypal_currency_symbol'] = get_woocommerce_currency_symbol();
}