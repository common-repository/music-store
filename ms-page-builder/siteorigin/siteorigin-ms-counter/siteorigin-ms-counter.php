<?php
/*
Widget Name: Music Store Sales Counter
Description: Inserts the sales counter shortcode.
Documentation: https://musicstore.dwbooster.com/documentation#music-store-shortcode
*/

class SiteOrigin_MusicStore_Counter extends SiteOrigin_Widget {

	public function __construct() {
		parent::__construct(
			'siteorigin-musicstore-counter',
			__( 'Music Store Sales Counter', 'music-store' ),
			array(
				'description'   => __( 'Inserts the Sales Counter Shortcode', 'music-store' ),
				'panels_groups' => array( 'music-store' ),
				'help'          => 'https://musicstore.dwbooster.com/documentation#counter-shortcode',
			),
			array(),
			array(
				'style'  => array(
					'type'    => 'radio',
					'label'   => __( 'Numbers Styles', 'music-store' ),
					'options' => array(
						__( 'Style One', 'music-store' ),
						__( 'Style Two', 'music-store' ),
					),
					'default' => 0,
				),
				'digits' => array(
					'type'    => 'number',
					'label'   => __( 'Number of digits in the counter, default 3', 'music-store' ),
					'default' => 3,
				),
			),
			plugin_dir_path( __FILE__ )
		);
	} // End __construct

	public function get_template_name( $instance ) {
		return 'siteorigin-ms-counter-shortcode';
	} // End get_template_name

	public function get_style_name( $instance ) {
		return '';
	} // End get_style_name

} // End Class SiteOrigin_MusicStore_Counter

// Registering the widget
siteorigin_widget_register( 'siteorigin-musicstore-counter', __FILE__, 'SiteOrigin_MusicStore_Counter' );
