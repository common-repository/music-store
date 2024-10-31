<?php
class CPMSBeaver extends FLBuilderModule {
	public function __construct() {
		 $modules_dir = dirname( __FILE__ ) . '/';
		$modules_url  = plugins_url( '/', __FILE__ ) . '/';

		parent::__construct(
			array(
				'name'            => __( 'Music Store', 'music-store' ),
				'description'     => __( 'Insert the store shortcode', 'music-store' ),
				'group'           => __( 'Music Store', 'music-store' ),
				'category'        => __( 'Music Store', 'music-store' ),
				'dir'             => $modules_dir,
				'url'             => $modules_url,
				'partial_refresh' => true,
			)
		);
	}
}
