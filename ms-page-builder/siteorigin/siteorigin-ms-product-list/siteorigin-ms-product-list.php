<?php
/*
Widget Name: Music Store Products List
Description: Inserts the products list shortcode.
Documentation: https://musicstore.dwbooster.com/documentation#products-list-shortcode
*/

class SiteOrigin_MusicStore_Products_List extends SiteOrigin_Widget {

	public function __construct() {
		parent::__construct(
			'siteorigin-musicstore-products-list',
			__( 'Music Store Products List', 'music-store' ),
			array(
				'description'   => __( 'Inserts the Products List shortcode', 'music-store' ),
				'panels_groups' => array( 'music-store' ),
				'help'          => 'https://musicstore.dwbooster.com/documentation#products-list-shortcode',
			),
			array(),
			array(
				'list_type' => array(
					'type'    => 'select',
					'label'   => __( 'List the products', 'music-store' ),
					'options' => array(
						'new_products' => __( 'New products', 'music-store' ),
						'top_rated'    => __( 'Top rated products', 'music-store' ),
						'top_selling'  => __( 'Most sold products', 'music-store' ),
					),
					'default' => 'top_rated',
				),
				'number'    => array(
					'type'    => 'number',
					'label'   => __( 'Enter the number of products', 'music-store' ),
					'default' => 3,
				),
				'columns'   => array(
					'type'    => 'number',
					'label'   => __( 'Number of columns', 'music-store' ),
					'default' => 2,
				),
				'genres'    => array(
					'type'  => 'text',
					'label' => __( "Enter the genres' ids or slugs separated by comma to restrict the store's products to these genres. All genres by default.", 'music-store' ),
				),
				'artists'   => array(
					'type'  => 'text',
					'label' => __( "Enter the artists' ids or slugs separated by comma to restrict the store's products to these artists. All artists by default.", 'music-store' ),
				),
				'albums'    => array(
					'type'  => 'text',
					'label' => __( "Enter the albums' ids or slugs separated by comma to restrict the store's products to these albums. All albums by default.", 'music-store' ),
				),
			),
			plugin_dir_path( __FILE__ )
		);
	} // End __construct

	public function get_template_name( $instance ) {
		return 'siteorigin-ms-products-list-shortcode';
	} // End get_template_name

	public function get_style_name( $instance ) {
		return '';
	} // End get_style_name

} // End Class SiteOrigin_MusicStore_Products_List

// Registering the widget
siteorigin_widget_register( 'siteorigin-musicstore-products-list', __FILE__, 'SiteOrigin_MusicStore_Products_List' );
