<?php
/**
 * Plugin Uninstall
 *
 * Only deletes data when the admin has explicitly opted in
 * via the "Delete all data on uninstall" setting.
 *
 * @package JQNA_Pro
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$should_delete = get_option( 'jqna_pro_delete_data_on_uninstall', 'no' );

if ( 'yes' !== $should_delete ) {
	// Just clean up plugin options, keep user data safe.
	delete_option( 'jqna_pro_delete_data_on_uninstall' );
	delete_option( 'jqna_pro_version' );
	return;
}

// Full cleanup requested.
global $wpdb;

// 1. Delete all jqna_question posts + meta.
$post_ids = $wpdb->get_col(
	"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'jqna_question'"
);

if ( ! empty( $post_ids ) ) {
	// Delete post meta first.
	$id_placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
	// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($id_placeholders)", $post_ids ) );

	// Delete posts.
	// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE ID IN ($id_placeholders)", $post_ids ) );
}

// 2. Delete taxonomy data.
$term_taxonomy_ids = $wpdb->get_col(
	"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'jqna_category'"
);

if ( ! empty( $term_taxonomy_ids ) ) {
	// Delete term relationships.
	$tt_placeholders = implode( ',', array_fill( 0, count( $term_taxonomy_ids ), '%d' ) );
	// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($tt_placeholders)", $term_taxonomy_ids ) );

	$term_ids = $wpdb->get_col(
		"SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'jqna_category'"
	);

	// Delete term taxonomy entries.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'jqna_category'" );

	// Delete terms that are now orphaned.
	if ( ! empty( $term_ids ) ) {
		$t_placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->terms} WHERE term_id IN ($t_placeholders)", $term_ids ) );
	}
}

// 3. Remove plugin options.
delete_option( 'jqna_pro_delete_data_on_uninstall' );
delete_option( 'jqna_pro_version' );

// 4. Clear scheduled hooks.
wp_clear_scheduled_hook( 'jqna_pro_daily_cleanup' );

// 5. Flush object cache.
wp_cache_flush();
