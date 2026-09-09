<?php
/**
 * Rating notice handler.
 *
 * @package Table_Addons_For_Elementor
 */

/**
 * Handles the admin rating notice lifecycle and AJAX actions.
 */
class Table_Addons_For_Elementor_Rating {

	/**
	 * Singleton instance.
	 *
	 * @var Table_Addons_For_Elementor_Rating|null
	 */
	private static $instance = null;

	/**
	 * Plugin display name.
	 *
	 * @var string
	 */
	private $plugin_name = 'Table Addons for Elementor';

	/**
	 * Plugin slug used for option names.
	 *
	 * @var string
	 */
	private $plugin_slug = 'table-addons-for-elementor';

	/**
	 * Plugin prefix used for AJAX action names.
	 *
	 * @var string
	 */
	private $plugin_prefix = 'tafe';

	/**
	 * Plugin logo URL.
	 *
	 * @var string
	 */
	private $plugin_logo_url = 'https://ps.w.org/table-addons-for-elementor/assets/icon-256x256.jpg';

	/**
	 * Plugin review URL.
	 *
	 * @var string
	 */
	private $plugin_review_url = 'https://wordpress.org/support/plugin/table-addons-for-elementor/reviews/';

	/**
	 * Plugin support URL.
	 *
	 * @var string
	 */
	private $plugin_help_url = 'https://fusionplugin.com/contact-us/';

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Table_Addons_For_Elementor_Rating
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'maybe_set_install_date' ) );
		add_action( 'admin_notices', array( $this, 'show_optin_notice' ) );
		add_action( 'wp_ajax_' . $this->plugin_prefix . '_rating_action', array( $this, 'handle_rating_action' ) );
	}

	/**
	 * Render the rating notice.
	 */
	public function show_optin_notice() {
		if ( ! $this->should_show_notice() ) {
			return;
		}

		$nonce  = wp_create_nonce( $this->plugin_prefix . '_rating_nonce' );
		$action = $this->plugin_prefix . '_rating_action';

		?>
		<style>
			.rating-notice-box {
				display: flex;
				align-items: center;
				gap: 15px;
				padding: 8px 8px !important;
			}

			.rating-notice-box .notice-logo {
				flex: 0 0 auto;
				width: 100px;
				height: 100px;
				max-width: 100px;
				border-radius: 16px;
				box-shadow: 0 8px 20px rgba(37, 99, 235, 0.14);
			}

			.rating-notice-box .notice-content p:first-child {
				padding-top: 0;
				margin-top: 0;
			}

			.rating-notice-box p {
				margin: 0 0 10px;
			}

			.rating-notice-box p:last-child {
				display: flex;
				flex-wrap: wrap;
				align-items: center;
				gap: 14px;
				margin-bottom: 0;
			}

			.rating-notice-box .tafe-already-given,
			.rating-notice-box .tafe-need-support,
			.rating-notice-box .tafe-not-good-enough {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				padding-bottom: 0;
				text-decoration: none;
			}

			.rating-notice-box .notice-icon {
				width: 18px;
				height: 18px;
				font-size: 18px;
			}

			@media (max-width: 782px) {
				.rating-notice-box {
					flex-direction: column;
					align-items: flex-start;
				}

				.rating-notice-box .notice-logo {
					width: 64px;
					height: 64px;
					max-width: 64px;
				}
			}
		</style>
		<div class="notice updated rating-notice-box <?php echo esc_attr( $this->plugin_prefix ); ?>-rating-notice">
			<img class="notice-logo" src="<?php echo esc_url( $this->plugin_logo_url ); ?>" alt="<?php echo esc_attr( $this->plugin_name ); ?>">
			<div class="notice-content">
					<p>
						<?php
						/* translators: %s: Plugin name. */
						$message_template = __( 'Hello! It looks like you have been using %s. Thank you so much for your support!<br />Could you please do us a <strong>big favor</strong> and give it a <strong>5-star</strong> rating on WordPress? It motivates our team and helps other users feel confident in choosing the plugin.', 'table-addons-for-elementor' );
						$message          = sprintf( $message_template, esc_html( $this->plugin_name ) );

						echo wp_kses_post( $message );
						?>
					</p>
				<p>
					<a href="<?php echo esc_url( $this->plugin_review_url ); ?>" class="button button-primary tafe-rating-action tafe-deserve" data-rating-action="done" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ok, you deserved it', 'table-addons-for-elementor' ); ?></a>
					<a href="#" class="tafe-rating-action tafe-already-given" data-rating-action="done"><i class="notice-icon dashicons-before dashicons-smiley"></i><?php esc_html_e( 'I already did', 'table-addons-for-elementor' ); ?></a>
					<a href="<?php echo esc_url( $this->plugin_help_url ); ?>" class="tafe-rating-action tafe-need-support" data-rating-action="support" target="_blank" rel="noopener noreferrer"><i class="notice-icon dashicons-before dashicons-sos"></i><?php esc_html_e( 'I need support', 'table-addons-for-elementor' ); ?></a>
					<a href="#" class="tafe-rating-action tafe-not-good-enough" data-rating-action="not_good"><i class="notice-icon dashicons-before dashicons-thumbs-down"></i><?php esc_html_e( 'No, not good enough', 'table-addons-for-elementor' ); ?></a>
				</p>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var $notice = $('.<?php echo esc_js( $this->plugin_prefix ); ?>-rating-notice');

				$notice.on('click', '.tafe-rating-action', function(event) {
					event.preventDefault();

					var $button = $(this);
					var targetUrl = $button.attr('href');
					var targetWindow = $button.attr('target');

					if ($button.hasClass('is-loading')) {
						return;
					}

					$button.addClass('is-loading');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							action: <?php echo wp_json_encode( $action ); ?>,
							nonce: <?php echo wp_json_encode( $nonce ); ?>,
							rating_action: $button.data('rating-action')
						}
					}).done(function(response) {
						if (!response || !response.success) {
							return;
						}

						$notice.slideUp(200, function() {
							$(this).remove();
						});

						if (targetUrl && targetUrl !== '#') {
							if (targetWindow === '_blank') {
								window.open(targetUrl, '_blank', 'noopener,noreferrer');
							} else {
								window.location.href = targetUrl;
							}
						}
					}).always(function() {
						$button.removeClass('is-loading');
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Determine whether the rating notice should be displayed.
	 *
	 * @return bool
	 */
	private function should_show_notice() {
		$rating_done     = get_option( $this->plugin_slug . '_rating_done', false );
		$rating_not_good = get_option( $this->plugin_slug . '_rating_not_good', false );
		$rating_later    = get_option( $this->plugin_slug . '_rating_later', false );
		$later_until     = (int) get_option( $this->plugin_slug . '_rating_later_until', 0 );
		$install_date    = (int) get_option( $this->plugin_slug . '_install_date', 0 );
		$current_time    = time();

		if ( $rating_done || $rating_not_good ) {
			return false;
		}

		if ( ! $install_date || ( $current_time - $install_date ) < ( 15 * DAY_IN_SECONDS ) ) {
			return false;
		}

		if ( $rating_later && $later_until && $current_time < $later_until ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Handle notice button AJAX actions.
	 */
	public function handle_rating_action() {
		check_ajax_referer( $this->plugin_prefix . '_rating_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Permission denied.', 'table-addons-for-elementor' ),
				)
			);
		}

		$rating_action = isset( $_POST['rating_action'] ) ? sanitize_key( wp_unslash( $_POST['rating_action'] ) ) : '';

		switch ( $rating_action ) {
			case 'done':
				update_option( $this->plugin_slug . '_rating_done', true );
				delete_option( $this->plugin_slug . '_rating_later_until' );
				wp_send_json_success();
				break;

			case 'support':
				$this->set_rating_later( 14 );
				wp_send_json_success();
				break;

			case 'not_good':
				if ( get_option( $this->plugin_slug . '_rating_later', false ) ) {
					update_option( $this->plugin_slug . '_rating_not_good', true );
					delete_option( $this->plugin_slug . '_rating_later_until' );
				} else {
					$this->set_rating_later( 30 );
				}
				wp_send_json_success();
				break;
		}

		wp_send_json_error(
			array(
				'message' => esc_html__( 'Invalid rating action.', 'table-addons-for-elementor' ),
			)
		);
	}

	/**
	 * Set the deferred notice expiration.
	 *
	 * @param int $days Number of days to defer the notice.
	 */
	private function set_rating_later( $days ) {
		update_option( $this->plugin_slug . '_rating_later', true );
		update_option( $this->plugin_slug . '_rating_later_until', time() + ( DAY_IN_SECONDS * (int) $days ) );
	}

	/**
	 * Store the initial install timestamp once.
	 */
	public function maybe_set_install_date() {
		if ( ! get_option( $this->plugin_slug . '_install_date', false ) ) {
			update_option( $this->plugin_slug . '_install_date', time() );
		}
	}
}

// Initialize the rating class.
Table_Addons_For_Elementor_Rating::instance();
