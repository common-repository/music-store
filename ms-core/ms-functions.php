<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

// Errors management
$ms_errors = array();
function music_store_setError( $error_text ) {
	global $ms_errors;
	$ms_errors[] = __( $error_text, 'music-store' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
}

function music_store_sanitize_permalink( $value ) {
	 global $wpdb;

	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

	if ( is_wp_error( $value ) ) {
		$value = '';
	}

	$value = esc_url_raw( trim( $value ) );
	$value = str_replace( 'http://', '', $value );
	return untrailingslashit( $value );
}

function music_store_complete_url( $url ) {
	 global $music_store_settings;
	if ( $music_store_settings['ms_prevent_cache'] ) {
		$url = add_query_arg( '_msr', time(), remove_query_arg( '_msr', $url ) );
	}
	return $url;

} // End music_store_complete_url

function music_store_strip_tags( $vs, $esc_html = false ) {
	 $allowed_tags = '<a><abbr><audio><b><blockquote><br><cite><code><del><dd><div><dl><dt><em><h1><h2><h3><h4><h5><h6><i><img><li><ol><p><q><source><span><strike><strong><table><tbody><theader><tfooter><tr><td><th><ul><video>';

	if ( is_array( $vs ) ) {
		foreach ( $vs as $i => $v ) {
			if ( is_string( $v ) ) {
				$v        = strip_tags( wp_unslash( $v ), $allowed_tags );
				$vs[ $i ] = ( $esc_html ) ? esc_html( $v ) : $v;
			}
		}
	} elseif ( is_string( $vs ) ) {
		$vs = strip_tags( wp_unslash( $vs ), $allowed_tags );
		if ( $esc_html ) {
			$vs = esc_html( $vs );
		}
	}
	return $vs;
}

if ( ! function_exists( 'ms_getIP' ) ) {
	function ms_getIP() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = isset( $_SERVER['HTTP_CLIENT_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) ) : '';
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '';
		}

		return str_replace( array( ':', '.' ), array( '_', '_' ), $ip );
	}
}

// Check if URL is for a local file, and return the relative URL or false
function music_store_is_local( $file ) {
	// Easy Check
	if ( defined( 'ABSPATH' ) ) {
		$path_component = parse_url( $file, PHP_URL_PATH );
		$path = rtrim( ABSPATH, '/' ) . '/' . ltrim( $path_component, '/' );
		if ( file_exists( $path ) ) {
			return $path;
		}
	}

	// Complex check
	$file = trim( $file );
	$file = str_replace( '\\', '/', $file );
	$file = preg_replace( '/^https?:\/\/(www\.)?/i', '', $file );

	$site_url = str_replace( '\\', '/', MS_H_URL );
	$site_url = preg_replace( '/^https?:\/\/(www\.)?/i', '', $site_url );

	$tmp_file     = strtolower( $file );
	$tmp_site_url = strtolower( $site_url );
	if ( strpos( $tmp_file, $tmp_site_url ) === false ) {
		return false;
	}

	$ms_url = str_replace( '\\', '/', MS_URL );
	$ms_url = preg_replace( '/^https?:\/\/(www\.)?/i', '', $ms_url );

	$parts          = explode( '/', str_ireplace( $site_url, '', $ms_url . '/sd-core' ) );
	$file           = str_ireplace( $site_url, '', $file );
	$path           = '';
	$_parts_counter = count( $parts );
	for ( $i = 0; $i < $_parts_counter; $i++ ) {
		$path .= '../';
	}
	$file = html_entity_decode( urldecode( dirname( __FILE__ ) . '/' . $path . $file ), ENT_QUOTES );
	return file_exists( $file ) ? $file : false;
}

// Check if the PHP memory is sufficient
function music_store_check_memory( $files = array() ) {
	$required = 0;

	$m = ini_get( 'memory_limit' );
	$m = trim( $m );
	$l = strtolower( $m[ strlen( $m ) - 1 ] ); // last
	$m = substr( $m, 0, -1 );
	switch ( $l ) {
		case 'g': // The 'G' modifier is available since PHP 5.1.0
			$m *= 1024;
		case 'm':
			$m *= 1024;
		case 'k':
			$m *= 1024;
	}

	$aux = function ( $files, &$required ) use ( $m, &$aux ) {
		if ( is_array( $files ) ) {
			foreach( $files as $file ) {
				if( ! $aux( $file, $required ) ) return false;
			}
		} else {
			$memory_available = $m - memory_get_usage( true );
			if ( ( $relative_path = music_store_is_local( $files ) ) !== false ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				$required += filesize( $relative_path );
				if ( $required >= $memory_available - 100 ) {
					return false;
				}
			} else {
				$response = wp_remote_head( $files );
				if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] ) {
					$required += $response['headers']['content-length'];
					if ( $required >= $memory_available - 100 ) {
						return false;
					}
				} else {
					return false;
				}
			}
		}

		return true;

	};

	foreach ( $files as $file ) {
		if( ! $aux( $file, $required ) ) return false;
	}
	return true;
} // music_store_check_memory

if ( ! function_exists( 'music_store_clearOlder' ) ) {
	/**
	 * Remove the older files
	 */
	function music_store_clearOlder( $dirname = '' ) {
		if ( isset( $GLOBALS['music_store_cleared_older_files'] ) ) {
			return;
		}
		$GLOBALS['music_store_cleared_older_files'] = true;
		if ( empty( $dirname ) ) {
			$dirname = MS_FILE_PATH . '/ms-temp';
		}
		$older_than      = 5; // Number of days
		$number_of_files = 10; // Number of files to delete in each call
		$c               = 0;
		$now             = time();

		$dif = 5 * 86400;
		$d   = dir( $dirname );

		while ( false !== ( $entry = $d->read() ) && $c < $number_of_files ) {
			if ( '.' != $entry && '..' != $entry && '.htaccess' != $entry ) {
				$file_name = $dirname . '/' . $entry;
				$date      = filemtime( $file_name );
				if ( $now - $date >= $dif ) { // Delete file
					@unlink( $file_name );
					$c++;
				}
			}
		}
		$d->close();
	} // End music_store_clearOlder
}

if ( ! function_exists( 'music_store_copy' ) ) {
	function music_store_copy( $from, $to ) {
		try {
			if ( ! file_exists( $to ) ) {
				if ( ( $path = music_store_is_local( $from ) ) !== false ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
					if ( filesize( $path ) < 104857600 ) {
						return copy( $path, $to );
					}

					// 5 meg at a time
					$buffer_size = 5242880;
					$ret         = 0;
					$fin         = fopen( $path, 'rb' );
					$fout        = fopen( $to, 'w' );
					while ( ! feof( $fin ) ) {
						$ret += fwrite( $fout, fread( $fin, $buffer_size ) );
					}
					fclose( $fin );
					fclose( $fout );
				} else {
					$response = wp_remote_get(
						$from,
						array(
							'timeout'  => MS_REMOTE_TIMEOUT,
							'stream'   => true,
							'filename' => $to,
						)
					);
					return ( ! is_wp_error( $response ) && 200 == $response['response']['code'] );
				}
			}
		} catch ( Exception $err ) {
			return false;
		}
		return true;
	}
}

function music_store_extract_attr_as_str( $arr, $attr, $separator ) {
	$result = '';
	$c      = count( $arr );
	if ( $c ) {
		$t       = (array) $arr[0];
		$result .= $t[ $attr ];
		for ( $i = 1; $i < $c; $i++ ) {
			$t       = (array) $arr[ $i ];
			$result .= $separator . $t[ $attr ];
		}
	}

	return $result;
} // End music_store_extract_attr_as_str

if ( ! function_exists( 'music_store_mime_type_accepted' ) ) {
	function music_store_mime_type_accepted( $file ) {
		$mime = wp_check_filetype( basename( $file ) );
		if (
			false == $mime['type'] ||
			preg_match( '/\b(php|asp|aspx|cgi|pl|perl|exe)\b/i', $mime['type'] . ' ' . $mime['ext'] )
		) {
			return false;
		}
		return true;
	}
}

function music_store_get_type( $file ) {
	$type = 'mpeg';
	$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	switch ( $ext ) {
		case 'ogg':
		case 'oga':
			$type = 'ogg';
			break;
		case 'wav':
			$type = 'wav';
			break;
		case 'wma':
			$type = 'wma';
			break;
		case 'aac':
			$type = 'mp4';
	}
	return $type;
} // music_store_get_type

function music_store_get_img_id( $url ) {
	global $wpdb;
	$attachment = $wpdb->get_col( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->prefix . 'posts WHERE guid=%s;', $url ) );
	return $attachment[0];
} // End music_store_get_img_id

// From PayPal Data RAW
/*
  $fieldsArr, array( 'fields name' => 'alias', ... )
  $selectAdd, used if is required complete the results like: COUNT(*) as count
  $groupBy, array( 'alias', ... ) the alias used in the $fieldsArr parameter
  $orderBy, array( 'alias' => 'direction', ... ) the alias used in the $fieldsArr parameter, direction = ASC or DESC
*/
function music_store_getFromPayPalData( $fieldsArr, $selectAdd = '', $from = '', $where = '', $groupBy = array(), $orderBy = array(), $returnAs = 'json' ) {
	global $wpdb;

	$_select  = 'SELECT ';
	$_from    = 'FROM ' . $wpdb->prefix . MSDB_PURCHASE . ( ( ! empty( $from ) ) ? ',' . $from : '' );
	$_where   = 'WHERE ' . ( ( ! empty( $where ) ) ? $where : 1 );
	$_groupBy = ( ! empty( $groupBy ) ) ? 'GROUP BY ' : '';
	$_orderBy = ( ! empty( $orderBy ) ) ? 'ORDER BY ' : '';

	$separator = '';
	foreach ( $fieldsArr as $key => $value ) {
		$length    = strlen( $key ) + 1;
		$_select  .= $separator . '
						SUBSTRING(paypal_data,
						LOCATE("' . $key . '", paypal_data)+' . $length . ',
						LOCATE("\r\n", paypal_data, LOCATE("' . $key . '", paypal_data))-(LOCATE("' . $key . '", paypal_data)+' . $length . ')) AS ' . $value;
		$separator = ',';
	}

	if ( ! empty( $selectAdd ) ) {
		$_select .= $separator . $selectAdd;
	}

	$separator = '';
	foreach ( $groupBy as $value ) {
		$_groupBy .= $separator . $value;
		$separator = ',';
	}

	$separator = '';
	foreach ( $orderBy as $key => $value ) {
		$_orderBy .= $separator . $key . ' ' . $value;
		$separator = ',';
	}

	$query = $_select . ' ' . $_from . ' ' . $_where . ' ' . $_groupBy . ' ' . $_orderBy;

	$result = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( ! empty( $result ) ) {
		switch ( $returnAs ) {
			case 'json':
				return json_encode( $result );
			break;
			default:
				return $result;
			break;
		}
	}
} // End music_store_getFromPayPalData

// Registers the purchase on database
function music_store_register_purchase( $product_id, $purchase_id, $email, $amount, $paypal_data, $purchase_note = '', $date = '' ) {
	global $wpdb;

	if ( empty( $GLOBALS['buyer_id'] ) ) {
		$buyer_user = get_user_by( 'email', $email );
		if ( $buyer_user ) {
			$GLOBALS['buyer_id'] = $buyer_user->ID;
		}
	}

	return $wpdb->insert(
		$wpdb->prefix . MSDB_PURCHASE,
		array(
			'product_id'  => $product_id,
			'purchase_id' => $purchase_id,
			'buyer_id'    => ! empty( $GLOBALS['buyer_id'] ) ? $GLOBALS['buyer_id'] : null,
			'date'        => ( '' == $date ) ? gmdate( 'Y-m-d H:i:s' ) : $date,
			'email'       => $email,
			'amount'      => $amount,
			'paypal_data' => apply_filters( 'musicstore_payment_data', $paypal_data ),
			'note'        => $purchase_note,
		),
		array( '%d', '%s', '%d', '%s', '%s', '%f', '%s', '%s' )
	);
} // End music_store_register_purchase

if ( ! function_exists( 'music_store_apply_taxes' ) ) {
	function music_store_apply_taxes( $v ) {
		global $music_store_settings;
		if ( ! empty( $music_store_settings['ms_tax'] ) ) {
			$v *= ( 1 + $music_store_settings['ms_tax'] / 100 );
		}
		return $v;
	} // End music_store_apply_taxes
}

function music_store_set_license( $license_type, &$licenses ) {
	 global $music_store_settings;
	if (
		'exclusive' == $license_type &&
		! empty( $music_store_settings['ms_license_for_exclusive'] )
	) {
		$licenses[ $license_type ] = esc_html__( 'License for exclusive purchases', 'music-store' ) . ': ' . $music_store_settings['ms_license_for_exclusive'];
	} elseif (
		'regular' == $license_type &&
		! empty( $music_store_settings['ms_license_for_regular'] )
	) {
		$licenses[ $license_type ] = esc_html__( 'License for regular purchases', 'music-store' ) . ': ' . $music_store_settings['ms_license_for_regular'];
	}
}

function music_store_send_emails( $purchase_settings, $licenses = array() ) {
	global $music_store_settings;

	$ms_notification_from_email = trim( $music_store_settings['ms_notification_from_email'] );

	/* Moidifies the "from" email if it is empty, invalid, or the default email address */
	if (
		empty( $ms_notification_from_email ) ||
		MS_NOTIFICATION_FROM_EMAIL == $ms_notification_from_email ||
		sanitize_email( MS_NOTIFICATION_FROM_EMAIL ) == $ms_notification_from_email ||
		false === strpos( $ms_notification_from_email, '@' )
	) {
		$ms_notification_from_email = 'admin@' . str_replace( 'www.', '', ( isset( $_SERVER['HTTP_HOST'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '' ) );
	}

	$ms_notification_to_email = $music_store_settings['ms_notification_to_email'];

	$ms_notification_to_payer_subject = $music_store_settings['ms_notification_to_payer_subject'];
	$ms_notification_to_payer_message = $music_store_settings['ms_notification_to_payer_message'];

	$ms_notification_to_seller_subject = $music_store_settings['ms_notification_to_seller_subject'];
	$ms_notification_to_seller_message = $music_store_settings['ms_notification_to_seller_message'];

	$dlurl  = $GLOBALS['music_store']->_ms_create_pages( 'ms-download-page', 'Download Page' ); // create download-page
	$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' );

	if ( empty( $purchase_settings['payer_email'] ) ) {
		$purchase_settings['payer_email'] = '';
	}

	if ( ! empty( $purchase_settings['amount'] ) ) {
		$purchase_settings['amount'] = number_format( $purchase_settings['amount'], 2 );
	}

	$tax = '';
	if ( ! empty( $purchase_settings['tax'] ) ) {
		$purchase_settings['tax'] = number_format( $purchase_settings['tax'], 2 );
		$tax                      = "Tax: {$purchase_settings['tax']} {$purchase_settings['currency']}\n";
	}

	$information_payer = "Product: {$purchase_settings['item_name']}\n" .
						( ( ! empty( $purchase_settings['amount'] ) ) ? "Amount: {$purchase_settings['amount']} {$purchase_settings['currency']}\n" : '' ) .
						$tax .
						'Download Link: ' . $dlurl . "ms-action=download&purchase_id={$purchase_settings['purchase_id']}\n";

	$information_seller = "Product: {$purchase_settings['item_name']}\n" .
						( ( ! empty( $purchase_settings['amount'] ) ) ? "Amount: {$purchase_settings['amount']} {$purchase_settings['currency']}\n" : '' ) .
						$tax .
						"Buyer Email: {$purchase_settings['payer_email']}\n" .
						'Download Link: ' . $dlurl . "ms-action=download&purchase_id={$purchase_settings['purchase_id']}\n";

	$current_datetime = ( isset( $purchase_settings['date'] ) ) ? $purchase_settings['date'] : gmdate( 'Y-m-d h:ia' );

	// Get the buyer name from the buyer email,
	// only if there is an user with the same email than buyer
	$buyer_name = '';
	if ( ! empty( $purchase_settings['payer_email'] ) ) {
		$buyer_user = get_user_by( 'email', $purchase_settings['payer_email'] );
		if ( $buyer_user ) {
			if ( $buyer_user->first_name ) {
				$buyer_name = $buyer_user->first_name;
				if ( $buyer_user->last_name ) {
					$buyer_name .= ' ' . $buyer_user->last_name;
				}
			} else {
				$buyer_name = $buyer_user->display_name;
			}
		}
	}

	// Replace constants
	$ms_notification_to_payer_message = str_replace(
		array(
			'%INFORMATION%',
			'%DATETIME%',
			'%BUYERNAME%',
		),
		array(
			$information_payer . "\n" . implode( "\n", $licenses ),
			$current_datetime,
			$buyer_name,
		),
		$ms_notification_to_payer_message
	);

	$ms_notification_to_seller_message = str_replace(
		array(
			'%INFORMATION%',
			'%DATETIME%',
			'%BUYERNAME%',
		),
		array(
			$information_seller,
			$current_datetime,
			$buyer_name,
		),
		$ms_notification_to_seller_message
	);

	$ms_notification_to_payer_message = apply_filters( 'musicstore_notification_to_buyer_email', $ms_notification_to_payer_message );
	// Send email to payer
	try {
		wp_mail(
			$purchase_settings['payer_email'],
			$ms_notification_to_payer_subject,
			$ms_notification_to_payer_message,
			"From: \"$ms_notification_from_email\" <$ms_notification_from_email>\r\n" .
			"Content-Type: text/plain; charset=utf-8\n" .
			'X-Mailer: PHP/' . phpversion()
		);
	} catch ( Exception $mail_err ) {
		error_log( $mail_err->getMessage() );
	}

	// Send email to seller
	try {
		wp_mail(
			$ms_notification_to_email,
			$ms_notification_to_seller_subject,
			$ms_notification_to_seller_message,
			"From: \"$ms_notification_from_email\" <$ms_notification_from_email>\r\n" .
			"Content-Type: text/plain; charset=utf-8\n" .
			'X-Mailer: PHP/' . phpversion()
		);
	} catch ( Exception $mail_err ) {
		error_log( $mail_err->getMessage() );
	}

} // End music_store_send_emails
