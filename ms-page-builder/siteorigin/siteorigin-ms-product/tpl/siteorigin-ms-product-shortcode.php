<?php
$product = trim( ! empty( $instance['product'] ) ? $instance['product'] : '' );
$product = is_numeric( $product ) ? intval( $product ) : 0;
if ( $product ) {
	$shortcode = '[music_store_product id="' . esc_attr( $product ) . '"';
	$layout    = sanitize_text_field( ! empty( $instance['layout'] ) ? $instance['layout'] : '' );
	if ( ! empty( $layout ) ) {
		$shortcode .= ' layout="' . esc_attr( $layout ) . '"';
	}
	$shortcode .= ']';
}
print ! empty( $shortcode ) ? $shortcode : ''; // phpcs:ignore WordPress.Security.EscapeOutput
