<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

if ( ! class_exists( 'MSSong_WC' ) ) {
	class MSSong_WC extends WC_Product {

		private $_song;
		private $product_type = 'ms_song';
		protected $data       = array(
			'name'               => '',
			'slug'               => '',
			'date_created'       => null,
			'date_modified'      => null,
			'status'             => false,
			'featured'           => false,
			'catalog_visibility' => 'visible',
			'description'        => '',
			'short_description'  => '',
			'sku'                => '',
			'price'              => '',
			'regular_price'      => '',
			'sale_price'         => '',
			'date_on_sale_from'  => null,
			'date_on_sale_to'    => null,
			'total_sales'        => '0',
			'tax_status'         => 'taxable',
			'tax_class'          => '',
			'manage_stock'       => false,
			'stock_quantity'     => null,
			'stock_status'       => 'instock',
			'backorders'         => 'no',
			'low_stock_amount'   => '',
			'sold_individually'  => false,
			'weight'             => '',
			'length'             => '',
			'width'              => '',
			'height'             => '',
			'upsell_ids'         => array(),
			'cross_sell_ids'     => array(),
			'parent_id'          => 0,
			'reviews_allowed'    => true,
			'purchase_note'      => '',
			'attributes'         => array(),
			'default_attributes' => array(),
			'menu_order'         => 0,
			'post_password'      => '',
			'virtual'            => false,
			'downloadable'       => false,
			'category_ids'       => array(),
			'tag_ids'            => array(),
			'shipping_class_id'  => 0,
			'downloads'          => array(),
			'image_id'           => '',
			'gallery_image_ids'  => array(),
			'download_limit'     => -1,
			'download_expiry'    => -1,
			'rating_counts'      => array(),
			'average_rating'     => 0,
			'review_count'       => 0,
		);

		public function __construct( $product, $deprecated = array() ) {
			if ( is_object( $product ) ) {
				$product_id = $product->get_id();
			} else {
				$product_id = $product;
			}

			parent::__construct();

			$this->_song                = new MSSong( $product_id );
			$this->id                   = $product_id;
			$this->data['name']         = ! empty( $this->_song->post_title ) ? $this->_song->post_title : $product_id;
			$this->data['virtual']      = true;
			$this->data['downloadable'] = true;
			$this->data['price']        = wc_format_decimal( $this->_song->get_price() );
			$this->data['status']       = $this->_song->post_status;

			$downloads = array();
			$file      = $this->_song->file;
			if ( ! empty( $file ) ) {
				$file_key  = md5( $file );
				$file_name = basename( $file );

				$download_object = new WC_Product_Download();
				$download_object->set_id( $file_key );
				$download_object->set_name( $file_name );
				$download_object->set_file( $file );
				$download_object->set_enabled( true );
				$downloads[] = $download_object;
			}

			$this->data['downloads'] = $downloads;

		} // End __construct

		public function __get( $name ) {
			if ( property_exists( $this->_song, $name ) ) {
				return $this->_song->$name;
			}

		} // End __get

		public function is_purchasable() {
			return true;
		} // End is_purchasable

		public function get_type() {
			return $this->product_type;
		} // End get_type

		public function get_id() {
			return $this->id;
		} // End get_id

		public function get_price( $context = 'view' ) {
			return $this->data['price'];
		} // End get_price

		public function set_price( $price = 0 ) {
			$this->data['price'] = floatval( $price );
		} // End set_price

		public function get_sale_price( $context = 'view' ) {
			return $this->data['sale_price'];
		} // End get_price

		public function get_downloadable( $context = 'view' ) {
			return true;
		} // end get_downloadable

		public function get_downloads( $context = 'view' ) {
			return $this->data['downloads'];
		} // end get_downloads

		public function get_image( $size = 'woocommerce_thumbnail', $attr = array(), $placeholder = true ) {
			$size_data = wc_get_image_size( 'woocommerce_thumbnail' );
			$style     = '';
			if ( ! empty( $size_data ) && ! empty( $size_data['width'] ) ) {
				$style = 'style="max-width:' . esc_attr( $size_data['width'] ) . 'px;"';
			}

			global $music_store_settings;

			if ( ! empty( $this->_song->cover ) ) {
				return '<img src="' . esc_attr( $this->_song->cover ) . '" ' . $style . ' />';
			}

			if ( ! empty( $music_store_settings['ms_pp_default_cover'] ) ) {
				return '<img src="' . $music_store_settings['ms_pp_default_cover'] . '" ' . $style . ' />';
			}

			return '';

		} // End get_image

		public function get_permalink() {
			return get_permalink( $this->_song->ID );
		} // End get_permalink

		public function is_sold_individually() {
			return true;
		} // End is_sold_individually

	} // End MSSong_WC

}
