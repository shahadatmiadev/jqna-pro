<?php
/**
 * Admin menu, question list, add/edit pages.
 *
 * @package JQNA_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JQNA_Admin
 */
class JQNA_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu',             array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_jqna_save_question',   array( $this, 'save_question' ) );
		add_action( 'admin_post_jqna_delete_question', array( $this, 'delete_question' ) );
		add_action( 'admin_post_jqna_approve_question',array( $this, 'approve_question' ) );
	}

	// ------------------------------------------------------------------
	// Assets
	// ------------------------------------------------------------------

	/**
	 * Enqueue admin styles (only on our pages).
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'jqna' ) ) {
			return;
		}
		wp_enqueue_style(
			'jqna-pro-admin',
			JQNA_PRO_URL . 'admin/css/admin.css',
			array(),
			JQNA_PRO_VERSION
		);
		wp_enqueue_editor();
	}

	// ------------------------------------------------------------------
	// Menu registration
	// ------------------------------------------------------------------

	/**
	 * Register admin menus.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'JQNA Pro', 'jqna-pro' ),
			__( 'JQNA Pro', 'jqna-pro' ),
			'manage_options',
			'jqna-dashboard',
			array( $this, 'page_dashboard' ),
			'dashicons-format-chat',
			30
		);

		add_submenu_page(
			'jqna-dashboard',
			__( 'All Questions', 'jqna-pro' ),
			__( 'All Questions', 'jqna-pro' ),
			'manage_options',
			'jqna-questions',
			array( $this, 'page_questions' )
		);

		add_submenu_page(
			'jqna-dashboard',
			__( 'Add New Question', 'jqna-pro' ),
			__( 'Add New', 'jqna-pro' ),
			'manage_options',
			'jqna-add-question',
			array( $this, 'page_edit_question' )
		);

		// Categories – use the built-in taxonomy page.
		add_submenu_page(
			'jqna-dashboard',
			__( 'Categories', 'jqna-pro' ),
			__( 'Categories', 'jqna-pro' ),
			'manage_options',
			'edit-tags.php?taxonomy=jqna_category&post_type=jqna_question'
		);
	}

	// ------------------------------------------------------------------
	// Dashboard page
	// ------------------------------------------------------------------

	/**
	 * Dashboard page callback.
	 */
	public function page_dashboard() {
		$counts     = wp_count_posts( 'jqna_question' );
		$cat_count  = wp_count_terms( array( 'taxonomy' => 'jqna_category' ) );
		?>
		<div class="wrap jqna-admin-wrap">
			<h1><?php esc_html_e( 'JQNA Pro Dashboard', 'jqna-pro' ); ?></h1>
			<div class="jqna-stats">
				<div class="jqna-stat-card">
					<span class="jqna-stat-icon dashicons dashicons-editor-help"></span>
					<div class="jqna-stat-body">
						<div class="jqna-stat-number"><?php echo esc_html( (int) ( $counts->publish ?? 0 ) + (int) ( $counts->pending ?? 0 ) ); ?></div>
						<div class="jqna-stat-label"><?php esc_html_e( 'Total Questions', 'jqna-pro' ); ?></div>
					</div>
				</div>
				<div class="jqna-stat-card">
					<span class="jqna-stat-icon dashicons dashicons-yes-alt"></span>
					<div class="jqna-stat-body">
						<div class="jqna-stat-number"><?php echo esc_html( (int) ( $counts->publish ?? 0 ) ); ?></div>
						<div class="jqna-stat-label"><?php esc_html_e( 'Published', 'jqna-pro' ); ?></div>
					</div>
				</div>
				<div class="jqna-stat-card">
					<span class="jqna-stat-icon dashicons dashicons-clock"></span>
					<div class="jqna-stat-body">
						<div class="jqna-stat-number"><?php echo esc_html( (int) ( $counts->pending ?? 0 ) ); ?></div>
						<div class="jqna-stat-label"><?php esc_html_e( 'Pending', 'jqna-pro' ); ?></div>
					</div>
				</div>
				<div class="jqna-stat-card">
					<span class="jqna-stat-icon dashicons dashicons-category"></span>
					<div class="jqna-stat-body">
						<div class="jqna-stat-number"><?php echo esc_html( is_wp_error( $cat_count ) ? 0 : (int) $cat_count ); ?></div>
						<div class="jqna-stat-label"><?php esc_html_e( 'Categories', 'jqna-pro' ); ?></div>
					</div>
				</div>
			</div>

			<div class="jqna-shortcode-info">
				<h3><?php esc_html_e( 'Shortcode', 'jqna-pro' ); ?></h3>
				<p><?php esc_html_e( 'Use the following shortcode on any page to display the Q&A system:', 'jqna-pro' ); ?></p>
				<code>[jqna_pro]</code>
			</div>
		</div>
		<?php
	}

	// ------------------------------------------------------------------
	// Questions list page
	// ------------------------------------------------------------------

	/**
	 * All questions list page callback.
	 */
	public function page_questions() {
		// Filter.
		$status_filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'any';
		$allowed_statuses = array( 'any', 'publish', 'pending' );
		if ( ! in_array( $status_filter, $allowed_statuses, true ) ) {
			$status_filter = 'any';
		}

		$questions = get_posts(
			array(
				'post_type'      => 'jqna_question',
				'post_status'    => $status_filter,
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$base_url = admin_url( 'admin.php?page=jqna-questions' );
		?>
		<div class="wrap jqna-admin-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Questions', 'jqna-pro' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=jqna-add-question' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'jqna-pro' ); ?>
			</a>
			<hr class="wp-header-end">

			<!-- Status filter links -->
			<ul class="subsubsub">
				<li>
					<a href="<?php echo esc_url( $base_url ); ?>" <?php echo 'any' === $status_filter ? 'class="current"' : ''; ?>>
						<?php esc_html_e( 'All', 'jqna-pro' ); ?>
					</a> |
				</li>
				<li>
					<a href="<?php echo esc_url( add_query_arg( 'status', 'publish', $base_url ) ); ?>" <?php echo 'publish' === $status_filter ? 'class="current"' : ''; ?>>
						<?php esc_html_e( 'Published', 'jqna-pro' ); ?>
					</a> |
				</li>
				<li>
					<a href="<?php echo esc_url( add_query_arg( 'status', 'pending', $base_url ) ); ?>" <?php echo 'pending' === $status_filter ? 'class="current"' : ''; ?>>
						<?php esc_html_e( 'Pending', 'jqna-pro' ); ?>
					</a>
				</li>
			</ul>

			<?php $this->show_admin_notice(); ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" style="width:50px"><?php esc_html_e( 'ID', 'jqna-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Question', 'jqna-pro' ); ?></th>
						<th scope="col" style="width:140px"><?php esc_html_e( 'Category', 'jqna-pro' ); ?></th>
						<th scope="col" style="width:100px"><?php esc_html_e( 'Status', 'jqna-pro' ); ?></th>
						<th scope="col" style="width:100px"><?php esc_html_e( 'Answer', 'jqna-pro' ); ?></th>
						<th scope="col" style="width:180px"><?php esc_html_e( 'Actions', 'jqna-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $questions ) ) : ?>
					<tr>
						<td colspan="6"><?php esc_html_e( 'No questions found.', 'jqna-pro' ); ?></td>
					</tr>
					<?php else : ?>
					<?php foreach ( $questions as $q ) :
						$cats   = wp_get_post_terms( $q->ID, 'jqna_category', array( 'fields' => 'names' ) );
						$answer = get_post_meta( $q->ID, '_jqna_answer', true );
					?>
					<tr>
						<td><?php echo esc_html( $q->ID ); ?></td>
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=jqna-add-question&edit=' . $q->ID ) ); ?>">
									<?php echo esc_html( $q->post_title ); ?>
								</a>
							</strong>
						</td>
						<td><?php echo ! empty( $cats ) && ! is_wp_error( $cats ) ? esc_html( implode( ', ', $cats ) ) : '—'; ?></td>
						<td>
							<?php if ( 'publish' === $q->post_status ) : ?>
								<span class="jqna-badge jqna-badge-green"><?php esc_html_e( 'Published', 'jqna-pro' ); ?></span>
							<?php else : ?>
								<span class="jqna-badge jqna-badge-orange"><?php esc_html_e( 'Pending', 'jqna-pro' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $answer ) ) : ?>
								<span class="jqna-badge jqna-badge-green"><?php esc_html_e( 'Yes', 'jqna-pro' ); ?></span>
							<?php else : ?>
								<span class="jqna-badge jqna-badge-red"><?php esc_html_e( 'No', 'jqna-pro' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="jqna-row-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=jqna-add-question&edit=' . $q->ID ) ); ?>">
								<?php esc_html_e( 'Edit', 'jqna-pro' ); ?>
							</a>

							<?php if ( 'pending' === $q->post_status ) : ?>
								<?php
								$approve_url = wp_nonce_url(
									admin_url( 'admin-post.php?action=jqna_approve_question&id=' . $q->ID ),
									'jqna_approve_' . $q->ID
								);
								?>
								<span class="jqna-sep">|</span>
								<a href="<?php echo esc_url( $approve_url ); ?>" class="jqna-link-approve">
									<?php esc_html_e( 'Approve', 'jqna-pro' ); ?>
								</a>
							<?php endif; ?>

							<?php
							$delete_url = wp_nonce_url(
								admin_url( 'admin-post.php?action=jqna_delete_question&id=' . $q->ID ),
								'jqna_delete_' . $q->ID
							);
							?>
							<span class="jqna-sep">|</span>
							<a href="<?php echo esc_url( $delete_url ); ?>"
								class="jqna-link-delete"
								onclick="return confirm('<?php esc_attr_e( 'Delete this question permanently?', 'jqna-pro' ); ?>')">
								<?php esc_html_e( 'Delete', 'jqna-pro' ); ?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	// ------------------------------------------------------------------
	// Add / Edit question page
	// ------------------------------------------------------------------

	/**
	 * Add / Edit question page callback.
	 */
	public function page_edit_question() {
		$edit_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$question = $edit_id ? get_post( $edit_id ) : null;
		$answer   = $edit_id ? (string) get_post_meta( $edit_id, '_jqna_answer', true ) : '';

		// Pre-selected category.
		$sel_cat = 0;
		if ( $edit_id ) {
			$terms   = wp_get_post_terms( $edit_id, 'jqna_category', array( 'fields' => 'ids' ) );
			$sel_cat = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? (int) $terms[0] : 0;
		}
		?>
		<div class="wrap jqna-admin-wrap">
			<h1><?php echo $edit_id ? esc_html__( 'Edit Question', 'jqna-pro' ) : esc_html__( 'Add New Question', 'jqna-pro' ); ?></h1>

			<?php $this->show_admin_notice(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="jqna_save_question">
				<?php if ( $edit_id ) : ?>
					<input type="hidden" name="question_id" value="<?php echo esc_attr( $edit_id ); ?>">
				<?php endif; ?>
				<?php wp_nonce_field( 'jqna_save_question_' . $edit_id, 'jqna_save_nonce' ); ?>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">

						<!-- Main column -->
						<div id="post-body-content">
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Question', 'jqna-pro' ); ?></span></h2>
								<div class="inside">
									<label for="jqna-q-title-admin" class="screen-reader-text">
										<?php esc_html_e( 'Question Title', 'jqna-pro' ); ?>
									</label>
									<input
										type="text"
										id="jqna-q-title-admin"
										name="question_title"
										value="<?php echo $question ? esc_attr( $question->post_title ) : ''; ?>"
										placeholder="<?php esc_attr_e( 'Enter question…', 'jqna-pro' ); ?>"
										class="widefat"
										required
									/>
								</div>
							</div>

							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Answer', 'jqna-pro' ); ?></span></h2>
								<div class="inside">
									<?php
									wp_editor(
										$answer,
										'jqna_answer',
										array(
											'textarea_name' => 'question_answer',
											'textarea_rows' => 10,
											'teeny'         => false,
										)
									);
									?>
								</div>
							</div>
						</div><!-- #post-body-content -->

						<!-- Side column -->
						<div id="postbox-container-1" class="postbox-container">
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Publish', 'jqna-pro' ); ?></span></h2>
								<div class="inside">
									<label for="jqna-status"><?php esc_html_e( 'Status', 'jqna-pro' ); ?></label>
									<select id="jqna-status" name="question_status" class="widefat">
										<option value="pending" <?php selected( $question ? $question->post_status : 'pending', 'pending' ); ?>>
											<?php esc_html_e( 'Pending Review', 'jqna-pro' ); ?>
										</option>
										<option value="publish" <?php selected( $question ? $question->post_status : '', 'publish' ); ?>>
											<?php esc_html_e( 'Published', 'jqna-pro' ); ?>
										</option>
									</select>
									<br><br>
									<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Question', 'jqna-pro' ); ?>">
								</div>
							</div>

							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Category', 'jqna-pro' ); ?></span></h2>
								<div class="inside">
									<?php
									wp_dropdown_categories(
										array(
											'taxonomy'          => 'jqna_category',
											'name'              => 'question_category',
											'id'                => 'jqna-category',
											'hide_empty'        => false,
											'show_option_none'  => __( '— Default (Islamic) —', 'jqna-pro' ),
											'option_none_value' => 0,
											'selected'          => $sel_cat,
											'class'             => 'widefat',
										)
									);
									?>
									<br>
									<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=jqna_category&post_type=jqna_question' ) ); ?>" target="_blank">
										+ <?php esc_html_e( 'Add New Category', 'jqna-pro' ); ?>
									</a>
								</div>
							</div>
						</div><!-- #postbox-container-1 -->

					</div><!-- #post-body -->
				</div><!-- #poststuff -->
			</form>
		</div>
		<?php
	}

	// ------------------------------------------------------------------
	// Form action handlers (admin-post.php)
	// ------------------------------------------------------------------

	/**
	 * Save (insert or update) a question from the admin form.
	 */
	public function save_question() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'jqna-pro' ) );
		}

		$edit_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;

		if ( ! isset( $_POST['jqna_save_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['jqna_save_nonce'] ) ), 'jqna_save_question_' . $edit_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'jqna-pro' ) );
		}

		$title    = isset( $_POST['question_title'] )  ? sanitize_text_field( wp_unslash( $_POST['question_title'] ) ) : '';
		$answer   = isset( $_POST['question_answer'] ) ? wp_kses_post( wp_unslash( $_POST['question_answer'] ) )       : '';
		$status   = isset( $_POST['question_status'] ) ? sanitize_key( $_POST['question_status'] )                     : 'pending';
		$cat_id   = isset( $_POST['question_category'] ) ? absint( $_POST['question_category'] )                       : 0;

		$allowed_statuses = array( 'publish', 'pending' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'pending';
		}

		if ( empty( $title ) ) {
			wp_safe_redirect(
				add_query_arg(
					'jqna_notice',
					'empty_title',
					admin_url( 'admin.php?page=jqna-add-question' . ( $edit_id ? '&edit=' . $edit_id : '' ) )
				)
			);
			exit;
		}

		$data = array(
			'post_title'   => $title,
			'post_status'  => $status,
			'post_type'    => 'jqna_question',
			'post_content' => '',
		);

		if ( $edit_id ) {
			$data['ID'] = $edit_id;
			$post_id    = wp_update_post( $data, true );
		} else {
			$data['post_author'] = get_current_user_id();
			$post_id             = wp_insert_post( $data, true );
		}

		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'jqna_notice', 'save_error', admin_url( 'admin.php?page=jqna-questions' ) ) );
			exit;
		}

		update_post_meta( $post_id, '_jqna_answer', $answer );

		// Assign category; if none selected, assign default Islamic.
		if ( $cat_id > 0 ) {
			wp_set_post_terms( $post_id, array( $cat_id ), 'jqna_category' );
		} else {
			$default = get_term_by( 'slug', 'islamic', 'jqna_category' );
			if ( $default ) {
				wp_set_post_terms( $post_id, array( $default->term_id ), 'jqna_category' );
			}
		}

		wp_safe_redirect( add_query_arg( 'jqna_notice', 'saved', admin_url( 'admin.php?page=jqna-questions' ) ) );
		exit;
	}

	/**
	 * Delete a question.
	 */
	public function delete_question() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'jqna-pro' ) );
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $id || ! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'jqna_delete_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'jqna-pro' ) );
		}

		wp_delete_post( $id, true );

		wp_safe_redirect( add_query_arg( 'jqna_notice', 'deleted', admin_url( 'admin.php?page=jqna-questions' ) ) );
		exit;
	}

	/**
	 * Approve (publish) a pending question.
	 */
	public function approve_question() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'jqna-pro' ) );
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $id || ! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'jqna_approve_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'jqna-pro' ) );
		}

		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );

		wp_safe_redirect( add_query_arg( 'jqna_notice', 'approved', admin_url( 'admin.php?page=jqna-questions' ) ) );
		exit;
	}

	// ------------------------------------------------------------------
	// Helper: Admin notices
	// ------------------------------------------------------------------

	/**
	 * Display inline admin notice from query arg.
	 */
	private function show_admin_notice() {
		if ( ! isset( $_GET['jqna_notice'] ) ) {
			return;
		}
		$code = sanitize_key( $_GET['jqna_notice'] );

		$messages = array(
			'saved'      => array( 'success', __( 'Question saved successfully.', 'jqna-pro' ) ),
			'deleted'    => array( 'success', __( 'Question deleted.', 'jqna-pro' ) ),
			'approved'   => array( 'success', __( 'Question approved and published.', 'jqna-pro' ) ),
			'save_error' => array( 'error',   __( 'Error saving question.', 'jqna-pro' ) ),
			'empty_title'=> array( 'error',   __( 'Question title cannot be empty.', 'jqna-pro' ) ),
		);

		if ( isset( $messages[ $code ] ) ) {
			list( $type, $msg ) = $messages[ $code ];
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $type ),
				esc_html( $msg )
			);
		}
	}
}
