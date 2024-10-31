<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }
	error_reporting( E_ERROR | E_PARSE );

if ( ! function_exists( 'ms_mime_content_type' ) ) {
	function ms_mime_content_type( $filename ) {

		$file_parts = explode( '.', $filename );
		$idx        = end( $file_parts );
		$idx        = strtolower( $idx );

		$mimet = array(
			'ai'      => 'application/postscript',
			'3gp'     => 'audio/3gpp',
			'flv'     => 'video/x-flv',
			'aif'     => 'audio/x-aiff',
			'aifc'    => 'audio/x-aiff',
			'aiff'    => 'audio/x-aiff',
			'asc'     => 'text/plain',
			'atom'    => 'application/atom+xml',
			'avi'     => 'video/x-msvideo',
			'bcpio'   => 'application/x-bcpio',
			'bmp'     => 'image/bmp',
			'cdf'     => 'application/x-netcdf',
			'cgm'     => 'image/cgm',
			'cpio'    => 'application/x-cpio',
			'cpt'     => 'application/mac-compactpro',
			'crl'     => 'application/x-pkcs7-crl',
			'crt'     => 'application/x-x509-ca-cert',
			'csh'     => 'application/x-csh',
			'css'     => 'text/css',
			'dcr'     => 'application/x-director',
			'dir'     => 'application/x-director',
			'djv'     => 'image/vnd.djvu',
			'djvu'    => 'image/vnd.djvu',
			'doc'     => 'application/msword',
			'dtd'     => 'application/xml-dtd',
			'dvi'     => 'application/x-dvi',
			'dxr'     => 'application/x-director',
			'eps'     => 'application/postscript',
			'etx'     => 'text/x-setext',
			'ez'      => 'application/andrew-inset',
			'gif'     => 'image/gif',
			'gram'    => 'application/srgs',
			'grxml'   => 'application/srgs+xml',
			'gtar'    => 'application/x-gtar',
			'hdf'     => 'application/x-hdf',
			'hqx'     => 'application/mac-binhex40',
			'html'    => 'text/html',
			'html'    => 'text/html',
			'ice'     => 'x-conference/x-cooltalk',
			'ico'     => 'image/x-icon',
			'ics'     => 'text/calendar',
			'ief'     => 'image/ief',
			'ifb'     => 'text/calendar',
			'iges'    => 'model/iges',
			'igs'     => 'model/iges',
			'jpe'     => 'image/jpeg',
			'jpeg'    => 'image/jpeg',
			'jpg'     => 'image/jpeg',
			'js'      => 'application/x-javascript',
			'kar'     => 'audio/midi',
			'latex'   => 'application/x-latex',
			'm3u'     => 'audio/x-mpegurl',
			'man'     => 'application/x-troff-man',
			'mathml'  => 'application/mathml+xml',
			'me'      => 'application/x-troff-me',
			'mesh'    => 'model/mesh',
			'm4a'     => 'audio/x-m4a',
			'mid'     => 'audio/midi',
			'midi'    => 'audio/midi',
			'mif'     => 'application/vnd.mif',
			'mov'     => 'video/quicktime',
			'movie'   => 'video/x-sgi-movie',
			'mp2'     => 'audio/mpeg',
			'mp3'     => 'audio/mpeg',
			'mp4'     => 'video/mp4',
			'm4v'     => 'video/x-m4v',
			'mpe'     => 'video/mpeg',
			'mpeg'    => 'video/mpeg',
			'mpg'     => 'video/mpeg',
			'mpga'    => 'audio/mpeg',
			'ms'      => 'application/x-troff-ms',
			'msh'     => 'model/mesh',
			'mxu m4u' => 'video/vnd.mpegurl',
			'nc'      => 'application/x-netcdf',
			'oda'     => 'application/oda',
			'ogg'     => 'application/ogg',
			'pbm'     => 'image/x-portable-bitmap',
			'pdb'     => 'chemical/x-pdb',
			'pdf'     => 'application/pdf',
			'pgm'     => 'image/x-portable-graymap',
			'pgn'     => 'application/x-chess-pgn',
			'php'     => 'application/x-httpd-php',
			'php4'    => 'application/x-httpd-php',
			'php3'    => 'application/x-httpd-php',
			'phtml'   => 'application/x-httpd-php',
			'phps'    => 'application/x-httpd-php-source',
			'png'     => 'image/png',
			'pnm'     => 'image/x-portable-anymap',
			'ppm'     => 'image/x-portable-pixmap',
			'ppt'     => 'application/vnd.ms-powerpoint',
			'ps'      => 'application/postscript',
			'qt'      => 'video/quicktime',
			'ra'      => 'audio/x-pn-realaudio',
			'ram'     => 'audio/x-pn-realaudio',
			'ras'     => 'image/x-cmu-raster',
			'rdf'     => 'application/rdf+xml',
			'rgb'     => 'image/x-rgb',
			'rm'      => 'application/vnd.rn-realmedia',
			'roff'    => 'application/x-troff',
			'rtf'     => 'text/rtf',
			'rtx'     => 'text/richtext',
			'sgm'     => 'text/sgml',
			'sgml'    => 'text/sgml',
			'sh'      => 'application/x-sh',
			'shar'    => 'application/x-shar',
			'shtml'   => 'text/html',
			'silo'    => 'model/mesh',
			'sit'     => 'application/x-stuffit',
			'skd'     => 'application/x-koan',
			'skm'     => 'application/x-koan',
			'skp'     => 'application/x-koan',
			'skt'     => 'application/x-koan',
			'smi'     => 'application/smil',
			'smil'    => 'application/smil',
			'snd'     => 'audio/basic',
			'spl'     => 'application/x-futuresplash',
			'src'     => 'application/x-wais-source',
			'sv4cpio' => 'application/x-sv4cpio',
			'sv4crc'  => 'application/x-sv4crc',
			'svg'     => 'image/svg+xml',
			'swf'     => 'application/x-shockwave-flash',
			't'       => 'application/x-troff',
			'tar'     => 'application/x-tar',
			'tcl'     => 'application/x-tcl',
			'tex'     => 'application/x-tex',
			'texi'    => 'application/x-texinfo',
			'texinfo' => 'application/x-texinfo',
			'tgz'     => 'application/x-tar',
			'tif'     => 'image/tiff',
			'tiff'    => 'image/tiff',
			'tr'      => 'application/x-troff',
			'tsv'     => 'text/tab-separated-values',
			'txt'     => 'text/plain',
			'ustar'   => 'application/x-ustar',
			'vcd'     => 'application/x-cdlink',
			'vrml'    => 'model/vrml',
			'vxml'    => 'application/voicexml+xml',
			'wav'     => 'audio/x-wav',
			'wbmp'    => 'image/vnd.wap.wbmp',
			'wbxml'   => 'application/vnd.wap.wbxml',
			'wml'     => 'text/vnd.wap.wml',
			'wmlc'    => 'application/vnd.wap.wmlc',
			'wmlc'    => 'application/vnd.wap.wmlc',
			'wmls'    => 'text/vnd.wap.wmlscript',
			'wmlsc'   => 'application/vnd.wap.wmlscriptc',
			'wmlsc'   => 'application/vnd.wap.wmlscriptc',
			'wrl'     => 'model/vrml',
			'xbm'     => 'image/x-xbitmap',
			'xht'     => 'application/xhtml+xml',
			'xhtml'   => 'application/xhtml+xml',
			'xls'     => 'application/vnd.ms-excel',
			'xml xsl' => 'application/xml',
			'xpm'     => 'image/x-xpixmap',
			'xslt'    => 'application/xslt+xml',
			'xul'     => 'application/vnd.mozilla.xul+xml',
			'xwd'     => 'image/x-xwindowdump',
			'xyz'     => 'chemical/x-xyz',
			'zip'     => 'application/zip',
		);

		if ( isset( $mimet[ $idx ] ) ) {
			return $mimet[ $idx ];
		} else {
			return 'application/octet-stream';
		}
	}
}

if ( ! function_exists( 'ms_sanitize_file_name' ) ) {
	function ms_sanitize_file_name( $filename ) {
		$filename = urldecode( $filename );
		return sanitize_file_name( $filename );
	}
}

function ms_include_the_timeout() {
	global $music_store_settings;
	if (
		$music_store_settings['ms_troubleshoot_no_dl'] &&
		! empty( $music_store_settings['ms_troubleshoot_email_address'] ) &&
		empty( $_REQUEST['timeout'] )
	) {
		$to_email   = $music_store_settings['ms_troubleshoot_email_address'];
		$from_email = $music_store_settings['ms_notification_from_email'];
		$subject    = __( 'Music Store broken link notification', 'music-store' );
		$link       = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
		$link      .= isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$link      .= isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$body       = "The following download link is broken:\n\n\nLink: {$link}\n\n\nIf it is associated to a valid purchase, please, check the payment gateway settings.\n\nIf you are using PayPal, please, be sure the IPN (Instant Payment Notifications) are enabled in the PayPal account.";

		wp_mail(
			$to_email,
			$subject,
			$body,
			"From: \"{$from_email}\" <{$from_email}>\r\n" .
			"Content-Type: text/plain; charset=utf-8\n" .
			'X-Mailer: PHP/' . phpversion()
		);
	}
	music_store_setError( '<div id="music_store_error_mssg"></div><script>var timeout_text = "' . esc_attr( __( 'The store should be processing the purchase. You will be redirected in', 'music-store' ) ) . '";</script>' );
}

function ms_check_download_permissions() {
	global $music_store_settings;
	global $wpdb;

	// and check the existence of a parameter with the purchase_id
	if ( empty( $_REQUEST['purchase_id'] ) ) {
		music_store_setError( 'The purchase id is required' );
		return false;
	}

	// Check if download for free or the user is an admin
	if ( ( ! empty( $GLOBALS[ MS_SESSION_NAME ]['download_for_free'] ) && in_array( $_REQUEST['purchase_id'], $GLOBALS[ MS_SESSION_NAME ]['download_for_free'] ) ) || current_user_can( 'manage_options' ) ) {
		return true;
	}

	if (
		false == $music_store_settings['ms_buy_button_for_registered_only'] ||
		is_user_logged_in()
	) {
		if ( $music_store_settings['ms_safe_download'] ) {
			if ( ! empty( $_REQUEST['ms_user_email'] ) ) {
				$GLOBALS[ MS_SESSION_NAME ]['ms_user_email'] = sanitize_email( wp_unslash( $_REQUEST['ms_user_email'] ) );
			}

			// Check if the user has typed the email used to purchase the product
			if ( empty( $GLOBALS[ MS_SESSION_NAME ]['ms_user_email'] ) ) {
				music_store_setError( 'Please, go to the download page, and enter the email address used in products purchasing' );
				return false;
			}
			$data = $wpdb->get_row( $wpdb->prepare( 'SELECT CASE WHEN checking_date IS NULL THEN DATEDIFF(NOW(), date) ELSE DATEDIFF(NOW(), checking_date) END AS days, downloads, id FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' WHERE purchase_id=%s AND email=%s ORDER BY checking_date DESC, date DESC', array( sanitize_key( $_REQUEST['purchase_id'] ), $GLOBALS[ MS_SESSION_NAME ]['ms_user_email'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$data = $wpdb->get_row( $wpdb->prepare( 'SELECT CASE WHEN checking_date IS NULL THEN DATEDIFF(NOW(), date) ELSE DATEDIFF(NOW(), checking_date) END AS days, downloads, id FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' WHERE purchase_id=%s ORDER BY checking_date DESC, date DESC', array( sanitize_key( $_REQUEST['purchase_id'] ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	} else {
		music_store_setError( 'Anonymous User' );
		return false;
	}

	if ( is_null( $data ) ) {
		ms_include_the_timeout();
		return false;
	} elseif ( $music_store_settings['ms_old_download_link'] < $data->days ) {
		music_store_setError( 'The download link has expired, please contact to the vendor' );
		return false;
	} elseif ( $music_store_settings['ms_downloads_number'] > 0 && $music_store_settings['ms_downloads_number'] <= $data->downloads ) {
		music_store_setError( 'The number of downloads has reached its limit, please contact to the vendor' );
		return false;
	}

	if ( isset( $_REQUEST['f'] ) && ! isset( $GLOBALS[ MS_SESSION_NAME ]['cpms_donwloads'] ) ) {
		$GLOBALS[ MS_SESSION_NAME ]['cpms_donwloads'] = true;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_PURCHASE . ' SET downloads=downloads+1 WHERE id=%d', $data->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	return true;
} // End ms_check_download_permissions

function ms_copy_download_links( $file ) {
	$parts         = pathinfo( $file );
	$new_file_name = sanitize_file_name( utf8_decode( rawurldecode( $parts['basename'] ) ) . '_' . md5( $file ) . ( ( ! empty( $parts['extension'] ) ) ? '.' . $parts['extension'] : '' ) );
	$dest          = MS_DOWNLOAD . '/' . $new_file_name;

	return music_store_copy( $file, $dest ) ? $new_file_name : $file;
}

function ms_remove_download_links() {
	global $music_store_settings;

	$now = time();
	$dif = $music_store_settings['ms_old_download_link'] * 86400;
	$d   = @dir( MS_DOWNLOAD );
	while ( false !== ( $entry = $d->read() ) ) {
		// The music-store-icon.png file allow to know that htaccess file is supported, so it should not be deleted
		if ( '.' != $entry && '..' != $entry && 'music-store-icon.png' != $entry && '.htaccess' != $entry ) {
			$file_name = MS_DOWNLOAD . '/' . $entry;
			$date      = filemtime( $file_name );
			if ( $now - $date >= $dif ) { // Delete file
				@unlink( $file_name );
			}
		}
	}
	$d->close();
} // End ms_remove_download_links

function ms_song_title( $song_obj ) {
	if ( isset( $song_obj->post_title ) ) {
		return $song_obj->post_title;
	}
	return pathinfo( $song_obj->file, PATHINFO_FILENAME );
}

function ms_generate_downloads() {
	global $wpdb, $download_links_str;
	ms_remove_download_links();

	if ( ms_check_download_permissions() ) {
		if ( isset( $_GET['purchase_id'] ) ) {
			$purchase = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' WHERE purchase_id=%s', sanitize_key( wp_unslash( $_GET['purchase_id'] ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$download_links_str = '';

		if ( $purchase ) { // Exists the purchase
			do_action( 'musicstore_purchased_products', $purchase );
			$id = $purchase->product_id;

			$_post = get_post( $id );
			if ( is_null( $_post ) ) {
				$download_links_str = __( 'The product is no longer available in our Music Store', 'music-store' );
				return;
			}
			if ( 'ms_song' == $_post->post_type ) {
				$obj = new MSSong( $id );
			} else {
				$download_links_str = esc_html__( 'The product is not valid', 'music-store' );
				return;
			}

			$urls    = array();
			$songObj = new stdClass();
			if ( isset( $obj->file ) ) {
				$songObj->title = ms_song_title( $obj );
				$songObj->link  = str_replace( ' ', '%20', wp_kses_decode_entities( $obj->file ) );
				$urls[]         = $songObj;
			}

			foreach ( $urls as $url ) {
				$download_link = ms_copy_download_links( $url->link );
				if ( $download_link !== $url->link ) {
					$download_link = MS_H_URL . '?ms-action=f-download' . ( ( isset( $GLOBALS[ MS_SESSION_NAME ]['ms_user_email'] ) ) ? '&ms_user_email=' . $GLOBALS[ MS_SESSION_NAME ]['ms_user_email'] : '' ) . '&f=' . $download_link . ( ( ! empty( $_REQUEST['purchase_id'] ) ) ? '&purchase_id=' . sanitize_key( $_REQUEST['purchase_id'] ) : '' );
				}
				$download_links_str .= '<div> <a class="ms-download-link" href="' . esc_url( $download_link ) . '">' . music_store_strip_tags( $url->title ) . '</a></div>';

				if ( next( $urls ) ) {
					$download_links_str .= '<div style="border-bottom:1px solid #DADADA;margin-top:10px;margin-bottom:10px;"></div>';
				}
			}

			if ( empty( $download_links_str ) ) {
				$download_links_str = __( 'The list of purchased products is empty', 'music-store' );
			}
			// End purchase checking
		} else {
			ms_include_the_timeout();
		}
	}
}

function ms_browser_output( $file, $name ) {
	global $music_store_settings;
	try {
		header( 'Content-Type: ' . ms_mime_content_type( basename( $file ) ) );
		header( 'Content-Disposition: attachment; filename="' . ms_sanitize_file_name( $name ) . '"' );

		$file = wp_kses_decode_entities( $file );

		if ( ! $music_store_settings['ms_troubleshoot_no_ob'] ) {
			@ob_end_clean();
		}
		// @ob_start();

		$h = fopen( $file, 'rb' );
		if ( $h ) {
			while ( ! feof( $h ) ) {
				echo fread( $h, 1024 * 8 ); // @codingStandardsIgnoreLine
				if ( ! $music_store_settings['ms_troubleshoot_no_ob'] ) {
					@ob_flush();
					flush();
				}
			}
			fclose( $h );
		} else {
			print 'The file cannot be opened';
		}
	} catch ( Exception $err ) {
		@unlink( MS_DOWNLOAD . '/.htaccess' );
		header( 'location:' . esc_url_raw( MS_URL . '/ms-downloads/' . basename( $file ) ) );
	}
	exit;
} // End ms_browser_output

function ms_download_file() {
	global $wpdb, $ms_errors;
	if ( isset( $_REQUEST['f'] ) ) {
		$_f = sanitize_text_field( wp_unslash( $_REQUEST['f'] ) );
	}
	if ( isset( $_f ) && ms_check_download_permissions() ) {
		$_f = utf8_decode( $_f );
		$file          = MS_DOWNLOAD . '/' . basename( $_f );

		// Processing file name
		$file_name = basename( $_f );
		$ext1      = pathinfo( $file_name, PATHINFO_EXTENSION );
		$pos       = strrpos( $file_name, '_' );
		if ( false !== $pos ) {
			$file_name = substr( $file_name, 0, $pos );
		}
		$ext2 = pathinfo( $file_name, PATHINFO_EXTENSION );
		if ( empty( $ext2 ) && ! empty( $ext1 ) ) {
			$file_name .= '.' . $ext1;
		}

		ms_browser_output( $file, $file_name );

	} else {
		$dlurl  = $GLOBALS['music_store']->_ms_create_pages( 'ms-download-page', 'Download Page' );
		$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ) . 'ms-action=download' . ( ( ! empty( $_REQUEST['purchase_id'] ) ) ? '&purchase_id=' . sanitize_key( $_REQUEST['purchase_id'] ) : '' );
		header( 'location: ' . esc_url_raw( $dlurl ) );
	}
} // End ms_download_file
