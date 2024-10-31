<?php
/*
Widget Name: Music Store Product
Description: Inserts a product's shortcode.
Documentation: https://musicstore.dwbooster.com/documentation#product-shortcode
*/

class SiteOrigin_MusicStore_Product extends SiteOrigin_Widget {

	public function __construct() {
		parent::__construct(
			'siteorigin-musicstore-product',
			__( 'Music Store Product', 'music-store' ),
			array(
				'description'   => __( 'Inserts the Product shortcode', 'music-store' ),
				'panels_groups' => array( 'music-store' ),
				'help'          => 'https://musicstore.dwbooster.com/documentation#product-shortcode',
			),
			array(),
			array(
				'product' => array(
					'type'  => 'number',
					'label' => __( "Enter the product's id", 'music-store' ),
				),
				'layout'  => array(
					'type'    => 'select',
					'label'   => __( "Select the product's layout", 'music-store' ),
					'default' => 'store',
					'options' => array(
						'store'  => __( 'Short', 'music-store' ),
						'single' => __( 'Completed', 'music-store' ),
					),
				),
			),
			plugin_dir_path( __FILE__ )
		);
	} // End __construct

	public function get_template_name( $instance ) {
		return 'siteorigin-ms-product-shortcode';
	} // End get_template_name

	public function get_style_name( $instance ) {
		return '';
	} // End get_style_name

} // End Class SiteOrigin_MusicStore_Product

// Registering the widget
siteorigin_widget_register( 'siteorigin-musicstore-product', __FILE__, 'SiteOrigin_MusicStore_Product' );
