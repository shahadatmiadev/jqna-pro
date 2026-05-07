<?php
/**
 * Registers the jqna_question custom post type and jqna_category taxonomy.
 *
 * @package JQNA_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JQNA_Post_Type
 */
class JQNA_Post_Type {

	/**
	 * Constructor – hooks in.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_all' ) );
		add_action( 'save_post_jqna_question', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register post type and taxonomy.
	 */
	public function register_all() {
		$this->register_post_type();
		$this->register_taxonomy();
	}

	/**
	 * Register post type.
	 */
	private function register_post_type() {
		$labels = array(
			'name'               => _x( 'Questions', 'post type general name', 'jqna-pro' ),
			'singular_name'      => _x( 'Question', 'post type singular name', 'jqna-pro' ),
			'menu_name'          => _x( 'JQNA Pro', 'admin menu', 'jqna-pro' ),
			'add_new'            => __( 'Add New', 'jqna-pro' ),
			'add_new_item'       => __( 'Add New Question', 'jqna-pro' ),
			'edit_item'          => __( 'Edit Question', 'jqna-pro' ),
			'view_item'          => __( 'View Question', 'jqna-pro' ),
			'search_items'       => __( 'Search Questions', 'jqna-pro' ),
			'not_found'          => __( 'No questions found.', 'jqna-pro' ),
			'not_found_in_trash' => __( 'No questions found in Trash.', 'jqna-pro' ),
		);

		register_post_type(
			'jqna_question',
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false, // We build our own menu.
				'supports'        => array( 'title', 'author' ),
				'capability_type' => 'post',
				'show_in_rest'    => false,
			)
		);
	}

	/**
	 * Register taxonomy.
	 */
	private function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Categories', 'taxonomy general name', 'jqna-pro' ),
			'singular_name'     => _x( 'Category', 'taxonomy singular name', 'jqna-pro' ),
			'search_items'      => __( 'Search Categories', 'jqna-pro' ),
			'all_items'         => __( 'All Categories', 'jqna-pro' ),
			'edit_item'         => __( 'Edit Category', 'jqna-pro' ),
			'update_item'       => __( 'Update Category', 'jqna-pro' ),
			'add_new_item'      => __( 'Add New Category', 'jqna-pro' ),
			'new_item_name'     => __( 'New Category Name', 'jqna-pro' ),
			'menu_name'         => __( 'Categories', 'jqna-pro' ),
		);

		register_taxonomy(
			'jqna_category',
			'jqna_question',
			array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Save question meta (answer, category) on post save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['jqna_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['jqna_meta_nonce'] ), 'jqna_save_meta_' . $post_id ) ) {
			return;
		}

		if ( array_key_exists( 'jqna_answer', $_POST ) ) {
			update_post_meta(
				$post_id,
				'_jqna_answer',
				wp_kses_post( wp_unslash( $_POST['jqna_answer'] ) )
			);
		}

		if ( ! empty( $_POST['jqna_category_id'] ) ) {
			wp_set_post_terms(
				$post_id,
				array( absint( $_POST['jqna_category_id'] ) ),
				'jqna_category'
			);
		}
	}

	// ------------------------------------------------------------------
	// Static query helpers
	// ------------------------------------------------------------------

	/**
	 * Get published questions.
	 *
	 * @param int $category_id Taxonomy term ID or 0 for all.
	 * @param int $paged       Page number.
	 * @return WP_Post[]
	 */
	public static function get_questions( $category_id = 0, $paged = 1 ) {
		$args = array(
			'post_type'      => 'jqna_question',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'paged'          => absint( $paged ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => false,
		);

		if ( $category_id > 0 ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'jqna_category',
					'field'    => 'term_id',
					'terms'    => absint( $category_id ),
				),
			);
		}

		return new WP_Query( $args );
	}

	/**
	 * Get categories with published question counts.
	 *
	 * @return array
	 */
	public static function get_categories_with_counts() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'jqna_category',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$result = array();
		foreach ( $terms as $term ) {
			$count = (int) wp_count_posts( 'jqna_question' )->publish; // approximate.

			// Accurate per-category count using WP_Query.
			$q = new WP_Query(
				array(
					'post_type'      => 'jqna_question',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => false,
					'tax_query'      => array(
						array(
							'taxonomy' => 'jqna_category',
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
				)
			);

			$result[] = array(
				'id'    => $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => $q->found_posts,
			);
		}

		return $result;
	}

	/**
	 * Submit a new question (pending status).
	 *
	 * @param int    $user_id  Author user ID.
	 * @param string $title    Question title.
	 * @param int    $cat_id   Category term ID.
	 * @return int|WP_Error
	 */
	public static function submit_question( $user_id, $title, $cat_id = 0 ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => sanitize_text_field( $title ),
				'post_content' => '',
				'post_status'  => 'pending',
				'post_type'    => 'jqna_question',
				'post_author'  => absint( $user_id ),
			),
			true
		);

		if ( ! is_wp_error( $post_id ) && $cat_id > 0 ) {
			wp_set_post_terms( $post_id, array( absint( $cat_id ) ), 'jqna_category' );
		}

		return $post_id;
	}
}
