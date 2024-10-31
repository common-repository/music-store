<?php
require_once dirname( __FILE__ ) . '/ms/ms.pb.php';

FLBuilder::register_module(
	'CPMSBeaver',
	array(
		'cpms-tab' => array(
			'title'    => __( 'Music Store', 'music-store' ),
			'sections' => array(
				'cpms-section' => array(
					'title'  => __( 'Store\'s Attributes', 'music-store' ),
					'fields' => array(
						'columns'    => array(
							'type'        => 'text',
							'label'       => __( 'Number of Columns', 'music-store' ),
							'description' => __( 'Number of columns to distribute the products in the store\'s pages', 'music-store' ),
						),
						'attributes' => array(
							'type'        => 'text',
							'label'       => __( 'Additional attributes', 'music-store' ),
							'description' => '<a href="https://musicstore.dwbooster.com/documentation#music-store-shortcode" target="_blank">' . __( 'Click here to know the complete list of attributes', 'music-store' ) . '</a>',
						),
					),
				),
			),
		),
	)
);
