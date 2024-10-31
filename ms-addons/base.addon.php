<?php
if ( ! class_exists( 'MS_BASE_ADDONS' ) ) {
	class MS_BASE_ADDONS {

		private static $_instance;
		private $add_ons_url = 'https://wordpress.dwbooster.com/licensesystem/code/l.php';

		private function __construct() {
			if ( is_admin() ) {
				add_action( 'musicstore_settings_page', array( &$this, 'show_settings' ), 11 );
			}
		}

		private function _install_plugin( $plugin_zip ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			wp_cache_flush();
			$installer = new Plugin_Upgrader();
			$installed = $installer->install( $plugin_zip );
			if ( $installed ) {
				$info = $installer->plugin_info();
				if ( $info ) {
					activate_plugin( $info );
				}
			}
			return $installed;
		} // End _install_plugin

		private function _get_addons() {
			$available_addons = get_transient( 'music_store_available_add_ons' );
			if ( empty( $available_addons ) ) {
				$response = wp_remote_get(
					$this->add_ons_url,
					array(
						'body' => array(
							'a' => 'addons',
						),
					)
				);
				if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
					$addons = json_decode( $response['body'] );
					if ( ! empty( $addons ) ) {
						$available_addons = $addons;
						set_transient( 'music_store_available_add_ons', $available_addons, 60 * 60 * 24 );
					}
				}
			}
			return $available_addons;
		} // End _get_addons

		public function show_settings() {
			$available_addons = $this->_get_addons();
			if ( ! empty( $available_addons ) ) {
				// Installing plugin
				if ( ! empty( $_REQUEST['ms-addon-install'] ) ) {
					foreach ( $available_addons as $addon ) {
						if ( $addon->name == $_REQUEST['ms-addon-install'] ) {
							$this->_install_plugin( $addon->link );
							if ( class_exists( $addon->class ) ) {
								$addon_obj = new $addon->class();
								if ( method_exists( $addon_obj, 'show_settings' ) ) {
									$addon_obj->show_settings();
								}
							}
							break;
						}
					}
				}

				$installed_plugins = get_plugins();
				foreach ( $available_addons as $addon ) {
					if ( class_exists( $addon->class ) ) {
						continue;
					}
					if (
						! empty( $addon->requirements ) &&
						class_exists( 'MusicStore' ) &&
						property_exists( 'MusicStore', 'version' )
					) {
						$valid_plugin = true;
						foreach ( $addon->requirements as $operator => $version ) {
							if ( ! version_compare( MusicStore::$version, $version, $operator ) ) {
								$valid_plugin = false;
								break;
							}
						}
						if ( ! $valid_plugin ) {
							break;
						}
					}
					$is_installed = false;
					foreach ( $installed_plugins as $index => $plugin ) {
						if ( strtolower( $plugin['Name'] ) == strtolower( $addon->name ) ) {
							$is_installed = true;
							break;
						}
					}
					?>
					<div class="postbox ms-webhook-addon">
						<h3 class='hndle' style="padding:5px;"><span><?php echo wp_kses_post( __( $addon->title, 'music-store' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?></span></h3>
						<div class="inside">
							<p><?php echo wp_kses_post( __( $addon->description, 'music-store' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?></p>
							<div style="text-align:right;">
							<?php
							if ( $is_installed ) {
								print '<input type="button" value="' . esc_attr__( 'Activate add on', 'music-store' ) . '" onclick="document.location.href=\'' . str_replace( '&amp;', '&', esc_js( admin_url( 'plugins.php' ) ) ) . '\'" class="button-primary" />'; // phpcs:ignore WordPress.Security.EscapeOutput
							} else {
								if ( stripos( $addon->link, 'https://downloads.wordpress.org/' ) !== false ) {
									print '<input type="submit" value="' . esc_attr__( 'Install add on', 'music-store' ) . '" class="button-primary" onclick="' . esc_attr( 'jQuery(this).after(\'<input type="hidden" name="ms-addon-install" value="' . esc_attr( $addon->name ) . '">\')' ) . '" />';
								} else {
									print '<input type="button" value="' . esc_attr__( 'Install add on', 'music-store' ) . '" onclick="document.location.href=\'' . str_replace( '&amp;', '&', esc_js( $addon->link ) ) . '\'" class="button-primary" />'; // phpcs:ignore WordPress.Security.EscapeOutput
								}
							}
							?>
							</div>
						</div>
					</div>
					<?php
				}
			}
		} // End show_settings

		public static function init() {
			if ( null == self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		} // End init
	} // End MS_BASE_ADDONS
}
MS_BASE_ADDONS::init();
?>
