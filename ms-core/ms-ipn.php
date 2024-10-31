<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

	error_reporting( E_ERROR | E_PARSE );

	add_action( 'musicstore_send_notification_emails', 'music_store_send_emails', 10, 2 );
	global $music_store_settings;
	do_action( 'musicstore_checking_payment' );
