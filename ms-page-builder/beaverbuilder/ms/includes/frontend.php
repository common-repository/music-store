<?php
$columns    = '';
$attributes = '';

// Processing columns
if ( ! empty( $settings->columns ) ) {
	$columns = preg_replace( '/[^\d]/', '', $settings->columns );
}
if ( ! empty( $columns ) ) {
	$columns = ' columns="' . $columns . '"';
}

// Processing the additional attributes
if ( ! empty( $settings->attributes ) ) {
	$attributes = sanitize_text_field( wp_unslash( $settings->attributes ) );
}
if ( ! empty( $attributes ) ) {
	$attributes = ' ' . $attributes;
}

echo '[music_store' . $columns . $attributes . ']'; // phpcs:ignore WordPress.Security.EscapeOutput
