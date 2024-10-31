<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

// ************************************** WIDGETS CLASSES *********************************************/

/**
 * MSProductListWidget Class
 */
class MSProductListWidget extends WP_Widget {

	/** constructor */
	public function __construct() {
		parent::__construct( false, $name = 'Music Store Products List' );

	}

	public function widget( $args, $instance ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract
		$title = apply_filters( 'widget_title', ( ! empty( $instance['title'] ) ? $instance['title'] : '' ) );

		$defaults   = array(
			'list_type' => 'top_rated',
			'columns'   => 1,
			'number'    => 3,
		);
		$instance_p = wp_parse_args( (array) $instance, $defaults );

		$list_type = $instance_p['list_type'];
		$columns   = $instance_p['columns'];
		$number    = $instance_p['number'];

		$atts = array(
			'type'    => $list_type,
			'columns' => $columns,
			'number'  => $number,
		);
		echo wp_kses_post( $before_widget );
		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}
		echo $GLOBALS['music_store']->load_product_list( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo wp_kses_post( $after_widget );
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title']     = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['columns']   = ( ! empty( $new_instance['columns'] ) && is_numeric( $new_instance['columns'] ) ) ? max( intval( $new_instance['columns'] ), 1 ) : 1;
		$instance['number']    = ( ! empty( $new_instance['number'] ) && is_numeric( $new_instance['number'] ) ) ? max( intval( $new_instance['number'] ), 1 ) : 1;
		$instance['list_type'] = $new_instance['list_type'];

		return $instance;
	}

	public function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array(
			'title'     => '',
			'list_type' => 'top_rated',
			'columns'   => 1,
			'number'    => 3,
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title     = $instance['title'];
		$list_type = $instance['list_type'];
		$columns   = $instance['columns'];
		$number    = $instance['number'];

		?>
			<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'music-store' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'list_type' ) ); ?>"><?php esc_html_e( 'Select the type of list:', 'music-store' ); ?><br />
					<select style="width:100%;" id="<?php echo esc_attr( $this->get_field_id( 'list_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'list_type' ) ); ?>">
						<option value="top_rated" <?php if ( 'top_rated' == $list_type ) {
							echo 'SELECTED';} ?> >Top Rated</option>
						<option value="new_products" <?php if ( 'new_products' == $list_type ) {
							echo 'SELECTED';} ?> >New Products</option>
						<option value="top_selling" <?php if ( 'top_selling' == $list_type ) {
							echo 'SELECTED';} ?> >Top Selling</option>
					</select>
				</label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Enter the number of products to show:', 'music-store' ); ?><br />
					<input style="width:100%;" id="<?php print esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'number' ) ); ?>" value="<?php print esc_attr( $number ); ?>" />
				</label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"><?php esc_html_e( 'Enter the number of columns:', 'music-store' ); ?><br />
					<input style="width:100%;" id="<?php print esc_attr( $this->get_field_id( 'columns' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'columns' ) ); ?>" value="<?php print esc_attr( $columns ); ?>" />
				</label>
			</p>
		<?php
	}

} // clase MSProductListWidget

/**
 * MSProductWidget Class
 */
class MSProductWidget extends WP_Widget {

	/** constructor */
	public function __construct() {
		parent::__construct( false, $name = 'Music Store Product' );
	}

	public function widget( $args, $instance ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract
		$title = apply_filters( 'widget_title', ( ! empty( $instance['title'] ) ? $instance['title'] : '' ) );

		$defaults   = array( 'product_id' => '' );
		$instance_p = wp_parse_args( (array) $instance, $defaults );

		$product_id = $instance_p['product_id'];

		$atts = array( 'id' => $product_id );
		echo wp_kses_post( $before_widget );
		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}
		echo $GLOBALS['music_store']->load_product( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo wp_kses_post( $after_widget );
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['product_id'] = $new_instance['product_id'] * 1;

		return $instance;
	}

	public function form( $instance ) {
		/* Set up some default widget settings. */
		$defaults   = array(
			'title'      => '',
			'product_id' => '',
		);
		$instance   = wp_parse_args( (array) $instance, $defaults );
		$title      = $instance['title'];
		$product_id = $instance['product_id'];
		?>
			<p><label for="<?php print esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'music-store' ); ?> <input class="widefat" id="<?php print esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php print esc_attr( $title ); ?>" /></label></p>

			<p>
				<label for="<?php print esc_attr( $this->get_field_id( 'product_id' ) ); ?>"><?php esc_html_e( 'Enter the product ID:', 'music-store' ); ?><br />
					<input class="widefat" id="<?php print esc_attr( $this->get_field_id( 'product_id' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'product_id' ) ); ?>" value="<?php print esc_attr( $product_id ); ?>" />
				</label>
			</p>
		<?php
	}

} // clase MSProductWidget


/**
 * MSSalesCounterWidget Class
 */
class MSSalesCounterWidget extends WP_Widget {

	/** constructor */
	public function __construct() {
		parent::__construct( false, $name = 'Music Store Sales Counter' );

	}

	public function widget( $args, $instance ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract
		$title = apply_filters( 'widget_title', ( ! empty( $instance['title'] ) ? $instance['title'] : '' ) );

		$style      = $instance['style'];
		$min_length = ( ! empty( $instance['min_length'] ) && is_numeric( $instance['min_length'] ) ) ? max( intval( $instance['min_length'] ), 1 ) : 1;

		$atts = array(
			'style'      => $style,
			'min_length' => $min_length,
		);
		echo wp_kses_post( $before_widget );
		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}
		echo $GLOBALS['music_store']->sales_counter( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo wp_kses_post( $after_widget );
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		/* Strip tags (if needed) and update the widget settings. */
		$instance['title']      = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['min_length'] = ( ! empty( $new_instance['min_length'] ) && is_numeric( $new_instance['min_length'] ) ) ? max( intval( $new_instance['min_length'] ), 1 ) : 1;
		$instance['style']      = $new_instance['style'];

		return $instance;
	}

	public function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array(
			'title'      => '',
			'min_length' => 3,
		);

		// create an array to hold directory list
		// create a handler for the directory
		$handler      = opendir( MS_CORE_IMAGES_PATH . '/counter' );
		$digit_design = '';
		// open directory and walk through the filenames

		while ( $file = readdir( $handler ) ) {

			// if file isn't this directory or its parent, add it to the results
			if ( '.' != $file && '..' != $file ) {
				if ( is_dir( MS_CORE_IMAGES_PATH . '/counter/' . $file ) ) {
					if ( ! isset( $defaults['style'] ) ) {
						$defaults['style'] = $file;
					}
					$digit_design .= '<option value="' . esc_attr( $file ) . '" ' . ( ( isset( $instance['style'] ) && $instance['style'] == $file ) ? 'SELECTED' : '' ) . ' >' . music_store_strip_tags( $file, true ) . '</option>';
				}
			}
		}

		// tidy up: close the handler
		closedir( $handler );

		$instance   = wp_parse_args( (array) $instance, $defaults );
		$title      = $instance['title'];
		$min_length = $instance['min_length'];

		?>
		<p><label for="<?php print esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'music-store' ); ?> <input class="widefat" id="<?php print esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php print esc_attr( $title ); ?>" /></label></p>

		<p>
			<label for="<?php print esc_attr( $this->get_field_id( 'style' ) ); ?>"><?php esc_html_e( 'Select the numbers design:', 'music-store' ); ?><br />
				<select style="width:100%;" id="<?php print esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'style' ) ); ?>">
					<?php echo $digit_design; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php print esc_attr( $this->get_field_id( 'min_length' ) ); ?>"><?php esc_html_e( 'Enter minimum length of counter:', 'music-store' ); ?><br />
				<input style="width:100%;" id="<?php print esc_attr( $this->get_field_id( 'min_length' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'min_length' ) ); ?>" value="<?php print esc_attr( $min_length ); ?>" />
			</label>
		</p>
		<?php
	}

} // clase MSSalesCounterWidget

/**
 * MSLoginFormWidget Class
 */
class MSLoginFormWidget extends WP_Widget {

	/** constructor */
	public function __construct() {
		parent::__construct( false, $name = 'Music Store Login Form' );

	}

	public function widget( $args, $instance ) {
		$user_obj = wp_get_current_user();

		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract
		echo wp_kses_post( $before_widget );
		$title     = apply_filters( 'widget_title', ( ! empty( $instance['title'] ) ? $instance['title'] : '' ) );
		$store_url = $instance['store_url'];
		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}

		if ( $user_obj->ID ) {
			echo '<p>' . esc_html( $user_obj->display_name ) . '<br/><br/>
            <a href="' . esc_attr( wp_logout_url( $store_url ) ) . '">' . esc_html__( 'Logout', 'music-store' ) . '</a></p>';

		} else {
			wp_login_form( array( 'redirect' => $store_url ) );
		}

		echo wp_kses_post( $after_widget );
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['store_url'] = sanitize_text_field( $new_instance['store_url'] );

		return $instance;
	}

	public function form( $instance ) {
		global $music_store_settings;

		/* Set up some default widget settings. */
		$defaults = array(
			'title'     => '',
			'store_url' => ! empty( $music_store_settings ) && ! empty( $music_store_settings['ms_main_page'] ) ? $music_store_settings['ms_main_page'] : '',
		);

		$instance  = wp_parse_args( (array) $instance, $defaults );
		$title     = $instance['title'];
		$store_url = $instance['store_url'];

		?>
		<p><label for="<?php print esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'music-store' ); ?> <input class="widefat" id="<?php print esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php print esc_attr( $title ); ?>" /></label></p>
		<p>
			<label for="<?php print esc_attr( $this->get_field_id( 'store_url' ) ); ?>"><?php esc_html_e( 'Enter the URL to the store page:', 'music-store' ); ?><br />
				<input class="widefat" id="<?php print esc_attr( $this->get_field_id( 'store_url' ) ); ?>" name="<?php print esc_attr( $this->get_field_name( 'store_url' ) ); ?>" type="text" value="<?php print esc_attr( $store_url ); ?>" />
			</label>
		</p>
		<?php
	}

} // clase MSLoginFormWidget
