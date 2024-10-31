<?php
/*
Plugin Name: Music Store - WordPress eCommerce
Plugin URI: http://musicstore.dwbooster.com
Version: 1.1.18
Author: CodePeople
Author URI: http://musicstore.dwbooster.com
Description: Music Store is an online WordPress store for selling audio files: music, speeches, narratives, everything audio. With Music Store your sales will be safe, with all the security PayPal offers.
Text Domain: music-store
 */

// CONSTANTS
define( 'MS_FILE_PATH', dirname( __FILE__ ) );

// Feedback system
require_once 'feedback/cp-feedback.php';
new CP_FEEDBACK( 'music-store', __FILE__, 'https://musicstore.dwbooster.com/contact-us' );

require_once MS_FILE_PATH . '/banner.php';
$codepeople_promote_banner_plugins['codepeople-music-store'] = array(
	'plugin_name' => 'Music Store',
	'plugin_url'  => 'https://wordpress.org/support/plugin/music-store/reviews/#new-post',
);

define( 'MS_URL', plugins_url( '', __FILE__ ) );
define( 'MS_H_URL', rtrim( get_home_url( get_current_blog_id() ), '/' ) . ( ( strpos( get_current_blog_id(), '?' ) === false ) ? '/' : '' ) );
define( 'MS_DOWNLOAD', MS_FILE_PATH . '/ms-downloads' );
define( 'MS_OLD_DOWNLOAD_LINK', 3 ); // Number of days considered old download links
define( 'MS_DOWNLOADS_NUMBER', 3 );  // Number of downloads by purchase
define( 'MS_CORE_IMAGES_URL', MS_URL . '/ms-core/images' );
define( 'MS_CORE_IMAGES_PATH', MS_FILE_PATH . '/ms-core/images' );
define( 'MS_TEXT_DOMAIN', 'music-store' );
define( 'MS_MAIN_PAGE', false ); // The location to the music store main page
define( 'MS_SECURE_PLAYBACK_TEXT', 'Audio is played partially for security reasons' );
define( 'MS_REMOTE_TIMEOUT', 300 ); // wp_remote_get timeout

// PAYPAL CONSTANTS
define( 'MS_PAYPAL_EMAIL', '' );
define( 'MS_PAYPAL_ENABLED', true );
define( 'MS_PAYPAL_CURRENCY', 'USD' );
define( 'MS_PAYPAL_CURRENCY_SYMBOL', '$' );
define( 'MS_PAYPAL_LANGUAGE', 'EN' );
define( 'MS_PAYPAL_BUTTON', 'button_d.gif' );

// NOTIFICATION CONSTANTS
define( 'MS_NOTIFICATION_FROM_EMAIL', 'put_your@emailhere.com' );
define( 'MS_NOTIFICATION_TO_EMAIL', 'put_your@emailhere.com' );
define( 'MS_NOTIFICATION_TO_PAYER_SUBJECT', 'Thank you for your purchase...' );
define( 'MS_NOTIFICATION_TO_SELLER_SUBJECT', 'New product purchased...' );
define( 'MS_NOTIFICATION_TO_PAYER_MESSAGE', "We have received your purchase notification with the following information:\n\n%INFORMATION%\n\nThe download link is assigned an expiration time, please download the purchased product now.\n\nThank you.\n\nBest regards." );
define( 'MS_NOTIFICATION_TO_SELLER_MESSAGE', "New purchase made with the following information:\n\n%INFORMATION%\n\nBest regards." );

// DOWNLOAD FILES
define( 'MS_DISABLE_DOWNLOAD_LINKS', false );

// SAFE PLAYBACK
define( 'MS_SAFE_DOWNLOAD', false );

// DISPLAY CONSTANTS
define( 'MS_ITEMS_PAGE', 10 );
define( 'MS_ITEMS_PAGE_SELECTOR', true );
define( 'MS_FILTER_BY_TYPE', false );
define( 'MS_FILTER_BY_GENRE', true );
define( 'MS_FILTER_BY_ARTIST', false );
define( 'MS_FILTER_BY_ALBUM', false );
define( 'MS_ORDER_BY_POPULARITY', true );
define( 'MS_ORDER_BY_PRICE', true );
define( 'MS_PLAYER_STYLE', 'mejs-classic' );

// TABLE NAMES
define( 'MSDB_POST_DATA', 'msdb_post_data' );
define( 'MSDB_PURCHASE', 'msdb_purchase' );

require 'ms-core/ms-functions.php';
require 'ms-core/ms-song.php';
require 'ms-core/tpleng.class.php';

// Fixes a conflict with the "Speed Booster Pack" plugin
add_filter( 'option_sbp_settings', array( 'MusicStore', 'troubleshoot' ) );

// Load the addons
function ms_loading_add_ons() {
	$path = MS_FILE_PATH . '/ms-addons';
	if ( file_exists( $path ) ) {
		$addons = dir( $path );
		while ( false !== ( $entry = $addons->read() ) ) {
			if ( strlen( $entry ) > 3 && 'php' == strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ) ) {
				require_once $addons->path . '/' . $entry;
			}
		}
	}
}
ms_loading_add_ons();

// Load files
require_once MS_FILE_PATH . '/ms-core/ms-review.php';

if ( ! class_exists( 'MusicStore' ) ) {
	/**
	 * Main Music_Store Class
	 *
	 * Contains the main functions for Music Store, stores variables, and handles error messages
	 *
	 * @class MusicStore
	 * @version 1.0.1
	 * @since 1.4
	 * @package MusicStore
	 * @author CodePeople
	 */

	class MusicStore {

		public static $version = '1.1.18';

		private $music_store_slug = 'music-store-menu';
		private $layouts          = array();
		private $layout           = array();
		private $user_id;
		private $user_purchases;
		private $settings_importer;

		/**
		 * MusicStore constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			global $music_store_settings;

			$this->user_purchases = array();

			$this->_load_settings(); // Load the global settings to prevent read them in each section of website

			add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ), 1 );
			add_action('plugins_loaded', array( $this, 'plugins_loaded' ) );
			add_action( 'init', array( &$this, 'init' ), 1 );
			add_action( 'admin_init', array( &$this, 'admin_init' ), 1 );
			add_action( 'widgets_init', array( &$this, '_load_widgets' ) );
			add_action( 'current_screen', array( $this, '_permalinks_screen' ) );
			// Set the menu link
			add_action( 'admin_menu', array( &$this, 'menu_links' ), 10 );

			// Load selected layout
			if ( false !== $music_store_settings['ms_layout'] ) {
				$this->layout = $music_store_settings['ms_layout'];
			}

			// Public actions and filters
			if ( ! is_admin() ) {
				add_filter( 'musicstore_buynow_button', array( $this, 'populate_payment_gateways_list' ), 10 );
			}

			// Script to integrate with editors
			require_once MS_FILE_PATH . '/ms-page-builder/ms-page-builders.php';
			MS_PAGE_BUILDERS::run();

			// Add a post display state for special pages.
			add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );

			// Reject Cache URIs
			$this->_reject_cache_uris();
		} // End __constructor

		/** INITIALIZE PLUGIN FOR PUBLIC WordPress AND ADMIN SECTION **/

		public function plugins_loaded() {

			global $music_store_settings;

			if ( $music_store_settings['ms_woocommerce_integration'] && class_exists( 'WooCommerce' ) ) {
				require_once dirname( __FILE__ ) . '/ms-core/ms-woocommerce/ms-woocommerce.php';
			}

		} // End plugins_loaded

		public function after_setup_theme() {
			// I18n
			load_plugin_textdomain( 'music-store', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			$this->init_taxonomies(); // Init MusicStore taxonomies
			$this->init_post_types(); // Init MusicStore custom post types

		} // End after_setup_theme

		/**
		 * Init MusicStore when WordPress Initialize
		 *
		 * @access public
		 * @return void
		 */
		public function init() {

			global $music_store_settings;

			$this->user_id = get_current_user_id();

			add_action('save_post', array(&$this, 'save_data'), 10, 3);
			add_filter('musicstore_notify_url', function( $url ){
				$url_parse = parse_url( $url );

				if ( ! empty( $url_parse['query'] ) ) {
					wp_parse_str( $url_parse['query'], $args );

					if ( ! empty( $args['ms-action'] ) ) {
						$transient_id = uniqid( 'ms-ipn-', true );
						set_transient( $transient_id, $args['ms-action'], 24 * 60 *60 );
						$args['ms-action'] = $transient_id;
						$url_parse['query'] = build_query( $args );

						$url = $url_parse['scheme'] . '://' . $url_parse['host'] .
							( ! empty( $url_parse['port'] ) ? ':' . $url_parse['port'] : '' ) .
							( ! empty( $url_parse['path'] ) ? $url_parse['path'] : '' ) .
							( ! empty( $url_parse['query'] ) ? '?' . $url_parse['query'] : '' ) .
							( ! empty( $url_parse['fragment'] ) ? '#' . $url_parse['fragment'] : '' );
					}
				}
				return $url;
			}, 99);

			if ( ! is_admin() ) {
				global $wpdb;
				add_filter( 'get_pages', array( &$this, '_ms_exclude_pages' ) ); // for download-page

				if ( isset( $_REQUEST['ms-action'] ) ) {
					switch ( strtolower( sanitize_text_field( wp_unslash( $_REQUEST['ms-action'] ) ) ) ) {
						case 'buynow':
							include_once MS_FILE_PATH . '/ms-core/ms-submit.php';
							exit;
							break;
						case 'registerfreedownload':
							if ( ! empty( $_REQUEST['id'] ) ) {
								global $wpdb;
								$mssg = __( 'Product distributed for free', 'music-store' );
								$id   = ( ! empty( $_REQUEST['id'] ) && is_numeric( $_REQUEST['id'] ) ) ? intval( $_REQUEST['id'] ) : 0;
								$data = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . MSDB_POST_DATA . ' WHERE id=%d', array( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
								if ( ! empty( $data ) ) {
									// Check if the payment gateway is enabled
									if ( apply_filters( 'musicstore_payment_gateway_enabled', false, $music_store_settings ) ) {
										// Check if the product has assigned a price and it is different to zero
										$price = @floatval( $data->price );
										if ( $price ) {
											exit;
										}
									}

									$current_user       = wp_get_current_user();
									$current_user_email = '';
									if ( 0 !== $current_user->ID ) {
										$current_user_email = $current_user->user_email;
									} else {
										$current_user_email = ms_getIP();
										$current_user_email = str_replace( '_', '.', $current_user_email );
									}

									// Insert download in database
									$wpdb->insert(
										$wpdb->prefix . MSDB_PURCHASE,
										array(
											'product_id'  => $id,
											'purchase_id' => 0,
											'date'        => gmdate( 'Y-m-d H:i:s' ),
											'email'       => $current_user_email,
											'amount'      => 0,
											'paypal_data' => '',
										),
										array( '%d', '%s', '%s', '%s', '%f', '%s' )
									);
								}
							}
							exit;
							break;
						case 'plays':
							if (!empty($_POST['id']) && is_numeric($_POST['id']) && ($_product_id = intval($_POST['id'])) != 0) { // @codingStandardsIgnoreLine
								if ( ! isset( $GLOBALS[ MS_SESSION_NAME ]['ms-plays-registered'] ) ) {
									$GLOBALS[ MS_SESSION_NAME ]['ms-plays-registered'] = array();
								}
								if ( ! in_array( $_product_id, $GLOBALS[ MS_SESSION_NAME ]['ms-plays-registered'] ) ) {
									$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'msdb_post_data SET plays=plays+1 WHERE id=%d', $_product_id ) );
									$GLOBALS[ MS_SESSION_NAME ]['ms-plays-registered'][] = $_product_id;
								}
							}
							exit;
							break;
						case 'popularity':
							if ( ! headers_sent() ) {
								header( 'Content-Type: application/json' );
							}
							if (isset($_POST['id']) && is_numeric($_POST['id']) && ($id = intval($_POST['id'])) != 0 && isset($_POST['review']) && is_numeric($_POST['review']) && ($review = intval($_POST['review'])) <= 5 && 1 <= $review) { // @codingStandardsIgnoreLine
								MS_REVIEW::set_review( $id, $review );
								$data = MS_REVIEW::get_review( $id );
								if ( $data ) {
									exit( json_encode( $data ) );
								}
							}
							exit( json_encode( array( 'error' => true ) ) );
							break;
						case 'f-download':
							require_once MS_FILE_PATH . '/ms-core/ms-download.php';
							ms_download_file();
							exit;
							break;
						default:
							$ms_action = sanitize_text_field( wp_unslash( $_REQUEST['ms-action'] ) );
							if(
								stripos($ms_action,'ipn|') !== false ||
								( $_GET['ms-action'] = get_transient( $ms_action ) ) !== false
							)
							{
								delete_transient( $ms_action );
								if ( $music_store_settings['ms_debug_payment'] ) {
									try {
										if ( ! empty( $_GET ) ) {
											error_log( 'Music Store payment gateway GET parameters: ' . json_encode( $_GET ) );
										}

										if ( ! empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
											error_log( 'Music Store payment gateway POST parameters: ' . json_encode( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification
										}
									} catch ( Exception $err ) {
										error_log( $err->getMessage() );
									}
								}
								include_once MS_FILE_PATH . '/ms-core/ms-ipn.php';
								exit;
							}
							break;
					}
				}

				// Set custom post_types on search result
				add_filter( 'pre_get_posts', array( &$this, 'add_post_type_to_results' ) );
				add_shortcode( 'music_store', array( &$this, 'load_store' ) );
				add_shortcode( 'music_store_product', array( &$this, 'load_product' ) );
				add_shortcode( 'music_store_product_list', array( &$this, 'load_product_list' ) );
				add_shortcode( 'music_store_purchased_list', array( &$this, 'load_purchased_products' ) );
				add_shortcode( 'music_store_sales_counter', array( &$this, 'sales_counter' ) );
				add_filter( 'the_content', array( &$this, '_ms_the_content' ), 99 ); // For download-page
				add_filter( 'the_excerpt', array( &$this, '_ms_the_excerpt' ), 1 );
				add_filter( 'get_the_excerpt', array( &$this, '_ms_the_excerpt' ), 1 );
				add_action( 'wp_head', array( &$this, 'load_meta' ) );
				$this->load_templates(); // Load the music store template for songs display

				// Load public resources
				add_action( 'wp_enqueue_scripts', array( &$this, 'public_resources' ), 99 );

				// Search functions
				if ( $music_store_settings['ms_search_taxonomy'] ) {
					add_filter( 'posts_where', array( &$this, 'custom_search_where' ) );
					add_filter( 'posts_join', array( &$this, 'custom_search_join' ) );
					add_filter( 'posts_groupby', array( &$this, 'custom_search_groupby' ) );
				}

				// Check if the user has purchased the file previously to replace the purchase buttons by download links
				add_filter( 'musicstore_buynow_button', array( $this, 'include_download_link' ), 10, 2 );
				add_filter( 'musicstore_shopping_cart_button', array( $this, 'include_download_link' ), 10, 2 );
				add_filter( 'musicstore_demo_url', array( $this, 'demo_url' ), 10, 3 );

				// Display Preview
				$this->_preview();
			}

			// Init action
			do_action( 'musicstore_init' );
		} // End init

		public function _permalinks_screen() {
			if( ! function_exists( 'get_current_screen' ) ) { return; }
			$screen = get_current_screen();

			if ( ! $screen || 'options-permalink' != $screen->id ) {
				return;
			}
			MSSong::save_permalink();
			self::save_taxonomies_permalink();
			add_settings_section(
				'music-store-permalink',
				__( 'Music Store permalinks', 'music-store' ),
				function () {
					MSSong::permalink_settings();
					$GLOBALS['music_store']::taxonomies_permalink_settings();
				},
				'permalink'
			);
		}

		public function _load_widgets() {
			include_once dirname( __FILE__ ) . '/ms-core/ms-widgets.php';
			register_widget( 'MSProductListWidget' );
			register_widget( 'MSProductWidget' );
			register_widget( 'MSSalesCounterWidget' );
			register_widget( 'MSLoginFormWidget' );
		}

		public function _preview() {
			$user          = wp_get_current_user();
			$allowed_roles = array( 'editor', 'administrator', 'author' );

			if ( array_intersect( $allowed_roles, $user->roles ) ) {
				if ( ! empty( $_REQUEST['ms-preview'] ) ) {
					// Sanitizing variable
					$preview = sanitize_text_field( wp_unslash( $_REQUEST['ms-preview'] ) );

					// Remove every shortcode that is not in the music store list
					remove_all_shortcodes();

					add_shortcode( 'music_store', array( &$this, 'load_store' ) );
					add_shortcode( 'music_store_product', array( &$this, 'load_product' ) );
					add_shortcode( 'music_store_product_list', array( &$this, 'load_product_list' ) );
					add_shortcode( 'music_store_sales_counter', array( &$this, 'sales_counter' ) );
					add_shortcode( 'music_store_purchased_list', array( &$this, 'load_purchased_products' ) );

					if (
						has_shortcode( $preview, 'music_store' ) ||
						has_shortcode( $preview, 'music_store_product' ) ||
						has_shortcode( $preview, 'music_store_product_list' ) ||
						has_shortcode( $preview, 'music_store_sales_counter' ) ||
						has_shortcode( $preview, 'music_store_purchased_list' )
					) {
						print '<!DOCTYPE html>';
						$scale = true;
						$plus  = '+25';

						$if_empty = __( 'There are no products that satisfy the block\'s settings', 'music-store' );
						if ( has_shortcode( $preview, 'music_store_sales_counter' ) ) {
							$scale = false;
							$plus  = '+10';
						}
						$output = do_shortcode( $preview );
						if ( preg_match( '/^\s*$/', $output ) ) {
							$output = '<div>' . $if_empty . '</div>';
						}
						if ( $scale ) {
							print '<script type="text/javascript">var min_screen_width = 0;</script>';
							print '<style>body{width:640px;-ms-transform: scale(0.78);-moz-transform: scale(0.78);-o-transform: scale(0.78);-webkit-transform: scale(0.78);transform: scale(0.78);-ms-transform-origin: 0 0;-moz-transform-origin: 0 0;-o-transform-origin: 0 0;-webkit-transform-origin: 0 0;transform-origin: 0 0;}</style>';
						}

						// Deregister all scripts and styles for loading only the plugin styles.
						global  $wp_styles, $wp_scripts;
						if ( ! empty( $wp_scripts ) ) {
							$wp_scripts->reset();
						}
						$this->public_resources();
						if ( ! empty( $wp_styles ) ) {
							$wp_styles->do_items();
						}
						if ( ! empty( $wp_scripts ) ) {
							$wp_scripts->do_items();
						}

						print '<div class="ms-preview-container">' . $output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

						print '<script type="text/javascript">jQuery(window).on("load", function(){ var frameEl = window.frameElement; if(frameEl) frameEl.height = jQuery(".ms-preview-container").outerHeight(true)' . ( $scale ? '*0.78' : '' ) . esc_js( $plus ) . '; });</script><style>.music-store-item {clear:none;} .music-store-header span{ display: inline-block;clear:none;float:left;} .music-store-ordering{float:right;} .music-store-song .left-column, .music-store-collection .left-column{width:150px;clear:none;} .music-store-song .right-column.single, .music-store-collection .right-column.single{float:left; padding-left:10px; width:-moz-calc(100% - 260px); width:-webkit-calc(100% - 260px);	width:calc(100% - 260px);} .music-store-collection .collection-cover, .music-store-song .song-cover{width:150px; max-height:150px;} </style>';
						exit;
					}
				}
			}
		} // End _preview

		public function load_meta() {
			global $post;
			if ( isset( $post ) ) {
				if ( 'ms_song' == $post->post_type ) {
					$obj = new MSSong( $post->ID );
				}
				if ( ! empty( $obj ) ) {
					$output = '';

					if ( isset( $obj->cover ) ) {
						$output .= '<meta property="og:image" content="' . esc_attr( $obj->cover ) . '" />';
					}

					if ( ! empty( $obj->post_title ) ) {
						$output .= '<meta property="og:title" content="' . esc_attr( $obj->post_title ) . '" />';
					}

					if ( ! empty( $obj->post_excerpt ) ) {
						$output .= '<meta property="og:description" content="' . esc_attr( $obj->post_excerpt ) . '" />';
					} elseif ( ! empty( $obj->post_content ) ) {
						$output .= '<meta property="og:description" content="' . esc_attr( wp_trim_words( $obj->post_content ) ) . '" />';
					}

					if ( is_array( $obj->artist ) && count( $obj->artist ) ) {
						$artists_names = array();
						foreach ( $obj->artist as $artist ) {
							if ( ! empty( $artist->name ) ) {
								$artists_names[] = $artist->name;
							}
						}

						if ( ! empty( $artists_names ) ) {
							$output .= '<meta property="article:author" content="' . esc_attr( implode( ',', $artists_names ) ) . '" />';
						}
					}

					$output .= '<meta property="og:url" content="' . esc_attr( get_permalink( $obj->ID ) ) . '" />';
					$output .= '<meta property="og:type" content="song" />';

					print $output; // phpcs:ignore WordPress.Security.EscapeOutput
				}
			}
		}

		/** CODE REQUIRED FOR DOWNLOAD PAGE **/
		public function add_display_post_states( $post_states, $post ) {
			if ( 'ms-download-page' == $post->post_name ) {
				$post_states['ms-download-page'] = 'Music Store - ' . __( 'Download Page', 'music-store' );
			}

			return $post_states;
		} //  End add_display_post_states

		public function _ms_create_pages( $slug, $title ) {
			if ( isset( $GLOBALS[ MS_SESSION_NAME ][ $slug ] ) ) {
				return $GLOBALS[ MS_SESSION_NAME ][ $slug ];
			}

			$page = get_page_by_path( $slug );
			if ( is_null( $page ) ) {
				if ( is_admin() ) {
					if (
						false != ( $id = wp_insert_post( // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
							array(
								'comment_status' => 'closed',
								'post_name'      => $slug,
								'post_title'     => __( $title, 'music-store' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
								'post_status'    => 'publish',
								'post_type'      => 'page',
								'post_content'   => '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->',
							)
						)
						)
					) {
						$GLOBALS[ MS_SESSION_NAME ][ $slug ] = get_permalink( $id );
					}
				}
			} else {
				if ( is_admin() && 'publish' != $page->post_status ) {
					$page->post_status = 'publish';
					wp_update_post( $page );
				}
				$GLOBALS[ MS_SESSION_NAME ][ $slug ] = get_permalink( $page->ID );
			}

			$GLOBALS[ MS_SESSION_NAME ][ $slug ] = ( isset( $GLOBALS[ MS_SESSION_NAME ][ $slug ] ) ) ? $GLOBALS[ MS_SESSION_NAME ][ $slug ] : MS_H_URL;
			return $GLOBALS[ MS_SESSION_NAME ][ $slug ];
		}

		public function _ms_exclude_pages( $pages ) {
			$exclude   = array();
			$new_pages = array();

			$p = get_page_by_path( 'ms-download-page' );
			if ( ! is_null( $p ) ) {
				$exclude[] = $p->ID;
			}

			foreach ( $pages as $page ) {
				if ( ! in_array( $page->ID, $exclude ) ) {
					$new_pages[] = $page;
				}
			}

			return $new_pages;
		}

		public function _ms_the_excerpt( $the_excerpt ) {
			global $post;
			if (
				/* is_search() && */
				isset( $post ) &&
				'ms_song' == $post->post_type
			) {
				$tpl = new music_store_tpleng( MS_FILE_PATH . '/ms-templates/', 'comment' );
				$obj = new MSSong( $post->ID );
				return $obj->display_content( 'multiple', $tpl, 'return' );
			}

			return $the_excerpt;
		}

		public function _ms_the_content( $the_content ) {
			global $post, $ms_errors, $download_links_str, $music_store_settings;

			if ( isset( $_REQUEST ) && isset( $_REQUEST['ms-action'] ) && strtolower( sanitize_text_field( wp_unslash( $_REQUEST['ms-action'] ) ) ) == 'download' ) {
				if ( ! $music_store_settings['ms_disable_download_links'] ) {
					require_once MS_FILE_PATH . '/ms-core/ms-download.php';
					ms_generate_downloads();

					if ( empty( $ms_errors ) ) {
						$_download_links = apply_filters(
							'musicstore_download_page',
							'<div class="ms-download-title">'
								. esc_html__( 'Download Links:', 'music-store' )
								. '</div><div>'
								. music_store_strip_tags( $download_links_str )
								. '</div>'
						);

						if ( preg_match( '/\{download\-links\-here\}/', $the_content, $matches ) ) {
							$the_content = str_replace( $matches[0], $_download_links, $the_content );
						} else {
							$the_content .= $_download_links;
						}
					} else {
						$error       = (!empty($_REQUEST['error_mssg'])) ? $_REQUEST['error_mssg'] : ''; // @codingStandardsIgnoreLine
						if ( is_array( $error ) ) {
							foreach ( $error as $error_key => $error_message ) {
								$error[ $error_key ] = music_store_strip_tags( $error_message );
							}
						} else {
							$error = music_store_strip_tags( $error );
						}

						if ( ( ! $music_store_settings['ms_safe_download'] && ! empty( $ms_errors ) ) || ! empty( $GLOBALS[ MS_SESSION_NAME ]['ms_user_email'] ) ) {
							$error .= '<li>' . implode( '</li><li>', $ms_errors ) . '</li>';
						}

						$the_content .= ( ! empty( $error ) ) ? '<div class="music-store-error-mssg"><ul>' . $error . '</ul></div>' : '';

						$dlurl  = $GLOBALS['music_store']->_ms_create_pages( 'ms-download-page', 'Download Page' );
						$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ) . 'ms-action=download' . ( ( isset( $_REQUEST['purchase_id'] ) ) ? '&purchase_id=' . sanitize_key( $_REQUEST['purchase_id'] ) : '' );

						if (
							false == $music_store_settings['ms_buy_button_for_registered_only'] ||
							is_user_logged_in()
						) {
							if ( $music_store_settings['ms_safe_download'] ) {
								$_email_form = '
									<form action="'.esc_url($dlurl).'" method="POST" >
										<div style="text-align:center;">
											<div>
												'.__( 'Type the email address used to purchase our products', MS_TEXT_DOMAIN ).'
											</div>
											<div>
												<input type="text" name="ms_user_email" /> <input type="submit" value="Get Products" />
											</div>
										</div>
									</form>
								';
								if(preg_match('/\{download\-links\-here\}/', $the_content, $matches))
									$the_content = str_replace($matches[0],$_email_form,$the_content);
								else $the_content .= $_email_form;
							}
						} else {
							$_login_form = $this->_login_form( $dlurl );
							if(preg_match('/\{download\-links\-here\}/', $the_content, $matches))
								$the_content = str_replace($matches[0],$_login_form,$the_content);
							else $the_content .= $_login_form;
						}
					}
				}
			}
			$the_content = preg_replace( '/\{download\-links\-here\}/', '', $the_content );
			return $the_content;
		}

		/** BUYERS METHODS **/

		public function purchased_product( $product_id ) {
			global $wpdb, $music_store_settings;

			if ( ! empty( $this->user_id ) ) {
				if ( ! empty( $this->user_purchases ) ) {
					if ( $this->user_purchases[ $product_id ] ) {
						return true;
					}
				} else {
					// Load the user purchases, and check if the product was purchased by the user and the link is valid
					$purchases = $wpdb->get_results( $wpdb->prepare( 'SELECT CASE WHEN checking_date IS NULL THEN DATEDIFF(NOW(), date) ELSE DATEDIFF(NOW(), checking_date) END AS days, purchase_id FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' WHERE product_id=%d AND buyer_id=%d AND downloads < %d ORDER BY checking_date DESC, date DESC', $product_id, $this->user_id, $music_store_settings['ms_downloads_number'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					foreach ( $purchases as $purchase ) {
						if ( $purchase->days <= $music_store_settings['ms_old_download_link'] ) {
							return $purchase->purchase_id;
						}
					}
				}
			}
			return false;
		}

		public function include_download_link( $button, $product_id = 0 ) {
			if ( $product_id && ( $purchase_id = $this->purchased_product( $product_id ) ) !== false ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				$download_page  = $this->_ms_create_pages( 'ms-download-page', 'Download Page' );
				$download_page .= ( ( strpos( $download_page, '?' ) === false ) ? '?' : '&' ) . 'ms-action=download&purchase_id=' . $purchase_id;
				return '<input type="button" onclick="document.location.href=\'' . esc_attr( $download_page ) . '\'" class="ms-purchased-button" value="' . esc_attr__( 'Download', 'music-store' ) . '" />';
			}
			return $button;
		}

		public function demo_url( $demo_url, $commercial_url, $product_id ) {
			music_store_clearOlder();
			if ( $product_id && ( $purchase_id = $this->purchased_product( $product_id ) ) !== false ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				// Generates tmp audio file for demo and return its URL
				if ( $this->purchased_product( $product_id ) ) {
					$parts                     = pathinfo( $commercial_url );
					$commercial_demo_file_name = md5( $commercial_url ) . '_' . $purchase_id . ( ! empty( $parts['extension'] ) ? '.' . $parts['extension'] : '' );
					$commercial_demo_path      = MS_FILE_PATH . '/ms-temp/' . $commercial_demo_file_name;
					$commercial_demo_url       = MS_URL . '/ms-temp/' . $commercial_demo_file_name;
					if ( music_store_copy( $commercial_url, $commercial_demo_path ) ) {
						return $commercial_demo_url;
					}
				}
			}
			return $demo_url;
		}

		/** INITIALIZATION **/

		/**
		 * Init MusicStore when the WordPress is open for admin
		 *
		 * @access public
		 * @return void
		 */
		public function admin_init() {
			global $wpdb, $music_store_settings;

			if (
				( $ms_current_version = get_option( 'music_store_version_number' ) ) == false || // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				version_compare( $ms_current_version, self::$version, '<' )
			) {
				update_option( 'music_store_version_number', self::$version );
				$this->_create_db_structure();
			}

			// Export, Import store settings
			require_once MS_FILE_PATH.'/ms-core/ms-store-importer.php';
			$this->settings_importer = new MusicStoreImporter( $music_store_settings, $this );

			if ( isset( $_REQUEST['ms-action'] ) && 'paypal-data' == $_REQUEST['ms-action'] ) {
				if ( isset( $_REQUEST['data'] ) && isset( $_REQUEST['from'] ) && isset( $_REQUEST['to'] ) ) {
					$where = 'DATEDIFF(date, "' . sanitize_text_field( wp_unslash( $_REQUEST['from'] ) ) . '")>=0 AND DATEDIFF(date, "' . sanitize_text_field( wp_unslash( $_REQUEST['to'] ) ) . '")<=0';
					switch ( $_REQUEST['data'] ) {
						case 'residence_country':
							$where .= ' AND amount<>0';
							print wp_kses_post( music_store_getFromPayPalData( array( 'residence_country' => 'residence_country' ), 'COUNT(*) AS count', '', $where, array( 'residence_country' ), array( 'count' => 'DESC' ) ) );
							break;
						case 'mc_currency':
							$where .= ' AND amount<>0';
							print wp_kses_post( music_store_getFromPayPalData( array( 'mc_currency' => 'mc_currency' ), 'SUM(amount) AS sum', '', $where, array( 'mc_currency' ), array( 'sum' => 'DESC' ) ) );
							break;
						case 'product_name':
							$where .= ' AND amount<>0';
							$json   = music_store_getFromPayPalData( array( 'mc_currency' => 'mc_currency' ), 'SUM(amount) AS sum, post_title', $wpdb->posts . ' AS posts', $where . ' AND product_id = posts.ID', array( 'product_id', 'mc_currency' ) );
							$obj    = json_decode( $json );
							foreach ( $obj as $key => $value ) {
								$obj[ $key ]->post_title .= ' [' . $value->mc_currency . ']';
							}
							print json_encode( $obj );
							break;
						case 'download_by_product':
							$results = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT CONCAT(post.post_title,\' [\',COUNT(*),\']\') as post_title, COUNT(*) AS count FROM ' . $wpdb->prefix . 'posts AS post, ' . $wpdb->prefix . MSDB_PURCHASE . ' AS purchase WHERE DATEDIFF(purchase.date, %s)>=0 AND DATEDIFF(purchase.date, %s)<=0 AND purchase.amount=0 AND post.ID=purchase.product_id GROUP BY purchase.product_id ORDER BY post.post_title ASC', array( sanitize_text_field( wp_unslash( $_REQUEST['from'] ) ), sanitize_text_field( wp_unslash( $_REQUEST['to'] ) ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							if ( ! empty( $results ) ) {
								print json_encode( $results );
							}
							break;
					}
				}
				exit;
			}

			// Init the metaboxs for song
			add_meta_box( 'ms_song_metabox', __( "Song's data", 'music-store' ), array( &$this, 'metabox_form' ), 'ms_song', 'normal', 'high' );

			// Only accessible by website's administrators
			if ( current_user_can( 'administrator' ) ) {
				add_meta_box( 'ms_song_metabox_emulate_purchase', __( 'Manual Purchase', 'music-store' ), array( &$this, 'metabox_manual_purchase' ), 'ms_song', 'side', 'low' );
			}

			// add_action( 'save_post', array( &$this, 'save_data' ), 10, 3 );

			add_meta_box( 'ms_song_metabox_discount', __( 'Programming Discounts', 'music-store' ), array( &$this, 'metabox_discount' ), 'ms_song', 'normal', 'high' );

			if ( current_user_can( 'delete_posts' ) ) {
				add_action( 'delete_post', array( &$this, 'delete_post' ) );
			}

			// Load admin resources
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_resources' ), 10 );

			// Set a new media button for music store insertion
			add_action( 'media_buttons', array( &$this, 'set_music_store_button' ), 100 );

			$plugin = plugin_basename( __FILE__ );
			add_filter( 'plugin_action_links_' . $plugin, array( &$this, 'customizationLink' ) );

			$this->_ms_create_pages( 'ms-download-page', 'Download Page' ); // for download-page and download-page

			// Init action
			do_action( 'musicstore_admin_init' );
		} // End init

		public function customizationLink( $links ) {
			$settings_link = '<a href="https://wordpress.org/support/plugin/music-store/#new-post">' . __( 'Help' ) . '</a>';
			array_unshift( $links, $settings_link );
			$settings_link = '<a href="http://musicstore.dwbooster.com/customization" target="_blank">' . __( 'Request custom changes' ) . '</a>';
			array_unshift( $links, $settings_link );
			$settings_link = '<a href="admin.php?page=music-store-menu-settings">' . __( 'Settings' ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		} // End customizationLink


		/** READ THE STORE SETTINGS **/

		/*
		 * Read the store settings and create a global variable with data
		 *
		 * @access private
		 * @return void
		 */
		public function _load_settings() {
			global $music_store_settings;

			$music_store_settings = array(
				'ms_woocommerce_integration'           => get_option( 'ms_woocommerce_integration', 0 ),
				'ms_main_page'                         => stripcslashes( get_option( 'ms_main_page', MS_MAIN_PAGE ) ),
				'ms_prevent_cache'                     => get_option( 'ms_prevent_cache', 1 ),
				'ms_filter_by_genre'                   => get_option( 'ms_filter_by_genre', MS_FILTER_BY_GENRE ),
				'ms_filter_by_artist'                  => get_option( 'ms_filter_by_artist', MS_FILTER_BY_ARTIST ),
				'ms_filter_by_album'                   => get_option( 'ms_filter_by_album', MS_FILTER_BY_ALBUM ),
				'ms_search_taxonomy'                   => get_option( 'ms_search_taxonomy' ),
				'ms_items_page_selector'               => get_option( 'ms_items_page_selector', MS_ITEMS_PAGE_SELECTOR ),
				'ms_friendly_url'                      => get_option( 'ms_friendly_url', 0 ),

				'ms_items_page'                        => stripcslashes( get_option( 'ms_items_page', MS_ITEMS_PAGE ) ),
				'ms_layout'                            => get_option( 'ms_layout' ),
				'ms_popularity'                        => get_option( 'ms_popularity', 1 ),
				'ms_player_style'                      => get_option( 'ms_player_style', MS_PLAYER_STYLE ),

				'ms_pp_accept_zip'                     => get_option( 'ms_pp_accept_zip', 0 ),
				'ms_pp_related_products'               => get_option( 'ms_pp_related_products' ),
				'ms_pp_default_cover'                  => get_option( 'ms_pp_default_cover', '' ),
				'ms_pp_cover_size'                     => get_option( 'ms_pp_cover_size', 'medium' ),
				'ms_pp_related_products_number'        => get_option( 'ms_pp_related_products_number', 3 ),
				'ms_pp_related_products_columns'       => get_option( 'ms_pp_related_products_columns', 3 ),
				'ms_pp_related_products_by'            => get_option( 'ms_pp_related_products_by', array( 'album', 'genre', 'artist' ) ),

				'ms_paypal_email'                      => stripcslashes( get_option( 'ms_paypal_email', MS_PAYPAL_EMAIL ) ),
				'ms_paypal_button'                     => get_option( 'ms_paypal_button', MS_PAYPAL_BUTTON ),
				'ms_hide_download_link_for_price_in_blank' => get_option( 'ms_hide_download_link_for_price_in_blank' ),
				'ms_paypal_currency'                   => stripcslashes( get_option( 'ms_paypal_currency', MS_PAYPAL_CURRENCY ) ),
				'ms_paypal_currency_symbol'            => stripcslashes( get_option( 'ms_paypal_currency_symbol', MS_PAYPAL_CURRENCY_SYMBOL ) ),
				'ms_paypal_language'                   => stripcslashes( get_option( 'ms_paypal_language', MS_PAYPAL_LANGUAGE ) ),
				'ms_paypal_enabled'                    => get_option( 'ms_paypal_enabled', MS_PAYPAL_ENABLED ),
				'ms_paypal_sandbox'                    => get_option( 'ms_paypal_sandbox' ),

				'ms_tax'                               => get_option( 'ms_tax' ),
				'ms_debug_payment'                     => get_option( 'ms_debug_payment' ),

				'ms_notification_from_email'           => stripcslashes( get_option( 'ms_notification_from_email', MS_NOTIFICATION_FROM_EMAIL ) ),
				'ms_notification_to_email'             => stripcslashes( get_option( 'ms_notification_to_email', MS_NOTIFICATION_TO_EMAIL ) ),
				'ms_notification_to_payer_subject'     => stripcslashes( get_option( 'ms_notification_to_payer_subject', MS_NOTIFICATION_TO_PAYER_SUBJECT ) ),
				'ms_notification_to_payer_message'     => stripcslashes( get_option( 'ms_notification_to_payer_message', MS_NOTIFICATION_TO_PAYER_MESSAGE ) ),
				'ms_notification_to_seller_subject'    => stripcslashes( get_option( 'ms_notification_to_seller_subject', MS_NOTIFICATION_TO_SELLER_SUBJECT ) ),
				'ms_notification_to_seller_message'    => stripcslashes( get_option( 'ms_notification_to_seller_message', MS_NOTIFICATION_TO_SELLER_MESSAGE ) ),

				'ms_disable_download_links'            => get_option( 'ms_disable_download_links', MS_DISABLE_DOWNLOAD_LINKS ),
				'ms_old_download_link'                 => stripcslashes( get_option( 'ms_old_download_link', MS_OLD_DOWNLOAD_LINK ) ),
				'ms_downloads_number'                  => stripcslashes( get_option( 'ms_downloads_number', MS_DOWNLOADS_NUMBER ) ),
				'ms_safe_download'                     => get_option( 'ms_safe_download', MS_SAFE_DOWNLOAD ),
				'ms_play_all'                          => get_option( 'ms_play_all', 0 ),
				'ms_preload'                           => get_option( 'ms_preload', 0 ),

				'ms_social_buttons'                    => get_option( 'ms_social_buttons' ),
				'ms_facebook_app_id'                   => stripcslashes( get_option( 'ms_facebook_app_id', '' ) ),

				'ms_download_link_for_registered_only' => get_option( 'ms_download_link_for_registered_only' ),
				'ms_buy_button_for_registered_only'    => get_option( 'ms_buy_button_for_registered_only' ),

				'ms_license_for_regular'               => stripcslashes( get_option( 'ms_license_for_regular', '' ) ),
				'ms_license_for_free'                  => stripcslashes( get_option( 'ms_license_for_free', '' ) ),

				'ms_troubleshoot_no_ob'                => get_option( 'ms_troubleshoot_no_ob' ),
				'ms_troubleshoot_no_dl'                => get_option( 'ms_troubleshoot_no_dl' ),
				'ms_troubleshoot_email_address'        => get_option( 'ms_troubleshoot_email_address', '' ),
			);

		} // End _load_settings

		// Updates the "FROM" and "TO" emails in the global settings.
		private function _update_settings() {
			global $music_store_settings;
			if ( MS_NOTIFICATION_FROM_EMAIL == $music_store_settings['ms_notification_from_email'] ) {
				$user_email = get_the_author_meta( 'user_email', get_current_user_id() );
				$host       = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
				preg_match( '/[^\.\/]+(\.[^\.\/]+)?$/', $host, $matches );
				$domain = $matches[0];
				$pos    = strpos( $user_email, $domain );
				if ( false === $pos ) {
					$music_store_settings['ms_notification_from_email'] = 'admin@' . $domain;
				}
			}

			if ( MS_NOTIFICATION_TO_EMAIL == $music_store_settings['ms_notification_to_email'] ) {
				if ( ! isset( $user_email ) ) {
					$user_email = get_the_author_meta( 'user_email', get_current_user_id() );
				}
				if ( ! empty( $user_email ) ) {
					$music_store_settings['ms_notification_to_email'] = $user_email;
				}
			}
		} // End _update_settings

		private function _login_form( $redirect_to = '' ) {
			if ( empty( $redirect_to ) ) {
				global $wp;
				$redirect_to = home_url( add_query_arg( array(), $wp->request ) );
			}

			return preg_replace(
				'/<\/form>/i',
				wp_register( '<div>', '</div>', false ) . '</form>',
				wp_login_form(
					array(
						'echo' => false,
						'redirect' => $redirect_to,
						'form_id' => 'ms-login-form'
					)
				)
			);
		} // End _login_form

		/** MANAGE DATABASES FOR ADITIONAL POST DATA **/

		/*
		 *  Create database tables
		 *
		 *  @access public
		 *  @return void
		 */
		public function register( $networkwide ) {
			global $wpdb;

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				if ( $networkwide ) {
					$old_blog = $wpdb->blogid;
					// Get all blog ids
					$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
					foreach ( $blogids as $blog_id ) {
						switch_to_blog( $blog_id );
						$this->_create_db_structure( true );
						update_option( 'ms_social_buttons', true );
					}
					switch_to_blog( $old_blog );
					return;
				}
			}
			$this->_create_db_structure( true );
			update_option( 'ms_social_buttons', true );
		}  // End register

		/*
		 * A new blog has been created in a multisite WordPress
		 */
		public function install_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
			global $wpdb;
			if ( is_plugin_active_for_network() ) {
				$current_blog = $wpdb->blogid;
				switch_to_blog( $blog_id );
				$this->_create_db_structure( true );
				update_option( 'ms_social_buttons', true );
				switch_to_blog( $current_blog );
			}
		}

		public function redirect_to_settings( $plugin, $network_activation ) {
			if (
				empty( $_REQUEST['_ajax_nonce'] ) &&
				plugin_basename( __FILE__ ) == $plugin &&
				( ! isset( $_POST['action'] ) || 'activate-selected' != $_POST['action'] ) && // phpcs:ignore WordPress.Security.NonceVerification
				( ! isset( $_POST['action2'] ) || 'activate-selected' != $_POST['action2'] ) // phpcs:ignore WordPress.Security.NonceVerification
			) {
				wp_redirect( esc_url( admin_url( 'admin.php?page=music-store-menu-settings' ) ) );
				exit;
			}
		}

		/*
		 * Create the Music Store tables
		 *
		 * @access private
		 * @return void
		 */
		private function _create_db_structure( $installing = false ) {
			global $wpdb;
			try {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';

				$charset_collate = $wpdb->get_charset_collate();

				$db_queries   = array();
				$db_queries[] = 'CREATE TABLE ' . $wpdb->prefix . MSDB_POST_DATA . " (
                    id mediumint(9) NOT NULL,
                    time VARCHAR(25) NULL,
                    popularity TINYINT NOT NULL DEFAULT 0,
                    plays mediumint(9) NOT NULL DEFAULT 0,
                    purchases mediumint(9) NOT NULL DEFAULT 0,
                    file VARCHAR(255) NULL,
                    demo VARCHAR(255) NULL,
                    protect TINYINT(1) NOT NULL DEFAULT 0,
                    info VARCHAR(255) NULL,
                    cover VARCHAR(255) NULL,
                    price FLOAT NULL,
                    year VARCHAR(25),
					isrc VARCHAR(50) NULL,
                    as_single TINYINT(1) NOT NULL DEFAULT 0,
                    UNIQUE KEY id (id)
                 ) $charset_collate;";

				$db_queries[] = 'CREATE TABLE ' . $wpdb->prefix . MSDB_PURCHASE . " (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    product_id mediumint(9) NOT NULL,
                    purchase_id varchar(50) NOT NULL,
                    buyer_id bigint(20),
                    date DATETIME NOT NULL,
                    checking_date DATETIME,
                    email VARCHAR(255) NOT NULL,
                    amount FLOAT NOT NULL DEFAULT 0,
                    downloads INT NOT NULL DEFAULT 0,
                    paypal_data TEXT,
					note TEXT,
                    UNIQUE KEY id (id)
                 ) $charset_collate;";

				$db_queries[] = MS_REVIEW::db_structure();

				dbDelta( $db_queries ); // Running the queries
				$index = $wpdb->get_var( 'SHOW INDEX FROM ' . $wpdb->prefix . MSDB_PURCHASE . " WHERE key_name = 'product_purchase'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( ! empty( $index ) ) {
					$wpdb->query( 'ALTER TABLE ' . $wpdb->prefix . MSDB_PURCHASE . ' DROP INDEX product_purchase' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
			} catch ( Exception $err ) {
				error_log( $err->getMessage() );
			}

			// Add new columns
			$this->_add_column( $wpdb->prefix . MSDB_PURCHASE, 'buyer_id', 'bigint(20)' );
		} // End _create_db_structure

		private function _add_column( $table_name, $column_name, $column_structure ) {
			global $wpdb;

			$results = $wpdb->get_results( 'SHOW columns FROM `' . $table_name . "` where field='" . $column_name . "'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! count( $results ) ) {
				$sql = 'ALTER TABLE  `' . $table_name . '` ADD `' . $column_name . '` ' . $column_structure;
				$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		} // End _add_column

		/** REGISTER POST TYPES AND TAXONOMIES **/

		/**
		 * Init MusicStore post types
		 *
		 * @access public
		 * @return void
		 */
		public function init_post_types() {
			global $music_store_settings;

			if ( post_type_exists( 'ms_song' ) ) {
				return;
			}

			// Post Types
			// Create song post type
			register_post_type(
				'ms_song',
				array(
					/* 'description'         => __( 'This is where you can add new song to your music store.', 'music-store' ), */
					'capability_type'     => 'page',
					'supports'            => array( 'title', 'editor', 'thumbnail', 'comments', 'custom-fields' ),
					'exclude_from_search' => false,
					'public'              => true,
					'show_ui'             => true,
					'show_in_nav_menus'   => true,
					'show_in_menu'        => $this->music_store_slug,
					'labels'              => array(
						'name'               => __( 'Songs', 'music-store' ),
						'singular_name'      => __( 'Song', 'music-store' ),
						'add_new'            => __( 'Add New', 'music-store' ),
						'add_new_item'       => __( 'Add New Song', 'music-store' ),
						'edit_item'          => __( 'Edit Song', 'music-store' ),
						'new_item'           => __( 'New Song', 'music-store' ),
						'view_item'          => __( 'View Song', 'music-store' ),
						'search_items'       => __( 'Search Songs', 'music-store' ),
						'not_found'          => __( 'No songs found', 'music-store' ),
						'not_found_in_trash' => __( 'No songs found in Trash', 'music-store' ),
						'menu_name'          => __( 'Songs for Sale', 'music-store' ),
						'parent_item_colon'  => '',
					),
					'query_var'           => true,
					'has_archive'         => true,
					// 'register_meta_box_cb' => 'wpsc_meta_boxes',
					'rewrite'             => ( ( $music_store_settings['ms_friendly_url'] * 1 ) ? array( 'slug' => MSSong::get_permalink() ) : false ),
				)
			);

			add_filter( 'manage_ms_song_posts_columns', 'MSSong::columns' );
			add_action( 'manage_ms_song_posts_custom_column', 'MSSong::columns_data', 2 );

			if ( $music_store_settings['ms_friendly_url'] * 1 && empty( $GLOBALS[ MS_SESSION_NAME ]['music_store_flush_rewrite_rules'] ) ) {
				flush_rewrite_rules();
				$GLOBALS[ MS_SESSION_NAME ]['music_store_flush_rewrite_rules'] = 1;
			}
		}//end init_post_types()

		/**
		 * Init MusicStore taxonomies
		 *
		 * @access public
		 * @return void
		 */
		public function init_taxonomies() {
			global $music_store_settings;

			if ( taxonomy_exists( 'ms_genre' ) ) {
				return;
			}

			do_action( 'musicstore_register_taxonomy' );

			// Create Genre taxonomy
			register_taxonomy(
				'ms_genre',
				array(
					'ms_song',
				),
				array(
					'hierarchical'      => true,
					'label'             => __( 'Genres', 'music-store' ),
					'labels'            => array(
						'name'          => __( 'Genres', 'music-store' ),
						'singular_name' => __( 'Genre', 'music-store' ),
						'search_items'  => __( 'Search Genres', 'music-store' ),
						'all_items'     => __( 'All Genres', 'music-store' ),
						'edit_item'     => __( 'Edit Genre', 'music-store' ),
						'update_item'   => __( 'Update Genre', 'music-store' ),
						'add_new_item'  => __( 'Add New Genre', 'music-store' ),
						'new_item_name' => __( 'New Genre Name', 'music-store' ),
						'menu_name'     => __( 'Genres', 'music-store' ),
					),
					'public'            => true,
					'show_ui'           => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => ( ( $music_store_settings['ms_friendly_url'] * 1 ) ? array( 'slug' => self::get_taxonomy_permalink( 'ms_genre' ) ) : false ),
				)
			);

			// Register artist taxonomy
			register_taxonomy(
				'ms_artist',
				array(
					'ms_song',
				),
				array(
					'hierarchical'      => false,
					'label'             => __( 'Artists', 'music-store' ),
					'labels'            => array(
						'name'          => __( 'Artists', 'music-store' ),
						'singular_name' => __( 'Artist', 'music-store' ),
						'search_items'  => __( 'Search Artists', 'music-store' ),
						'all_items'     => __( 'All Artists', 'music-store' ),
						'edit_item'     => __( 'Edit Artist', 'music-store' ),
						'update_item'   => __( 'Update Artist', 'music-store' ),
						'add_new_item'  => __( 'Add New Artist', 'music-store' ),
						'new_item_name' => __( 'New Artist Name', 'music-store' ),
						'menu_name'     => __( 'Artists', 'music-store' ),
					),
					'public'            => true,
					'show_ui'           => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => ( ( $music_store_settings['ms_friendly_url'] * 1 ) ? array( 'slug' => self::get_taxonomy_permalink( 'ms_artist' ) ) : false ),
				)
			);

			// Register album taxonomy
			register_taxonomy(
				'ms_album',
				array(
					'ms_song',
				),
				array(
					'hierarchical'      => false,
					'label'             => __( 'Albums', 'music-store' ),
					'labels'            => array(
						'name'          => __( 'Albums', 'music-store' ),
						'singular_name' => __( 'Album', 'music-store' ),
						'search_items'  => __( 'Search Albums', 'music-store' ),
						'all_items'     => __( 'All Albums', 'music-store' ),
						'edit_item'     => __( 'Edit Album', 'music-store' ),
						'update_item'   => __( 'Update Album', 'music-store' ),
						'add_new_item'  => __( 'Add New Album', 'music-store' ),
						'new_item_name' => __( 'New Album Name', 'music-store' ),
						'menu_name'     => __( 'Albums', 'music-store' ),
					),
					'public'            => true,
					'show_ui'           => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => ( ( $music_store_settings['ms_friendly_url'] * 1 ) ? array( 'slug' => self::get_taxonomy_permalink( 'ms_album' ) ) : false ),
				)
			);

			add_action( 'admin_menu', array( &$this, 'remove_meta_box' ) );
		} // End init_taxonomies

		/**
		 *  Remove the taxonomies metabox
		 *
		 * @access public
		 * @return void
		 */
		public function remove_meta_box() {
			remove_meta_box( 'tagsdiv-ms_artist', 'ms_song', 'side' );
			remove_meta_box( 'tagsdiv-ms_album', 'ms_song', 'side' );
		} // End remove_meta_box

		// Taxonomies permalinks
		public static function get_taxonomy_permalink( $taxonomy ) {
			return get_option( $taxonomy . '_permalink', $taxonomy );
		} // End get_taxonomy_permalink

		public static function save_taxonomies_permalink() {
			if ( isset( $_POST['ms_genre_permalink'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$permalink = music_store_sanitize_permalink(wp_unslash($_POST['ms_genre_permalink'])); // @codingStandardsIgnoreLine
				if ( empty( $permalink ) ) {
					$permalink = 'ms_genre';
				}
				update_option( 'ms_genre_permalink', $permalink );
			}

			if ( isset( $_POST['ms_artist_permalink'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$permalink = music_store_sanitize_permalink(wp_unslash($_POST['ms_artist_permalink'])); // @codingStandardsIgnoreLine
				if ( empty( $permalink ) ) {
					$permalink = 'ms_artist';
				}
				update_option( 'ms_artist_permalink', $permalink );
			}

			if ( isset( $_POST['ms_album_permalink'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$permalink = music_store_sanitize_permalink(wp_unslash($_POST['ms_album_permalink'])); // @codingStandardsIgnoreLine
				if ( empty( $permalink ) ) {
					$permalink = 'ms_album';
				}
				update_option( 'ms_album_permalink', $permalink );
			}
		} // End save_taxonomies_permalink

		public static function taxonomies_permalink_settings() {              ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th><label><?php esc_html_e( 'Genre permalink', 'music-store' ); ?></label></th>
						<td>
							<input name="ms_genre_permalink" id="ms_genre_permalink" type="text" value="<?php echo esc_attr( self::get_taxonomy_permalink( 'ms_genre' ) ); ?>" class="regular-text code">
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Artist permalink', 'music-store' ); ?></label></th>
						<td>
							<input name="ms_artist_permalink" id="ms_artist_permalink" type="text" value="<?php echo esc_attr( self::get_taxonomy_permalink( 'ms_artist' ) ); ?>" class="regular-text code">
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Album permalink', 'music-store' ); ?></label></th>
						<td>
							<input name="ms_album_permalink" id="ms_album_permalink" type="text" value="<?php echo esc_attr( self::get_taxonomy_permalink( 'ms_album' ) ); ?>" class="regular-text code">
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		} // End taxonomies_permalink_settings

		/** METABOXS FOR ENTERING POST_TYPE ADDITIONAL DATA **/

		/**
		 * Save data of store products
		 *
		 * @access public
		 * @return void
		 */
		public function save_data( $post_id, $post, $update ) {
			if ( $post ) {
				if ( isset( $post->post_type ) && 'ms_song' == $post->post_type ) {
					MSSong::save_data( $post );
				} elseif ( preg_match( '/\[\s*music_store\s*/i', $post->post_content ) ) {
					if (
						defined( 'MS_SESSION_NAME' ) &&
						! empty( $GLOBALS[MS_SESSION_NAME] )
					) {
						unset( $GLOBALS[MS_SESSION_NAME][ 'ms_page_' . $post_id ] );
					}
				}
			}
		} // End save_data

		/**
		 * Print metabox for post song
		 *
		 * @access public
		 * @return void
		 */
		public function metabox_form( $obj ) {
			global $post;

			if ( 'ms_song' == $obj->post_type ) {
				MSSong::print_metabox();
			}
		} // End metabox_form

		public function metabox_discount( $obj ) {
			if ( 'ms_song' == $obj->post_type ) {
				MSSong::print_discount_metabox();
			}
		} // End metabox_form


		public function metabox_manual_purchase( $obj ) {
			print '<p>' . esc_html__( 'To emulate a purchase it is possible to include a manual entry in the sales reports. Configure the product, and press the manual purchase button', 'music-store' ) . '</p><div style="text-align:right;"><a href="' . esc_attr( get_admin_url( null, 'admin.php?page=music-store-menu-reports&ms-product-id=' . $obj->ID ) ) . '" class="button-primary">' . esc_html__( 'Manual Purchase', 'music-store' ) . '</a></div>';
		} // End metabox_manual_purchase

		/** SETTINGS PAGE FOR MUSIC STORE CONFIGURATION AND SUBMENUS**/

		// highlight the proper top level menu for taxonomies submenus
		public function tax_menu_correction( $parent_file ) {
			global $current_screen;
			$taxonomy = $current_screen->taxonomy;
			if ( 'ms_genre' == $taxonomy || 'ms_artist' == $taxonomy || 'ms_album' == $taxonomy ) {
				$parent_file = $this->music_store_slug;
			}
			return $parent_file;
		} // End tax_menu_correction

		/*
		 * Create the link for music store menu, submenus and settings page
		 *
		 */
		public function menu_links() {
			if ( is_admin() ) {
				add_options_page( 'Music Store', 'Music Store', 'manage_options', $this->music_store_slug . '-settings1', array( &$this, 'settings_page' ) );

				add_menu_page( 'Music Store', 'Music Store', 'edit_pages', $this->music_store_slug, null, MS_CORE_IMAGES_URL . '/music-store-menu-icon.png', 4.55555555555555 );

				// Submenu for taxonomies
				add_submenu_page( $this->music_store_slug, __( 'Genres', 'music-store' ), __( 'Set Genres', 'music-store' ), 'edit_pages', 'edit-tags.php?taxonomy=ms_genre' );
				add_submenu_page( $this->music_store_slug, __( 'Artists', 'music-store' ), __( 'Set Artists', 'music-store' ), 'edit_pages', 'edit-tags.php?taxonomy=ms_artist' );
				add_submenu_page( $this->music_store_slug, __( 'Albums', 'music-store' ), __( 'Set Albums', 'music-store' ), 'edit_pages', 'edit-tags.php?taxonomy=ms_album' );

				add_action( 'parent_file', array( &$this, 'tax_menu_correction' ) );

				// Settings Submenu
				add_submenu_page( $this->music_store_slug, __( 'Music Store Settings', 'music-store' ), __( 'Store Settings', 'music-store' ), 'manage_options', $this->music_store_slug . '-settings', array( &$this, 'settings_page' ) );

				// Templates Submenu
				add_submenu_page( $this->music_store_slug, __( 'Music Store Templates', 'music-store' ), __( 'Products Templates', 'music-store' ), 'manage_options', $this->music_store_slug . '-templates', array( &$this, 'templates_page' ) );

				// Sales report submenu
				add_submenu_page( $this->music_store_slug, __( 'Music Store Sales Report', 'music-store' ), __( 'Sales Report', 'music-store' ), 'manage_options', $this->music_store_slug . '-reports', array( &$this, 'settings_page' ) );

				// Importer submenu
				add_submenu_page( $this->music_store_slug, __( 'Songs Importer', 'music-store' ), __( 'Songs Importer', 'music-store' ), 'manage_options', $this->music_store_slug . '-importer', array( &$this, 'importer' ) );

				// Help
				add_submenu_page( $this->music_store_slug, __( 'Online Help', 'music-store' ), __( 'Online Help', 'music-store' ), 'edit_pages', $this->music_store_slug . '-help', array( &$this, 'help' ) );
			}
		} // End menu_links

		public function help() {
			print '<p>Redirecting...</p>';
			print '<script>document.location.href="https://wordpress.org/support/plugin/music-store/#new-post";</script>';
			exit;
		}
		/*
		 *   Create tabs for setting page and payment stats
		 */
		public function settings_tabs( $current = 'reports' ) {
			$tabs = array(
				'settings'   => __( 'Music Store Settings', 'music-store' ),
				'song'       => __( 'Music Store Songs', 'music-store' ),
				'collection' => __( 'Music Store Collections', 'music-store' ),
				'reports'    => __( 'Sales Report', 'music-store' ),
				'importer'   => __( 'Songs Importer', 'music-store' ),
			);
			echo '<h2 class="nav-tab-wrapper">';
			$h1 = '';
			foreach ( $tabs as $tab => $name ) {
				$class = '';
				if ( $tab == $current ) {
					$class = ' nav-tab-active';
					$h1    = $name;
				}
				if ( 'song' == $tab ) {
					echo "<a class='nav-tab$class' href='edit.php?post_type=ms_$tab'>$name</a>"; // phpcs:ignore WordPress.Security.EscapeOutput
				} elseif ( 'collection' == $tab ) {
					echo "<a class='nav-tab$class' href='javascript:void(0);' onclick='window.alert(\"Collections only available for commercial version of plugin\")'>$name</a>"; // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo "<a class='nav-tab$class' href='admin.php?page={$this->music_store_slug}-$tab&tab=$tab'>$name</a>"; // phpcs:ignore WordPress.Security.EscapeOutput
				}
			}
			echo '</h2>';
			$this->settings_importer->print_message();
			echo '<h1>' . $h1 . '</h1>'; // phpcs:ignore WordPress.Security.EscapeOutput
		} // End settings_tabs

		/**
		 * Get the list of available layouts
		 */
		public function _layouts() {
			$tpls_dir = dir( MS_FILE_PATH . '/ms-layouts' );
			while ( false !== ( $entry = $tpls_dir->read() ) ) {
				if ( '.' != $entry && '..' != $entry && is_dir( $tpls_dir->path . '/' . $entry ) && file_exists( $tpls_dir->path . '/' . $entry . '/config.ini' ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
					if ( ( $ini_array = parse_ini_file( $tpls_dir->path . '/' . $entry . '/config.ini' ) ) === false ) {
						$config_content = file_get_contents( $tpls_dir->path . '/' . $entry . '/config.ini' );
						$ini_array      = parse_ini_string( $config_content );
					}
					if ( ! empty( $ini_array ) ) {
						if ( ! empty( $ini_array['style_file'] ) ) {
							$ini_array['style_file'] = 'ms-layouts/' . $entry . '/' . $ini_array['style_file'];
						}
						if ( ! empty( $ini_array['script_file'] ) ) {
							$ini_array['script_file'] = 'ms-layouts/' . $entry . '/' . $ini_array['script_file'];
						}
						if ( ! empty( $ini_array['thumbnail'] ) ) {
							$ini_array['thumbnail'] = MS_URL . '/ms-layouts/' . $entry . '/' . $ini_array['thumbnail'];
						}
						$this->layouts[ $ini_array['id'] ] = $ini_array;
					}
				}
			}
		}

		/**
		 * Get the list of possible paypal butt
		 */
		public function get_paypal_button( $args = array() ) {
			$attrs = isset( $args['attrs'] ) ? $args['attrs'] : '';
			$class = isset( $args['class'] ) ? $args['class'] : '';

			$buttons = array(
				'button_a.gif'               => '<div class="ms-payment-button-container"><input type="submit" ' . $attrs . ' class="ms-payment-button ' . $class . '" value="' . __( 'Buy Now', 'music-store' ) . '" /><div class="ms-payment-button-cards-container"><span class="ms-payment-button-card ms-payment-button-visa"></span><span class="ms-payment-button-card ms-payment-button-mastercard"></span><span class="ms-payment-button-card ms-payment-buttonmex"></span><span class="ms-payment-button-card ms-payment-button-discover"></span></div></div>',

				'button_b.gif'               => '<div class="ms-payment-button-container"><input type="submit" ' . $attrs . ' class="ms-payment-button ' . $class . '" value="' . __( 'Pay Now', 'music-store' ) . '" /><div class="ms-payment-button-cards-container"><span class="ms-payment-button-card ms-payment-button-visa"></span><span class="ms-payment-button-card ms-payment-button-mastercard"></span><span class="ms-payment-button-card ms-payment-buttonmex"></span><span class="ms-payment-button-card ms-payment-button-discover"></span></div></div>',

				'button_c.gif'               => '<div class="ms-payment-button-container"><input type="submit" ' . $attrs . ' class="ms-payment-button ' . $class . '" value="' . __( 'Pay Now', 'music-store' ) . '" /></div>',

				'button_d.gif'               => '<div class="ms-payment-button-container"><input type="submit" ' . $attrs . ' class="ms-payment-button ' . $class . '" value="' . __( 'Buy Now', 'music-store' ) . '" /></div>',

				'shopping_cart/button_e.gif' => '<div class="ms-payment-button-container"><input type="submit" ' . $attrs . ' class="ms-payment-button shopping-cart-btn ' . $class . '" value="' . __( 'Add to Cart', 'music-store' ) . '" /></div>',

				'shopping_cart/button_f.gif' => '<div class="ms-payment-button-container "><input type="submit" ' . $attrs . ' class="ms-payment-button view-cart-btn ' . $class . '" value="' . __( 'View Cart', 'music-store' ) . '" /></div>',
			);
			if ( isset( $args['button'] ) && ! empty( $buttons[ $args['button'] ] ) ) {
				return $buttons[ $args['button'] ];
			}
			return '';
		}

		public function _paypal_buttons() {
			global $music_store_settings;

			$b             = $music_store_settings['ms_paypal_button'];
			$buttons_names = array( 'button_a.gif', 'button_b.gif', 'button_c.gif', 'button_d.gif' );
			$str           = '';
			foreach ( $buttons_names as $button ) {
				$str .= "<input type='radio' name='ms_paypal_button' value='" . esc_attr( $button ) . "' " . ( ( $b == $button ) ? 'checked' : '' ) . ' />&nbsp;' . $this->get_paypal_button(
					array(
						'button' => $button,
						'attrs'  => 'DISABLED',
					)
				) . '&nbsp;&nbsp;';
			}
			return $str;
		} // End _paypal_buttons

		public function importer() {
			$_REQUEST['tab'] = 'importer';
			$this->settings_page();
		} // End Importer

		private function _make_seed() {
			list($usec, $sec) = explode( ' ', microtime() );
			return (float) $sec + ( (float) $usec * 100000 );
		} // End _make_seed

		public function templates_page() {
			include_once dirname( __FILE__ ) . '/ms-core/ms-templates.php';
		} // End templates_page

		/*
		 * Set the music store settings
		 */
		public function settings_page() {
			global $music_store_settings;

			print '<div class="wrap">'; // Open Wrap
			global $wpdb;
			$this->_layouts(); // Load the available layouts

			$ms_video_style     = 'style="display:none;"';
			$ms_first_time_mssg = '';

			if ( isset( $_POST['ms_settings'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ms_settings'] ) ), plugin_basename( __FILE__ ) ) ) {
				update_option( 'ms_main_page', isset( $_POST['ms_main_page'] ) ? esc_url_raw( wp_unslash( $_POST['ms_main_page'] ) ) : '' );
				update_option( 'ms_woocommerce_integration', ( isset( $_POST['ms_woocommerce_integration'] ) ? 1 : 0 ) );
				update_option( 'ms_prevent_cache', ( isset( $_POST['ms_prevent_cache'] ) ? 1 : 0 ) );
				update_option( 'ms_filter_by_genre', ( ( isset( $_POST['ms_filter_by_genre'] ) ) ? true : false ) );
				update_option( 'ms_filter_by_artist', ( ( isset( $_POST['ms_filter_by_artist'] ) ) ? true : false ) );
				update_option( 'ms_filter_by_album', ( ( isset( $_POST['ms_filter_by_album'] ) ) ? true : false ) );
				update_option( 'ms_search_taxonomy', ( ( isset( $_POST['ms_search_taxonomy'] ) ) ? true : false ) );
				update_option( 'ms_items_page_selector', ( ( isset( $_POST['ms_items_page_selector'] ) ) ? true : false ) );
				update_option( 'ms_friendly_url', ( ( isset( $_POST['ms_friendly_url'] ) ) ? 1 : 0 ) );
				update_option( 'ms_items_page', isset( $_POST['ms_items_page'] ) && is_numeric( $_POST['ms_items_page'] ) ? intval( $_POST['ms_items_page'] ) : 0 );
				update_option( 'ms_popularity', ( isset( $_POST['ms_popularity'] ) ) ? 1 : 0 );

				update_option( 'ms_pp_accept_zip', ( isset( $_POST['ms_pp_accept_zip'] ) ) ? 1 : 0 );
				update_option( 'ms_pp_related_products', ( isset( $_POST['ms_pp_related_products'] ) ) ? 1 : 0 );

				$cover = '';
				if ( isset( $_POST['ms_pp_default_cover'] ) ) {
					$cover = esc_url_raw( sanitize_text_field( wp_unslash( $_POST['ms_pp_default_cover'] ) ) );
					if ( ! empty( $cover ) && ! music_store_mime_type_accepted( $cover ) ) {
						music_store_setError( esc_html__( 'Invalid file type for cover.', 'music-store' ) );
						$cover = '';
					}
				}
				update_option( 'ms_pp_default_cover', $cover );
				update_option( 'ms_pp_cover_size', ( isset( $_POST['ms_pp_cover_size'] ) && 'full' == $_POST['ms_pp_cover_size'] ) ? 'full' : 'medium' );

				update_option( 'ms_pp_related_products_number', ( empty( $_POST['ms_pp_related_products_number'] ) || ! is_numeric( $_POST['ms_pp_related_products_number'] ) || 0 == ( $ms_pp_related_products_number = intval( $_POST['ms_pp_related_products_number'] ) ) ) ? 3 : $ms_pp_related_products_number ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

				update_option( 'ms_pp_related_products_columns', ( empty( $_POST['ms_pp_related_products_columns'] ) || ! is_numeric( $_POST['ms_pp_related_products_columns'] ) || 0 == ( $ms_pp_related_products_columns = intval( $_POST['ms_pp_related_products_columns'] ) ) ) ? 3 : $ms_pp_related_products_columns ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

				update_option(
					'ms_pp_related_products_by',
					( empty( $_POST['ms_pp_related_products_by'] ) ) ? array() : ( ( is_array( $_POST['ms_pp_related_products_by'] ) ) ? array_map(
						function ( $v ) {
							return sanitize_text_field( wp_unslash( $v ) );
						},
						$_POST['ms_pp_related_products_by']
					) : sanitize_text_field( wp_unslash( $_POST['ms_pp_related_products_by'] ) ) )
				); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments, WordPress.Security.ValidatedSanitizedInput

				if ( ! empty( $_POST['ms_layout'] ) ) {
					$_ms_layout = sanitize_text_field( wp_unslash( $_POST['ms_layout'] ) );
					if ( isset( $this->layouts[ $_ms_layout ] ) ) {
						$this->layout = $this->layouts[ $_ms_layout ];
						update_option( 'ms_layout', $this->layout );
					} else {
						delete_option( 'ms_layout' );
						$this->layout = array();
					}
				} else {
					delete_option( 'ms_layout' );
					$this->layout = array();
				}

				update_option(
					'ms_player_style',
					( isset( $_POST['ms_player_style'] ) && in_array( $_POST['ms_player_style'], array( 'mejs-classic', 'mejs-ted', 'mejs-wmp' ) )
					) ? sanitize_text_field( wp_unslash( $_POST['ms_player_style'] ) ) : 'mejs-classic'
				);
				update_option( 'ms_paypal_email', isset( $_POST['ms_paypal_email'] ) ? sanitize_email( wp_unslash( $_POST['ms_paypal_email'] ) ) : '' );
				update_option(
					'ms_paypal_button',
					( isset( $_POST['ms_paypal_button'] ) &&
						in_array(
							$_POST['ms_paypal_button'],
							array( 'button_a.gif', 'button_b.gif', 'button_c.gif', 'button_d.gif' )
						)
					) ? sanitize_text_field( wp_unslash( $_POST['ms_paypal_button'] ) ) : 'button_d.gif'
				);
				update_option( 'ms_hide_download_link_for_price_in_blank', ( isset( $_POST['ms_hide_download_link_for_price_in_blank'] ) ) ? true : false );
				update_option( 'ms_paypal_currency', isset( $_POST['ms_paypal_currency'] ) ? html_entity_decode( sanitize_text_field( wp_unslash( $_POST['ms_paypal_currency'] ) ) ) : '' );
				update_option( 'ms_paypal_currency_symbol', isset( $_POST['ms_paypal_currency_symbol'] ) ? html_entity_decode( sanitize_text_field( wp_unslash( $_POST['ms_paypal_currency_symbol'] ) ) ) : '' );
				update_option( 'ms_paypal_language', isset( $_POST['ms_paypal_language'] ) ? html_entity_decode( sanitize_text_field( wp_unslash( $_POST['ms_paypal_language'] ) ) ) : '' );
				update_option( 'ms_paypal_enabled', ( ( isset( $_POST['ms_paypal_enabled'] ) ) ? true : false ) );
				update_option( 'ms_paypal_sandbox', ( ( isset( $_POST['ms_paypal_sandbox'] ) ) ? true : false ) );
				update_option( 'ms_tax', ( ( ! empty( $_POST['ms_tax'] ) && ( $ms_tax = sanitize_text_field( wp_unslash( $_POST['ms_tax'] ) ) ) != '' ) ? @floatval( $ms_tax ) : false ) ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				update_option( 'ms_notification_from_email', isset( $_POST['ms_notification_from_email'] ) ? sanitize_email( wp_unslash( $_POST['ms_notification_from_email'] ) ) : '' );
				update_option( 'ms_notification_to_email', isset( $_POST['ms_notification_to_email'] ) ? sanitize_email( wp_unslash( $_POST['ms_notification_to_email'] ) ) : '' );
				update_option( 'ms_notification_to_payer_subject', isset( $_POST['ms_notification_to_payer_subject'] ) ? wp_kses_data( wp_unslash( $_POST['ms_notification_to_payer_subject'] ) ) : '' );
				update_option( 'ms_notification_to_payer_message', isset( $_POST['ms_notification_to_payer_message'] ) ? wp_kses_data( wp_unslash( $_POST['ms_notification_to_payer_message'] ) ) : '' );
				update_option( 'ms_notification_to_seller_subject', isset( $_POST['ms_notification_to_seller_subject'] ) ? wp_kses_data( wp_unslash( $_POST['ms_notification_to_seller_subject'] ) ) : '' );
				update_option( 'ms_notification_to_seller_message', isset( $_POST['ms_notification_to_seller_message'] ) ? wp_kses_data( wp_unslash( $_POST['ms_notification_to_seller_message'] ) ) : '' );
				update_option( 'ms_disable_download_links', ( ( isset( $_POST['ms_disable_download_links'] ) ) ? true : false ) );
				update_option( 'ms_old_download_link', ( isset( $_POST['ms_old_download_link'] ) && is_numeric( $_POST['ms_old_download_link'] ) ) ? intval( $_POST['ms_old_download_link'] ) : 0 );
				update_option( 'ms_downloads_number', ( isset( $_POST['ms_downloads_number'] ) && is_numeric( $_POST['ms_downloads_number'] ) ) ? intval( $_POST['ms_downloads_number'] ) : '' );
				update_option( 'ms_safe_download', ( ( isset( $_POST['ms_safe_download'] ) ) ? true : false ) );
				update_option( 'ms_play_all', ( ( isset( $_POST['ms_play_all'] ) ) ? 1 : 0 ) );
				update_option( 'ms_preload', ( ( isset( $_POST['ms_preload'] ) ) ? 1 : 0 ) );
				update_option( 'ms_social_buttons', ( ( isset( $_POST['ms_social_buttons'] ) ) ? true : false ) );
				update_option( 'ms_facebook_app_id', ( ( ! empty( $_POST['ms_facebook_app_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['ms_facebook_app_id'] ) ) : '' ) );

				// Restrictions
				update_option( 'ms_download_link_for_registered_only', ( isset( $_POST['ms_download_link_for_registered_only'] ) ) ? 1 : 0 );
				update_option( 'ms_buy_button_for_registered_only', ( isset( $_POST['ms_buy_button_for_registered_only'] ) ) ? 1 : 0 );
				update_option( 'ms_debug_payment', ( isset( $_POST['ms_debug_payment'] ) ) ? 1 : 0 );

				// Licenses
				update_option( 'ms_license_for_regular', isset( $_POST['ms_license_for_regular'] ) ? esc_url_raw( sanitize_text_field( wp_unslash( $_POST['ms_license_for_regular'] ) ) ) : '' );
				update_option( 'ms_license_for_free', isset( $_POST['ms_license_for_free'] ) ? esc_url_raw( sanitize_text_field( wp_unslash( $_POST['ms_license_for_free'] ) ) ) : '' );

				// Troubleshoot
				update_option( 'ms_troubleshoot_no_ob', ( isset( $_POST['ms_troubleshoot_no_ob'] ) ) ? 1 : 0 );
				update_option( 'ms_troubleshoot_no_dl', ( isset( $_POST['ms_troubleshoot_no_dl'] ) ) ? 1 : 0 );
				update_option( 'ms_troubleshoot_email_address', isset( $_POST['ms_troubleshoot_email_address'] ) ? sanitize_email( wp_unslash( $_POST['ms_troubleshoot_email_address'] ) ) : '' );

				do_action( 'musicstore_save_settings' );
				if (
					$music_store_settings['ms_paypal_enabled'] &&
					get_option( 'ms_paypal_first_time_enable', false ) == false
				) {
					$ms_first_time_mssg = '<span id="ms_first_time_mssg">' . esc_html__( 'Settings Updated', 'music-store' ) . ' - </span>';
					update_option( 'ms_paypal_first_time_enable', true );
					$ms_video_style = 'style="display:block;"';
				}

				$this->_load_settings();
				unset( $GLOBALS[ MS_SESSION_NAME ]['music_store_flush_rewrite_rules'] );

				?>
				<div class="updated" style="margin:5px 0;"><strong><?php esc_html_e( 'Settings Updated', 'music-store' ); ?></strong></div>
				<?php
			}

			// Checks if it is the first time and display the wizard
			include_once dirname( __FILE__ ) . '/ms-core/ms-wizard.php';
			if ( ! empty( $wizard_active ) ) {
				return;
			}

			$current_tab = isset( $_REQUEST['tab'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ), array( 'reports', 'settings', 'importer' ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : ( isset( $_REQUEST['page'] ) && 'music-store-menu-reports' == $_REQUEST['page'] ? 'reports' : 'settings' );

			$this->settings_tabs(
				$current_tab
			);
			?>
			<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
				To get commercial version of Music Store, <a href="http://musicstore.dwbooster.com" target="_blank">CLICK HERE</a><br />
				For reporting an issue or to request a customization, <a href="http://musicstore.dwbooster.com/contact-us" target="_blank">CLICK HERE</a><br />
				If you want test the premium version of Music Store go to the following links:<br /> <a href="http://demos.dwbooster.com/music-store/wp-login.php" target="_blank">Administration area: Click to access the administration area demo</a><br />
				<a href="http://demos.dwbooster.com/music-store/" target="_blank">Public page: Click to access the Store Page</a>
			</p>
			<?php
			switch ( $current_tab ) {
				case 'settings':
					$this->_update_settings(); // Updates the notification emails.
					$player_style = $music_store_settings['ms_player_style'];
					?>
					<div style="text-align:right;margin-bottom:15px;margin-bottom:15px;">
						<form method="post" action="<?php echo admin_url('admin.php?page=music-store-menu-settings&tab=settings'); ?>" style="display:inline-block; vertical-align:middle;">
							<?php wp_nonce_field( 'music-store-export-settings', 'ms_export_settings' ); ?>
							<button type="submit" class="button-secondary"><?php esc_html_e( 'Export Store Settings', 'music-store' ); ?></button>
						</form>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
						<form method="post" action="<?php echo admin_url('admin.php?page=music-store-menu-settings&tab=settings'); ?>" style="display:inline-block; vertical-align:middle;" enctype="multipart/form-data">
							<?php wp_nonce_field( 'music-store-import-settings', 'ms_import_settings' ); ?>
							<input type="file" name="ms-store-settings-file">
							<button type="submit" class="button-secondary"><?php esc_html_e( 'Import Store Settings', 'music-store' ); ?></button>
						</form>
					</div>
					<form method="post" action="<?php echo esc_attr( admin_url( 'admin.php?page=music-store-menu-settings&tab=settings' ) ); ?>" class="music-store-settings">
						<input type="hidden" name="tab" value="settings" />
						<!-- STORE CONFIG -->
						<div class="postbox ms-woocommerce-integration">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Store page config', 'music-store' ); ?></span></h3>
							<div class="inside">
								<?php
								do_action( 'musicstore_before_store_page_settings' );
								?>
								<table class="form-table">
									<?php
									if ( class_exists( 'WooCommerce' ) ):
									?>
									<tr valign="top">
										<th style="border:2px solid purple;border-right:0;padding:10px;"><?php esc_html_e('Integrate into WooCommerce', MS_TEXT_DOMAIN); ?></th>
										<td style="border:2px solid purple;border-left:0;padding:10px;">
											<input type="checkbox" name="ms_woocommerce_integration" <?php
												if ( class_exists( 'WooCommerce' ) && $music_store_settings['ms_woocommerce_integration'] ) {
													echo 'CHECKED';
												}
											?> /> <b>(<?php esc_html_e( 'Experimental feature',  'music-store' ); ?>)</b>
											<br />
											<p style="border:1px solid #E6DB55; margin-bottom:10px; padding:5px; background-color:#FFFFE0;"><?php
											esc_html_e('Checking the WooCommerce integration checkbox would disable discounts, coupons, payment gateways, shopping cart, add-ons, notification emails and any other features of the Music Store plugin involved in the purchase process and communication with the buyer.  Since in this case all these functionalities would fall on WooCommerce. The Music Store would be restricted only to generate and display the products.', 'music-store' );
											?></p>
										</td>
									</tr>
									<?php
									endif;
									?>
									<tr valign="top">
										<th><?php esc_html_e( 'URL of store page', 'music-store' ); ?></th>
										<td>
											<input type="text" name="ms_main_page" size="40" value="<?php echo esc_attr( esc_url( $music_store_settings['ms_main_page'] ) ); ?>" />
											<br />
											<em><?php esc_html_e( 'Set the URL of page where the music store was inserted', 'music-store' ); ?></em>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Prevent the products pages be cached', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_prevent_cache" <?php if ( $music_store_settings['ms_prevent_cache'] ) {
																								echo 'checked';
																						   } ?> />
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Allow searching by taxonomies', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_search_taxonomy" value="1" <?php if ( $music_store_settings['ms_search_taxonomy'] ) {
																											echo 'checked';
																									   } ?> />
											<br />Including albums, artists, and genres
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Allow filtering by type', 'music-store' ); ?></th>
										<td>
											<input type="checkbox" name="ms_filter_by_type" disabled />
											<em style="color:#FF0000;"><?php esc_html_e( 'The option is not available because the free version allows to create only songs', 'music-store' ); ?></em>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Allow filtering by genre', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_filter_by_genre" value="1" <?php if ( $music_store_settings['ms_filter_by_genre'] ) {
																											echo 'checked';
																									   } ?> /></td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Allow filtering by artist', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_filter_by_artist" value="1" <?php if ( $music_store_settings['ms_filter_by_artist'] ) {
																											echo 'checked';
																										} ?> /></td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Allow filtering by album', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_filter_by_album" value="1" <?php if ( $music_store_settings['ms_filter_by_album'] ) {
																											echo 'checked';
																									   } ?> /></td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Allow multiple pages', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_items_page_selector" value="1" <?php if ( $music_store_settings['ms_items_page_selector'] ) {
																												echo 'checked';
																										   } ?> /></td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Items per page', 'music-store' ); ?></th>
										<td><input type="text" name="ms_items_page" value="<?php echo isset( $music_store_settings['ms_items_page'] ) && is_numeric( $music_store_settings['ms_items_page'] ) ? intval( $music_store_settings['ms_items_page'] ) : ''; ?>" /></td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Use friendly URLs on products', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_friendly_url" value="1" <?php if ( $music_store_settings['ms_friendly_url'] ) {
																										echo 'checked';
																									} ?> /></td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Store layout', 'music-store' ); ?></th>
										<td>
											<select name="ms_layout" id="ms_layout">
												<option value=""><?php esc_html_e( 'Default layout', 'music-store' ); ?></option>
												<?php
												foreach ( $this->layouts as $id => $layout ) {
													print '<option value="' . esc_attr( $id ) . '" ' . ( ( ! empty( $this->layout ) && $id == $this->layout['id'] ) ? 'SELECTED' : '' ) . ' thumbnail="' . esc_attr( $layout['thumbnail'] ) . '">' . esc_html( $layout['title'] ) . '</option>';
												}
												?>
											</select>
											<div id="ms_layout_thumbnail">
												<?php
												if ( ! empty( $this->layout ) ) {
													print '<img src="' . esc_url( $this->layout['thumbnail'] ) . '" title="' . esc_attr( $this->layout['title'] ) . '" />';
												}
												?>
											</div>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Show the products popularity', 'music-store' ); ?></th>
										<td>
											<input type="checkbox" name="ms_popularity" id="ms_popularity" <?php if ( $music_store_settings['ms_popularity'] ) {
																												print 'CHECKED';
																										   } ?> />
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Player style', 'music-store' ); ?></th>
										<td>
											<table>
												<tr>
													<td><input name="ms_player_style" type="radio" value="mejs-classic" <?php echo ( ( 'mejs-classic' == $player_style ) ? 'checked' : '' ); ?> /></td>
													<td><img src="<?php print esc_url( MS_URL ); ?>/ms-core/images/skin1.png" /></td>
												</tr>

												<tr>
													<td><input name="ms_player_style" type="radio" value="mejs-ted" <?php echo ( ( 'mejs-ted' == $player_style ) ? 'checked' : '' ); ?> /></td>
													<td><img src="<?php print esc_url( MS_URL ); ?>/ms-core/images/skin2.png" /></td>
												</tr>

												<tr>
													<td><input name="ms_player_style" type="radio" value="mejs-wmp" <?php echo ( ( 'mejs-wmp' == $player_style ) ? 'checked' : '' ); ?> /></td>
													<td><img src="<?php print esc_url( MS_URL ); ?>/ms-core/images/skin3.png" /></td>
												</tr>
											</table>
											<em><?php esc_html_e( 'For MIDI audio files only the play/pause button will be displayed on players.', 'music-store' ); ?></em>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Play all', 'music-store' ); ?></th>
										<td>
											<input name="ms_play_all" type="checkbox" <?php echo ( ( 1 == $music_store_settings['ms_play_all'] ) ? 'checked' : '' ); ?> /> <br /><em><?php esc_html_e( 'Play all songs in the webpage, one after the other', 'music-store' ); ?></em>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Preload audio files', 'music-store' ); ?></th>
										<td>
											<input name="ms_preload" type="checkbox" <?php echo ( ( 1 == $music_store_settings[ 'ms_preload' ] ) ? 'checked' : '' ) ;?> /> <br /><em><?php esc_html_e( 'Preloading of audio files by players. Allows audio files to be preloaded for quick playback by pressing the play buttons, but increases resource consumption', 'music-store' ); ?></em>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Percent of audio used for protected playbacks', 'music-store' ); ?></th>
										<td>
											<input type="text" name="ms_file_percent" disabled /> % <br />
											<em><?php esc_html_e( 'To prevent unauthorized copying of audio files, the files will be partially accessible', 'music-store' ); ?>
											</em>
											<em style="color:#FF0000;"><?php esc_html_e( 'The commercial version of plugin generates a truncated version of the audio file for selling to be used as demo', 'music-store' ); ?>
											</em>
										</td>
									</tr>

									<tr valign="top">
										<th><?php esc_html_e( 'Explain text for protected playbacks', 'music-store' ); ?></th>
										<td>
											<input type="text" name="ms_secure_playback_text" size="40" disabled /><br />
											<em><?php esc_html_e( 'The text will be shown below of the music player when secure playback is checked.', 'music-store' ); ?>
											</em>
											<em style="color:#FF0000;">
												<?php esc_html_e( 'The secure playback is available only in the commercial version of plugin', 'music-store' ); ?>
											</em>

										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Share in social networks', 'music-store' ); ?></th>
										<td>
											<input type="checkbox" name="ms_social_buttons" <?php echo ( ( $music_store_settings['ms_social_buttons'] ) ? 'CHECKED' : '' ); ?> /><br />
											<em><?php esc_html_e( 'The option enables the buttons for share the pages of songs and collections in social networks', 'music-store' ); ?></em>

										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Facebook app id for sharing in Facebook', 'music-store' ); ?></th>
										<td>
											<input type="text" name="ms_facebook_app_id" value="<?php echo esc_attr( $music_store_settings['ms_facebook_app_id'] ); ?>" size="40" /><br />
											<em><?php print wp_kses_post( __( 'Click the link to generate the Facebook App and get its ID: <a target="_blank" href="https://developers.facebook.com/apps">https://developers.facebook.com/apps</a>', 'music-store' ) ); ?></em>
										</td>
									</tr>
								</table>
								<?php
								do_action( 'musicstore_after_store_page_settings' );
								?>
							</div>
						</div>

						<!-- PRODUCT PAGES CONFIG -->
						<div class="postbox product-data  ms-woocommerce-integration">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Product config', 'music-store' ); ?></span></h3>
							<div class="inside">
								<?php
								do_action( 'musicstore_before_product_page_settings' );
								?>
								<table class="form-table">
									<tr valign="top">
										<th><?php esc_html_e( 'Default cover image', 'music-store' ); ?></th>
										<td>
											<input type="text" name="ms_pp_default_cover" value="<?php if ( ! empty( $music_store_settings['ms_pp_default_cover'] ) ) {
																										echo esc_attr( $music_store_settings['ms_pp_default_cover'] );
																								 } ?>" class="file_path" placeholder="<?php print esc_attr( __( 'File path/URL', 'music-store' ) ); ?>" size="40" />
											<input type="button" class="button_for_upload button" value="<?php print esc_attr( __( 'Upload a file', 'music-store' ) ); ?>" />
											<br />
											<i><?php esc_html_e( 'Cover to display when products do not have an associated image', 'music-store' ); ?></i>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Size of cover image', 'music-store' ); ?></th>
										<td>
											<input type="radio" name="ms_pp_cover_size" <?php if ( empty( $music_store_settings['ms_pp_cover_size'] ) || 'medium' == $music_store_settings['ms_pp_cover_size'] ) {
																							echo 'checked';
																						} ?> value="medium" /> <?php esc_html_e( 'Medium size', 'music-store' ); ?>
											<input type="radio" name="ms_pp_cover_size" <?php if ( ! empty( $music_store_settings['ms_pp_cover_size'] ) && 'full' == $music_store_settings['ms_pp_cover_size'] ) {
																							echo 'checked';
																						} ?> value="full" /> <?php esc_html_e( 'Full size', 'music-store' ); ?><br />
											<i><?php esc_html_e( 'The size of cover image selected only affects to the images associated to the products from now, the images selected previously won\'t be modified', 'music-store' ); ?></i>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<h3><?php esc_html_e( 'Product page', 'music-store' ); ?></h3>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Include list of related products', 'music-store' ); ?></th>
										<td>
											<input type="checkbox" name="ms_pp_related_products" <?php if ( $music_store_settings['ms_pp_related_products'] ) {
																										echo 'checked';
																								 } ?> />
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Number of related products', 'music-store' ); ?></th>
										<td>
											<input type="number" name="ms_pp_related_products_number" value="<?php echo esc_attr( $music_store_settings['ms_pp_related_products_number'] ); ?>" />
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Display list in', 'music-store' ); ?></th>
										<td><input type="number" name="ms_pp_related_products_columns" value="<?php echo esc_attr( $music_store_settings['ms_pp_related_products_columns'] ); ?>" /> <?php esc_html_e( 'columns', 'music-store' ); ?>
										</td>
									</tr>
									<tr valign="top">
										<th><?php esc_html_e( 'Products related by', 'music-store' ); ?></th>
										<td>
											<input type="checkbox" name="ms_pp_related_products_by[]" value="album" <?php if ( in_array( 'album', $music_store_settings['ms_pp_related_products_by'] ) ) {
																														echo 'checked';
																													} ?> />
											<?php esc_html_e( 'Albums', 'music-store' ); ?><br>
											<input type="checkbox" name="ms_pp_related_products_by[]" value="genre" <?php if ( in_array( 'genre', $music_store_settings['ms_pp_related_products_by'] ) ) {
																														echo 'checked';
																													} ?> />
											<?php esc_html_e( 'Genres', 'music-store' ); ?><br>
											<input type="checkbox" name="ms_pp_related_products_by[]" value="artist" <?php if ( in_array( 'artist', $music_store_settings['ms_pp_related_products_by'] ) ) {
																															echo 'checked';
																													 } ?> />
											<?php esc_html_e( 'Artists', 'music-store' ); ?><br>
										</td>
									</tr>
								</table>
								<?php
								do_action( 'musicstore_after_product_page_settings' );
								?>
							</div>
						</div>

						<!-- PAYPAL BOX -->
						<div id="ms_ipn_video_tutorial" <?php print $ms_video_style; // phpcs:ignore WordPress.Security.EscapeOutput
						?>>
							<div style="padding:0 10px 10px 0;"><span><?php print wp_kses_post( $ms_first_time_mssg );
																		esc_html_e( 'How configure your PayPal account', 'music-store' ); ?></span><span style="float:right;"><a href="javascript:void(0);" onclick="jQuery('#ms_ipn_video_tutorial').hide();"><?php esc_html_e( 'close [x]', 'music-store' ); ?></a></span></div>
							<video controls preload="none" width="100%">
								<source src="https://www.dropbox.com/s/07qc58ma88fr9rx/ipn.mp4?raw=1" type="video/mp4">
							</video>
							<p><a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#id089EG030E5Z" target="_blank"><?php esc_html_e( 'PayPal Documentation', MS_TEXT_DOMAIN ); ?></a></p>
						</div>
						<p class="ms_more_info" style="display:block;">The Music Store uses PayPal only as payment gateway, but depending of your PayPal account, it is possible to charge the purchase directly from the Credit Cards of customers. It is possible to use Stripe as payment gateway installing the <a href="https://wordpress.org/plugins/music-store-stripe-add-on/" target="_blank">"Music Store Stripe add-on" plugin</a></p>
						<div class="postbox">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Payment Gateway Configuration', 'music-store' ); ?></span></h3>
							<div class="inside">
								<?php
								do_action( 'musicstore_before_payment_gateway_settings' );
								if (
									isset( $_SERVER['REMOTE_ADDR'] ) &&
									( '127.' == substr( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), 0, 4 )
										|| '::1' == sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) )
								) {
									print '<div style="border: 1px solid #FF0000; background-color: rgba(255,0,0,0.1); padding: 10px; margin: 10px 0;font-weight:bold;">' . esc_html__( 'Your website is hosted locally, so, the PayPal IPN cannot reach your website. For testing the purchases the website should be hosted publicly.', 'music-store' ) . '</div>';
								}
								?>
								<table class="form-table">
									<tr valign="top">
										<th scope="row" style="border-top:2px solid purple;border-left:2px solid purple;border-bottom:2px solid purple;padding-left:10px;"><?php esc_html_e( 'Enable Paypal Payments?', 'music-store' ); ?></th>
										<td style="border-top:2px solid purple;border-right:2px solid purple;border-bottom:2px solid purple; padding-right:10px;"><input type="checkbox" name="ms_paypal_enabled" value="1" <?php if ( $music_store_settings['ms_paypal_enabled'] ) {
																																																								echo 'checked';
																																																							} ?> /><br><i>Remember to enable the IPN (Instant Payments Notification) in the PayPal account (use the URL to your home page in the process): <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#link-settingupipnnotificationsonpaypal" target="_blank">How to enable the IPN?</a></i></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Use Paypal Sandbox', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_paypal_sandbox" value="1" <?php if ( $music_store_settings['ms_paypal_sandbox'] ) {
																											echo 'checked';
																									  } ?> /><br><i>The PayPal Sandbox account and the PayPal account are independent, remember to enable the IPN in the PayPal Sandbox account too: <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#link-settingupipnnotificationsonpaypal" target="_blank">How to enable the IPN?</a></i></td>
									</tr>

									<tr valign="top">
										<th scope="row" style="border-top:2px solid purple;border-left:2px solid purple;border-bottom:2px solid purple;padding-left:10px;"><?php esc_html_e( 'Paypal email', 'music-store' ); ?></th>
										<td style="border-top:2px solid purple;border-right:2px solid purple;border-bottom:2px solid purple; padding-right:10px;"><input type="text" name="ms_paypal_email" size="40" value="<?php echo esc_attr( $music_store_settings['ms_paypal_email'] ); ?>" />
											<span class="ms_more_info_hndl" style="margin-left: 10px;"><a href="javascript:void(0);" onclick="ms_display_more_info( this );">[ + <?php esc_html_e( 'more information', 'music-store' ); ?>]</a></span><span style="margin-left: 10px;"><a href="javascript:void(0);" onclick="jQuery('#ms_first_time_mssg').hide();jQuery('#ms_ipn_video_tutorial').show();">[ + <?php esc_html_e( 'enabling the IPN in PayPal tutorial', 'music-store' ); ?>]</a></span>
											<div class="ms_more_info">
												<p>If let empty the email associated to PayPal, the Music Store assumes the product will be distributed for free, and displays a download link in place of the button for purchasing</p>
												<a href="javascript:void(0)" onclick="ms_hide_more_info( this );">[ + less information]</a>
											</div>

										</td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Currency', 'music-store' ); ?></th>
										<td><input type="text" name="ms_paypal_currency" value="<?php echo esc_attr( $music_store_settings['ms_paypal_currency'] ); ?>" /><br>
											<b>USD</b> (United States dollar), <b>EUR</b> (Euro), <b>GBP</b> (Pound sterling) (<a href="https://developer.paypal.com/docs/api/reference/currency-codes/" target="_blank">PayPal Currency Codes</a>)
										</td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Currency Symbol', 'music-store' ); ?></th>
										<td><input type="text" name="ms_paypal_currency_symbol" value="<?php echo esc_attr( $music_store_settings['ms_paypal_currency_symbol'] ); ?>" /></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Paypal language', 'music-store' ); ?></th>
										<td><input type="text" name="ms_paypal_language" value="<?php echo esc_attr( $music_store_settings['ms_paypal_language'] ); ?>" /><br>
											<b>EN</b> (English), <b>ES</b> (Spain), <b>DE</b> (Germany) (<a href="https://developer.paypal.com/docs/api/reference/locale-codes/" target="_blank">PayPal Localee Codes</a>)
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<hr />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Payment button', 'music-store' ); ?></th>
										<td><?php print $this->_paypal_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput
										?></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'or use a shopping cart', 'music-store' ); ?></th>
										<td>
											<input type='radio' value='shopping_cart' disabled />
											<?php
											print $this->get_paypal_button( // phpcs:ignore WordPress.Security.EscapeOutput
												array(
													'button' => 'shopping_cart/button_e.gif',
													'attrs'  => 'DISABLED',
												)
											);

											print $this->get_paypal_button( // phpcs:ignore WordPress.Security.EscapeOutput
												array(
													'button' => 'shopping_cart/button_f.gif',
													'attrs'  => 'DISABLED',
												)
											);
											?>
											<em style="color:#FF0000;"><?php esc_html_e( 'The shopping cart is available only in the commercial version of plugin', 'music-store' ); ?></em>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Hide download link if price in blank', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_hide_download_link_for_price_in_blank" <?php if ( ! empty( $music_store_settings['ms_hide_download_link_for_price_in_blank'] ) ) {
																														print 'CHECKED';
																												   } ?> /><i><?php esc_html_e( 'The plugin assumes the songs whose price is left in blank are distributed for free, and includes a download link in them, click the checkbox to change this behavior.', 'music-store' ); ?></i></td>
									</tr>

									<tr>
										<td colspan="2">
											<hr />
										</td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Apply taxes (in percentage)', 'music-store' ); ?></th>
										<td><input type="number" name="ms_tax" value="<?php if ( ! empty( $music_store_settings['ms_tax'] ) ) {
																							print esc_attr( $music_store_settings['ms_tax'] );
																					  } ?>" /></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Hide the shopping cart icon from the store and products pages?', 'music-store' ); ?></th>
										<td><input type="checkbox" disabled /><em style="color:#FF0000;"><?php esc_html_e( 'The shopping cart is available only in the commercial version of plugin', 'music-store' ); ?></em></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Pay what you want', 'music-store' ); ?></th>
										<td><input type="checkbox" disabled /> <?php esc_html_e( 'The prices of products are hidden in the public webpage and in their places are displayed input boxes to let the buyers pay the amount they consider adequate. If the "Pay what you want" option is enabled then the exclusive prices are not taken into account. The amounts entered by the buyers are compared with the base price of the products and if the values are under the base prices then the Music Store displays the text entered in the "price under minimum" message.', 'music-store' ); ?><br /><em style="color:#FF0000;"><?php esc_html_e( 'The option "Pay what you want" is available only in the commercial version of plugin', 'music-store' ); ?></em></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Label of price box', 'music-store' ); ?></th>
										<td><input type="text" size="40" disabled /><br /> <?php esc_html_e( 'Text to display above the price box if the "Pay what you want" option is ticked.', 'music-store' ); ?><br /><em style="color:#FF0000;"><?php esc_html_e( 'The option "Pay what you want" is available only in the commercial version of plugin', 'music-store' ); ?></em></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Price under minimum message', 'music-store' ); ?></th>
										<td><textarea cols="60" disabled></textarea><br /> <?php esc_html_e( 'Text to display if the "Pay what you want" option is ticked, and the amount entered by the buyer is under the base price defined in the product.', 'music-store' ); ?><br /><em style="color:#FF0000;"><?php esc_html_e( 'The option "Pay what you want" is available only in the commercial version of plugin', 'music-store' ); ?></em></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Disable download links', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_disable_download_links" <?php echo ( ( $music_store_settings['ms_disable_download_links'] ) ? 'CHECKED' : '' ); ?> /> <?php esc_html_e( 'It hides the download links from the downloads page.', 'music-store' ); ?></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Download link valid for', 'music-store' ); ?></th>
										<td><input type="text" name="ms_old_download_link" value="<?php echo isset( $music_store_settings['ms_old_download_link'] ) && is_numeric( $music_store_settings['ms_old_download_link'] ) ? floatval( $music_store_settings['ms_old_download_link'] ) : ''; ?>" /> <?php esc_html_e( 'day(s)', 'music-store' ); ?></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Number of downloads allowed by purchase', 'music-store' ); ?></th>
										<td><input type="text" name="ms_downloads_number" value="<?php echo isset( $music_store_settings['ms_downloads_number'] ) && is_numeric( $music_store_settings['ms_downloads_number'] ) ? intval( $music_store_settings['ms_downloads_number'] ) : ''; ?>" /></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Increase the download page security', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_safe_download" <?php echo ( ( $music_store_settings['ms_safe_download'] ) ? 'CHECKED' : '' ); ?> /> <?php esc_html_e( 'The customers must enter the email address used in the product\'s purchasing to access to the download link. The Music Store verifies the customer\'s data, from the file link too.', 'music-store' ); ?></td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Pack all purchased audio files as a single ZIP file', 'music-store' ); ?></th>
										<td><input type="checkbox" disabled>
											<em style="color:#FF0000;"><?php esc_html_e( 'Downloading all purchased products, packaged in a same zipped file, is only available in the commercial version of plugin', 'music-store' ); ?></em>
											<?php
											if ( ! class_exists( 'ZipArchive' ) ) {
												echo '<br /><span class="explain-text">' . esc_html__( "Your server can't create Zipped files dynamically. Please, contact to your hosting provider for enable ZipArchive in the PHP script", 'music-store' ) . '</span>';
											}
											?>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<div style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
												<p style="font-size:1.3em;">If you detect any issue with the payments or downloads please: <a href="#" onclick="jQuery('.ms-troubleshoot-area').show();return false;">CLICK HERE [ + ]</a></p>
												<div class="ms-troubleshoot-area" style="display:none;">
													<h3>An user has paid for a product but has not received the download link</h3>
													<p><b>Possible causes:</b></p>
													<p><span style="font-size:1.3em;">*</span> The Instant Payment Notification (IPN) is not enabled in your PayPal account, in whose case the website won't notified about the payments. Please, visit the following link: <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/#link-settingupipnnotificationsonpaypal" target="_blank">How to enable the IPN?</a>. PayPal needs the URL to the IPN Script in your website, however, you simply should enter the URL to the home page, because the store will send the correct URL to the IPN Script.</p>
													<p><span style="font-size:1.3em;">*</span> The status of the payment is different to "Completed". If the payment status is different to "Completed" the Music Store won't generate the download link, or send the notification emails, to protect the sellers against frauds. PayPal will contact to the store even if the payment is "Pending" or has "Failed".</p>
													<p><b>But if the IPN is enabled, how can be detected the cause of issue?</b></p>
													<p>In this case you should check the IPN history (<a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNOperations/#link-viewipnmessagesanddetails" target="_blank">CLICK HERE</a>) for checking all variables that your PayPal account has sent to your website, and pays special attention to the "payment_status" variable (<a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNOperations/#link-viewipnmessagesanddetails" target="_blank">CLICK HERE</a>)</p>
													<p><b>The IPN is enabled, and the status of the payment in the PayPal account is "Completed", the purchase has been registered in the sales reports of the Music Store (the menu option in your WordPress: "Music Store/Sales Report") but the buyer has not received the notification email. What is the cause?</b></p>
													<p><span style="font-size:1.3em;">*</span> Enter an email address belonging to your website's domain through the attribute: "Notification "from" email" in the store's settings ( accessible from the menu option: "Music Store/Store Settings"). The email services (like AOL, YAHOO, MSN, etc.) check the email addresses in the "Sender" header of the emails, and if they do not belong to the websites that send the emails, can be classified as spam or even worst, as "Phishing" emails.</p>
													<p><span style="font-size:1.3em;">*</span> The email address in the "From" attribute belongs to the store's domain, but the buyer is not receiving the notification email. In this case you should ask the hosting provider the accesses to the SMTP server (all hosting providers include one), and install any of the plugin for SMTP connection distributed for free through the WordPress directory.</p>
													<p><b>The buyer has received the notification email with the download link, but cannot download the audio files.</b></p>
													<p><span style="font-size:1.3em;">*</span> The Music Store prevents the direct access to the audio files for security reasons. From the download page, the Music Store checks the number of downloads, the buyer email, or the expiration time for the download link, so, the plugin works as proxy between the browser, and the audio file, so, the PHP Script should have assigned sufficient memory to load the audio file. Pay attention, the amount of memory assigned to the PHP Script in the web server can be bigger than the file's size, however, you should to consider that all the concurrent accesses to your website are sharing the same PHP memory, and if two buyers are downloading a same file at the same time, the PHP Script in the server should to load in memory the file twice.</p>
													<p><a href="#" onclick="jQuery('.ms-troubleshoot-area').hide();return false;">CLOSE SECTION [ - ]</a></p>
												</div>
											</div>
											<div style="border:1px solid #ddd;padding:15px;">
												<input type="checkbox" name="ms_debug_payment" <?php print $music_store_settings['ms_debug_payment'] ? 'CHECKED' : ''; ?> /> <b><?php esc_html_e( 'Debugging Payment Process', 'music-store' ); ?></b><br /><br />
												<i><?php print wp_kses_post( __( "(If the checkbox is ticked the plugin will create two new entries in the error  logs file on your server, with the texts <b>Music Store payment gateway GET parameters</b> and <b>Music Store payment gateway POST parameters</b>.  If after a purchase, none of these entries appear in the error logs file, the payment notification has not reached the plugin's code)", 'music-store' ) ); ?></i>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<hr />
										</td>
									</tr>
									<tr>
										<th scope="row">
											<?php esc_html_e( 'Restrict the access to registered users only', 'music-store' ); ?>
										</th>
										<td>
											<input type="checkbox" name="ms_download_link_for_registered_only" <?php print $music_store_settings['ms_download_link_for_registered_only'] ? 'CHECKED' : ''; ?> />
											<?php esc_html_e( 'Display the free download links only for registered users', 'music-store' ); ?><br />
											<input type="checkbox" name="ms_buy_button_for_registered_only" <?php print $music_store_settings['ms_buy_button_for_registered_only'] ? 'CHECKED' : ''; ?> />
											<?php esc_html_e( 'Include the "Buy Now" or "Shopping Cart" buttons only for registered users', 'music-store' ); ?><br />
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<p style="border:1px solid #ddd; padding: 15px;"><?php print wp_kses_post( __( 'You can insert the <b>"Music Store Login Form"</b> on the sidebar through the menu option: <i>Appearance > Widgets</i>', 'music-store' ) ); ?></p>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<hr />
										</td>
									</tr>
									<tr>
										<th scope="row">
											<?php esc_html_e( 'Licenses', 'music-store' ); ?>
										</th>
										<td>
											<?php esc_html_e( 'Enter the URL to the webpage, or file, with the license for products downloaded for free.', 'music-store' ); ?><br />
											<input type="text" name="ms_license_for_free" value="<?php print esc_attr( $music_store_settings['ms_license_for_free'] ); ?>" style="width:100%;" />
											<?php esc_html_e( 'Enter the URL to the webpage, or file, with the license for regular purchases.', 'music-store' ); ?><br />
											<input type="text" name="ms_license_for_regular" value="<?php print esc_attr( $music_store_settings['ms_license_for_regular'] ); ?>" style="width:100%;" />
											<?php esc_html_e( 'Enter the URL to the webpage, or file, with the license for exclusive purchases.', 'music-store' ); ?><br />
											<input type="text" style="width:100%;" DISABLED /><br>
											<em style="color:#FF0000;"><?php esc_html_e( 'The exclusive sales are available only in the commercial version of the plugin, similar to the license', 'music-store' ); ?></em>
											<p style="font-style:italic;">
												<strong><?php
														esc_html_e( 'Note:', 'music-store' );
												?></strong>
												<?php
												esc_html_e( 'The links to the corresponding licenses are sent to the buyers in the notification emails, and in the case of license for free downloads, are inserted beside the download links.', 'music-store' );
												?>
											</p>
										</td>
									</tr>
								</table>
								<?php
								do_action( 'musicstore_after_payment_gateway_settings' );
								?>
							</div>
						</div>
						<?php $currency = $music_store_settings['ms_paypal_currency']; ?>
						<!--DISCOUNT BOX -->
						<div class="postbox">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Discount Settings', 'music-store' ); ?></span></h3>
							<div class="inside">
								<?php
								do_action( 'musicstore_before_discount_settings' );
								?>
								<em style="color:#FF0000;"><?php esc_html_e( 'The discounts are only available for commercial version of plugin' ); ?></em>
								<div><input type="checkbox" DISABLED /> <?php esc_html_e( 'Display discount promotions in the music store page', 'music-store' ); ?></div>
								<h4><?php esc_html_e( 'Scheduled Discounts', 'music-store' ); ?></h4>
								<input type="hidden" name="ms_discount_list" id="ms_discount_list" />
								<table class="form-table ms_discount_table" style="border:1px dotted #dfdfdf;">
									<tr>
										<td style="font-weight:bold;"><?php esc_html_e( 'Percent of discount', 'music-store' ); ?></td>
										<td style="font-weight:bold;"><?php esc_html_e( 'In Sales over than ... ', 'music-store' );
																		echo esc_html( $currency ); ?></td>
										<td style="font-weight:bold;"><?php esc_html_e( 'Valid from dd/mm/yyyy', 'music-store' ); ?></td>
										<td style="font-weight:bold;"><?php esc_html_e( 'Valid to dd/mm/yyyy', 'music-store' ); ?></td>
										<td style="font-weight:bold;"><?php esc_html_e( 'Promotional text', 'music-store' ); ?></td>
										<td style="font-weight:bold;"><?php esc_html_e( 'Status', 'music-store' ); ?></td>
										<td></td>
									</tr>
								</table>
								<table class="form-table">
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Percent of discount (*)', 'music-store' ); ?></th>
										<td><input type="text" DISABLED /> %</td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Valid for sales over than (*)', 'music-store' ); ?></th>
										<td><input type="text" DISABLED /> <?php echo esc_html( $currency ); ?></td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Valid from (dd/mm/yyyy)', 'music-store' ); ?></th>
										<td><input type="text" DISABLED /></td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Valid to (dd/mm/yyyy)', 'music-store' ); ?></th>
										<td><input type="text" DISABLED /></td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Promotional text', 'music-store' ); ?></th>
										<td><textarea DISABLED cols="60"></textarea></td>
									</tr>
									<tr>
										<td colspan="2"><input type="button" class="button" value="<?php esc_attr_e( 'Add/Update Discount', 'music-store' ); ?>" DISABLED /></td>
									</tr>
								</table>
								<?php
								do_action( 'musicstore_after_discount_settings' );
								?>
							</div>
						</div>

						<!-- NOTIFICATIONS BOX -->
						<div class="postbox">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Notification Settings', 'music-store' ); ?></span></h3>
							<div class="inside">
								<?php
								do_action( 'musicstore_before_notification_settings' );
								?>
								<table class="form-table">
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Notification "from" email', 'music-store' ); ?></th>
										<td><input type="text" name="ms_notification_from_email" size="40" value="<?php echo esc_attr( $music_store_settings['ms_notification_from_email'] ); ?>" /><br />
											<i><?php esc_html_e( 'EX', 'music-store' ) . ': admin@' . str_replace( 'www.', '', isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ); ?></i>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Send notification to email', 'music-store' ); ?></th>
										<td><input type="text" name="ms_notification_to_email" size="40" value="<?php echo esc_attr( $music_store_settings['ms_notification_to_email'] ); ?>" /></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Email subject confirmation to user', 'music-store' ); ?></th>
										<td><input type="text" name="ms_notification_to_payer_subject" size="40" value="<?php echo esc_attr( $music_store_settings['ms_notification_to_payer_subject'] ); ?>" /></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Email confirmation to user', 'music-store' ); ?></th>
										<td><textarea name="ms_notification_to_payer_message" cols="60" rows="5"><?php echo esc_textarea( $music_store_settings['ms_notification_to_payer_message'] ); ?></textarea></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Email subject notification to admin', 'music-store' ); ?></th>
										<td><input type="text" name="ms_notification_to_seller_subject" size="40" value="<?php echo esc_attr( $music_store_settings['ms_notification_to_seller_subject'] ); ?>" /></td>
									</tr>

									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Email notification to admin', 'music-store' ); ?></th>
										<td><textarea name="ms_notification_to_seller_message" cols="60" rows="5"><?php echo esc_textarea( $music_store_settings['ms_notification_to_seller_message'] ); ?></textarea></td>
									</tr>
								</table>
								<?php
								do_action( 'musicstore_after_notification_settings' );
								?>
							</div>
						</div>

						<!-- TROUBLESHOOT AREA -->
						<div class="postbox ms-woocommerce-integration" style="border: 1px solid #FF0000; background-color: rgba(255,0,0,0.1);">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Troubleshoot Area', 'music-store' ); ?></span></h3>
							<div class="inside">
								<?php
								do_action( 'musicstore_before_troubleshoot_settings' );
								?>
								<table class="form-table">
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'The downloaded file is broken', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_troubleshoot_no_ob" <?php if ( $music_store_settings['ms_troubleshoot_no_ob'] ) {
																									print 'CHECKED';
																								} ?> />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'The download link is invalid or displays a timer', 'music-store' ); ?></th>
										<td><input type="checkbox" name="ms_troubleshoot_no_dl" <?php if ( $music_store_settings['ms_troubleshoot_no_dl'] ) {
																									print 'CHECKED';
																								} ?> /> <?php esc_html_e( 'send me an email with the broken link to', 'music-store' ); ?> <input type="text" name="ms_troubleshoot_email_address" value="<?php print esc_attr( $music_store_settings['ms_troubleshoot_email_address'] ); ?>">
										</td>
									</tr>
								</table>
								<?php
								do_action( 'musicstore_after_throubleshoot_settings' );
								?>
							</div>
						</div>
						<?php
						do_action( 'musicstore_settings_page' );
						wp_nonce_field( plugin_basename( __FILE__ ), 'ms_settings' );
						?>
						<div class="submit"><input type="submit" class="button-primary" value="<?php esc_html_e( 'Update Settings', 'music-store' ); ?>" />
					</form>

					<?php
					break;
				case 'reports':
					$error_message = '';
					$message       = '';
					$message_list  = '';

					if ( isset( $_POST['ms_purchase_stats'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ms_purchase_stats'] ) ), plugin_basename( __FILE__ ) ) ) {
						if ( isset( $_POST['ms_new_entry'] ) ) {
							if ( ! empty( $_POST['new_entry_buyer'] ) ) {
								if ( ! empty( $_POST['new_entry_product'] ) ) {
									if (
										! empty( $_POST['new_entry_year'] ) &&
										isset( $_POST['new_entry_month'] ) &&
										isset( $_POST['new_entry_day'] )
									) {
										if (
											isset( $_POST['new_entry_amount'] ) &&
											! empty( $_POST['new_entry_currency'] )
										) {
											mt_srand( intval( $this->_make_seed() ) );
											$randval               = mt_rand( 1, 999999 );
											$new_entry_purchase_id = md5( $randval . uniqid( '', true ) );

											if (
												$wpdb->insert(
													$wpdb->prefix . MSDB_PURCHASE,
													array(
														'product_id'  => sanitize_text_field( wp_unslash( $_POST['new_entry_product'] ) ),
														'purchase_id' => $new_entry_purchase_id,
														'date'        => sanitize_text_field( wp_unslash( $_POST['new_entry_year'] ) ) . '-' . sanitize_text_field( wp_unslash( $_POST['new_entry_month'] ) ) . '-' . sanitize_text_field( wp_unslash( $_POST['new_entry_day'] ) ),
														'email'       => sanitize_text_field( wp_unslash( $_POST['new_entry_buyer'] ) ),
														'amount'      => is_numeric( $_POST['new_entry_amount'] ) ? floatval( $_POST['new_entry_amount'] ) : 0,
														'paypal_data' => ( isset( $_POST['new_entry_payment_data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['new_entry_payment_data'] ) ) : '' ) . ' mc_currency=' . sanitize_text_field( wp_unslash( $_POST['new_entry_currency'] ) ),
													),
													array( '%d', '%s', '%s', '%s', '%f', '%s' )
												)
											) {
												// Sends the download link to the buyer
												if ( ! empty( $_POST['new_entry_send_mail'] ) ) {
													$_POST['resend_purchase_id'] = $wpdb->insert_id;
												}

												// Updates the number of purchases
												$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . MSDB_POST_DATA . ' SET purchases=purchases+1 WHERE id=%d', ( ( ! empty( $_POST['new_entry_product'] ) && is_numeric( $_POST['new_entry_product'] ) ) ? intval( $_POST['new_entry_product'] ) : 0 ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

												$message .= '<li>' . esc_html__( 'New entry added to the sales report', 'music-store' ) . '</li>';
											} else {
												$error_message .= '<li>' . esc_html__( 'The new entry couldn\'t be inserted in the sales reports', 'music-store' ) . '</li>';
											}
										} else {
											$error_message .= '<li>' . esc_html__( 'The amount and currency are required', 'music-store' ) . '</li>';
										}
									} else {
										$error_message .= '<li>' . esc_html__( 'The date is wrong', 'music-store' ) . '</li>';
									}
								} else {
									$error_message .= '<li>' . esc_html__( 'The product is required', 'music-store' ) . '</li>';
								}
							} else {
								$error_message .= '<li>' . esc_html__( 'The buyer is required', 'music-store' ) . '</li>';
							}
						}

						if ( isset( $_POST['delete_purchase_id'] ) && is_numeric( $_POST['delete_purchase_id'] ) ) { // Delete the purchase
							$wpdb->query(
								$wpdb->prepare(
									'DELETE FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' WHERE id=%d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
									intval( $_POST['delete_purchase_id'] )
								)
							);
						}

						if ( isset( $_POST['resend_purchase_id'] ) && is_numeric( $_POST['resend_purchase_id'] ) ) { // Resend the email to the buyer with the download link
							$purchase_to_resend = $wpdb->get_row(
								$wpdb->prepare(
									'SELECT * FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' WHERE id=%d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
									intval( $_POST['resend_purchase_id'] )
								)
							);

							if ( ! empty( $purchase_to_resend ) ) {
								music_store_send_emails(
									array(
										'item_name'   => esc_html__( 'Products from ', 'music-store' ) . get_bloginfo( 'show' ),
										'payer_email' => $purchase_to_resend->email,
										'date'        => $purchase_to_resend->date,
										'purchase_id' => $purchase_to_resend->purchase_id,

									)
								);
								$message_list .= '<li>' . esc_html__( 'Email sent', 'music-store' ) . '</li>';
							}
						}

						if (
							isset( $_POST['reset_purchase_id'] ) ||
							isset( $_POST['resend_purchase_id'] )
						) { // Reset downloads and time interval
							$wpdb->query(
								$wpdb->prepare(
									'UPDATE ' . $wpdb->prefix . MSDB_PURCHASE . ' SET checking_date = NOW(),downloads = 0 WHERE id=%d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
									( isset( $_POST['reset_purchase_id'] ) && is_numeric( $_POST['reset_purchase_id'] ) ? intval( $_POST['reset_purchase_id'] ) : 0 ) + ( isset( $_POST['resend_purchase_id'] ) && is_numeric( $_POST['resend_purchase_id'] ) ? intval( $_POST['resend_purchase_id'] ) : 0 )
								)
							);
						}

						if ( isset( $_POST['show_purchase_id'] ) && is_numeric( $_POST['show_purchase_id'] ) ) { // Display paypal details
							$paypal_data = '<div class="ms-paypal-data"><h3>' . esc_html__( 'Payment data', 'music-store' ) . '</h3>' . $wpdb->get_var(
								$wpdb->prepare(
									'SELECT paypal_data FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' WHERE id=%d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
									intval( $_POST['show_purchase_id'] )
								)
							) . '</div>';
							$paypal_data = preg_replace( '/\n+/', '<br />', $paypal_data );
						}

						if ( isset( $_POST['old_email'] ) && isset( $_POST['new_email'] ) ) {
							$old_email = sanitize_email( wp_unslash( $_POST['old_email'] ) );
							$new_email = sanitize_email( wp_unslash( $_POST['new_email'] ) );

							if ( ! empty( $old_email ) && ! empty( $new_email ) ) {
								$wpdb->query(
									$wpdb->prepare(
										'UPDATE ' . $wpdb->prefix . MSDB_PURCHASE . ' SET email=%s WHERE email=%s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
										$new_email,
										$old_email
									)
								);

								if ( ! empty( $_POST['buyer'] ) && $_POST['buyer'] == $old_email ) {
									$_POST['buyer'] = $new_email;
								}
							}
						}
					}

					$group_by_arr = array(
						'no_group'  => 'Group by',
						'ms_artist' => 'Artist',
						'ms_genre'  => 'Genre',
						'ms_album'  => 'Album',
					);

					$from_day   = isset( $_POST['from_day'] ) ? intval( $_POST['from_day'] ) : gmdate( 'j' );
					$from_month = isset( $_POST['from_month'] ) ? intval( $_POST['from_month'] ) : gmdate( 'm' );
					$from_year  = isset( $_POST['from_year'] ) ? intval( $_POST['from_year'] ) : gmdate( 'Y' );
					$buyer      = ! empty( $_POST['buyer'] ) ? sanitize_email( wp_unslash( $_POST['buyer'] ) ) : '';

					$to_day   = isset( $_POST['to_day'] ) ? intval( $_POST['to_day'] ) : gmdate( 'j' );
					$to_month = isset( $_POST['to_month'] ) ? intval( $_POST['to_month'] ) : gmdate( 'm' );
					$to_year  = isset( $_POST['to_year'] ) ? intval( $_POST['to_year'] ) : gmdate( 'Y' );

					$group_by   = isset( $_POST['group_by'] ) ? sanitize_text_field( wp_unslash( $_POST['group_by'] ) ) : 'no_group';
					if ( ! in_array( $group_by, [ 'ms_artist', 'ms_genre', 'ms_album'] ) ) $group_by = 'no_group';

					$to_display = isset( $_POST['to_display'] ) ? sanitize_text_field( wp_unslash( $_POST['to_display'] ) ) : 'sales';

					$_select = '';
					$_from   = ' FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' AS purchase, ' . $wpdb->prefix . 'posts AS posts ';
					$_where  = " WHERE posts.ID = purchase.product_id
									  AND posts.post_type = 'ms_song'
									  AND DATEDIFF(purchase.date, '{$from_year}-{$from_month}-{$from_day}')>=0
									  AND DATEDIFF(purchase.date, '{$to_year}-{$to_month}-{$to_day}')<=0 ";

					if ( isset( $_REQUEST['list_purchases'] ) ) {
						if ( 'paid' == $_REQUEST['list_purchases'] ) {
							$_where .= ' AND purchase.amount<>0 ';
						} elseif ( 'free' == $_REQUEST['list_purchases'] ) {
							$_where .= ' AND purchase.amount=0 ';
						}
					}

					if ( ! empty( $buyer ) ) {
						$_where .= $wpdb->prepare( 'AND purchase.email LIKE %s', '%' . $wpdb->esc_like( $buyer ) . '%' );
					}

					$_group        = '';
					$_order        = '';
					$_date_dif     = floor( max( abs( strtotime( $to_year . '-' . $to_month . '-' . $to_day ) - strtotime( $from_year . '-' . $from_month . '-' . $from_day ) ) / ( 60 * 60 * 24 ), 1 ) );
					$_table_header = array( __( 'Date', 'music-store' ), __( 'Product', 'music-store' ), __( 'Buyer', 'music-store' ), __( 'Amount', 'music-store' ), __( 'Currency', 'music-store' ), __( 'Download link', 'music-store' ), '' );

					if ( 'no_group' == $group_by ) {
						if ( 'sales' == $to_display ) {
							$_select .= 'SELECT purchase.*, posts.*';
						} else {
							$_select .= "SELECT SUM(purchase.amount)/{$_date_dif} as purchase_average, SUM(purchase.amount) as purchase_total, COUNT(posts.ID) as purchase_count, posts.*";
							$_group   = ' GROUP BY posts.ID';
							if ( 'amount' == $to_display ) {
								$_table_header = array( 'Product', 'Amount of Sales', 'Total' );
								$_order        = ' ORDER BY purchase_count DESC';
							} else {
								$_table_header = array( 'Product', 'Daily Average', 'Total' );
								$order         = ' ORDER BY purchase_average DESC';
							}
						}
					} else {
						$_select .= "SELECT SUM(purchase.amount)/{$_date_dif} as purchase_average, SUM(purchase.amount) as purchase_total, COUNT(posts.ID) as purchase_count, terms.name as term_name, terms.slug as term_slug";

						$_from  .= ", {$wpdb->prefix}term_taxonomy as taxonomy,
								     {$wpdb->prefix}term_relationships as term_relationships,
								     {$wpdb->prefix}terms as terms";
						$_where .= $wpdb->prepare( " AND taxonomy.taxonomy = %s
									 AND taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
									 AND term_relationships.object_id=posts.ID
									 AND taxonomy.term_id=terms.term_id", $group_by );
						$_group  = ' GROUP BY terms.term_id';
						$_order  = ' ORDER BY terms.slug;';

						if ( 'amount' == $to_display ) {
							$_order        = ' ORDER BY purchase_count DESC';
							$_table_header = array( $group_by_arr[ $group_by ], 'Amount of Sales', 'Total' );
						} else {
							$order = ' ORDER BY purchase_average DESC';
							if ( 'sales' == $to_display ) {
								$_table_header = array( $group_by_arr[ $group_by ], 'Total' );
							} else {
								$_table_header = array( $group_by_arr[ $group_by ], 'Daily Average', 'Total' );
							}
						}
					}
					$purchase_list = $wpdb->get_results( $_select . $_from . $_where . $_group . $_order ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					?>
					<form method="post" action="<?php echo esc_attr( admin_url( 'admin.php?page=music-store-menu-reports&tab=reports' ) ); ?>" id="purchase_form">
						<?php
						wp_nonce_field( plugin_basename( __FILE__ ), 'ms_purchase_stats' );
						$months_list = array(
							'01' => __( 'January', 'music-store' ),
							'02' => __( 'February', 'music-store' ),
							'03' => __( 'March', 'music-store' ),
							'04' => __( 'April', 'music-store' ),
							'05' => __( 'May', 'music-store' ),
							'06' => __( 'June', 'music-store' ),
							'07' => __( 'July', 'music-store' ),
							'08' => __( 'August', 'music-store' ),
							'09' => __( 'September', 'music-store' ),
							'10' => __( 'October', 'music-store' ),
							'11' => __( 'November', 'music-store' ),
							'12' => __( 'December', 'music-store' ),
						);
						$today       = getdate();
						?>
						<input type="hidden" name="tab" value="reports" />
						<!-- MANUAL ENTRY -->
						<div class="postbox">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Manual Entry', 'music-store' ); ?></span></h3>
							<div class="inside">
								<div>
									<?php
									if ( ! empty( $error_message ) ) {
										print '<div class="music-store-error-mssg"><ul>' . wp_kses_post( $error_message ) . '</ul></div>';
									}
									if ( ! empty( $message ) ) {
										print '<div class="music-store-mssg"><ul>' . wp_kses_post( $message ) . '</ul></div>';
									}
									?>
									<table>
										<tr>
											<td>
												<label><?php esc_html_e( 'Buyer', 'music-store' ); ?>*:</label>
											</td>
											<td>
												<input type="text" name="new_entry_buyer" id="new_entry_buyer" />
												<input type="checkbox" name="new_entry_send_mail" name="new_entry_send_mail" /> <?php esc_html_e( 'Send the download link to the buyer', 'music-store' ); ?>
											</td>
										</tr>
										<tr>
											<td>
												<label><?php esc_html_e( 'Product', 'music-store' ); ?>*:</label>
											</td>
											<td>
												<select name="new_entry_product" id="new_entry_product">
													<option value=""><?php esc_html_e( 'Select a product', 'music-store' ); ?></option>
													<?php
													$all_products = $wpdb->get_results(
														'SELECT ID,post_title,post_type FROM ' . $wpdb->posts . " WHERE post_type='ms_song' AND post_status='publish' ORDER BY post_title ASC",
														ARRAY_A
													);

													if ( $all_products ) {
														$manual_product_id = ! empty( $_GET['ms-product-id'] ) && is_numeric( $_GET['ms-product-id'] ) ? intval( $_GET['ms-product-id'] ) : 0;
														foreach ( $all_products as $product ) {
															print '<option value="' . esc_attr( $product['ID'] ) . '" ' . ( $manual_product_id == $product['ID'] ? 'SELECTED' : '' ) . '>' . esc_html( '(' . $product['ID'] . ') ' . $product['post_title'] ) . '</option>';
														}
													}
													?>
												</select>
											</td>
										</tr>
										<tr>
											<td>
												<label><?php esc_html_e( 'Date', 'music-store' ); ?>*:</label>
											</td>
											<td>
												<select name="new_entry_day">
													<?php
													for ( $i = 1; $i <= 31; $i++ ) {
														print '<option value="' . esc_attr( $i ) . '" ' . ( ( $i == $today['mday'] ) ? 'SELECTED' : '' ) . '>' . esc_html( $i ) . '</option>';
													}
													?>
												</select>
												<select name="new_entry_month">
													<?php
													foreach ( $months_list as $month => $name ) {
														print '<option value="' . esc_attr( $month ) . '" ' . ( ( $month * 1 == $today['mon'] ) ? 'SELECTED' : '' ) . '>' . esc_html( $name ) . '</option>';
													}
													?>
												</select>
												<input type="text" name="new_entry_year" value="<?php print esc_attr( $today['year'] ); ?>" />
											</td>
										</tr>
										<tr>
											<td>
												<label><?php esc_html_e( 'Amount', 'music-store' ); ?>*: </label>
											</td>
											<td>
												<input type="text" name="new_entry_amount" />
												<label><?php esc_html_e( 'Currency', 'music-store' ); ?>*: </label>
												<input type="text" name="new_entry_currency" placeholder="<?php esc_html_e( 'for example: USD', 'music-store' ); ?>" value="<?php print esc_attr( $music_store_settings['ms_paypal_currency'] ); ?>" />
											</td>
										</tr>
										<tr>
											<td valign="top">
												<label><?php esc_html_e( 'Payment data' ); ?>: </label>
											</td>
											<td>
												<textarea name="new_entry_payment_data" style="min-width:50%;height:100px;"></textarea>
											</td>
										</tr>
									</table>
									<input type="submit" value="<?php esc_attr_e( 'Add Entry', 'music-store' ); ?>" class="button-primary" onmousedown="jQuery(this).closest('form').append('<input type=\'hidden\' name=\'ms_new_entry\' value=\'1\' />');" />
								</div>
								<div style="clear:both;"></div>
							</div>
						</div>
						<!-- FILTER REPORT -->
						<div class="postbox">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Filter the sales reports', 'music-store' ); ?></span></h3>
							<div class="inside">
								<div>
									<h4><?php esc_html_e( 'Filter by date', 'music-store' ); ?></h4>
									<label><?php esc_html_e( 'Buyer: ', 'music-store' ); ?></label><input type="text" name="buyer" id="buyer" value="<?php print esc_attr( $buyer ); ?>" />
									<label><?php esc_html_e( 'From: ', 'music-store' ); ?></label>
									<select name="from_day">
										<?php
										for ( $i = 1; $i <= 31; $i++ ) {
											print '<option value="' . esc_attr( $i ) . '"' . ( ( $from_day == $i ) ? ' SELECTED' : '' ) . '>' . esc_html( $i ) . '</option>';
										}
										?>
									</select>
									<select name="from_month">
										<?php
										foreach ( $months_list as $month => $name ) {
											print '<option value="' . esc_attr( $month ) . '"' . ( ( $from_month == $month ) ? ' SELECTED' : '' ) . '>' . esc_html( $name ) . '</option>';
										}
										?>
									</select>
									<input type="text" name="from_year" value="<?php print esc_attr( $from_year ); ?>" />

									<label><?php esc_html_e( 'To: ', 'music-store' ); ?></label>
									<select name="to_day">
										<?php
										for ( $i = 1; $i <= 31; $i++ ) {
											print '<option value="' . esc_attr( $i ) . '"' . ( ( $to_day == $i ) ? ' SELECTED' : '' ) . '>' . esc_html( $i ) . '</option>';
										}
										?>
									</select>
									<select name="to_month">
										<?php
										foreach ( $months_list as $month => $name ) {
											print '<option value="' . esc_attr( $month ) . '"' . ( ( $to_month == $month ) ? ' SELECTED' : '' ) . '>' . esc_html( $name ) . '</option>';
										}
										?>
									</select>
									<input type="text" name="to_year" value="<?php print esc_attr( $to_year ); ?>" />
									<input type="submit" value="<?php esc_attr_e( 'Search', 'music-store' ); ?>" class="button-primary" />
								</div>

								<div style="float:left;margin-right:20px;">
									<h4><?php esc_html_e( 'Grouping the sales', 'music-store' ); ?></h4>
									<label><?php esc_html_e( 'By: ', 'music-store' ); ?></label>
									<select name="group_by">
										<?php
										foreach ( $group_by_arr as $key => $value ) {
											print '<option value="' . esc_attr( $key ) . '"' . ( ( isset( $group_by ) && $group_by == $key ) ? ' SELECTED' : '' ) . '>' . esc_html( $value ) . '</option>';
										}
										?>
									</select>
								</div>
								<div style="float:left;margin-right:20px;">
									<h4><?php esc_html_e( 'Display', 'music-store' ); ?></h4>
									<label><input type="radio" name="to_display" <?php echo ( ( ! isset( $to_display ) || 'sales' == $to_display ) ? 'CHECKED' : '' ); ?> value="sales" /> <?php esc_html_e( 'Sales', 'music-store' ); ?></label>
									<label><input type="radio" name="to_display" <?php echo ( ( isset( $to_display ) && 'amount' == $to_display ) ? 'CHECKED' : '' ); ?> value="amount" /> <?php esc_html_e( 'Amount of sales', 'music-store' ); ?></label>
									<label><input type="radio" name="to_display" <?php echo ( ( isset( $to_display ) && 'average' == $to_display ) ? 'CHECKED' : '' ); ?> value="average" /> <?php esc_html_e( 'Daily average', 'music-store' ); ?></label>
								</div>
								<div style="clear:both;"></div>
							</div>
						</div>
						<!-- PURCHASE LIST -->
						<div class="postbox">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Store sales report', 'music-store' ); ?></span></h3>
							<div class="inside">
								<?php
								if ( ! empty( $paypal_data ) ) {
									print wp_kses_post( $paypal_data );
								}
								if ( count( $purchase_list ) ) {
									print '
										<div>
											<label style="margin-right: 20px;" ><input type="checkbox" onclick="ms_load_report(this, \'sales_by_country\', \'' . esc_js( __( 'Sales by country', 'music-store' ) ) . '\', \'residence_country\', \'Pie\', \'residence_country\', \'count\');" /> ' . esc_html__( 'Sales by country', 'music-store' ) . '</label>
											<label style="margin-right: 20px;" ><input type="checkbox" onclick="ms_load_report(this, \'sales_by_currency\', \'' . esc_js( __( 'Sales by currency', 'music-store' ) ) . '\', \'mc_currency\', \'Bar\', \'mc_currency\', \'sum\');" /> ' . esc_html__( 'Sales by currency', 'music-store' ) . '</label>
											<label style="margin-right: 20px;" ><input type="checkbox" onclick="ms_load_report(this, \'sales_by_product\', \'' . esc_js( __( 'Sales by product', 'music-store' ) ) . '\', \'product_name\', \'Bar\', \'post_title\', \'sum\');" /> ' . esc_html__( 'Sales by product', 'music-store' ) . '</label>
											<label><input type="checkbox" onclick="ms_load_report(this, \'download_by_product\', \'' . esc_js( __( 'Downloads free products', 'music-store' ) ) . '\', \'download_by_product\', \'Pie\', \'post_title\', \'count\');" /> ' . esc_html__( 'Downloads free products', 'music-store' ) . '</label>
										</div>';
								}
								?>
								<div id="charts_content">
									<div id="sales_by_country"></div>
									<div id="sales_by_currency"></div>
									<div id="sales_by_product"></div>
									<div id="download_by_product"></div>
								</div>
								<div class="ms-section-title"><?php esc_html_e( 'Products List', 'music-store' ); ?></div>
								<div>
									<input type="radio" name="list_purchases" value="all" <?php if ( ! isset( $_REQUEST['list_purchases'] ) || 'all' == $_REQUEST['list_purchases'] ) {
																								print 'CHECKED';
																						  } ?> onchange="ms_filtering_products_list(this);"><?php esc_html_e( 'List all products', 'music-store' ); ?>&nbsp;&nbsp;&nbsp;
									<input type="radio" name="list_purchases" value="paid" <?php if ( isset( $_REQUEST['list_purchases'] ) && 'paid' == $_REQUEST['list_purchases'] ) {
																								print 'CHECKED';
																						   } ?> onchange="ms_filtering_products_list(this);"><?php esc_html_e( 'List only the purchased products', 'music-store' ); ?>&nbsp;&nbsp;&nbsp;
									<input type="radio" name="list_purchases" value="free" <?php if ( isset( $_REQUEST['list_purchases'] ) && 'free' == $_REQUEST['list_purchases'] ) {
																								print 'CHECKED';
																						   } ?> onchange="ms_filtering_products_list(this);"><?php esc_html_e( 'List only the products downloaded for free', 'music-store' ); ?>
								</div>
								<?php
								if ( ! empty( $message_list ) ) {
									print '<div class="music-store-mssg" style="margin-top: 20px;margin-bottom: 20px;"><ul>' . wp_kses_post( $message_list ) . '</ul></div>';
								}
								?>
								<table class="form-table" style="border-bottom:1px solid #CCC;margin-bottom:10px;">
									<THEAD>
										<TR style="border-bottom:1px solid #CCC;">
											<?php
											foreach ( $_table_header as $_header ) {
												print '<TH>' . esc_html( $_header ) . '</TH>';
											}
											?>
										</TR>
									</THEAD>
									<TBODY>
										<?php

										$totals = array( 'UNDEFINED' => 0 );
										$dlurl  = $this->_ms_create_pages( 'ms-download-page', 'Download Page' );
										$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' );

										if ( count( $purchase_list ) ) {

											foreach ( $purchase_list as $purchase ) {

												if ( 'no_group' == $group_by ) {

													if ( 'sales' == $to_display ) {
														if ( preg_match( '/mc_currency=([^\s]*)/', $purchase->paypal_data, $matches ) ) {
															$currency = strtoupper( $matches[1] );
															if ( ! isset( $totals[ $currency ] ) ) {
																$totals[ $currency ] = $purchase->amount;
															} else {
																$totals[ $currency ] += $purchase->amount;
															}
														} else {
															$currency             = '';
															$totals['UNDEFINED'] += $purchase->amount;
														}

														$user_info = '';
														if ( ! empty( $purchase->buyer_id ) ) {
															$user_data = get_userdata( $purchase->buyer_id );
															if ( $user_data ) {
																$user_profile_link = add_query_arg( 'user_id', $user_data->ID, self_admin_url( 'user-edit.php' ) );
																$user_info         = '<br>(<a href="' . esc_attr( $user_profile_link ) . '">' . ( ! empty( $user_data->user_nicename ) ? $user_data->user_nicename : $user_data->user_login ) . '</a>)';
															}
														}

														echo '
													<TR>
														<TD>' . esc_html( $purchase->date ) . '</TD>
														<TD><a href="' . esc_attr( get_permalink( $purchase->ID ) ) . '" target="_blank">' . ( ( empty( $purchase->post_title ) ) ? esc_html( $purchase->ID ) : wp_kses_post( $purchase->post_title ) ) . '</a></TD>
														<TD>' . esc_html( $purchase->email ) . wp_kses_post( $user_info ) . '</TD>
														<TD>' . esc_html( $purchase->amount ) . '</TD>
														<TD>' . esc_html( $currency ) . '</TD>
														<TD><a href="' . esc_url( $dlurl . 'ms-action=download&purchase_id=' . $purchase->purchase_id ) . '" target="_blank">Download Link</a></TD>
														<TD class="ms-sales-report-actions">
															<input type="button" class="button-primary" onclick="delete_purchase(' . esc_js( $purchase->id ) . ');" value="Delete"> ' .
															(
																( $purchase->amount ) ?
																'<input type="button" class="button-primary" onclick="resend_email(' . esc_js( $purchase->id ) . ');" value="Resend Download Link">
															<input type="button" class="button-primary" onclick="reset_purchase(' . esc_js( $purchase->id ) . ');" value="Reset Time and Downloads">
															<input type="button" class="button-primary" onclick="show_purchase(' . esc_js( $purchase->id ) . ');" value="Payment Info">' : ''
															)
															. '</TD>
													</TR>
												';
													} elseif ( 'amount' == $to_display ) {
														echo '
													<TR>
														<TD><a href="' . esc_attr( get_permalink( $purchase->ID ) ) . '" target="_blank">' . wp_kses_post( $purchase->post_title ) . '</a></TD>
														<TD>' . esc_html( round( $purchase->purchase_count * 100 ) / 100 ) . '</TD>
														<TD>' . esc_html( round( $purchase->purchase_total * 100 ) / 100 ) . '</TD>
													</TR>
												';
													} else {
														echo '
													<TR>
														<TD><a href="' . esc_attr( get_permalink( $purchase->ID ) ) . '" target="_blank">' . wp_kses_post( $purchase->post_title ) . '</a></TD>
														<TD>' . esc_html( $purchase->purchase_average ) . '</TD>
														<TD>' . esc_html( round( $purchase->purchase_total * 100 ) / 100 ) . '</TD>
													</TR>
												';
													}
												} else {

													if ( 'sales' == $to_display ) {
														echo '
														<TR>
															<TD><a href="' . esc_attr( get_term_link( $purchase->term_slug, $group_by ) ) . '" target="_blank">' . wp_kses_post( $purchase->term_name ) . '</a></TD>
															<TD>' . esc_html( round( $purchase->purchase_total * 100 ) / 100 ) . '</TD>
														</TR>
													';
													} elseif ( 'amount' == $to_display ) {
														echo '
														<TR>
															<TD><a href="' . esc_attr( get_term_link( $purchase->term_slug, $group_by ) ) . '" target="_blank">' . wp_kses_post( $purchase->term_name ) . '</a></TD>
															<TD>' . esc_html( round( $purchase->purchase_count * 100 ) / 100 ) . '</TD>
															<TD>' . esc_html( round( $purchase->purchase_total * 100 ) / 100 ) . '</TD>
														</TR>
													';
													} else {
														echo '
														<TR>
															<TD><a href="' . esc_attr( get_term_link( $purchase->term_slug, $group_by ) ) . '" target="_blank">' . wp_kses_post( $purchase->term_name ) . '</a></TD>
															<TD>' . esc_html( $purchase->purchase_average ) . '</TD>
															<TD>' . esc_html( round( $purchase->purchase_total * 100 ) / 100 ) . '</TD>
														</TR>
													';
													}
												}
											}
										} else {
											echo '
										<TR>
											<TD COLSPAN="' . esc_attr( count( $_table_header ) ) . '">
												' . esc_html__( 'There are not sales registered with those filter options', 'music-store' ) . '
											</TD>
										</TR>
									';
										}
										?>
									</TBODY>
								</table>
								<table style="width:100%;">
									<tr>
										<td>
											<?php
											if ( count( $totals ) > 1 || $totals['UNDEFINED'] ) {
												?>
												<table style="border: 1px solid #CCC;">
													<TR>
														<TD COLSPAN="2" style="border-bottom:1px solid #CCC;">TOTALS</TD>
													</TR>
													<TR>
														<TD style="border-bottom:1px solid #CCC;">CURRENCY</TD>
														<TD style="border-bottom:1px solid #CCC;">AMOUNT</TD>
													</TR>
													<?php
													foreach ( $totals as $currency => $amount ) {
														if ( $amount ) {
															print '<TR><TD><b>' . esc_html( $currency ) . '</b></TD><TD>' . esc_html( $amount ) . '</TD></TR>';
														}
													}
													?>
												</table>
										</td>
										<td align="right">
											<table>
												<tr>
													<td>
														Buyer email:
														<input type="email" name="old_email" />
													</td>
													<td>
														New email:
														<input type="email" name="new_email" />
													</td>
													<td>
														<input type="submit" value="<?php esc_attr_e( 'Replace', 'music-store' ); ?>" class="button-primary" />
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
												<?php
											}
											?>
							</div>
						</div>
					</form>
					<?php
					break;
				case 'importer':
					?>
					<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
						The feature is only available in the commercial version of Music Store.
					</p>
					<?php
					break;
			}
			print '</div>'; // Close Wrap
		} // End settings_page

		/** LOADING PUBLIC OR ADMINSITRATION RESOURCES **/

		/**
		 * Load public scripts and styles
		 */
		public function public_resources() {
			global $music_store_settings;
			wp_enqueue_style( 'wp-mediaelement' );
			// wp_enqueue_style( 'wp-mediaelement-skins', 'https://cdnjs.cloudflare.com/ajax/libs/mediaelement/2.23.5/mejs-skins.min.css' );
			wp_enqueue_style( 'wp-mediaelement-skins', plugin_dir_url(__FILE__).'ms-styles/vendors/mejs-skins/mejs-skins.min.css', array(), self::$version );
			wp_enqueue_style( 'ms-style', plugin_dir_url( __FILE__ ) . 'ms-styles/ms-public.css', array( 'wp-mediaelement' ), self::$version );
			wp_enqueue_style( 'ms-buttons', plugin_dir_url( __FILE__ ) . 'ms-styles/ms-buttons.css', array(), self::$version );

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wp-mediaelement' );

			wp_enqueue_script( 'ms-media-script', plugin_dir_url( __FILE__ ) . 'ms-script/codepeople-plugins.js', array( 'wp-mediaelement' ), self::$version );

			// Load resources of layout
			if ( ! empty( $this->layout ) ) {
				if ( ! empty( $this->layout['style_file'] ) ) {
					wp_enqueue_style( 'ms-css-layout', plugin_dir_url( __FILE__ ) . $this->layout['style_file'], array( 'ms-style' ), self::$version );
				}
				if ( ! empty( $this->layout['script_file'] ) ) {
					wp_enqueue_script( 'ms-js-layout', plugin_dir_url( __FILE__ ) . $this->layout['script_file'], array( 'ms-media-script' ), self::$version );
				}
			}

			$play_all = ( isset( $music_store_settings ) && isset( $music_store_settings['ms_play_all'] ) ) ? $music_store_settings['ms_play_all'] : 0;
			$preload = ( isset( $music_store_settings ) && isset( $music_store_settings['ms_preload'] ) ) ? $music_store_settings['ms_preload'] : 0;

			wp_localize_script(
				'ms-media-script',
				'ms_global',
				array(
					'hurl'     => esc_url_raw( MS_H_URL ),
					'play_all' => $play_all,
					'preload'  => $preload,
				)
			);
		} // End public_resources

		/**
		 * Load admin scripts and styles
		 */
		public function admin_resources( $hook ) {
			global $post, $music_store_settings;

			if ( strpos( $hook, 'music-store' ) !== false ) {
				if ( function_exists( 'wp_enqueue_media' ) ) {
					wp_enqueue_media();
				}
				wp_enqueue_script( 'ms-admin-script-chart', plugin_dir_url( __FILE__ ) . 'ms-script/Chart.min.js', array( 'jquery' ), self::$version, true );
				wp_enqueue_script( 'ms-admin-script', plugin_dir_url( __FILE__ ) . 'ms-script/ms-admin.js', array( 'jquery' ), self::$version, true );
				wp_enqueue_style( 'ms-admin-style', plugin_dir_url( __FILE__ ) . 'ms-styles/ms-admin.css', array(), self::$version );
				wp_enqueue_style( 'ms-buttons', plugin_dir_url( __FILE__ ) . 'ms-styles/ms-buttons.css', array(), self::$version );
				wp_localize_script( 'ms-admin-script', 'ms_global', array( 'aurl' => admin_url() ) );
				wp_localize_script( 'ms-admin-script', 'music_store', array( 'cover' => $music_store_settings['ms_pp_cover_size'] ) );
			}
			if ( 'post-new.php' == $hook || 'post.php' == $hook || 'index.php' == $hook ) {
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'jquery-ui-draggable' );
				wp_enqueue_script( 'jquery-ui-droppable' );
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_script( 'ms-admin-script', plugin_dir_url( __FILE__ ) . 'ms-script/ms-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-dialog', 'media-upload' ), self::$version, true );

				// Scripts required for products list insertion

				// Set the variables for insertion dialog
				$tags_l  = '<div title="' . esc_attr__( 'Insert a Products List', 'music-store' ) . '"><div style="padding:20px;">';
				$tags_l .= '<div>' . esc_html__( 'Select the type of list:', 'music-store' ) . '<br /><select style="width:100%;" name="list_type" id="list_type"><option value="top_rated">Top Rated</option><option value="new_products">New Products</option><option value="top_selling">Top Selling</option></select></div>';
				$tags_l .= '<div>' . esc_html__( 'Enter the number of products to show:', 'music-store' ) . '<br /><input style="width:100%;" name="number" id="number" /></div>';
				$tags_l .= '<div>' . esc_html__( 'Enter the number of columns:', 'music-store' ) . '<br /><input style="width:100%;" name="columns" id="columns" /></div>';
				$tags_l .= '</div></div>';

				// Scripts required for counter insertion

				// create an array to hold directory list
				// create a handler for the directory
				$handler      = opendir( MS_CORE_IMAGES_PATH . '/counter' );
				$digit_design = '';
				// open directory and walk through the filenames
				$style_checked = 'CHECKED';
				while ( $file = readdir( $handler ) ) {

					// if file isn't this directory or its parent, add it to the results
					if ( '.' != $file && '..' != $file ) {
						if ( is_dir( MS_CORE_IMAGES_PATH . '/counter/' . $file ) ) {
							$digit_design .= '<input type="radio" name="style" value="' . esc_attr( $file ) . '" ' . $style_checked . ' />';
							$style_checked = '';
							for ( $i = 0; $i < 4; $i++ ) {
								$digit_design .= '<img src="' . esc_url( MS_CORE_IMAGES_URL ) . '/counter/' . $file . '/' . $i . '.gif" />';
							}
							$digit_design .= '<br />';
						}
					}
				}

				// tidy up: close the handler
				closedir( $handler );

				// Set the variables for insertion dialog
				$tags_c  = '<div title="' . esc_attr__( 'Insert the sales counter', 'music-store' ) . '"><div style="padding:20px;">';
				$tags_c .= '<div>' . esc_html__( 'Select the numbers design:', 'music-store' ) . '<br />' . $digit_design . '</div>';
				$tags_c .= '<div>' . esc_html__( 'Enter minimum length of counter:', 'music-store' ) . '<br /><input style="width:100%;" name="min_length" id="min_length" /></div>';
				$tags_c .= '</div></div>';

				if ( isset( $post ) && 'ms_song' == $post->post_type ) {
					// Scripts and styles required for metaboxs
					wp_enqueue_style( 'ms-admin-style', plugin_dir_url( __FILE__ ) . 'ms-styles/ms-admin.css', array(), self::$version );
					wp_localize_script(
						'ms-admin-script',
						'music_store',
						array(
							'post_id' => $post->ID,
							'tags_l'  => $tags_l,
							'tags_c'  => $tags_c,
							'cover'   => $music_store_settings['ms_pp_cover_size'],
						)
					);
				} else {
					// Scripts required for music store insertion
					wp_enqueue_style( 'wp-jquery-ui-dialog' );

					// Set the variables for insertion dialog
					$tags = '';
					// Load genres
					$genre_list = get_terms( 'ms_genre', array( 'hide_empty' => 0 ) );
					// Load artists
					$artist_list = get_terms( 'ms_artist', array( 'hide_empty' => 0 ) );
					// Album
					$album_list = get_terms( 'ms_album', array( 'hide_empty' => 0 ) );

					$tags .= '<div title="' . esc_attr__( 'Insert Music Store', 'music-store' ) . '"><div style="padding:20px;">';

					$tags .= '<div>' . esc_html__( 'Filter results by products type:', 'music-store' ) . '<br /><select id="load" name="load" style="width:100%"><option value="all">' . esc_html__( 'All types', 'music-store' ) . '</option></select><br /><em style="color:#FF0000;">' . esc_html__( 'Filter by product types is only available for commercial version of plugin' ) . '</em></div><div>' . esc_html__( 'Columns:', 'music-store' ) . ' <br /><input type="text" name="columns" id="columns" style="width:100%" value="1" /></div>';

					$tags .= '<div>' . esc_html__( 'Filter results by genre:', 'music-store' ) . '<br /><select id="genre" name="genre" style="width:100%"><option value="all">' . esc_html__( 'All genres', 'music-store' ) . '</option>';

					foreach ( $genre_list as $genre ) {
						$tags .= '<option value="' . esc_attr( $genre->term_id ) . '">' . esc_html( $genre->name ) . '</option>';
					}

					$tags .= '</select></div><div>' . esc_html__( '-or- filter results by artist:', 'music-store' ) . '<br /><select id="artist" name="artist" style="width:100%"><option value="all">' . esc_html__( 'All artists', 'music-store' ) . '</option>';

					foreach ( $artist_list as $artist ) {
						$tags .= '<option value="' . esc_attr( $artist->term_id ) . '">' . esc_html( $artist->name ) . '</option>';
					}
					$tags .= '</select></div><div>' . esc_html__( '-or- filter results by album:', 'music-store' ) . '<br /><select id="album" name="album" style="width:100%"><option value="all">' . esc_html__( 'All albums', 'music-store' ) . '</option>';

					foreach ( $album_list as $album ) {
						$tags .= '<option value="' . esc_attr( $album->term_id ) . '">' . esc_html( $album->name ) . '</option>';
					}
					$tags .= '</select></div></div></div>';

					// Scripts required for product insertion

					// Set the variables for insertion dialog
					$tags_p  = '<div title="' . esc_attr__( 'Insert a Product', 'music-store' ) . '"><div style="padding:20px;">';
					$tags_p .= '<div>' . esc_html__( 'Enter the Song ID:', 'music-store' ) . '<br /><input id="product_id" name="product_id" style="width:100%" /></div>';
					$tags_p .= '</div></div>';

					wp_localize_script(
						'ms-admin-script',
						'music_store',
						array(
							'tags'   => $tags,
							'tags_l' => $tags_l,
							'tags_c' => $tags_c,
							'tags_p' => $tags_p,
						)
					);
				}
			}
		} // End admin_resources


		/** LOADING MUSIC STORE AND ITEMS ON WordPress SECTIONS **/
		/**
		 * Includes the list of payment gateways beside the buynow buttons.
		 */
		public function populate_payment_gateways_list( $tags ) {
			global $music_store_settings;

			$list             = '';
			$payment_gateways = array();
			$payment_gateways = apply_filters( 'musicstore_payment_gateway_list', $payment_gateways );
			$count            = count( $payment_gateways );
			if ( 1 == $count ) {
				$keys  = array_keys( $payment_gateways );
				$list .= '<input type="hidden" name="ms_payment_gateway" value="' . esc_attr( $keys[0] ) . '">';
			} elseif ( 1 < $count ) {
				$list .= '<span class="ms-payment-gateway-label">' . __( 'Payment method', 'music-store' ) . ': </span><span class="ms-payment-gateway"><select aria-label="' . esc_attr( __( 'Payment method', 'music-store' ) ) . '" name="ms_payment_gateway">';
				foreach ( $payment_gateways as $key => $value ) {
					$list .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
				}
				$list .= '</select></span>';
			}

			if ( preg_match( '/<div\s+class="ms-payment-button-container">/', $tags ) ) {
				$tags = preg_replace(
					'/<div\s+class="ms-payment-button-container">/',
					$list . '$0',
					$tags,
					1
				);
			} else {
				$tags = preg_replace(
					'/<input type="(image|submit)"/',
					$list . '<input type="$1"',
					$tags,
					1
				);
			}
			return $tags;
		} // End populate_payment_gateways_list

		/**
		 *  Add custom post type to the search result
		 */
		public function add_post_type_to_results( $query ) {
			global $wpdb;
			if ( $query->is_search ) {
				$not_in          = array();
				$restricted_list = $wpdb->get_results( 'SELECT posts.ID FROM ' . $wpdb->prefix . MSDB_POST_DATA . ' as post_data,' . $wpdb->prefix . "posts as posts  WHERE posts.post_type='ms_song' AND posts.ID=post_data.id AND posts.post_status='publish' AND post_data.as_single=0" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				foreach ( $restricted_list as $restricted ) {
					$not_in[] = $restricted->ID;
				}

				if ( ! empty( $not_in ) ) {
					$query->set( 'post__not_in', $not_in );
				}
			}
			return $query;
		} // End add_post_type_to_results

		/**
		 * Replace the music_store_product shortcode with correct item
		 */
		public function load_product( $atts ) {
			extract( // phpcs:ignore WordPress.PHP.DontExtract
				shortcode_atts(
					array(
						'id'     => '',
						'layout' => 'store',
						/* SHOW/HIDE ELEMENTS */
						'no_cover' 		=> 0,
						'no_popularity'	=> 0,
						'no_artist'		=> 0,
						'no_album'		=> 0,
						'no_genre'		=> 0
					),
					$atts
				)
			);
			$r  = '';
			$id = trim( $id );
			$id = is_numeric( $id ) ? intval( $id ) : 0;
			if ( ! empty( $id ) ) {
				$p = get_post( $id );
				if ( ! empty( $p ) ) {
					if ( 'ms_song' == $p->post_type ) {
						$obj = new MSSong( $p->ID );
					}

					if ( isset( $obj ) ) {
						$tpl    = new music_store_tpleng( MS_FILE_PATH . '/ms-templates/', 'comment' );
						$layout = ( in_array( $layout, array( 'store', 'single', 'multiple' ) ) ) ? $layout : 'store';
						$r      = $obj->display_content( $layout, $tpl, 'return', [
							'no_cover' 		=> $no_cover,
							'no_popularity'	=> $no_popularity,
							'no_artist'		=> $no_artist,
							'no_album'		=> $no_album,
							'no_genre'		=> $no_genre
						] );
					}
				}
			}

			return $r;
		} // End load_product

		/**
		 * Replace the sales_counter shortcode with the correct number
		 */
		public function sales_counter( $atts ) {
			global $wpdb;

			extract( // phpcs:ignore WordPress.PHP.DontExtract
				shortcode_atts(
					array(
						'min_length' => 3,
						'style'      => '',
					),
					$atts
				)
			);
			$min_length = trim( $min_length );
			$min_length = is_numeric( $min_length ) ? intval( $min_length ) : 0;
			$r          = '';

			$_select = 'SELECT SUM(purchases)';
			$_from   = 'FROM ' . $wpdb->prefix . MSDB_POST_DATA;
			$query   = $_select . ' ' . $_from;
			$result  = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$rest = $min_length - strlen( $result );
			if ( $rest > 0 ) {
				for ( $i = 0; $i < $rest; $i++ ) {
					$result = '0' . $result;
				}
			}

			if ( ! empty( $style ) ) {
				$_result_length = strlen( $result );
				for ( $i = 0; $i < $_result_length; $i++ ) {
					$r .= '<img src="' . esc_url( MS_CORE_IMAGES_URL . '/counter/' . $style . '/' . $result[ $i ] . '.gif' ) . '" />';
				}
			} else {
				$r .= $result;
			}

			return '<div class="music-store-sales-counter">' . wp_kses_post( $r ) . '</div>';
		}

		/**
		 * Replace the music_store shortcode with correct items
		 */
		public function load_store( $atts, $content, $tag ) {

			$aux = function( $v ) {
				$result = [];

				$v = strval( $v );
				$v = explode( ',', $v );

				foreach( $v as $v_item ) {
					$v_item = strtolower( trim( $v_item ) );
					if ( empty( $v_item ) ) continue;
					if ( 'all' == $v_item ) return [];
					$result[] = $v_item;
				}

				return $result;
			};

			global $wpdb, $music_store_settings;

			$page_id = 'ms_page_' . get_the_ID();

			if ( ! isset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ] ) ) {
				$GLOBALS[ MS_SESSION_NAME ][ $page_id ] = array();
			}

			// Generated music store
			$music_store   = '';
			$page_links    = '';
			$header        = '';
			$items_summary = '';

			// Extract the music store attributes
			extract( // phpcs:ignore WordPress.PHP.DontExtract
				shortcode_atts(
					array(
						'load'    => 'all',
						'genre'   => 'all',
						'artist'  => 'all',
						'album'   => 'all',
						'columns' => 1,
						'exclude' => '',

						/* SHOW/HIDE ELEMENTS */
						'no_cover' 		=> 0,
						'no_popularity'	=> 0,
						'no_artist'		=> 0,
						'no_album'		=> 0,
						'no_genre'		=> 0
					),
					$atts
				)
			);

			$load 	= trim( $load );
			$genre 	= trim( $genre );
			$artist = trim( $artist );
			$album 	= trim( $album );

			if( empty( $load ) )   $load 	= 'all';
			if( empty( $genre ) )  $genre 	= 'all';
			if( empty( $artist ) ) $artist	= 'all';
			if( empty( $album ) )  $album 	= 'all';

			$genre_ref = [];
			if ( isset( $atts['genre'] ) )  $genre_ref = $aux( $atts['genre'] );

			$artist_ref = [];
			if ( isset( $atts['artist'] ) )  $artist_ref = $aux( $atts['artist'] );

			$album_ref = [];
			if ( isset( $atts['album'] ) )  $album_ref = $aux( $atts['album'] );

			// Extract query_string variables correcting music store attributes
			if (
				isset( $_REQUEST['filter_by_genre'] ) ||
				isset( $_REQUEST['filter_by_artist'] ) ||
				isset( $_REQUEST['filter_by_album'] )
			) {
				unset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_post_type'] );
				unset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_genre'] );
				unset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_artist'] );
				unset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_album'] );
			}

			if ( isset( $_REQUEST['filter_by_type'] ) && in_array( $_REQUEST['filter_by_type'], array( 'all', 'singles' ) ) ) {
				$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_post_type'] = sanitize_text_field( wp_unslash( $_REQUEST['filter_by_type'] ) );
			}

			if ( isset( $_REQUEST['filter_by_genre'] ) ) {
				$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_genre'] = sanitize_text_field( wp_unslash( $_REQUEST['filter_by_genre'] ) );
			}

			if ( isset( $_REQUEST['filter_by_album'] ) ) {
				$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_album'] = sanitize_text_field( wp_unslash( $_REQUEST['filter_by_album'] ) );
			}

			if ( isset( $_REQUEST['filter_by_artist'] ) ) {
				$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_artist'] = sanitize_text_field( wp_unslash( $_REQUEST['filter_by_artist'] ) );
			}

			if ( isset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_post_type'] ) ) {
				$load = $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_post_type'];
			}

			if ( isset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_genre'] ) ) {
				$genre = $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_genre'];
			}

			if ( isset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_album'] ) ) {
				$album = $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_album'];
			}

			if ( isset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_artist'] ) ) {
				$artist = $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_artist'];
			}

			if ( isset( $_REQUEST['ordering_by'] ) && in_array( $_REQUEST['ordering_by'], array( 'popularity', 'price_high_low', 'price_low_high', 'post_title', 'post_date' ) ) ) {
				$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] = sanitize_text_field( wp_unslash( $_REQUEST['ordering_by'] ) );
			} elseif ( ! isset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ) ) {
				$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] = ( isset( $atts['order_by'] ) && in_array( $atts['order_by'], array( 'popularity', 'price_high_low', 'price_low_high', 'post_title', 'post_date' ) ) ) ? $atts['order_by'] : 'post_date';
			}

			// Extract info from music_store options
			$allow_filter_by_genre  = ( isset( $atts['filter_by_genre'] ) ) ? $atts['filter_by_genre'] * 1 : $music_store_settings['ms_filter_by_genre'];
			$allow_filter_by_artist = ( isset( $atts['filter_by_artist'] ) ) ? $atts['filter_by_artist'] * 1 : $music_store_settings['ms_filter_by_artist'];
			$allow_filter_by_album  = ( isset( $atts['filter_by_album'] ) ) ? $atts['filter_by_album'] * 1 : $music_store_settings['ms_filter_by_album'];

			// Items per page
			$items_page = max( $music_store_settings['ms_items_page'], 1 );
			// Display pagination
			$items_page_selector = $music_store_settings['ms_items_page_selector'];

			// Query clauses
			$_select = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT posts.*, posts_data.*';
			$_from   = 'FROM ' . $wpdb->prefix . 'posts as posts,' . $wpdb->prefix . MSDB_POST_DATA . ' as posts_data';
			$_where  = "WHERE posts.ID = posts_data.id AND posts.post_status='publish'";

			// Exclude the products passed as parameters
			$exclude = preg_replace( '/[^\d\,]/', '', $exclude );
			$exclude = trim( $exclude, ',' );
			if ( ! empty( $exclude ) ) {
				$_where .= ' AND posts.ID NOT IN (' . $exclude . ')';
			}

			$_order_by = 'ORDER BY ' . ( ( 'post_title' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] || 'post_date' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ) ? 'posts' : 'posts_data' ) . '.' .
				(
					( 'price_high_low' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ||
						'price_low_high' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering']
					) ? 'price' : $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering']
				) . ' ' . ( ( 'popularity' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] || 'post_date' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] || 'price_high_low' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ) ? 'DESC' : 'ASC' );

			$_limit = '';

			// Load the taxonomy tables
			if ( $genre !== 'all' || ! empty( $genre_ref ) ) {

				$_from .= ", ".$wpdb->prefix."term_taxonomy as taxonomy, ".$wpdb->prefix."term_relationships as term_relationships, ".$wpdb->prefix."terms as terms";

				$_where .= " AND taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id AND term_relationships.object_id=posts.ID AND taxonomy.term_id=terms.term_id ";

				// Search for genres assigned directly to the posts
				$_where .= "AND taxonomy.taxonomy='ms_genre' ";

				if ( $genre != 'all' ) {

					$_where .= "AND (";

					$genre = explode(',', $genre);
					$connector = "";
					foreach ( $genre as $genre_e ) {
						$genre_e = trim($genre_e);
						if ( ! empty( $genre_e ) ) {
							if ( is_numeric( $genre_e ) ) {
								$_where .= $connector."terms.term_id=$genre_e";
							} else {
								$_where .= $connector.$wpdb->prepare("terms.slug=%s", $genre_e);
							}
							$connector = " OR ";
						}
					}

					$_where .= ")";
				}

				if ( ! empty( $genre_ref ) ) {

					$_where .= "AND (";
					$connector = "";
					foreach ( $genre_ref as $genre_e ) {
						$genre_e = trim($genre_e);
						if ( ! empty( $genre_e ) ) {
							if ( is_numeric( $genre_e ) ) {
								$_where .= $connector."terms.term_id=$genre_e";
							} else {
								$_where .= $connector.$wpdb->prepare("terms.slug=%s", $genre_e);
							}
							$connector = " OR ";
						}
					}

					$_where .= ")";
				}
			}

			if ( $artist !== 'all' || ! empty( $artist_ref ) ) {

				$_from .= ", ".$wpdb->prefix."term_taxonomy as taxonomy1, ".$wpdb->prefix."term_relationships as term_relationships1, ".$wpdb->prefix."terms as terms1";

				$_where .= " AND taxonomy1.term_taxonomy_id=term_relationships1.term_taxonomy_id AND term_relationships1.object_id=posts.ID AND taxonomy1.term_id=terms1.term_id ";

				// Search for artist assigned directly to the posts
				$_where .= "AND taxonomy1.taxonomy='ms_artist' ";

				if ( $artist !== 'all' ) {

					$_where .= "AND ";

					if ( is_numeric( $artist ) ) {
						$_where .= "terms1.term_id=$artist";
					} else {
						$_where .= $wpdb->prepare("terms1.slug=%s", $artist);
					}
				}

				if ( ! empty( $artist_ref ) ) {

					$_where .= "AND (";
					$connector = "";
					foreach ( $artist_ref as $artist_e ) {
						$artist_e = trim($artist_e);
						if ( ! empty( $artist_e ) ) {
							if ( is_numeric( $artist_e ) ) {
								$_where .= $connector."terms1.term_id=$artist_e";
							} else {
								$_where .= $connector.$wpdb->prepare("terms1.slug=%s", $artist_e);
							}
							$connector = " OR ";
						}
					}

					$_where .= ")";
				}
			}

			if ( $album !== 'all' || ! empty( $album_ref ) ) {

				$_from .= ", ".$wpdb->prefix."term_taxonomy as taxonomy2, ".$wpdb->prefix."term_relationships as term_relationships2, ".$wpdb->prefix."terms as terms2";

				$_where .= " AND taxonomy2.term_taxonomy_id=term_relationships2.term_taxonomy_id AND term_relationships2.object_id=posts.ID AND taxonomy2.term_id=terms2.term_id ";

				// Search for albums assigned directly to the posts
				$_where .= "AND taxonomy2.taxonomy='ms_album' ";

				if ( $album !== 'all' ) {

					$_where .= "AND ";

					if ( is_numeric( $album ) ) {
						$_where .= "terms2.term_id=$album";
					} else {
						$_where .= $wpdb->prepare("terms2.slug=%s", $album);
					}
				}

				if ( ! empty( $album_ref ) ) {

					$_where .= "AND (";
					$connector = "";
					foreach ( $album_ref as $album_e ) {
						$album_e = trim($album_e);
						if ( ! empty( $album_e ) ) {
							if ( is_numeric( $album_e ) ) {
								$_where .= $connector."terms2.term_id=$album_e";
							} else {
								$_where .= $connector.$wpdb->prepare("terms2.slug=%s", $album_e);
							}
							$connector = " OR ";
						}
					}

					$_where .= ")";
				}
			}
			// End taxonomies

			$_where .= ' AND post_type="ms_song" ';

			// Create pagination section
			if ( $items_page_selector && $items_page ) {
				// Checking for page parameter or get page from session variables
				// Clear the page number if filtering option change
				if ( isset( $_REQUEST['filter_by_type'] ) || isset( $_REQUEST['filter_by_genre'] ) || isset( $_REQUEST['filter_by_artist'] ) ) {
					$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_page_number'] = 0;
				}
				if ( isset( $_GET['page_number'] ) && is_numeric( $_GET['page_number'] ) ) {
					$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_page_number'] = intval( $_GET['page_number'] );
				}
				if ( ! isset( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_page_number'] ) ) {
					$GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_page_number'] = 0;
				}

				$_limit = 'LIMIT ' . ( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_page_number'] * $items_page ) . ", $items_page";

				// Create items section
				$query   = $_select . ' ' . $_from . ' ' . $_where . ' ' . $_order_by . ' ' . $_limit;
				$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				// Get total records for pagination
				$query       = 'SELECT FOUND_ROWS()';
				$total       = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$total_pages = ceil( $total / max( $items_page, 1 ) );

				if ( $total ) {
					$min_in_page = ( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_page_number'] - 1 ) * $items_page + $items_page + 1;
					$max_in_page = min( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_page_number'] * $items_page + $items_page, $total );

					$items_summary = '<div class="music-store-filtering-result">' . $min_in_page . '-' . $max_in_page . ' ' . __( 'of', 'music-store' ) . ' ' . $total . '</div>';
				}

				if ( $total_pages > 1 ) {

					// Make page links
					$page_links .= "<DIV class='music-store-pagination'>";
					$page_href   = '?' . ( ( ! empty( $_SERVER['QUERY_STRING'] ) ) ? preg_replace( '/(&)?page_number=\d+/', '', sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) ) . '&' : '' );

					for ( $i = 0, $h = $total_pages; $i < $h; $i++ ) {
						if ( $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_page_number'] == $i ) {
							$page_links .= "<span class='page-selected'>" . ( $i + 1 ) . '</span> ';
						} else {
							$page_links .= "<a class='page-link' href='" . esc_attr( $page_href ) . 'page_number=' . $i . "'>" . ( $i + 1 ) . '</a> ';
						}
					}
					$page_links .= '</DIV>';
				}
			} else {
				// Create items section
				$query   = $_select . ' ' . $_from . ' ' . $_where . ' ' . $_order_by . ' ' . $_limit;
				$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$tpl = new music_store_tpleng( MS_FILE_PATH . '/ms-templates/', 'comment' );

			$width        = 100 / $columns;
			$music_store .= "<div class='music-store-items'>";
			$item_counter = 0;
			foreach ( $results as $result ) {
				$obj          = new MSSong( $result->ID, (array) $result );
				$music_store .= "<div style='width:{$width}%;' data-width='{$width}%' class='music-store-item'>" . $obj->display_content( 'store', $tpl, 'return', [
					'no_cover' 		=> $no_cover,
					'no_popularity' 	=> $no_popularity,
					'no_artist'		=> $no_artist,
					'no_album'		=> $no_album,
					'no_genre'		=> $no_genre
				] ) . '</div>';
				$item_counter++;
				if ( 0 == $item_counter % $columns ) {
					$music_store .= "<div class='clearer'></div>";
				}
			}
			$music_store .= "<div class='clearer'></div>";
			$music_store .= '</div>';
			$header      .= "
						<form method='get'>
						<div class='music-store-header'>
						";

			foreach ( $_GET as $var => $value ) {
				if ( ! in_array( $var, array( 'filter_by_type', 'filter_by_genre', 'filter_by_artist', 'filter_by_album', 'page_number', 'ordering_by' ) ) ) {
					$header .= "<input type='hidden' name='" . esc_attr( $var ) . "' value='" . esc_attr( sanitize_text_field( wp_unslash( $value ) ) ) . "' />";
				}
			}

			// Create filter section
			if (
				$allow_filter_by_genre ||
				$allow_filter_by_artist ||
				$allow_filter_by_album ||
				! isset( $atts['show_order_by'] ) ||
				$atts['show_order_by'] * 1
			) {
				$header .= "<div class='music-store-filters'>";
				if (
					$allow_filter_by_genre ||
					$allow_filter_by_artist ||
					$allow_filter_by_album
				) {
					$header .= '<span>' . __( 'Filter by: ', 'music-store' ) . '</span>';
				}
				if ( $allow_filter_by_genre ) {
					$header .= "<span><select aria-label='" . esc_attr( __( 'Filter by genre', 'music-store' ) ) . "' id='filter_by_genre' name='filter_by_genre' onchange='this.form.submit();'>
							<option value='all'>" . __( 'All genres', 'music-store' ) . '</option>
							';
					$genres  = get_terms( 'ms_genre' );

					if ( ! is_array( $genre ) ) {
						$genre = array( $genre );
					}

					foreach ( $genres as $genre_item ) {
						if (
							! empty( $genre_ref ) &&
							! in_array( $genre_item->slug, $genre_ref ) &&
							! in_array( $genre_item->term_id, $genre_ref )
						) continue;
						$header .= "<option value='" . esc_attr( $genre_item->slug ) . "' " . ( ( in_array( $genre_item->slug, $genre ) || in_array( $genre_item->term_id, $genre ) ) ? 'SELECTED' : '' ) . '>' . music_store_strip_tags( $genre_item->name, true ) . '</option>';
					}
					$header .= '</select></span>';
				}

				if ( $allow_filter_by_album ) {
					$header .= "<span><select aria-label='" . esc_attr( __( 'Filter by album', 'music-store' ) ) . "' id='filter_by_album' name='filter_by_album' onchange='this.form.submit();'>
							<option value='all'>" . __( 'All albums', 'music-store' ) . '</option>
							';
					$albums  = get_terms( 'ms_album' );
					foreach ( $albums as $album_item ) {
						if (
							! empty( $album_ref ) &&
							! in_array( $album_item->slug, $album_ref ) &&
							! in_array( $album_item->term_id, $album_ref )
						) continue;
						$header .= "<option value='" . esc_attr( $album_item->slug ) . "' " . ( ( $album == $album_item->slug || $album == $album_item->term_id ) ? 'SELECTED' : '' ) . '>' . music_store_strip_tags( $album_item->name, true ) . '</option>';
					}
					$header .= '</select></span>';
				}

				if ( $allow_filter_by_artist ) {
					$header .= "<span><select aria-label='" . esc_attr( __( 'Filter by artist', 'music-store' ) ) . "' id='filter_by_artist' name='filter_by_artist' onchange='this.form.submit();'>
							<option value='all'>" . __( 'All artists', 'music-store' ) . '</option>
							';
					$artists = get_terms( 'ms_artist' );
					foreach ( $artists as $artist_item ) {
						if (
							! empty( $artist_ref ) &&
							! in_array( $artist_item->slug, $artist_ref ) &&
							! in_array( $artist_item->term_id, $artist_ref )
						) continue;
						$header .= "<option value='" . esc_attr( $artist_item->slug ) . "' " . ( ( $artist == $artist_item->slug || $artist == $artist_item->term_id ) ? 'SELECTED' : '' ) . '>' . music_store_strip_tags( $artist_item->name, true ) . '</option>';
					}
					$header .= '</select></span>';
				}
				$header .= '</div>';
				// Create order filter
				if ( ! isset( $atts['show_order_by'] ) || $atts['show_order_by'] * 1 ) {
					$header .= "<div class='music-store-ordering'><span>" .
						__( 'Order by: ', 'music-store' ) .
						"</span><select aria-label='" . esc_attr( __( 'Order by', 'music-store' ) ) . "' id='ordering_by' name='ordering_by' onchange='this.form.submit();'>
										<option value='post_date' " . ( ( 'post_date' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ) ? 'SELECTED' : '' ) . '>' . __( 'Newest', 'music-store' ) . "</option>
										<option value='post_title' " . ( ( 'post_title' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ) ? 'SELECTED' : '' ) . '>' . __( 'Title', 'music-store' ) . "</option>
										<option value='popularity' " . ( ( 'popularity' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ) ? 'SELECTED' : '' ) . '>' . __( 'Popularity', 'music-store' ) . "</option>
										<option value='price_low_high' " . ( ( 'price_low_high' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ) ? 'SELECTED' : '' ) . '>' . __( 'Price: Low to High', 'music-store' ) . "</option>
										<option value='price_high_low' " . ( ( 'price_high_low' == $GLOBALS[ MS_SESSION_NAME ][ $page_id ]['ms_ordering'] ) ? 'SELECTED' : '' ) . '>' . __( 'Price: High to Low', 'music-store' ) . '</option>
									</select>
								</div>';
				}
			}
			$header .= "<div style='clear:both;'></div>
						</div>
						</form>
						";
			return '<div class="music-store">' . $header . $items_summary . $music_store . $page_links . '</div>';
		} // End load_store

		private function _sanitizeAttr( $v ) {
			$v = strtolower( $v );
			$v = preg_replace( '/[^\da-z\-\,_]/', '', $v );
			$v = preg_replace( '/\,+/', ',', $v );
			return ( empty( $v ) ) ? $v : explode( ',', $v );
		} // End _sanitizeAttr

		public function join_post_data_to_WPQuery( $join ) {
			global $wp_query, $wpdb;
			$join .= 'INNER JOIN ' . $wpdb->prefix . MSDB_POST_DATA . " as posts_data ON $wpdb->posts.ID = posts_data.id ";
			return $join;
		}

		public function add_orderby_to_WPQuery( $orderby, $wp_query ) {
			if ( ! empty( $wp_query->query['orderby'] ) ) {
				$comma = ! empty( $orderby ) ? ', ' : '';
				if ( isset( $wp_query->query['orderby']['popularity'] ) ) {
					$orderby = 'posts_data.popularity DESC' . $comma . $orderby;
					$comma   = ', ';
				}
				if ( isset( $wp_query->query['orderby']['purchases'] ) ) {
					$orderby = 'posts_data.purchases DESC' . $comma . $orderby;
				}
			}
			return $orderby;
		}

		/**
		 * Load the list of products purchased by the logged user
		 */
		public function load_purchased_products( $atts, $content = '' ) {
			global $wpdb, $music_store_settings;

			$products_list = '';
			$current_user  = wp_get_current_user();
			if ( 0 != $current_user->ID ) {
				$products_list .= '<h2 class="music-store-purchased-list-header">' . $current_user->first_name . ' ' . $current_user->last_name . ' - ' . __( 'purchased products list', 'music-store' ) . '</h2>';

				// Purchased products
				$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT a.*, b.post_type FROM ' . $wpdb->prefix . MSDB_PURCHASE . ' a INNER JOIN ' . $wpdb->posts . ' b ON (a.product_id = b.ID) WHERE a.buyer_id=%d GROUP BY product_id ORDER BY date DESC', $current_user->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( ! empty( $rows ) ) {
					$this->public_resources();
					$dlurl  = $this->_ms_create_pages( 'ms-download-page', 'Download Page' );
					$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' );

					$products_list .= '<div class="music-store-purchased-items">';

					foreach ( $rows as $row ) {
						if ( 'ms_song' != $row->post_type ) {
							continue;
						}
						$obj = new MSSong( $row->product_id );

						// Check if expired link
						$expired = false;

						$purchase_date = ! empty( $row->checking_date ) ? $row->checking_date : $row->date;
						$days_interval = ( time() - strtotime( $purchase_date ) ) / 86400;

						if (
							! empty( $music_store_settings['ms_old_download_link'] ) &&
							is_numeric( $music_store_settings['ms_old_download_link'] ) &&
							intval( $music_store_settings['ms_old_download_link'] ) < $days_interval
						) {
							$expired = true;
						}

						if (
							! empty( $music_store_settings['ms_downloads_number'] ) &&
							is_numeric( $music_store_settings['ms_downloads_number'] ) &&
							intval( $music_store_settings['ms_downloads_number'] ) <= $row->downloads
						) {
							$expired = true;
						}

						$product_link   = esc_url( music_store_complete_url( get_permalink( $obj->id ) ) );
						$products_list .= '<div class="music-store-purchased-item music-store-song"><div class="left-column">';

						// Cover
						if ( empty( $atts ) || empty( $atts['no_cover'] ) || ! is_numeric( $atts['no_cover'] ) || 0 == intval( $atts['no_cover'] ) ) {
							$cover = ! empty( $obj->cover ) ? $obj->cover : ( ! empty( $music_store_settings['ms_pp_default_cover'] ) ? $music_store_settings['ms_pp_default_cover'] : '' );
							if ( ! empty( $cover ) ) {
								$products_list .= '<div class="music-store-purchased-item-cover"><a href="' . $product_link . '" target="_blank"><img src="' . esc_attr( $cover ) . '" /></a></div>';
							}
						}

						$products_list .= '</div>'; // Close left-column

						// Information
						$products_list .= '<div class="right-column">';

						// Title
						$products_list .= '<div class="music-store-purchased-item-title"><a href="' . $product_link . '" target="_blank">' . $obj->post_title . '</a></div>';

						// Music player
						if ( ! $expired && ( empty( $atts ) || empty( $atts['no_player'] ) || ! is_numeric( $atts['no_player'] ) || 0 == intval( $atts['no_player'] ) ) ) {
							$player_style = esc_attr( $music_store_settings['ms_player_style'] );

							if ( isset( $obj->file ) ) {
								$products_list .= '<div class="ms-player single ' . $player_style . '" style="position:relative">';
								$products_list .= '<audio preload="none" style="width:100%;" class="' . $player_style . '" data-product="' . esc_attr( $obj->ID ) . '"><source src="' . esc_url( $obj->file ) . '" type="audio/' . music_store_get_type( $obj->file ) . '"></audio>';
								$products_list .= '</div>';
							}
						}

						// Purchase date
						$products_list .= '<div class="music-store-purchased-item-date"><span class="label">' . __( 'Purchase date', 'music-store' ) . ':</span> ' . $row->date . '</div>';

						// Payment information
						$products_list .= '<div class="music-store-purchased-item-paid"><span class="label">' . __( 'Paid', 'music-store' ) . ':</span> ' . $music_store_settings['ms_paypal_currency_symbol'] . number_format( $row->amount, 2 ) . ' ' . $music_store_settings['ms_paypal_currency'] . '</div>';

						if ( empty( $atts ) || empty( $atts['no_link'] ) || ! is_numeric( $atts['no_link'] ) || 0 == intval( $atts['no_link'] ) ) {
							$products_list .= '<div class="music-store-purchased-item-download-link"><span class="label">' . __( 'Download', 'music-store' ) . ':</span> <b>' . ( $expired ? __( 'Expired link', 'music-store' ) : '<a href="' . esc_url( $dlurl . 'ms-action=download&purchase_id=' . $row->purchase_id ) . '" target="_blank">' . __( 'Click Here', 'music-store' ) . '</a>' ) . '</b></div>';
						}

						$products_list .= '</div><div style="clear:both;"></div></div>';
					}
					$products_list .= '</div>';
				} else {
					$products_list .= '<p style="text-align:center;">' . __( 'The list of purchased products is empty.', 'music-store' ) . '</p>';
				}
            } else { // Display login dialog
				$products_list = $this->_login_form();
			}

			return $products_list;
		}

		/**
		 * Replace the music_store_product_list shortcode with correct items
		 */
		public function load_product_list( $atts ) {
			global $wpdb;

			extract( // phpcs:ignore WordPress.PHP.DontExtract
				shortcode_atts(
					array(
						'type'          => 'new_products', // new_products, top_rated, top_selling
						'genre'         => '', // ids or slugs separated by comma
						'artist'        => '', // ids or slugs separated by comma
						'album'         => '', // ids or slugs separated by comma
						'columns'       => 1,
						'number'        => 3,
						'exclude'       => '',
						'tax_connector' => 'AND',

						/* SHOW/HIDE ELEMENTS */
						'no_cover' 		=> 0,
						'no_popularity'	=> 0,
						'no_artist'		=> 0,
						'no_album'		=> 0,
						'no_genre'		=> 0
					),
					$atts
				)
			);

			$products_list = '';
			$type          = strtolower( trim( $type ) );
			$genre         = $this->_sanitizeAttr( $genre );
			$artist        = $this->_sanitizeAttr( $artist );
			$album         = $this->_sanitizeAttr( $album );
			$columns       = trim( $columns );
			$columns       = is_numeric( $columns ) ? intval( $columns ) : 0;
			$number        = trim( $number );
			$number        = is_numeric( $number ) ? intval( $number ) : 0;

			$args = array(
				'post_type'      => array( 'ms_song' ),
				'posts_per_page' => $number,
				'post_status'    => 'publish',
			);

			$tax_query = array();
			if ( ! empty( $genre ) ) {
				$tax_query['relation'] = $tax_connector;
				$tax_query[]           = array(
					'taxonomy' => 'ms_genre',
					'field'    => ( is_numeric( $genre[0] ) ) ? 'term_id' : 'slug',
					'terms'    => $genre,
				);
			}

			if ( ! empty( $artist ) ) {
				$tax_query['relation'] = $tax_connector;
				$tax_query[]           = array(
					'taxonomy' => 'ms_artist',
					'field'    => ( is_numeric( $artist[0] ) ) ? 'term_id' : 'slug',
					'terms'    => $artist,
				);
			}

			if ( ! empty( $album ) ) {
				$tax_query['relation'] = $tax_connector;
				$tax_query[]           = array(
					'taxonomy' => 'ms_album',
					'field'    => ( is_numeric( $album[0] ) ) ? 'term_id' : 'slug',
					'terms'    => $album,
				);
			}

			if ( ! empty( $tax_query ) ) {
				$args['tax_query'] = $tax_query;
			}
			if ( ! empty( $exclude ) ) {
				$args['post__not_in'] = explode( ',', $exclude );
			}

			switch ( $type ) {
				case 'top_rated':
					$args['orderby'] = array(
						'popularity' => 'DESC',
						'post_date'  => 'DESC',
					);
					break;
				case 'top_selling':
					$args['orderby'] = array(
						'purchases' => 'DESC',
						'post_date' => 'DESC',
					);
					break;
				default:
					$args['orderby'] = array( 'post_date' => 'DESC' );
					break;
			}

			add_filter( 'posts_join', array( $this, 'join_post_data_to_WPQuery' ) );
			add_filter( 'posts_orderby', array( $this, 'add_orderby_to_WPQuery' ), 10, 2 );
			$results = new WP_Query( $args );
			remove_filter( 'posts_join', array( $this, 'join_post_data_to_WPQuery' ) );
			remove_filter( 'posts_orderby', array( $this, 'add_orderby_to_WPQuery' ) );

			if ( $results->have_posts() ) {
				$tpl            = new music_store_tpleng( MS_FILE_PATH . '/ms-templates/', 'comment' );
				$width          = floor( 100 / min( $columns, max( $results->found_posts, 1 ) ) );
				$products_list .= "<div class='music-store-items'>";
				$item_counter   = 0;
				while ( $results->have_posts() ) {
					$results->the_post();
					$obj            = new MSSong( $results->post->ID );
					$products_list .= "<div style='width:{$width}%;' class='music-store-item'>" . $obj->display_content( 'store', $tpl, 'return', [
						'no_cover' 		=> $no_cover,
						'no_popularity'	=> $no_popularity,
						'no_artist'		=> $no_artist,
						'no_album'		=> $no_album,
						'no_genre'		=> $no_genre
					] ) . '</div>';
					$item_counter++;
					if ( 0 == $item_counter % $columns ) {
						$products_list .= "<div style='clear:both;'></div>";
					}
				}
				$products_list .= "<div style='clear:both;'></div>";
				$products_list .= '</div>';
			}
			wp_reset_postdata();
			return $products_list;
		}

		/** MODIFY CONTENT OF POSTS LOADED **/

		/*
		 * Load the music store templates for songs display
		 */
		public function load_templates() {
			add_filter( 'the_content', array( &$this, 'display_content' ), 1 );
		} // End load_templates

		/**
		 * Display content of songs through templates
		 */
		public function display_content( $content ) {
			global $post;
			if (
				/* in_the_loop() &&  */
				$post &&
				'ms_song' == $post->post_type
			) {
				remove_filter( 'the_content', 'wpautop' );
				remove_filter( 'the_excerpt', 'wpautop' );
				remove_filter( 'comment_text', 'wpautop', 30 );
				$tpl  = new music_store_tpleng( MS_FILE_PATH . '/ms-templates/', 'comment' );
				$song = new MSSong( $post->ID );
				return $song->display_content( ( ( is_singular() ) ? 'single' : 'multiple' ), $tpl, 'return' );
			} else {
				return $content;
			}
		} // End display_content


		/**
		 * Set a media button for music store insertion
		 */
		public function set_music_store_button() {
			global $post;

			if ( isset( $post ) && 'ms_song' != $post->post_type ) {
				print '<a href="javascript:open_insertion_music_store_window();" title="' . esc_attr__( 'Insert Music Store', 'music-store' ) . '"><img src="' . esc_url( MS_CORE_IMAGES_URL . '/music-store-icon.png' ) . '" alt="' . esc_attr__( 'Insert Music Store', 'music-store' ) . '" /></a>';

				print '<a href="javascript:open_insertion_music_store_product_window();" title="' . esc_attr__( 'Insert a product', 'music-store' ) . '"><img src="' . esc_url( MS_CORE_IMAGES_URL . '/music-store-product-icon.png' ) . '" alt="' . esc_attr__( 'Insert a product', 'music-store' ) . '" /></a>';
			}

			print '<a href="javascript:open_insertion_music_store_product_list_window();" title="' . esc_attr__( 'Insert a products list', 'music-store' ) . '"><img src="' . esc_url( MS_CORE_IMAGES_URL . '/music-store-product-list-icon.png' ) . '" alt="' . esc_attr__( 'Insert a products list', 'music-store' ) . '" /></a>';

			print '<a href="javascript:insert_music_store_sales_counter();" title="' . esc_attr__( 'Insert a sales counter', 'music-store' ) . '"><img src="' . esc_url( MS_CORE_IMAGES_URL . '/music-store-sales-counter-icon.png' ) . '" alt="' . esc_attr__( 'Insert a sales counter', 'music-store' ) . '" /></a>';
		} // End set_music_store_button


		/**
		 *  Check for post to delete and remove the metadata saved on additional metadata tables
		 */
		public function delete_post( $pid ) {
			global $wpdb;
			if (
				$wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . MSDB_POST_DATA . ' WHERE id=%d;', $pid ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			) {
				return $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . MSDB_POST_DATA . ' WHERE id=%d;', $pid ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			return false;
		} // End delete_post

		/** SEARCHING METHODS **/

		public function custom_search_where( $where ) {
			global $wpdb;
			if ( is_search() && get_search_query() ) {
				$where .= " OR ((t.name LIKE '%" . get_search_query() . "%' OR t.slug LIKE '%" . get_search_query() . "%') AND tt.taxonomy IN ('ms_artist', 'ms_album', 'ms_genre') AND {$wpdb->posts}.post_status = 'publish')";
			}
			return $where;
		}

		public function custom_search_join( $join ) {
			global $wpdb;
			if ( is_search() && get_search_query() ) {
				$join .= " LEFT JOIN ({$wpdb->term_relationships} tr INNER JOIN ({$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id) ON tr.term_taxonomy_id = tt.term_taxonomy_id) ON {$wpdb->posts}.ID = tr.object_id ";
			}
			return $join;
		}

		public function custom_search_groupby( $groupby ) {
			global $wpdb;

			// we need to group on post ID
			$groupby_id = "{$wpdb->posts}.ID";
			if ( ! is_search() || strpos( $groupby, $groupby_id ) !== false || ! get_search_query() ) {
				return $groupby;
			}
			// groupby was empty, use ours
			if ( ! strlen( trim( $groupby ) ) ) {
				return $groupby_id;
			}
			// wasn't empty, append ours
			return $groupby . ', ' . $groupby_id;
		}

		/** TROUBLESHOOT METHODS **/

		public static function troubleshoot( $option ) {
			if ( ! is_admin() ) {
				// Solves a conflict caused by the "Speed Booster Pack" plugin
				if ( is_array( $option ) && isset( $option['jquery_to_footer'] ) ) {
					unset( $option['jquery_to_footer'] );
				}
				if ( is_array( $option ) && isset( $option['sbp_css_async'] ) ) {
					unset( $option['sbp_css_async'] );
				}
			}
			return $option;
		} // End troubleshoot

		/**
		 * Prevent conflicts with third party plugins that manage the websites cache
		 */
		private function _reject_cache_uris() {
			if ( is_admin() ) {
				return;
			}

			// For WP Super Cache plugin
			global     $cache_rejected_uri;
			if ( ! empty( $cache_rejected_uri ) ) {
				$cache_rejected_uri[] = 'ms-download-page';
			}
		} // End _reject_cache_uris

	} // End MusicStore class

	define( 'MS_SESSION_NAME', 'ms_session_20200815' );
	if ( ! function_exists( 'ms_start_session' ) ) {
		function ms_start_session() {
			$GLOBALS[ MS_SESSION_NAME ] = array();
			$set_cookie                 = true;
			if ( isset( $_COOKIE[ MS_SESSION_NAME ] ) ) {
				$GLOBALS['MS_SESSION_ID'] = sanitize_text_field( wp_unslash( $_COOKIE[ MS_SESSION_NAME ] ) );
				$_stored_session          = get_transient( $GLOBALS['MS_SESSION_ID'] );
				if ( false !== $_stored_session ) {
					$GLOBALS[ MS_SESSION_NAME ] = $_stored_session;
					$set_cookie                 = false;
				}
			}

			if ( $set_cookie ) {
				$GLOBALS['MS_SESSION_ID'] = sanitize_key( uniqid( '', true ) );
				if ( ! headers_sent() ) {
					@setcookie( MS_SESSION_NAME, $GLOBALS['MS_SESSION_ID'], 0, '/' );
				}
			}
		}
		ms_start_session();
	}

	if ( ! function_exists( 'ms_session_dump' ) ) {
		function ms_session_dump() {
			if ( count( $GLOBALS[ MS_SESSION_NAME ] ) ) {
				set_transient( $GLOBALS['MS_SESSION_ID'], $GLOBALS[ MS_SESSION_NAME ], 12 * 60 * 60 );
			}
			delete_expired_transients( true );
		}
		add_action( 'shutdown', 'ms_session_dump', 99, 0 );
	}

	$GLOBALS['music_store'] = new MusicStore();

	register_activation_hook( __FILE__, array( &$GLOBALS['music_store'], 'register' ) );
	add_action( 'activated_plugin', array( &$GLOBALS['music_store'], 'redirect_to_settings' ), 10, 2 );
	add_action( 'wpmu_new_blog', array( &$GLOBALS['music_store'], 'install_new_blog' ), 10, 6 );
} // Class exists check
