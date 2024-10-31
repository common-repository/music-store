<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

	global $music_store_settings;

function ms_make_seed() {
	list($usec, $sec) = explode( ' ', microtime() );
	return intval( (float) $sec + ( (float) $usec * 1000000 ) );
}


if ( isset( $_POST['ms_product_id'] ) && isset( $_POST['ms_product_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	$obj = new MSSong( is_numeric( $_POST['ms_product_id'] ) ? intval( $_POST['ms_product_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $obj->ID ) ) {
		$amount = apply_filters( 'musicstore_final_price', $obj->price );
		if ( $amount > 0 ) {
			mt_srand( intval( ms_make_seed() ) );
			$randval     = mt_rand( 1, 999999 );
			$purchase_id = md5( $randval . uniqid( '', true ) );

			$baseurl    = MS_H_URL . '?ms-action=ipn';
			$returnurl  = $GLOBALS['music_store']->_ms_create_pages( 'ms-download-page', 'Download Page' );
			$returnurl .= ( ( strpos( $returnurl, '?' ) === false ) ? '?' : '&' ) . 'ms-action=download';
			if (
				preg_match( '/^(http(s)?:\/\/[^\/\n]*)/i', MS_H_URL, $matches ) &&
				isset( $_SERVER['HTTP_REFERER'] ) &&
				strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), $matches[0] )
			) {
				$cancelurl = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			}
			if ( empty( $cancelurl ) ) {
				$cancelurl = MS_H_URL;
			}
			$purchase_settings = array(
				'item_name'   => $obj->post_title,
				'item_number' => $obj->ID,
				'id'          => $purchase_id,
				'products'    => array( $obj ),
				'baseurl'     => $baseurl,
				'returnurl'   => $returnurl,
				'cancelurl'   => $cancelurl,
			);
			do_action(
				'musicstore_calling_payment_gateway',
				$amount,
				$purchase_settings
			);
			exit;
		} else // End amount == 0
		{
			$GLOBALS[ MS_SESSION_NAME ]['download_for_free'] = array();

			// Check if it is a registered user
			$current_user       = wp_get_current_user(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$current_user_email = '';
			if ( 0 !== $current_user->ID ) {
				$current_user_email = $current_user->user_email;
			} else {
				$current_user_email = ms_getIP();
				$current_user_email = str_replace( '_', '.', $current_user_email );
			}

			$GLOBALS[ MS_SESSION_NAME ]['download_for_free'][] = $purchase_id;
			music_store_register_purchase( $obj->ID, $purchase_id, $current_user_email, 0, '' );
			header( 'location: ' . esc_url_raw( $returnurl . '&purchase_id=' . $purchase_id ) );
			exit;
		}
	} // End if saler and object
} // End if parameters

	header( 'location: ' . $cancelurl );
