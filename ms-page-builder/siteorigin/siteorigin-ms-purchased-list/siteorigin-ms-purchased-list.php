<?php
/*
Widget Name: Music Store Purchased List
Description: Inserts the purchased list shortcode.
Documentation: https://musicstore.dwbooster.com/documentation#music-store-shortcode
*/

class SiteOrigin_MusicStore_Purchased_List extends SiteOrigin_Widget {

	public function __construct() {
		parent::__construct(
			'siteorigin-musicstore-purchased-list',
			__( 'Music Store Purchased List', 'music-store' ),
			array(
				'description'   => __( 'Inserts the purchased list shortcode', 'music-store' ),
				'panels_groups' => array( 'music-store' ),
				'help'          => 'https://musicstore.dwbooster.com/documentation#purchased-list-shortcode',
			),
			array(),
			array(),
			plugin_dir_path( __FILE__ )
		);
	} // End __construct

	public function get_template_name( $instance ) {
		return 'siteorigin-ms-purchased-list-shortcode';
	} // End get_template_name

	public function get_style_name( $instance ) {
		return '';
	} // End get_style_name

} // End Class SiteOrigin_MusicStore_Sopping_Cart

// Registering the widget
siteorigin_widget_register( 'siteorigin-musicstore-purchased-list', __FILE__, 'SiteOrigin_MusicStore_Purchased_List' );
