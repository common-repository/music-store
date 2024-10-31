<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }
if ( ! class_exists( 'MSSong' ) ) {
	class MSSong {
		/*
		* @var integer
		*/
		private $id;

		/*
		* @var object
		*/
		private $song_data = array();
		private $post_data = array();
		private $artist    = array();
		private $album     = array();
		private $genre     = array();

		/**
		 * MSSong constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( $id, $data = array() ) {
			global $wpdb;

			$this->id = $id;

			if ( empty( $data ) ) {
				// Read general data
				$data = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . MSDB_POST_DATA . ' WHERE id=%d', array( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( $data ) {
					$this->song_data = (array) $data;
				}
				$this->post_data = get_post( $id, 'ARRAY_A' );
			} else {
				$this->song_data = $data;
				$this->post_data = $data;
			}

			// Read artist list
			$this->artist = (array) wp_get_object_terms( $id, 'ms_artist', array( 'orderby' => 'term_order' ) );

			// Read album list
			$this->album = (array) wp_get_object_terms( $id, 'ms_album', array( 'orderby' => 'term_order' ) );

			// Read associated genres
			$this->genre = (array) wp_get_object_terms( $id, 'ms_genre' );

		} // End __construct

		public function __get( $name ) {
			switch ( $name ) {
				case 'genre':
					return music_store_strip_tags( $this->genre );
				break;
				case 'artist':
					return music_store_strip_tags( $this->artist );
				break;
				case 'album':
					return music_store_strip_tags( $this->album );
				break;
				case 'cover':
					if ( ! empty( $this->song_data[ $name ] ) ) {
						return $this->get_file_url( $this->song_data[ $name ] );
					} elseif ( false !== ( $thumbnail = get_the_post_thumbnail_url( $this->id, 'medium' ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
						return $thumbnail;
					} else {
						return null;
					}
					break;
				case 'file':
				case 'demo':
					if ( isset( $this->song_data[ $name ] ) ) {
						return $this->get_file_url( $this->song_data[ $name ] );
					} else {
						return null;
					}
					break;
				default:
					if ( isset( $this->song_data[ $name ] ) ) {
						if ( 'post_title' == $name && empty( $this->song_data[ $name ] ) ) {
							return $this->id;
						}
						return music_store_strip_tags( $this->song_data[ $name ] );
					} elseif ( isset( $this->post_data[ $name ] ) ) {
						if ( 'post_title' == $name && empty( $this->post_data[ $name ] ) ) {
							return $this->id;
						}
						return music_store_strip_tags( $this->post_data[ $name ] );
					} else {
						return null;
					}
			} // End switch
		} // End __get

		public function __set( $name, $value ) {
			global $wpdb;

			if (
				isset( $this->song_data[ $name ] ) &&
				$wpdb->update(
					$wpdb->prefix . MSDB_POST_DATA,
					array( $name => $value ),
					array( 'id' => $this->id )
				)
			) {
				$this->song_data[ $name ] = $value;
			}
		} // End __set

		public function __isset( $name ) {
			return isset( $this->song_data[ $name ] ) || isset( $this->post_data[ $name ] );
		} // End __isset

		/*
		* Display content
		*/
		public function get_file_url( $url ) {
			if ( preg_match( '/attachment_id=(\d+)/', $url, $matches ) ) {
				return wp_get_attachment_url( $matches[1] );
			}
			return $url;
		} // End get_file_url

		public function get_audio_tag( $mode, &$player_style ) {
			global $music_store_settings;
			$demo = $this->demo;

			if ( ! empty( $demo ) ) {
				$player_style = $music_store_settings['ms_player_style'];
				$demo         = apply_filters( 'musicstore_demo_url', $demo, $this->file, $this->id );
				return apply_filters( 'musicstore_song_audio_tag', '<audio ' . ( 'single' == $mode ? 'style="width:100%;"' : '' ) . ' class="' . esc_attr( $player_style ) . '" preload="none" data-product="' . $this->ID . '"><source src="' . $demo . '" type="audio/' . music_store_get_type( $demo ) . '" /></audio>', $this->id );
			}

			return '';
		} // End get_audio_tag

		public function get_price() {
			global $music_store_settings;
			$price = ! empty( $this->price ) ? $this->price : 0;

			if ( empty( $music_store_settings['ms_woocommerce_integration'] ) ) {
				$price = music_store_apply_taxes( $price );
			}

			return max( floatval( $price ), 0 );
		} // End get_price

		public function display_content( $mode, $tpl_engine, $output = 'echo', $args = [] ) {
			global $music_store_settings;

			$auxiliary = function( $attr ) use ($args) {
				return ! ( isset( $args[ $attr ] ) && $args[ $attr ] );
			};

			$currency_symbol = $music_store_settings['ms_paypal_currency_symbol'];
			$ms_main_page    = esc_url( music_store_complete_url( $music_store_settings['ms_main_page'] ) );
			$url_symbol      = ( strpos( $ms_main_page, '?' ) === false ) ? '?' : '&';

			$reviews = MS_REVIEW::get_review( $this->id );

			$song_arr = array(
				'id'                => $this->id,
				'title'             => apply_filters( 'musicstore_song_title', $this->post_title, $this->id ),
				'link'              => esc_url( music_store_complete_url( get_permalink( $this->id ) ) ),
				'popularity'        => ( $music_store_settings['ms_popularity']  && $auxiliary( 'no_popularity' ) ) ? ( ( $reviews && ! empty( $reviews['average'] ) && is_numeric( $reviews['average'] ) ) ? intval( $reviews['average'] ) : 0 ) : null,
				'votes'             => apply_filters( 'musicstore_song_votes', ( $reviews && ! empty( $reviews['votes'] ) && is_numeric( $reviews['votes'] ) ) ? intval( $reviews['votes'] ) : 0, $this->id ),
				'cover'             => null,
				'alt'               => esc_attr( $this->post_title ),
				'social'            => null,
				'facebook_app_id'   => null,
				'price'             => null,
				'year'              => null,
				'isrc'              => null,
				'has_albums'        => null,
				'has_artists'       => null,
				'has_genres'        => null,
				'demo'              => null,
				'salesbutton'       => '',

				// Labels
				'albums_label'      => __( 'Album(s)', 'music-store' ),
				'genres_label'      => __( 'Genre(s)', 'music-store' ),
				'duration_label'    => __( 'Duration', 'music-store' ),
				'year_label'        => __( 'Year', 'music-store' ),
				'isrc_label'        => __( 'ISRC', 'music-store' ),
				'description_label' => __( 'Description', 'music-store' ),
				'more_label'        => __( 'More Info', 'music-store' ),
				'store_page_label'  => __( 'Go to the store page', 'music-store' ),
				'get_back_label'    => __( 'Get back', 'music-store' ),
				'popularity_label'  => __( 'popularity', 'music-store' ),
				'price_label'       => __( 'Price', 'music-store' ),
			);

			$taxonomies = array();

			if ( ! empty( $this->cover ) && $auxiliary( 'no_cover' ) ) {
				$song_arr['cover'] = apply_filters( 'musicstore_song_cover_url', esc_url( $this->cover ), $this->id );
			} elseif ( ! empty( $music_store_settings['ms_pp_default_cover'] ) && $auxiliary( 'no_cover' ) ) {
				$song_arr['cover'] = esc_url( $music_store_settings['ms_pp_default_cover'] );
			}
			if ( $this->time ) {
				$song_arr['time'] = apply_filters( 'musicstore_song_time', strip_tags( html_entity_decode( $this->time ) ), $this->id );
			}
			if ( $this->year ) {
				$song_arr['year'] = apply_filters( 'musicstore_song_year', ( is_numeric( $this->year ) ? intval( $this->year ) : 0 ), $this->id );
			}
			if ( $this->isrc ) {
				$song_arr['isrc'] = apply_filters( 'musicstore_song_isrc', strip_tags( html_entity_decode( $this->isrc ) ), $this->id );
			}
			if ( $this->info ) {
				$song_arr['info'] = apply_filters( 'musicstore_song_info', esc_url( $this->info ), $this->id );
			}

			if ( $music_store_settings['ms_social_buttons'] ) {
				$song_arr['social'] = $song_arr['link'];
			}

			if ( ! empty( $music_store_settings['ms_facebook_app_id'] ) ) {
				$song_arr['facebook_app_id'] = $music_store_settings['ms_facebook_app_id'];
			}

			if ( count( $this->artist ) && $auxiliary( 'no_artist' ) ) {
				$taxonomies['artist']    = array();
				$song_arr['has_artists'] = true;
				$artists                 = array();
				foreach ( $this->artist as $artist ) {
					$taxonomies['artist'][] = $artist->slug;
					$link                   = get_term_link( $artist );
					if ( ! empty( $ms_main_page ) ) {
						$link = $ms_main_page . $url_symbol . 'filter_by_artist=' . $artist->slug;
					}
					$artists[] = array( 'data' => '<a href="' . music_store_complete_url( $link ) . '">' . music_store_strip_tags( $artist->name ) . '</a>' );
				}
				$tpl_engine->set_loop( 'artists', $artists );
			}

			if ( apply_filters( 'musicstore_payment_gateway_enabled', false ) ) {
				$paypal_enabled = true;
			} else {
				$paypal_enabled = false;
			}

			if ( ! empty( $this->file ) ) {
				if ( $paypal_enabled && ! empty( $this->price ) ) {
					$_price = $this->get_price();
					$song_arr['price'] = ! empty( $currency_symbol ) ? $currency_symbol . sprintf( '%.2f', $_price ) : sprintf( '%.2f', $_price ) . $music_store_settings['ms_paypal_currency'];
					if (
						false == $music_store_settings['ms_buy_button_for_registered_only'] ||
						is_user_logged_in()
					) {
						$paypal_button           = $music_store_settings['ms_paypal_button'];
						$song_arr['salesbutton'] = apply_filters(
							'musicstore_buynow_button',
							'<form action="' . esc_url( MS_H_URL ) . '" method="post"><input type="hidden" name="ms-action" value="buynow" /><input type="hidden" name="ms_product_type" value="single" /><input type="hidden" name="ms_product_id" value="' . esc_attr( $this->id ) . '" />' .
							$GLOBALS['music_store']->get_paypal_button(
								array(
									'button' => $music_store_settings['ms_paypal_button'],
									'attrs'  => 'onclick="return ms_buy_now(this, ' . $this->ID . ');"',
								)
							) .
							'</form>',
							$this->id
						);
					}
				} elseif (
					(
						false == $music_store_settings['ms_hide_download_link_for_price_in_blank'] &&
						(
							false == $music_store_settings['ms_download_link_for_registered_only'] ||
							is_user_logged_in()
						)
					) ||
					(
						true == $music_store_settings['ms_hide_download_link_for_price_in_blank'] &&
						true == $music_store_settings['ms_download_link_for_registered_only'] &&
						is_user_logged_in()
					)
				) {
					$song_arr['salesbutton'] = apply_filters(
						'musicstore_download_button',
						'<a href="' . esc_url( $this->file ) . '" target="_blank" data-id="' . esc_attr( $this->id ) . '" class="ms-download-link">' . __( 'Download', 'music-store' ) . '</a>' . ( ( ! empty( $music_store_settings['ms_license_for_free'] ) ) ? '|<a href="' . esc_url( $music_store_settings['ms_license_for_free'] ) . '" target="_blank">' . __( 'License', 'music-store' ) . '</a>' : '' ),
						$this->id
					);
				}
			}

			$song_arr['demo'] = $this->get_audio_tag( $mode, $player_style );
			if ( ! empty( $song_arr['demo'] ) ) {
				$song_arr['player_style'] = $player_style;
			}

			if ( 'store' == $mode || 'multiple' == $mode ) {
				if ( 'store' == $mode ) {
					$tpl_engine->set_file( 'song', 'song.tpl.html' );
				} else {
					$tpl_engine->set_file( 'song', 'song_multiple.tpl.html' );
				}

				$tpl_engine->set_var( 'song', $song_arr );
			} elseif ( 'single' == $mode ) {
				$tpl_engine->set_file( 'song', 'song_single.tpl.html' );
				if ( $ms_main_page ) {
					$song_arr['store_page'] = $ms_main_page;
				}

				if ( strlen( $this->post_content ) ) {
					$song_arr['description'] = apply_filters( 'musicstore_song_content', '<p>' . preg_replace( '/[\n\r]+/', '</p><p>', $this->post_content ) . '</p>', $this->id );
				}

				if ( count( $this->genre ) && $auxiliary( 'no_genre' ) ) {
					$taxonomies['genre']    = array();
					$song_arr['has_genres'] = true;
					$genres                 = array();
					foreach ( $this->genre as $genre ) {
						$taxonomies['genre'][] = $genre->slug;
						$link                  = get_term_link( $genre );
						if ( ! empty( $ms_main_page ) ) {
							$link = $ms_main_page . $url_symbol . 'filter_by_genre=' . $genre->slug;
						}
						$genres[] = array( 'data' => '<a href="' . esc_url( music_store_complete_url( $link ) ) . '">' . music_store_strip_tags( $genre->name ) . '</a>' );
					}
					$tpl_engine->set_loop( 'genres', $genres );
				}

				if ( count( $this->album ) && $auxiliary( 'no_album' ) ) {
					$taxonomies['album']    = array();
					$song_arr['has_albums'] = true;
					$albums                 = array();
					foreach ( $this->album as $album ) {
						$taxonomies['album'][] = $album->slug;
						$link                  = get_term_link( $album );
						if ( ! empty( $ms_main_page ) ) {
							$link = $ms_main_page . $url_symbol . 'filter_by_album=' . $album->slug;
						}
						$albums[] = array( 'data' => '<a href="' . esc_url( music_store_complete_url( $link ) ) . '">' . music_store_strip_tags( $album->name ) . '</a>' );
					}
					$tpl_engine->set_loop( 'albums', $albums );
				}

				$tpl_engine->set_var( 'song', $song_arr );

				if ( $music_store_settings['ms_pp_related_products'] ) {
					$args = array(
						'exclude'       => $this->ID,
						'number'        => $music_store_settings['ms_pp_related_products_number'],
						'columns'       => $music_store_settings['ms_pp_related_products_columns'],
						'tax_connector' => 'OR',
					);

					if (
						in_array( 'genre', $music_store_settings['ms_pp_related_products_by'] ) &&
						! empty( $taxonomies['genre'] )
					) {
						$args['genre'] = implode( ',', $taxonomies['genre'] );
					}

					if (
						in_array( 'artis', $music_store_settings['ms_pp_related_products_by'] ) &&
						! empty( $taxonomies['artist'] )
					) {
						$args['artist'] = implode( ',', $taxonomies['artist'] );
					}

					if (
						in_array( 'album', $music_store_settings['ms_pp_related_products_by'] ) &&
						! empty( $taxonomies['album'] )
					) {
						$args['album'] = implode( ',', $taxonomies['album'] );
					}

					$related_products = $GLOBALS['music_store']->load_product_list( $args );
					if (
						strpos( $related_products, 'music-store-song' ) !== false
					) {
						$related_products = '<section class="music-store-related-products"><h2>' . __( 'Related Products', 'music-store' ) . '</h2>' . $related_products . '</section>';
					} else {
						$related_products = '';
					}
				}
			}

			// Custom fields
			$custom_fields = get_post_custom( $this->id );
			$hidden_field  = '_';
			foreach ( $custom_fields as $key => $value ) {
				if ( ! empty( $value ) ) {
					$pos = strpos( $key, $hidden_field );
					if ( false !== $pos && 0 == $pos ) {
						continue;
					} elseif ( is_array( $value ) && 1 == count( $value ) ) {
						$custom_fields[ $key ] = $value[0];
					}

					if ( is_array( $custom_fields[ $key ] ) ) {
						$tpl_engine->set_loop( $key, $custom_fields[ $key ] );
					} else {
						$tpl_engine->set_var( $key, $custom_fields[ $key ] );
					}
				}
			}

			return $tpl_engine->parse( 'song', $output ) . ( ! empty( $related_products ) ? $related_products : '' );
		} // End display

		public static function permalink_settings() {           ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th><label><?php esc_html_e( 'Song permalink', 'music-store' ); ?></label></th>
						<td>
							<input name="ms_song_permalink" id="ms_song_permalink" type="text" value="<?php echo esc_attr( self::get_permalink() ); ?>" class="regular-text code"> <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'music-store' ); ?></span>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		} // End permalinks_settings

		public static function get_permalink() {
			return get_option( 'ms_song_permalink', 'ms_song' );
		} // End get_permalink

		public static function save_permalink() {
			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-permalink' ) && isset( $_POST['ms_song_permalink'] ) ) {
				$permalink = music_store_sanitize_permalink( wp_unslash( $_POST['ms_song_permalink'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				if ( empty( $permalink ) ) {
					$permalink = 'ms_song';
				}
				update_option( 'ms_song_permalink', $permalink );
			}
		} // End get_permalink

		/*
		* Class method print_metabox, for metabox generation print
		*
		* @return void
		*/
		public static function print_metabox() {
			global $wpdb, $post, $music_store_settings;

			$query = 'SELECT * FROM ' . $wpdb->prefix . MSDB_POST_DATA . " as data WHERE data.id = {$post->ID};";
			$data  = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$artist_post_list = wp_get_object_terms( $post->ID, 'ms_artist', array( 'orderby' => 'term_order' ) );
			$artist_list      = get_terms(
				'ms_artist',
				array(
					'hide_empty' => 0,
					'orderby'    => 'name',
				)
			);

			$album_post_list = wp_get_object_terms( $post->ID, 'ms_album', array( 'orderby' => 'term_order' ) );
			$album_list      = get_terms(
				'ms_album',
				array(
					'hide_empty' => 0,
					'orderby'    => 'name',
				)
			);

			wp_nonce_field( plugin_basename( __FILE__ ), 'ms_song_box_content_nonce' );
			$currency = $music_store_settings['ms_paypal_currency'];
			if ( ! empty( $GLOBALS[ MS_SESSION_NAME ]['ms_errors'] ) ) {
				echo '<div class="music-store-error-mssg">' . implode( '<br>', music_store_strip_tags( $GLOBALS[ MS_SESSION_NAME ]['ms_errors'] ) ) // @codingStandardsIgnoreLine
				. '</div>';
				unset( $GLOBALS[ MS_SESSION_NAME ]['ms_errors'] );
			}
			echo '
				<table class="form-table product-data">
					<tr>
						<td valign="top" style="border-top:2px solid purple;border-left:2px solid purple;border-bottom:2px solid purple;">
							' . esc_html__( 'Sales price:', 'music-store' ) . '
						</td>
						<td style="border-top:2px solid purple;border-right:2px solid purple;border-bottom:2px solid purple;">
							<input type="text" name="ms_price" id="ms_price" value="' . ( ( $data && $data->price ) ? esc_attr( sprintf( '%.2f', $data->price ) ) : '' ) . '" />
							' . esc_html( ( $currency ) ? $currency : '' ) . '
                            <span class="ms_more_info_hndl" style="margin-left: 10px;"><a href="javascript:void(0);" onclick="ms_display_more_info( this );">[ + more information]</a></span>
                            <div class="ms_more_info">
                                <p>If leave empty the product\'s prices (standard and exclusive prices), the Music Store assumes the product will be distributed for free, and displays a download link in place of the button for purchasing</p>
                                <a href="javascript:void(0)" onclick="ms_hide_more_info( this );">[ + less information]</a>
                            </div>
						</td>
					</tr>
					<tr>
						<td valign="top">
							' . esc_html__( 'Sales price (Exclusively):', 'music-store' ) . '
						</td>
						<td>
							<input type="text" disabled />
							' . esc_html( ( $currency ) ? $currency : '' ) . '
							<span class="ms_more_info_hndl" style="margin-left: 10px;"><a href="javascript:void(0);" onclick="ms_display_more_info( this );">[ + more information]</a></span>
                            <div class="ms_more_info">
                                <p>Allows purchase the product exclusively, removing the product from the store</p>
                                <a href="javascript:void(0)" onclick="ms_hide_more_info( this );">[ + less information]</a>
                            </div>
							<br /><em style="color:#FF0000;">' . esc_html__( 'The exclusive sales are available only in the commercial version of the plugin', 'music-store' ) . '</em>
						</td>
					</tr>
					<tr>
					<tr>
						<td style="border-top:2px solid purple;border-left:2px solid purple;">
							' . esc_html__( 'Sell as a single:', 'music-store' ) . '
						</td>
						<td style="border-top:2px solid purple;border-right:2px solid purple;">
							<input type="checkbox" name="ms_as_single" id="ms_as_price" CHECKED DISABLED /> <em style="color:#FF0000;">The commercial version of the plugin allows the sale of audio as a single, or only as part of a collection</em>
						</td>
					</tr>
					<tr>
						<td style="border-left:2px solid purple;border-bottom:2px solid purple;">
							' . esc_html__( 'Audio file for sale:', 'music-store' ) . '
						</td>
						<td style="border-right:2px solid purple;border-bottom:2px solid purple;">
							<input type="text" name="ms_file_path" class="file_path" id="ms_file_path" value="' . ( ( $data && $data->file ) ? esc_attr( $data->file ) : '' ) . '" placeholder="' . esc_attr__( 'File path/URL', 'music-store' ) . '" /> <input type="button" class="button_for_upload button" value="' . esc_attr__( 'Upload a file', 'music-store' ) . '" />
						</td>
					</tr>
					<tr>
						<td>
							' . esc_html__( 'Audio file for demo:', 'music-store' ) . '
						</td>
						<td>
							<input type="text" name="ms_demo_file_path" id="ms_demo_file_path" class="file_path"  value="' . ( ( $data && $data->demo ) ? esc_attr( $data->demo ) : '' ) . '" placeholder="' . esc_attr__( 'File path/URL', 'music-store' ) . '" /> <input type="button" class="button_for_upload button" value="' . esc_attr__( 'Upload a file', 'music-store' ) . '" /><br />
							<input type="checkbox" name="ms_protect" id="ms_protect" disabled />
							' . esc_html__( 'Protect the file', 'music-store' ) . '<em style="color:#FF0000;">' . esc_html__( 'The protection of audio files is only available for commercial version of plugin', 'music-store' ) . '</em><br><br>
							<em>' . esc_html__( 'For MIDI files, the protection option is not available, the audio file would be played completely.' ) . '</em>
						</td>
					</tr>
					<tr>
						<td valign="top">
							' . esc_html__( 'Artist:', 'music-store' ) . '
						</td>
						<td><ul id="ms_artist_list">';

			if ( $artist_post_list ) {
				foreach ( $artist_post_list as $artist ) {
					echo '<li class="ms-property-container"><input type="hidden" name="ms_artist[]" value="' . esc_attr( $artist->name ) . '" /><input type="button" onclick="ms_remove(this);" class="button" value="' . esc_attr( $artist->name ) . ' [x]"></li>';
				}
			}
						echo '</ul><div style="clear:both;"><select onchange="ms_select_element(this, \'ms_artist_list\', \'ms_artist\');"><option value="none">' . esc_html__( 'Select an Artist', 'music-store' ) . '</option>';
			if ( $artist_list ) {
				foreach ( $artist_list as $artist ) {
					echo '<option value="' . esc_attr( $artist->name ) . '">' . esc_html( music_store_strip_tags( $artist->name, true ) ) . '</option>';
				}
			}
						echo '
								 </select>
								 <input type="text" id="new_artist" placeholder="' . esc_attr__( 'Enter a new artist', 'music-store' ) . '">
								 <input type="button" value="' . esc_attr__( 'Add artist', 'music-store' ) . '" class="button" onclick="ms_add_element(\'new_artist\', \'ms_artist_list\', \'ms_artist_new\');"/><br />
								 <span class="ms-comment">' . esc_html__( 'Select an Artist from the list or enter new one', 'music-store' ) . '</span>
							</div>
						</td>
					</tr>
					<tr>
						<td valign="top" style="white-space:nowrap;">
							' . esc_html__( 'Album including the song:', 'music-store' ) . '
						</td>
						<td style="width:100%;"><ul id="ms_album_list">';
			if ( $album_post_list ) {
				foreach ( $album_post_list as $album ) {
					echo '<li class="ms-property-container"><input type="hidden" name="ms_album[]" value="' . esc_attr( $album->name ) . '" /><input type="button" onclick="ms_remove(this);" class="button" value="' . esc_attr( $album->name ) . ' [x]"></li>';
				}
			}
						echo '</ul><div style="clear:both;"><select onchange="ms_select_element(this, \'ms_album_list\', \'ms_album\');"><option value="none">' . esc_html__( 'Select an Album', 'music-store' ) . '</option>';

			if ( $album_list ) {
				foreach ( $album_list as $album ) {
					echo '<option value="' . esc_attr( $album->name ) . '">' . esc_html( music_store_strip_tags( $album->name, true ) ) . '</option>';
				}
			}
						echo '
								 </select>
								 <input type="text" id="new_album" placeholder="' . esc_attr__( 'Enter a new album', 'music-store' ) . '">
								 <input type="button" value="' . esc_attr__( 'Add album', 'music-store' ) . '" class="button" onclick="ms_add_element(\'new_album\', \'ms_album_list\', \'ms_album_new\');" /><br />
								 <span class="ms-comment">' . esc_html__( 'Select an Album from the list or enter new one', 'music-store' ) . '</span>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							' . esc_html__( 'Cover:', 'music-store' ) . '
						</td>
						<td>
							<input type="text" name="ms_cover" class="file_path" id="ms_cover" value="' . ( ( $data && $data->cover ) ? esc_attr( $data->cover ) : '' ) . '" placeholder="' . esc_attr__( 'File path/URL', 'music-store' ) . '" /> <input type="button" class="button_for_upload button" value="' . esc_attr__( 'Upload a file', 'music-store' ) . '" />
						</td>
					</tr>
					<tr>
						<td>
							' . esc_html__( 'Duration:', 'music-store' ) . '
						</td>
						<td>
							<input type="text" name="ms_time" id="ms_time" value="' . ( ( $data && $data->time ) ? esc_attr( $data->time ) : '' ) . '" /> <span class="ms-comment">' . esc_html__( 'For example 00:00', 'music-store' ) . '</span>
						</td>
					</tr>
					<tr>
						<td>
							' . esc_html__( 'Publication Year:', 'music-store' ) . '
						</td>
						<td>
							<input type="text" name="ms_year" id="ms_year" value="' . ( ( $data && $data->year ) ? esc_attr( $data->year ) : '' ) . '" /> <span class="ms-comment">' . esc_html__( 'For example 1999', 'music-store' ) . '</span>
						</td>
					</tr>
					<tr>
						<td>
							' . esc_html__( 'ISRC:', 'music-store' ) . '
						</td>
						<td>
							<input type="text" name="ms_isrc" id="ms_isrc" value="' . ( ( $data && $data->isrc ) ? esc_attr( $data->isrc ) : '' ) . '" /> <span class="ms-comment">' . esc_html__( 'Format: CC-XXX-YY-NNNNN', 'music-store' ) . '</span>
						</td>
					</tr>
					<tr>
						<td>
							' . esc_html__( 'Additional information:', 'music-store' ) . '
						</td>
						<td>
							<input type="text" name="ms_info" id="ms_info" value="' . ( ( $data && $data->info ) ? esc_attr( $data->info ) : '' ) . '" placeholder="' . esc_attr__( 'Page URL', 'music-store' ) . '" /> <span class="ms-comment">' . esc_html__( 'Different webpage with additional information', 'music-store' ) . '</span>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
								To get commercial version of Music Store, <a href="http://musicstore.dwbooster.com" target="_blank">CLICK HERE</a><br />
								For reporting an issue or to request a customization, <a href="http://musicstore.dwbooster.com/contact-us" target="_blank">CLICK HERE</a>
							</p>
						</td>
					</tr>
				</table>
			';
		} // End print_metabox

		public static function print_discount_metabox() {
			global $music_store_settings;

			$currency = $music_store_settings['ms_paypal_currency'];
			?>
			<em style="color:#FF0000;"><?php esc_html_e( 'The discounts are only available for commercial version of plugin' ); ?></em>
			<h4><?php esc_html_e( 'Scheduled Discounts', 'music-store' ); ?></h4>
			<table class="form-table ms_discount_table" style="border:1px dotted #dfdfdf;">
				<tr>
					<td style="font-weight:bold;"><?php esc_html_e( 'New price in ' . $currency, 'music-store' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?></td>
					<td style="font-weight:bold;"><?php esc_html_e( 'Valid from dd/mm/yyyy', 'music-store' ); ?></td>
					<td style="font-weight:bold;"><?php esc_html_e( 'Valid to dd/mm/yyyy', 'music-store' ); ?></td>
					<td style="font-weight:bold;"><?php esc_html_e( 'Promotional text', 'music-store' ); ?></td>
					<td style="font-weight:bold;"><?php esc_html_e( 'Status', 'music-store' ); ?></td>
					<td></td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'New price (*)', 'music-store' ); ?></th>
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
				<tr><td colspan="2"><input type="button" class="button" value="<?php esc_attr_e( 'Add/Update Discount', 'music-store' ); ?>" DISABLED /></td></tr>
			</table>
			<?php
		} // End print_discount_metabox

		/*
		* Save the song data
		*
		* @access public
		* @return void
		*/
		public static function save_data( $post ) {
			global $wpdb, $ms_errors;

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( empty( $_POST['ms_song_box_content_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ms_song_box_content_nonce'] ) ), plugin_basename( __FILE__ ) ) ) {
				return;
			}

			$id = $post->ID;

			if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $id ) ) {
					return;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $id ) ) {
					return;
				}
			}

			$file_path      = isset( $_POST['ms_file_path'] ) ? esc_url_raw( sanitize_text_field( wp_unslash( $_POST['ms_file_path'] ) ) ) : '';
			$demo_file_path = isset( $_POST['ms_demo_file_path'] ) ? esc_url_raw( sanitize_text_field( wp_unslash( $_POST['ms_demo_file_path'] ) ) ) : '';
			$cover          = isset( $_POST['ms_cover'] ) ? esc_url_raw( sanitize_text_field( wp_unslash( $_POST['ms_cover'] ) ) ) : '';

			if ( ! empty( $file_path ) && ! music_store_mime_type_accepted( $file_path ) ) {
				music_store_setError( esc_html__( 'Invalid file type for selling.', 'music-store' ) );
				$file_path = '';
			}

			if ( ! empty( $demo_file_path ) && ! music_store_mime_type_accepted( $demo_file_path ) ) {
				music_store_setError( esc_html__( 'Invalid file type for demo.', 'music-store' ) );
				$demo_file_path = '';
			}

			if ( ! empty( $cover ) && ! music_store_mime_type_accepted( $cover ) ) {
				music_store_setError( esc_html__( 'Invalid file type for cover.', 'music-store' ) );
				$cover = '';
			}

			$GLOBALS[ MS_SESSION_NAME ]['ms_errors'] = $ms_errors;

			$data   = array(
				'time'      => isset( $_POST['ms_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ms_time'] ) ) : '',
				'file'      => $file_path,
				'demo'      => $demo_file_path,
				'protect'   => ( isset( $_POST['ms_protect'] ) ) ? 1 : 0,
				'as_single' => 1,
				'info'      => isset( $_POST['ms_info'] ) ? esc_url_raw( sanitize_text_field( wp_unslash( $_POST['ms_info'] ) ) ) : '',
				'cover'     => $cover,
				'price'     => isset( $_POST['ms_price'] ) && is_numeric( $_POST['ms_price'] ) ? floatval( $_POST['ms_price'] ) : 0,
				'year'      => isset( $_POST['ms_year'] ) && is_numeric( $_POST['ms_year'] ) ? intval( $_POST['ms_year'] ) : 0,
				'isrc'      => isset( $_POST['ms_isrc'] ) ? sanitize_text_field( wp_unslash( $_POST['ms_isrc'] ) ) : '',
			);
			$format = array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' );
			$table  = $wpdb->prefix . MSDB_POST_DATA;
			if (
				0 < $wpdb->get_var(
					$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE id=%d;", $id ) // phpcs:ignore WordPress.DB.PreparedSQL
				)
			) {
				// Set an update query
				$wpdb->update(
					$table,
					$data,
					array( 'id' => $id ),
					$format,
					array( '%d' )
				);

			} else {
				// Set an insert query
				$data['id'] = $id;
				$wpdb->insert(
					$table,
					$data,
					$format
				);

			}

			// Clear the artist and album lists and then set the new ones
			wp_set_object_terms( $id, null, 'ms_artist' );
			wp_set_object_terms( $id, null, 'ms_album' );

			// Set the artists list
			if ( isset( $_POST['ms_artist'] ) ) {
				if ( is_array( $_POST['ms_artist'] ) ) {
					$term_order = 0;
					foreach ( $_POST['ms_artist'] as $artist ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
						$artist           = sanitize_text_field( wp_unslash( $artist ) );
						$term_taxonomy_id = wp_set_object_terms( $id, $artist, 'ms_artist', true );
						$wpdb->update(
							$wpdb->term_relationships,
							array(
								'term_order' => $term_order,
							),
							array(
								'term_taxonomy_id' => $term_taxonomy_id[0],
								'object_id'        => $id,
							),
							array( '%d' ),
							array( '%d', '%d' )
						);
						$term_order++;
					}
				} else {
					wp_set_object_terms( $id, sanitize_text_field( wp_unslash( $_POST['ms_artist'] ) ), 'ms_artist', true );
				}
			}

			if ( isset( $_POST['ms_artist_new'] ) ) {
				if ( is_array( $_POST['ms_artist_new'] ) ) {
					$_POST['ms_artist_new'] = array_map( 'wp_unslash', $_POST['ms_artist_new'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$_POST['ms_artist_new'] = array_map( 'sanitize_text_field', $_POST['ms_artist_new'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				} else {
					$_POST['ms_artist_new'] = sanitize_text_field( wp_unslash( $_POST['ms_artist_new'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				}
				wp_set_object_terms( $id, $_POST['ms_artist_new'], 'ms_artist', true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}

			// Set the album list
			if ( isset( $_POST['ms_album'] ) ) {
				$term_order = 0;
				foreach ( $_POST['ms_album'] as $album ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$album            = sanitize_text_field( wp_unslash( $album ) );
					$term_taxonomy_id = wp_set_object_terms( $id, $album, 'ms_album', true );
					$wpdb->update(
						$wpdb->term_relationships,
						array(
							'term_order' => $term_order,
						),
						array(
							'term_taxonomy_id' => $term_taxonomy_id[0],
							'object_id'        => $id,
						),
						array( '%d' ),
						array( '%d', '%d' )
					);
					$term_order++;
				}
			}

			if ( isset( $_POST['ms_album_new'] ) ) {
				if ( is_array( $_POST['ms_album_new'] ) ) {
					$_POST['ms_album_new'] = array_map( 'wp_unslash', $_POST['ms_album_new'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$_POST['ms_album_new'] = array_map( 'sanitize_text_field', $_POST['ms_album_new'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				} else {
					$_POST['ms_album_new'] = sanitize_text_field( wp_unslash( $_POST['ms_album_new'] ) );
				}
				wp_set_object_terms( $id, $_POST['ms_album_new'], 'ms_album', true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}

		} // End save_data

		/*
		* Create the list of properties to display of songs
		* @param array
		* @return array
		*/
		public static function columns( $columns ) {
			$song_columns = array(
				'cb'         => '<input type="checkbox" />',
				'id'         => __( 'Song Id', 'music-store' ),
				'title'      => __( 'Song Name', 'music-store' ),
				'artist'     => __( 'Artists', 'music-store' ),
				'album'      => __( 'Albums', 'music-store' ),
				'genre'      => __( 'Genres', 'music-store' ),
				'popularity' => __( 'Popularity', 'music-store' ),
				'plays'      => __( 'Plays', 'music-store' ),
				'purchases'  => __( 'Purchases', 'music-store' ),
				'date'       => __( 'Date', 'music-store' ),
			);
			if ( wp_is_mobile() ) {
				unset( $song_columns['id'] );
			}

			return $song_columns;
		} // End columns

		/*
		* Extrat the songs data for song list
		*/
		public static function columns_data( $column ) {
			global $post;
			$obj = new MSSong( $post->ID );

			switch ( $column ) {
				case 'artist':
					echo esc_html( music_store_extract_attr_as_str( $obj->artist, 'name', ', ' ) );
					break;
				case 'id':
					echo esc_html( $post->ID );
					break;
				case 'album':
					echo esc_html( music_store_extract_attr_as_str( $obj->album, 'name', ', ' ) );
					break;
				case 'genre':
					echo esc_html( music_store_extract_attr_as_str( $obj->genre, 'name', ', ' ) );
					break;
				case 'plays':
					echo esc_html( $obj->plays );
					break;
				case 'popularity':
					echo esc_html( $obj->popularity );
					break;
				case 'purchases':
					echo esc_html( $obj->purchases );
					break;
			} // End switch
		} // End columns_data

	}//end class
} // Class exists check
