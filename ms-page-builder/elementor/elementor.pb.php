<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Elementor_Music_Store_Widget extends Widget_Base {

	public function get_name() {
		return 'music-store';
	} // End get_name

	public function get_title() {
		return 'Music Store';
	} // End get_title

	public function get_icon() {
		return 'eicon-cart-solid';
	} // End get_icon

	public function get_categories() {
		return array( 'music-store-cat' );
	} // End get_categories

	public function is_reload_preview_required() {
		return true;
	} // End is_reload_preview_required

	protected function register_controls() {
		$this->start_controls_section(
			'ms_section',
			array(
				'label' => __( 'Music Store', 'music-store' ),
			)
		);

		$this->add_control(
			'exclude',
			array(
				'label'       => __( 'Enter the id of products to exclude', 'music-store' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'classes'     => 'ms-widefat',
				'description' => '<i>' . __( 'Enter the id of products to exclude from the store, separated by comma.', 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'       => __( 'Number of columns', 'music-store' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 2,
				'classes'     => 'ms-widefat',
				'description' => '<i>' . __( 'Enter the number of columns, one column by default.', 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'genres',
			array(
				'label'       => __( 'Genres', 'music-store' ),
				'type'        => Controls_Manager::TEXT,
				'classes'     => 'ms-widefat',
				'description' => '<i>' . __( "Enter the genres' ids or slugs separated by comma to restrict the store's products to these genres. All genres by default.", 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'artists',
			array(
				'label'       => __( 'Artists', 'music-store' ),
				'type'        => Controls_Manager::TEXT,
				'classes'     => 'ms-widefat',
				'description' => '<i>' . __( "Enter the artists' ids or slugs separated by comma to restrict the store's products to these artists. All artists by default.", 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'albums',
			array(
				'label'       => __( 'Albums', 'music-store' ),
				'type'        => Controls_Manager::TEXT,
				'classes'     => 'ms-widefat',
				'description' => '<i>' . __( "Enter the albums' ids or slugs separated by comma to restrict the store's products to these albums. All albums by default.", 'music-store' ) . '</i>',
			)
		);

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode() {
		 $attr    = '';
		$settings = $this->get_settings_for_display();

		$attr .= ' load="all"';

		$exclude = trim( $settings['exclude'] );
		$exclude = preg_replace( '/[^\d\,]/', '', $exclude );
		$exclude = trim( $exclude, ',' );
		if ( ! empty( $exclude ) ) {
			$attr .= ' exclude="' . esc_attr( $exclude ) . '"';
		}

		$columns = ! empty( $settings['columns'] ) ? trim( $settings['columns'] ) : 1;
		$columns = is_numeric( $columns ) ? max( 1, intval( $columns ) ) : 1;
		if ( ! empty( $columns ) ) {
			$attr .= ' columns="' . esc_attr( $columns ) . '"';
		}

		$genres = sanitize_text_field( $settings['genres'] );
		if ( ! empty( $genres ) ) {
			$attr .= ' genre="' . esc_attr( $genres ) . '"';
		}

		$artists = sanitize_text_field( $settings['artists'] );
		if ( ! empty( $artists ) ) {
			$attr .= ' artist="' . esc_attr( $artists ) . '"';
		}

		$albums = sanitize_text_field( $settings['albums'] );
		if ( ! empty( $albums ) ) {
			$attr .= ' album="' . esc_attr( $albums ) . '"';
		}

		return '[music_store' . $attr . ']';
	} // End _get_shortcode

	protected function render() {
		$shortcode = $this->_get_shortcode();
		if (
			isset( $_REQUEST['action'] ) &&
			(
				'elementor' == $_REQUEST['action'] ||
				'elementor_ajax' == $_REQUEST['action']
			)
		) {
			$url  = MS_H_URL;
			$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'ms-preview=' . urlencode( $shortcode );
			?>
			<div class="ms-iframe-container" style="position:relative;">
				<div class="ms-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
				<iframe height="0" width="100%" src="<?php print esc_attr( $url ); ?>" scrolling="no">
			</div>
			<?php
		} else {
			print do_shortcode( shortcode_unautop( $shortcode ) );
		}

	} // End render

	public function render_plain_content() {
		echo $this->_get_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput
	} // End render_plain_content

} // End Elementor_Music_Store_Widget

class Elementor_Music_Store_Product_Widget extends Widget_Base {

	public function get_name() {
		return 'music-store-product';
	} // End get_name

	public function get_title() {
		return 'Product';
	} // End get_title

	public function get_icon() {
		return 'eicon-play-o';
	} // End get_icon

	public function get_categories() {
		return array( 'music-store-cat' );
	} // End get_categories

	public function is_reload_preview_required() {
		return true;
	} // End is_reload_preview_required

	protected function register_controls() {
		$this->start_controls_section(
			'ms_section',
			array(
				'label' => __( 'Product', 'music-store' ),
			)
		);

		$this->add_control(
			'product',
			array(
				'label'       => __( "Enter the product's id", 'music-store' ),
				'type'        => Controls_Manager::NUMBER,
				'description' => '<i>' . __( 'Enter the id of a published product.', 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'       => __( "Select the product's layout", 'music-store' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => array(
					'store'  => __( "Like in the store's page", 'music-store' ),
					'single' => __( "Like in the product's page", 'music-store' ),
				),
				'default'     => 'store',
				'description' => '<i>' . __( 'Appearance applied to the product.', 'music-store' ) . '</i>',
			)
		);

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode( &$product_id = '' ) {
		$attr     = '';
		$settings = $this->get_settings_for_display();

		$product = sanitize_text_field( $settings['product'] );
		if ( ! empty( $product ) ) {
			$attr .= ' id="' . esc_attr( $product ) . '"';
		}
		$product_id = $product;

		$layout = sanitize_text_field( $settings['layout'] );
		if ( ! empty( $layout ) ) {
			$attr .= ' layout="' . esc_attr( $layout ) . '"';
		}

		return '[music_store_product' . $attr . ']';
	} // End _get_shortcode

	protected function render() {
		$shortcode = $this->_get_shortcode( $product_id );
		if (
			isset( $_REQUEST['action'] ) &&
			(
				'elementor' == $_REQUEST['action'] ||
				'elementor_ajax' == $_REQUEST['action']
			)
		) {
			if ( empty( $product_id ) ) {
				esc_html_e( "The product's id is required.", 'music-store' );
			} else {
				$url  = MS_H_URL;
				$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'ms-preview=' . urlencode( $shortcode );
				?>
				<div class="ms-iframe-container" style="position:relative;">
					<div class="ms-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
					<iframe height="0" width="100%" src="<?php print esc_attr( $url ); ?>" scrolling="no">
				</div>
				<?php
			}
		} else {
			print do_shortcode( shortcode_unautop( $shortcode ) );
		}

	} // End render

	public function render_plain_content() {
		echo $this->_get_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput
	} // End render_plain_content

} // End Elementor_Music_Store_Product_Widget

class Elementor_Music_Store_Products_List_Widget extends Widget_Base {

	public function get_name() {
		return 'music-store-products-list';
	} // End get_name

	public function get_title() {
		return 'Products list';
	} // End get_title

	public function get_icon() {
		return 'eicon-post-list';
	} // End get_icon

	public function get_categories() {
		return array( 'music-store-cat' );
	} // End get_categories

	public function is_reload_preview_required() {
		return true;
	} // End is_reload_preview_required

	protected function register_controls() {
		$this->start_controls_section(
			'ms_section',
			array(
				'label' => __( 'Products List', 'music-store' ),
			)
		);

		$this->add_control(
			'list_type',
			array(
				'label'       => __( 'List the products', 'music-store' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => array(
					'new_products' => __( 'New products', 'music-store' ),
					'top_rated'    => __( 'Top rated products', 'music-store' ),
					'top_selling'  => __( 'Most sold products', 'music-store' ),
				),
				'default'     => 'top_rated',
				'description' => '<i>' . __( 'Products to include in the list.', 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'       => __( 'Number of columns', 'music-store' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 3,
				'description' => '<i>' . __( 'Enter the number of columns, three columns by default.', 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'number',
			array(
				'label'       => __( 'Enter the number of products', 'music-store' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 3,
				'description' => '<i>' . __( 'Number of products to load. Three products by default.', 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'genres',
			array(
				'label'       => __( 'Genres', 'music-store' ),
				'type'        => Controls_Manager::TEXT,
				'description' => '<i>' . __( "Enter the genres' ids or slugs separated by comma to restrict the store's products to these genres. All genres by default.", 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'artists',
			array(
				'label'       => __( 'Artists', 'music-store' ),
				'type'        => Controls_Manager::TEXT,
				'description' => '<i>' . __( "Enter the artists' ids or slugs separated by comma to restrict the store's products to these artists. All artists by default.", 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'albums',
			array(
				'label'       => __( 'Albums', 'music-store' ),
				'type'        => Controls_Manager::TEXT,
				'description' => '<i>' . __( "Enter the albums' ids or slugs separated by comma to restrict the store's products to these albums. All albums by default.", 'music-store' ) . '</i>',
			)
		);

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode( &$product_id = '' ) {
		$attr     = '';
		$settings = $this->get_settings_for_display();

		$list_type = sanitize_text_field( $settings['list_type'] );
		if ( ! empty( $list_type ) ) {
			$attr .= ' type="' . esc_attr( $list_type ) . '"';
		}

		$attr .= ' products="all"';

		$number = ! empty( $settings['number'] ) ? trim( $settings['number'] ) : 0;
		$number = is_numeric( $number ) ? intval( $number ) : 0;
		if ( ! empty( $number ) ) {
			$attr .= ' number="' . esc_attr( $number ) . '"';
		}

		$columns = trim( $settings['columns'] );
		$columns = is_numeric( $columns ) ? max( 1, intval( $columns ) ) : 1;
		if ( ! empty( $columns ) ) {
			$attr .= ' columns="' . esc_attr( $columns ) . '"';
		}

		$genres = sanitize_text_field( $settings['genres'] );
		if ( ! empty( $genres ) ) {
			$attr .= ' genre="' . esc_attr( $genres ) . '"';
		}

		$artists = sanitize_text_field( $settings['artists'] );
		if ( ! empty( $artists ) ) {
			$attr .= ' artist="' . esc_attr( $artists ) . '"';
		}

		$albums = sanitize_text_field( $settings['albums'] );
		if ( ! empty( $albums ) ) {
			$attr .= ' album="' . esc_attr( $albums ) . '"';
		}

		return '[music_store_product_list' . $attr . ']';
	} // End _get_shortcode

	protected function render() {
		$shortcode = $this->_get_shortcode( $product_id );
		if (
			isset( $_REQUEST['action'] ) &&
			(
				'elementor' == $_REQUEST['action'] ||
				'elementor_ajax' == $_REQUEST['action']
			)
		) {
			$url  = MS_H_URL;
			$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'ms-preview=' . urlencode( $shortcode );
			?>
			<div class="ms-iframe-container" style="position:relative;">
				<div class="ms-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
				<iframe height="0" width="100%" src="<?php print esc_attr( $url ); ?>" scrolling="no">
			</div>
			<?php
		} else {
			print do_shortcode( shortcode_unautop( $shortcode ) );
		}

	} // End render

	public function render_plain_content() {
		echo $this->_get_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput
	} // End render_plain_content

} // End Elementor_Music_Store_Products_List_Widget

class Elementor_Music_Store_Purchased_List extends Widget_Base {

	public function get_name() {
		return 'music-store-purchased-list';
	} // End get_name

	public function get_title() {
		return 'Purchased list';
	} // End get_title

	public function get_icon() {
		return 'eicon-price-list';
	} // End get_icon

	public function get_categories() {
		return array( 'music-store-cat' );
	} // End get_categories

	public function is_reload_preview_required() {
		return true;
	} // End is_reload_preview_required

	protected function register_controls() {
		$this->start_controls_section(
			'ms_section',
			array(
				'label' => __( 'Purchased List', 'music-store' ),
			)
		);

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode( &$product_id = '' ) {
		return '[music_store_purchased_list]';
	} // End _get_shortcode

	protected function render() {
		$shortcode = $this->_get_shortcode( $product_id );
		if (
			isset( $_REQUEST['action'] ) &&
			(
				'elementor' == $_REQUEST['action'] ||
				'elementor_ajax' == $_REQUEST['action']
			)
		) {
			$url  = MS_H_URL;
			$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'ms-preview=' . urlencode( $shortcode );
			?>
			<div class="ms-iframe-container" style="position:relative;">
				<div class="ms-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
				<iframe height="0" width="100%" src="<?php print esc_attr( $url ); ?>" scrolling="no" style="min-height:50px;">
			</div>
			<?php
		} else {
			print do_shortcode( shortcode_unautop( $shortcode ) );
		}

	} // End render

	public function render_plain_content() {
		echo $this->_get_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput
	} // End render_plain_content

} // End Elementor_Music_Store_Purchased_List

class Elementor_Music_Store_Counter_Widget extends Widget_Base {

	public function get_name() {
		return 'music-store-counter';
	} // End get_name

	public function get_title() {
		return 'Sales counter';
	} // End get_title

	public function get_icon() {
		return 'eicon-number-field';
	} // End get_icon

	public function get_categories() {
		return array( 'music-store-cat' );
	} // End get_categories

	public function is_reload_preview_required() {
		return true;
	} // End is_reload_preview_required

	protected function register_controls() {
		$this->start_controls_section(
			'ms_section',
			array(
				'label' => __( 'Sales Counter', 'music-store' ),
			)
		);

		$this->add_control(
			'style',
			array(
				'label'       => __( 'Numbers styles', 'music-store' ),
				'type'        => Controls_Manager::CHOOSE,
				'options'     => array(
					array(
						'title' => __( 'Style One', 'music-store' ),
						'icon'  => 'eicon-circle-o',
					),
					array(
						'title' => __( 'Style Two', 'music-store' ),
						'icon'  => 'eicon-circle',
					),
				),
				'default'     => 0,
				'description' => '<i>' . __( 'Select the number styles', 'music-store' ) . '</i>',
			)
		);

		$this->add_control(
			'digits',
			array(
				'label'       => __( 'Digits', 'music-store' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 3,
				'description' => '<i>' . __( 'Number of digits in the counter, default 3.', 'music-store' ) . '</i>',
			)
		);

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode( &$product_id = '' ) {
		$attr     = '';
		$settings = $this->get_settings_for_display();

		$style = trim( $settings['style'] );
		if ( ! empty( $style ) ) {
			$attr .= ' style="alt_digits"';
		} else {
			$attr .= ' style="digits"';
		}

		$digits = ! empty( $settings['digits'] ) ? trim( $settings['digits'] ) : 0;
		$digits = is_numeric( $digits ) ? intval( $digits ) : 0;
		if ( ! empty( $digits ) ) {
			$attr .= ' min_length="' . esc_attr( $digits ) . '"';
		}

		return '[music_store_sales_counter' . $attr . ']';
	} // End _get_shortcode

	protected function render() {
		$shortcode = $this->_get_shortcode( $product_id );
		if (
			isset( $_REQUEST['action'] ) &&
			(
				'elementor' == $_REQUEST['action'] ||
				'elementor_ajax' == $_REQUEST['action']
			)
		) {
			$url  = MS_H_URL;
			$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'ms-preview=' . urlencode( $shortcode );
			?>
			<div class="ms-iframe-container" style="position:relative;">
				<div class="ms-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
				<iframe height="0" width="100%" src="<?php print esc_attr( $url ); ?>" scrolling="no">
			</div>
			<?php
		} else {
			print do_shortcode( shortcode_unautop( $shortcode ) );
		}

	} // End render

	public function render_plain_content() {
		echo $this->_get_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput
	} // End render_plain_content

} // End Elementor_Music_Store_Counter_Widget

// Register the widgets
Plugin::instance()->widgets_manager->register( new Elementor_Music_Store_Widget() );
Plugin::instance()->widgets_manager->register( new Elementor_Music_Store_Product_Widget() );
Plugin::instance()->widgets_manager->register( new Elementor_Music_Store_Products_List_Widget() );
Plugin::instance()->widgets_manager->register( new Elementor_Music_Store_Purchased_List() );
Plugin::instance()->widgets_manager->register( new Elementor_Music_Store_Counter_Widget() );
