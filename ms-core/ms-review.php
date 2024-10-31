<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

if ( ! defined( 'MSDB_REVIEWS' ) ) {
	define( 'MSDB_REVIEWS', 'msdb_reviews' );
}

if ( ! class_exists( 'MS_REVIEW' ) ) {
	class MS_REVIEW {

		public static function db_structure() {
			 global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			return 'CREATE TABLE ' . $wpdb->prefix . MSDB_REVIEWS . " (
                    product mediumint(9) NOT NULL,
                    ip VARCHAR(45) NOT NULL,
                    review TINYINT NOT NULL DEFAULT 1,
                    UNIQUE KEY id (product, ip)
                 ) $charset_collate;";
		} // End get_db_structure

		public static function set_review( $id, $review ) {
			 global $wpdb;
			if ( function_exists( 'ms_getIP' ) ) {
				$ip = ms_getIP();
				$wpdb->query(
					$wpdb->prepare(
						'INSERT INTO ' . $wpdb->prefix . MSDB_REVIEWS . ' (product, ip, review) VALUES(%d, %s, %d) ON DUPLICATE KEY UPDATE review=%d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						$id,
						$ip,
						$review,
						$review
					)
				);
				$row = self::get_review( $id );
				if ( $row && isset( $row['average'] ) ) {
					$wpdb->update( $wpdb->prefix . MSDB_POST_DATA, array( 'popularity' => $row['average'] ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
				}
			}
		} // End set_review

		public static function get_review( $id ) {
			global $wpdb;
			return $wpdb->get_row(
				$wpdb->prepare(
					'SELECT FLOOR(AVG(review)) as average, COUNT(review) as votes FROM ' . $wpdb->prefix . MSDB_REVIEWS . ' WHERE product=%d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$id
				),
				ARRAY_A
			);
		} // End get_review

	} // End MS_REVIEW
}
