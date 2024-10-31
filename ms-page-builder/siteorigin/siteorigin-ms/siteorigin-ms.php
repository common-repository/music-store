<?php
/*
Widget Name: Music Store
Description: Inserts the Music Store shortcode.
Documentation: https://musicstore.dwbooster.com/documentation#music-store-shortcode
*/

class SiteOrigin_MusicStore extends SiteOrigin_Widget {

	public function __construct() {
		parent::__construct(
			'siteorigin-music-store',
			__( 'Music Store', 'music-store' ),
			array(
				'description'   => __( 'Inserts the Music Store shortcode', 'music-store' ),
				'panels_groups' => array( 'music-store' ),
				'help'          => 'https://musicstore.dwbooster.com/documentation#music-store-shortcode',
			),
			array(),
			array(
				'exclude' => array(
					'type'  => 'text',
					'label' => __( 'Enter the id of products to exclude', 'music-store' ),
				),
				'columns' => array(
					'type'    => 'number',
					'label'   => __( 'Number of columns', 'music-store' ),
					'default' => 2,
				),
				'genres'  => array(
					'type'  => 'text',
					'label' => __( "Enter the genres' ids or slugs separated by comma to restrict the store's products to these genres. All genres by default.", 'music-store' ),
				),
				'artists' => array(
					'type'  => 'text',
					'label' => __( "Enter the artists' ids or slugs separated by comma to restrict the store's products to these artists. All artists by default.", 'music-store' ),
				),
				'albums'  => array(
					'type'  => 'text',
					'label' => __( "Enter the albums' ids or slugs separated by comma to restrict the store's products to these albums. All albums by default.", 'music-store' ),
				),
			),
			plugin_dir_path( __FILE__ )
		);
	} // End __construct

	public function get_template_name( $instance ) {
		return 'siteorigin-ms-shortcode';
	} // End get_template_name

	public function get_style_name( $instance ) {
		return '';
	} // End get_style_name

} // End Class SiteOrigin_MusicStore

// Registering the widget
siteorigin_widget_register( 'siteorigin-music-store', __FILE__, 'SiteOrigin_MusicStore' );
