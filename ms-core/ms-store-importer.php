<?php

if ( ! is_admin() ) {
	echo 'Direct access not allowed.';
	exit;
}

class MusicStoreImporter {
	private $message = '';
	private $message_type = 'notice notice-error';

	/**
	 * Class constructor
	 */
	public function __construct( $music_store_settings, $ms ) {
		// Export or import store's settings
		if ( current_user_can( 'manage_options' ) ) {
			if (
				isset( $_POST['ms_export_settings'] ) &&
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ms_export_settings'] ) ), 'music-store-export-settings' )
			) {
				if ( ! headers_sent() ) {
					$dt = date('Y-m-d_His');
					header("Content-type: application/octet-stream");
					header("Content-Disposition: attachment; filename=export_ms_settings_".$dt.".cpms");
				}
				print serialize( $music_store_settings );
				exit;
			} else if (
				isset( $_POST['ms_import_settings'] ) &&
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ms_import_settings'] ) ), 'music-store-import-settings' ) &&
				isset( $_FILES['ms-store-settings-file'] )
			) {
				$filename = $_FILES['ms-store-settings-file']['tmp_name'];
				if ( !empty( $filename ) && file_exists( $filename ) ) {
					if ( ( $handle = fopen( $filename, "r" ) ) !== false ) {
						$contents = fread( $handle, filesize( $filename ) );
						if ( $contents ) {
							$contents = preg_replace('/^[\t\r\n\s]*/', '', $contents);
							$contents = preg_replace('/[\t\r\n\s]*$/', '', $contents);
							$bom = pack('H*','EFBBBF');
							$contents = preg_replace("/$bom/", '', $contents);
							$contents_php = unserialize($contents);
							$flag = false;
							if ( $contents_php !== false && is_array( $contents_php ) ) {
								foreach ( $contents_php as $attr_name => $attr_value ) {
									if ( isset( $music_store_settings[ $attr_name ] ) ) {
										$flag  = true;
										if( is_string( $attr_value ) ) {
											update_option( $attr_name, sanitize_text_field( $attr_value ) );
										} else if( is_numeric( $attr_value ) && is_bool( $attr_value ) ) {
											update_option( $attr_name, $attr_value );
										}
									}
								}
								if ( $flag ) {
									$this->message = esc_html__( "Imported settings.", 'music-store' );
									$this->message_type = 'notice notice-success';
									$ms->_load_settings();
								}
							} else {
								$this->message = esc_html__( "The file's content is not a valid serialized PHP object.", 'music-store' );
							}
						}
						else
						{
							$this->message = esc_html__( "It is not possible to read the file's content.", 'music-store' );
						}
						fclose($handle);
					}
					@unlink($filename);
				}
			}
		}
	} // End __construct

	public function print_message() {
		if ( ! empty( $this->message ) ) {
			print '<div class="' . $this->message_type . '">' . $this->message . '</div>';
		}
	} // End print_message
} // End MusicStoreImporter
