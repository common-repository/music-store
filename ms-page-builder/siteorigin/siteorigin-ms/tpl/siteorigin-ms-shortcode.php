<?php
$product_type = 'all';
$exclude      = ( ! empty( $instance['exclude'] ) ) ? sanitize_text_field( $instance['exclude'] ) : '';
$columns      = ( ! empty( $instance['columns'] ) ) ? trim( $instance['columns'] ) : 2;
$genres       = ( ! empty( $instance['genres'] ) ) ? sanitize_text_field( $instance['genres'] ) : '';
$albums       = ( ! empty( $instance['albums'] ) ) ? sanitize_text_field( $instance['albums'] ) : '';
$artists      = ( ! empty( $instance['artists'] ) ) ? sanitize_text_field( $instance['artists'] ) : '';

$shortcode = '[music_store';

if ( ! empty( $product_type ) ) {
	$shortcode .= ' load="' . esc_attr( $product_type ) . '"';
}

$exclude = preg_replace( '/[^\d\,]/', '', $exclude );
$exclude = trim( $exclude, ',' );
if ( ! empty( $exclude ) ) {
	$shortcode .= ' exclude="' . esc_attr( $exclude ) . '"';
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
