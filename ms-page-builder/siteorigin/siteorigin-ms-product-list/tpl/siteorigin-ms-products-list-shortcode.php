<?php
$list_type    = ( ! empty( $instance['list_type'] ) ) ? sanitize_text_field( $instance['list_type'] ) : 'top_rated';
$product_type = 'all';
$number       = ( ! empty( $instance['number'] ) ) ? trim( $instance['number'] ) : 3;
$columns      = ( ! empty( $instance['columns'] ) ) ? trim( $instance['columns'] ) : 2;
$genres       = ( ! empty( $instance['genres'] ) ) ? sanitize_text_field( $instance['genres'] ) : '';
$albums       = ( ! empty( $instance['albums'] ) ) ? sanitize_text_field( $instance['albums'] ) : '';
$artists      = ( ! empty( $instance['artists'] ) ) ? sanitize_text_field( $instance['artists'] ) : '';

$shortcode = '[music_store_product_list';

if ( ! empty( $list_type ) ) {
	$shortcode .= ' type="' . esc_attr( $list_type ) . '"';
}

if ( ! empty( $product_type ) && 'all' != $product_type ) {
	$shortcode .= ' products="' . esc_attr( $product_type ) . '"';
}

$number = is_numeric( $number ) ? MAX( 1, intval( $number ) ) : 1;
if ( ! empty( $number ) ) {
	$shortcode .= ' number="' . esc_attr( $number ) . '"';
}

$columns = is_numeric( $columns ) ? max( 1, intval( $columns ) ) : 1;
if ( ! empty( $columns ) ) {
	$shortcode .= ' columns="' . esc_attr( $columns ) . '"';
}

if ( ! empty( $genres ) ) {
	$shortcode .= ' genre="' . esc_attr( $genres ) . '"';
}

if ( ! empty( $artists ) ) {
	$shortcode .= ' artist="' . esc_attr( $artists ) . '"';
}

if ( ! empty( $albums ) ) {
	$shortcode .= ' album="' . esc_attr( $albums ) . '"';
}

$shortcode .= ']';

print $shortcode; // phpcs:ignore WordPress.Security.EscapeOutput
