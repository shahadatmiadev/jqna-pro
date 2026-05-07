<?php
/**
 * Frontend shortcode [jqna_pro].
 *
 * @package JQNA_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JQNA_Shortcode
 */
class JQNA_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'jqna_pro', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue styles and scripts only on pages that use the shortcode.
	 */
	public function enqueue() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'jqna_pro' ) ) {
			wp_enqueue_style(
				'jqna-pro-frontend',
				JQNA_PRO_URL . 'frontend/css/frontend.css',
				array(),
				JQNA_PRO_VERSION
			);
			wp_enqueue_script(
				'jqna-pro-frontend',
				JQNA_PRO_URL . 'frontend/js/frontend.js',
				array( 'jquery' ),
				JQNA_PRO_VERSION,
				true
			);
			wp_localize_script(
				'jqna-pro-frontend',
				'jqnaPro',
				array(
					'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'jqna_nonce' ),
					'i18n'      => array(
						'loading'   => esc_html__( 'Loading…', 'jqna-pro' ),
						'error'     => esc_html__( 'An error occurred. Please try again.', 'jqna-pro' ),
						'noResults' => esc_html__( 'No questions found.', 'jqna-pro' ),
					),
				)
			);
		}
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts = array() ) {
		// Not authenticated – show login form.
		if ( ! JQNA_Auth::has_access() ) {
			return $this->render_login_form();
		}

		// Authenticated – show two-column Q&A layout.
		return $this->render_qa_page();
	}

	// ------------------------------------------------------------------
	// Login form
	// ------------------------------------------------------------------

	/**
	 * Render the login/verification form.
	 *
	 * @return string HTML.
	 */
	private function render_login_form() {
		$error_map = array(
			'empty_fields'    => __( 'Please fill in all fields.', 'jqna-pro' ),
			'invalid_user'    => __( 'No subscriber account found with that username or email.', 'jqna-pro' ),
			'invalid_password'=> __( 'Incorrect password. Please try again.', 'jqna-pro' ),
			'invalid_phone'   => __( 'No completed order found with that phone number.', 'jqna-pro' ),
		);

		$error_code = isset( $_GET['jqna_error'] ) ? sanitize_key( wp_unslash( $_GET['jqna_error'] ) ) : '';
		$error_msg  = isset( $error_map[ $error_code ] ) ? $error_map[ $error_code ] : '';

		ob_start();
		?>
		<div class="jqna-login-wrap">
			<div class="jqna-login-box">
				<h2 class="jqna-login-title">
					<?php esc_html_e( 'Access Q&amp;A System', 'jqna-pro' ); ?>
				</h2>
				<p class="jqna-login-desc">
					<?php esc_html_e( 'Please verify your identity to access the Q&A content.', 'jqna-pro' ); ?>
				</p>

				<?php if ( $error_msg ) : ?>
					<div class="jqna-alert jqna-alert-error" role="alert">
						<?php echo esc_html( $error_msg ); ?>
					</div>
				<?php endif; ?>

				<form class="jqna-login-form" method="post" action="">
					<?php wp_nonce_field( 'jqna_login', 'jqna_login_nonce' ); ?>

					<div class="jqna-field">
						<label for="jqna-username">
							<?php esc_html_e( 'Username or Email', 'jqna-pro' ); ?>
						</label>
						<input
							type="text"
							id="jqna-username"
							name="jqna_username"
							autocomplete="username"
							required
						/>
					</div>

					<div class="jqna-field">
						<label for="jqna-password">
							<?php esc_html_e( 'Password', 'jqna-pro' ); ?>
						</label>
						<input
							type="password"
							id="jqna-password"
							name="jqna_password"
							autocomplete="current-password"
							required
						/>
					</div>

					<div class="jqna-field">
						<label for="jqna-phone">
							<?php esc_html_e( 'WooCommerce Order Phone Number', 'jqna-pro' ); ?>
						</label>
						<input
							type="tel"
							id="jqna-phone"
							name="jqna_phone"
							autocomplete="tel"
							required
						/>
						<span class="jqna-hint">
							<?php esc_html_e( 'Phone number used when placing your completed order.', 'jqna-pro' ); ?>
						</span>
					</div>

					<button type="submit" class="jqna-btn jqna-btn-primary">
						<?php esc_html_e( 'Verify &amp; Access', 'jqna-pro' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// ------------------------------------------------------------------
	// Q&A Page (two-column layout)
	// ------------------------------------------------------------------

	/**
	 * Render the main Q&A two-column layout.
	 *
	 * @return string HTML.
	 */
	private function render_qa_page() {
		$user_id = JQNA_Auth::get_user_id();
		$user    = $user_id ? get_user_by( 'id', $user_id ) : null;

		$categories = JQNA_Post_Type::get_categories_with_counts();

		// Initial questions query.
		$query = JQNA_Post_Type::get_questions( 0, 1 );

		$logout_url = wp_nonce_url(
			add_query_arg( 'jqna_logout', '1' ),
			'jqna_logout',
			'jqna_logout_nonce'
		);

		ob_start();
		?>
		<div class="jqna-wrap" id="jqna-wrap">

			<!-- Header bar -->
			<div class="jqna-topbar">
				<?php if ( $user ) : ?>
					<span class="jqna-welcome">
						<?php
						printf(
							/* translators: %s: display name */
							esc_html__( 'Welcome, %s', 'jqna-pro' ),
							esc_html( $user->display_name )
						);
						?>
					</span>
				<?php endif; ?>
				<a href="<?php echo esc_url( $logout_url ); ?>" class="jqna-btn jqna-btn-logout">
					<?php esc_html_e( 'Logout', 'jqna-pro' ); ?>
				</a>
			</div>

			<!-- Two-column layout -->
			<div class="jqna-layout">

				<!-- LEFT: FAQ accordion -->
				<main class="jqna-main" aria-label="<?php esc_attr_e( 'Questions and Answers', 'jqna-pro' ); ?>">
					<div class="jqna-faq-header">
						<h2><?php esc_html_e( 'Questions &amp; Answers', 'jqna-pro' ); ?></h2>
						<button class="jqna-btn jqna-btn-ghost jqna-reset-btn" id="jqna-reset" style="display:none">
							<?php esc_html_e( '✕ Reset', 'jqna-pro' ); ?>
						</button>
					</div>

					<div class="jqna-accordion-list" id="jqna-accordion-list">
						<?php
						if ( $query->have_posts() ) {
							while ( $query->have_posts() ) {
								$query->the_post();
								$answer = get_post_meta( get_the_ID(), '_jqna_answer', true );
								JQNA_Ajax::render_accordion_item( get_the_ID(), get_the_title(), $answer );
							}
							wp_reset_postdata();
						} else {
							echo '<p class="jqna-no-results">' . esc_html__( 'No questions yet.', 'jqna-pro' ) . '</p>';
						}
						?>
					</div>

					<!-- Pagination -->
					<?php if ( $query->max_num_pages > 1 ) : ?>
					<nav class="jqna-pagination" id="jqna-pagination"
						data-total="<?php echo esc_attr( $query->max_num_pages ); ?>"
						data-current="1"
						data-category="0"
						aria-label="<?php esc_attr_e( 'Question pages', 'jqna-pro' ); ?>">
						<button class="jqna-page-btn" data-action="prev" disabled>&laquo; <?php esc_html_e( 'Prev', 'jqna-pro' ); ?></button>
						<span class="jqna-page-info">
							<?php
							printf(
								/* translators: 1: current page 2: total pages */
								esc_html__( 'Page %1$s of %2$s', 'jqna-pro' ),
								'<span id="jqna-cur-page">1</span>',
								'<span id="jqna-total-pages">' . esc_html( $query->max_num_pages ) . '</span>'
							);
							?>
						</span>
						<button class="jqna-page-btn" data-action="next"><?php esc_html_e( 'Next', 'jqna-pro' ); ?> &raquo;</button>
					</nav>
					<?php else : ?>
					<nav class="jqna-pagination" id="jqna-pagination"
						data-total="1"
						data-current="1"
						data-category="0"
						style="display:none">
					</nav>
					<?php endif; ?>

					<!-- Question submission form -->
					<div class="jqna-submit-section">
						<h3><?php esc_html_e( 'Ask a Question', 'jqna-pro' ); ?></h3>
						<form id="jqna-submit-form" class="jqna-submit-form">
							<div class="jqna-field">
								<label for="jqna-q-title">
									<?php esc_html_e( 'Your Question', 'jqna-pro' ); ?> <span aria-hidden="true">*</span>
								</label>
								<input
									type="text"
									id="jqna-q-title"
									name="title"
									placeholder="<?php esc_attr_e( 'Type your question here…', 'jqna-pro' ); ?>"
									required
									maxlength="255"
								/>
							</div>

							<?php if ( ! empty( $categories ) ) : ?>
							<div class="jqna-field">
								<label for="jqna-q-cat">
									<?php esc_html_e( 'Category', 'jqna-pro' ); ?>
								</label>
								<select id="jqna-q-cat" name="cat_id">
									<option value="0"><?php esc_html_e( '— Select Category (optional) —', 'jqna-pro' ); ?></option>
									<?php foreach ( $categories as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat['id'] ); ?>">
											<?php echo esc_html( $cat['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
							<?php endif; ?>

							<button type="submit" class="jqna-btn jqna-btn-primary">
								<?php esc_html_e( 'Submit Question', 'jqna-pro' ); ?>
							</button>
							<div class="jqna-form-msg" id="jqna-submit-msg" role="alert" aria-live="polite"></div>
						</form>
					</div>
				</main>

				<!-- RIGHT: Category sidebar -->
				<aside class="jqna-sidebar" aria-label="<?php esc_attr_e( 'Question Categories', 'jqna-pro' ); ?>">
					<h3 class="jqna-sidebar-title">
						<?php esc_html_e( 'Categories', 'jqna-pro' ); ?>
					</h3>
					<?php if ( ! empty( $categories ) ) : ?>
					<ul class="jqna-cat-list" id="jqna-cat-list">
						<li>
							<button class="jqna-cat-btn active" data-cat="0">
								<?php esc_html_e( 'All Categories', 'jqna-pro' ); ?>
							</button>
						</li>
						<?php foreach ( $categories as $cat ) : ?>
						<li>
							<button class="jqna-cat-btn" data-cat="<?php echo esc_attr( $cat['id'] ); ?>">
								<?php echo esc_html( $cat['name'] ); ?>
								<span class="jqna-cat-count">(<?php echo esc_html( $cat['count'] ); ?>)</span>
							</button>
						</li>
						<?php endforeach; ?>
					</ul>
					<?php else : ?>
					<p><?php esc_html_e( 'No categories yet.', 'jqna-pro' ); ?></p>
					<?php endif; ?>
				</aside>

			</div><!-- .jqna-layout -->
		</div><!-- .jqna-wrap -->
		<?php
		return ob_get_clean();
	}
}
