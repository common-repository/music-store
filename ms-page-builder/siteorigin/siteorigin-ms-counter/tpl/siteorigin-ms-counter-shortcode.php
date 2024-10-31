<?php
$style  = ( ! empty( $instance['style'] ) && is_numeric( $instance['style'] ) ) ? intval( $instance['style'] ) : 0;
$digits = ( ! empty( $instance['digits'] ) && is_numeric( $instance['digits'] ) ) ? intval( $instance['digits'] ) : 3;

$shortcode = '[music_store_sales_counter';

if ( ! empty( $style ) ) {
	$shortcode .= ' style="alt_digits"';
} else {
	$shortcode .= ' style="digits"';
}

if ( ! empty( $digits ) ) {
	$shortcode .= ' min_length="' . esc_attr( $digits ) . '"';
}

$shortcode .= ']';

print $shortcode; // phpcs:ignore WordPress.Security.EscapeOutput
