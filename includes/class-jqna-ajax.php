<?php
/**
 * AJAX handlers for question listing, pagination, submission, and category filtering.
 *
 * @package JQNA_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JQNA_Ajax
 */
class JQNA_Ajax {

	/**
	 * Constructor – register WP AJAX hooks.
	 */
	public function __construct() {
		// Load questions (pagination + category filter).
		add_action( 'wp_ajax_jqna_load_questions',        array( $this, 'load_questions' ) );
		add_action( 'wp_ajax_nopriv_jqna_load_questions', array( $this, 'load_questions' ) );

		// Submit a user question from the public FAQ page.
		add_action( 'wp_ajax_jqna_submit_question',        array( $this, 'submit_question' ) );
		add_action( 'wp_ajax_nopriv_jqna_submit_question', array( $this, 'submit_question' ) );
	}

	/**
	 * Return rendered accordion items + pagination via AJAX.
	 */
	public function load_questions() {
		check_ajax_referer( 'jqna_nonce', 'nonce' );

		if ( ! JQNA_Auth::has_access() ) {
			wp_send_json_error( esc_html__( 'Access denied.', 'jqna-pro' ) );
		}

		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$paged       = isset( $_POST['paged'] )       ? absint( $_POST['paged'] )       : 1;

		$query = JQNA_Post_Type::get_questions( $category_id, $paged );

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$answer = get_post_meta( get_the_ID(), '_jqna_answer', true );
				self::render_accordion_item( get_the_ID(), get_the_title(), $answer );
			}
			wp_reset_postdata();
		} else {
			echo '<p class="jqna-no-results">' . esc_html__( 'No questions found.', 'jqna-pro' ) . '</p>';
		}
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'        => $html,
				'total_pages' => (int) $query->max_num_pages,
				'current'     => $paged,
			)
		);
	}

	/**
	 * Handle question submission from the public page form.
	 */
	public function submit_question() {
		check_ajax_referer( 'jqna_nonce', 'nonce' );

		if ( ! JQNA_Auth::has_access() ) {
			wp_send_json_error( esc_html__( 'You must be logged in to submit a question.', 'jqna-pro' ) );
		}

		$title  = isset( $_POST['title'] )    ? sanitize_text_field( wp_unslash( $_POST['title'] ) )   : '';
		$cat_id = isset( $_POST['cat_id'] )   ? absint( $_POST['cat_id'] )                              : 0;

		if ( empty( $title ) ) {
			wp_send_json_error( esc_html__( 'Question title is required.', 'jqna-pro' ) );
		}

		$user_id = JQNA_Auth::get_user_id();
		$result  = JQNA_Post_Type::submit_question( $user_id, $title, $cat_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( esc_html__( 'Could not save your question. Please try again.', 'jqna-pro' ) );
		}

		wp_send_json_success( esc_html__( 'Your question has been submitted and is awaiting review.', 'jqna-pro' ) );
	}

	/**
	 * Render a single accordion FAQ item.
	 *
	 * @param int    $id     Post ID.
	 * @param string $title  Question title.
	 * @param string $answer Answer HTML.
	 */
	public static function render_accordion_item( $id, $title, $answer ) {
		?>
		<div class="jqna-accordion-item" data-id="<?php echo esc_attr( $id ); ?>">
			<button class="jqna-accordion-toggle" aria-expanded="false"
				aria-controls="jqna-panel-<?php echo esc_attr( $id ); ?>">
				<span class="jqna-accordion-title"><?php echo esc_html( $title ); ?></span>
				<span class="jqna-accordion-icon" aria-hidden="true">+</span>
			</button>
			<div class="jqna-accordion-panel" id="jqna-panel-<?php echo esc_attr( $id ); ?>" hidden>
				<?php if ( ! empty( $answer ) ) : ?>
					<div class="jqna-accordion-answer"><?php echo wp_kses_post( $answer ); ?></div>
				<?php else : ?>
					<p class="jqna-no-answer"><?php esc_html_e( 'Answer coming soon.', 'jqna-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
