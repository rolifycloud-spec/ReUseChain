<?php
/**
 * Class description
 *
 * @package   package_name
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Blocks_Handlers' ) ) {

	/**
	 * Define Jet_Blocks_Handlers class
	 */
	class Jet_Blocks_Handlers {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Constructor for the class
		 */
		public function init() {

			add_action( 'init', array( $this, 'register_handler' ) );
			add_action( 'init', array( $this, 'login_handler' ) );
			add_action( 'init', array( $this, 'reset_handler' ) );

			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.0.0', '>=' ) ) {

				add_filter(
					'woocommerce_add_to_cart_fragments',
					array( $this, 'cart_link_fragments' )
				);

				add_filter(
					'woocommerce_widget_cart_item_quantity',
					array( $this, 'render_cart_quantity_controls' ),
					10,
					3
				);

				add_action(
					'wc_ajax_jet_blocks_update_cart_quantity',
					array( $this, 'update_cart_quantity' )
				);

			} else {

				add_filter(
					'add_to_cart_fragments',
					array( $this, 'cart_link_fragments' )
				);
			}
		}

		/**
		 * Cart link fragments
		 *
		 * @return array
		 */
		public function cart_link_fragments( $fragments ) {

			global $woocommerce;

			$jet_fragments = apply_filters( 'jet-blocks/handlers/cart-fragments', array(
				'.jet-blocks-cart__total-val' => 'jet-blocks-cart/global/cart-totals.php',
				'.jet-blocks-cart__count-val' => 'jet-blocks-cart/global/cart-count.php',
			) );

			foreach ( $jet_fragments as $selector => $template ) {
				ob_start();
				include jet_blocks()->get_template( $template );
				$fragments[ $selector ] = ob_get_clean();
			}

			return $fragments;

		}

		/**
		 * Render quantity controls for a mini cart item.
		 *
		 * @param string $quantity_html Existing WooCommerce quantity markup.
		 * @param array  $cart_item     Cart item data.
		 * @param string $cart_item_key Cart item key.
		 *
		 * @return string
		 */
		public function render_cart_quantity_controls( $quantity_html, $cart_item, $cart_item_key ) {

			if (
				empty( $cart_item['data'] )
				|| ! $cart_item['data'] instanceof WC_Product
			) {
				return $quantity_html;
			}

			$product = $cart_item['data'];

			/*
			 * Quantity cannot be changed for products
			 * that may only be purchased individually.
			 */
			if ( $product->is_sold_individually() ) {
				return $quantity_html;
			}

			$quantity     = isset( $cart_item['quantity'] )
				? wc_stock_amount( $cart_item['quantity'] )
				: 1;
			$min_quantity = max(
				1,
				wc_stock_amount( $product->get_min_purchase_quantity() )
			);
			$max_quantity = wc_stock_amount(
				$product->get_max_purchase_quantity()
			);

			$controls = sprintf(
				'<span class="jet-blocks-cart-quantity-controls"
			data-cart-item-key="%1$s"
			data-min="%2$s"
			data-max="%3$s">
			<button
				type="button"
				class="jet-blocks-cart-quantity-controls__button jet-blocks-cart-quantity-controls__button--minus"
				aria-label="%4$s"
			>&minus;</button>
			<input
				type="number"
				class="jet-blocks-cart-quantity-controls__value"
				value="%5$s"
				min="%2$s"
				%6$s
				step="1"
				readonly
				aria-label="%7$s"
			>
			<button
				type="button"
				class="jet-blocks-cart-quantity-controls__button jet-blocks-cart-quantity-controls__button--plus"
				aria-label="%8$s"
			>&plus;</button>
		</span>',
				esc_attr( $cart_item_key ),
				esc_attr( $min_quantity ),
				0 < $max_quantity ? esc_attr( $max_quantity ) : '',
				esc_attr__( 'Decrease product quantity', 'jet-blocks' ),
				esc_attr( $quantity ),
				0 < $max_quantity
					? sprintf( 'max="%s"', esc_attr( $max_quantity ) )
					: '',
				esc_attr__( 'Product quantity', 'jet-blocks' ),
				esc_attr__( 'Increase product quantity', 'jet-blocks' )
			);

			return sprintf(
				'<span class="jet-blocks-cart-quantity-static">%1$s</span>%2$s',
				wp_kses_post( $quantity_html ),
				$controls
			);
		}

		/**
		 * Update mini cart item quantity.
		 *
		 * @return void
		 */
		public function update_cart_quantity() {

			check_ajax_referer(
				'jet-blocks-cart-quantity',
				'nonce'
			);

			if (
				! class_exists( 'WooCommerce' )
				|| ! WC()->cart
			) {
				wp_send_json_error(
					array(
						'message' => esc_html__(
							'WooCommerce cart is not available.',
							'jet-blocks'
						),
					),
					400
				);
			}

			$cart_item_key = isset( $_POST['cart_item_key'] )
				? wc_clean( wp_unslash( $_POST['cart_item_key'] ) )
				: '';

			$quantity = isset( $_POST['quantity'] )
				? wc_stock_amount( wp_unslash( $_POST['quantity'] ) )
				: 0;

			if ( ! $cart_item_key || 1 > $quantity ) {
				wp_send_json_error(
					array(
						'message' => esc_html__(
							'Invalid cart item data.',
							'jet-blocks'
						),
					),
					400
				);
			}

			$cart_item = WC()->cart->get_cart_item( $cart_item_key );

			if (
				empty( $cart_item )
				|| empty( $cart_item['data'] )
				|| ! $cart_item['data'] instanceof WC_Product
			) {
				wp_send_json_error(
					array(
						'message' => esc_html__(
							'Cart item was not found.',
							'jet-blocks'
						),
					),
					404
				);
			}

			$product = $cart_item['data'];

			if ( $product->is_sold_individually() ) {
				$quantity = 1;
			}

			$min_quantity = max(
				1,
				wc_stock_amount( $product->get_min_purchase_quantity() )
			);

			$max_quantity = wc_stock_amount(
				$product->get_max_purchase_quantity()
			);

			if (
				$quantity < $min_quantity
				|| ( 0 < $max_quantity && $quantity > $max_quantity )
			) {
				wp_send_json_error(
					array(
						'message' => esc_html__(
							'The requested quantity is not available.',
							'jet-blocks'
						),
					),
					400
				);
			}

			$passed_validation = apply_filters(
				'woocommerce_update_cart_validation',
				true,
				$cart_item_key,
				$cart_item,
				$quantity
			);

			if ( ! $passed_validation ) {
				wp_send_json_error(
					array(
						'message' => esc_html__(
							'The product quantity could not be updated.',
							'jet-blocks'
						),
					),
					400
				);
			}

			$updated = WC()->cart->set_quantity(
				$cart_item_key,
				$quantity,
				true
			);

			if ( ! $updated ) {
				wp_send_json_error(
					array(
						'message' => esc_html__(
							'The product quantity could not be updated.',
							'jet-blocks'
						),
					),
					400
				);
			}

			WC_AJAX::get_refreshed_fragments();
		}

		/**
		 * Login form handler.
		 *
		 * @return void
		 */
		public function login_handler() {

			if ( ! isset( $_POST['jet_login'] ) ) { // phpcs:ignore
				return;
			}

			$recaptcha_token    = isset( $_POST['token'] ) ? $_POST['token'] : ''; // phpcs:ignore
			$recaptcha_settings = jet_blocks_settings()->get( 'captcha' );

			if ( $recaptcha_settings ) {

				if ( 'true' === $recaptcha_settings['enable']) {

					$recaptcha_instance = jet_blocks()->integration_manager->get_integration_module_instance( 'recaptcha' );

					if ( '' != $recaptcha_token ) {
						$recaptcha_verify = $recaptcha_instance->maybe_verify( $recaptcha_token );

						if( true != $recaptcha_verify ) {
							return;
						}
					}
				}
			}

			try {

				if ( empty( $_POST['log'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

					$error = sprintf(
						'<strong>%1$s</strong>: %2$s',
						__( 'ERROR', 'jet-blocks' ),
						__( 'The username field is empty.', 'jet-blocks' )
					);

					throw new Exception( $error );

				}

				$signon = wp_signon();

				if ( is_wp_error( $signon ) ) {
					throw new Exception( $signon->get_error_message() );
				}

				$raw_redirect = isset( $_POST['redirect_to'] ) // phpcs:ignore
					? wp_unslash( $_POST['redirect_to'] ) // phpcs:ignore
					: home_url( '/' );

				$redirect = wp_validate_redirect( $raw_redirect, home_url( '/' ) );
                $redirect = $this->maybe_get_role_based_login_redirect( $redirect, $signon );

				wp_safe_redirect( $redirect );
				exit;

			} catch ( Exception $e ) {
				wp_cache_set( 'jet-login-messages', $e->getMessage() );
			}

		}

        /**
         * Maybe get role based redirect URL after login.
         *
         * @param string  $default_redirect Default redirect URL.
         * @param WP_User $user             Logged in user.
         *
         * @return string
         */
        public function maybe_get_role_based_login_redirect( $default_redirect, $user ) {

            if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
                return $default_redirect;
            }

            if (
                empty( $_POST['jet_login_redirect_type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
                || 'role_based' !== sanitize_key( wp_unslash( $_POST['jet_login_redirect_type'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            ) {
                return $default_redirect;
            }

            if (
                empty( $_POST['jet_login_role_based_redirect_nonce'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
                || ! wp_verify_nonce(
                    sanitize_text_field( wp_unslash( $_POST['jet_login_role_based_redirect_nonce'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    'jet_login_role_based_redirect'
                )
            ) {
                return $default_redirect;
            }

            if ( empty( $_POST['jet_login_role_based_redirects'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                return $default_redirect;
            }

            $redirects = json_decode(
                wp_unslash( $_POST['jet_login_role_based_redirects'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
                true
            );

            if ( empty( $redirects ) || ! is_array( $redirects ) ) {
                return $default_redirect;
            }

            foreach ( $redirects as $redirect_rule ) {

                if ( empty( $redirect_rule['role'] ) || empty( $redirect_rule['url'] ) ) {
                    continue;
                }

                $role = sanitize_key( $redirect_rule['role'] );
                $url  = trim( do_shortcode( $redirect_rule['url'] ) );

                if ( ! in_array( $role, (array) $user->roles, true ) ) {
                    continue;
                }

                if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
                    $url = home_url( $url );
                }

                return wp_validate_redirect( esc_url_raw( $url ), $default_redirect );
            }

            return $default_redirect;
        }

		/**
		 * Registration handler
		 *
		 * @return void
		 */
		public function register_handler() {

			if ( ! isset( $_POST['jet-register-nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['jet-register-nonce'], 'jet-register' ) ) { // phpcs:ignore
				return;
			}

			$recaptcha_token    = isset( $_POST['token'] ) ? $_POST['token'] : ''; // phpcs:ignore
			$recaptcha_settings = jet_blocks_settings()->get( 'captcha' );

			if ( $recaptcha_settings ) {

				if ( 'true' === $recaptcha_settings['enable']) {

					$recaptcha_instance = jet_blocks()->integration_manager->get_integration_module_instance( 'recaptcha' );

					if ( '' != $recaptcha_token ) {
						$recaptcha_verify = $recaptcha_instance->maybe_verify( $recaptcha_token );

						if( true != $recaptcha_verify ) {
							return;
						}
					}
				}

			}
			
			try {

				$username           = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
				$password           = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$email              = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
				$confirm_password   = isset( $_POST['jet_confirm_password'] ) ? filter_var( wp_unslash( $_POST['jet_confirm_password'] ), FILTER_VALIDATE_BOOLEAN ) : false;
				$confirmed_password = isset( $_POST['password-confirm'] ) ? (string) wp_unslash( $_POST['password-confirm'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( $confirm_password && $password !== $confirmed_password ) {
					throw new Exception( esc_html__( 'Entered passwords don\'t match', 'jet-blocks' ) );
				}

				$validation_error = new WP_Error();

				$user = $this->create_user( $username, sanitize_email( $email ), $password );

				if ( is_wp_error( $user ) ) {
					throw new Exception( $user->get_error_message() );
				}

				global $current_user;
				$current_user = get_user_by( 'id', $user );
				wp_set_auth_cookie( $user, true );

				if ( ! empty( $_POST['jet_redirect'] ) ) { // phpcs:ignore
					$redirect = wp_sanitize_redirect( $_POST['jet_redirect'] ); // phpcs:ignore
				} else { // phpcs:ignore
					$redirect = $_POST['_wp_http_referer']; // phpcs:ignore
				} // phpcs:ignore
				wp_redirect( $redirect ); // phpcs:ignore
				exit; // phpcs:ignore

			} catch ( Exception $e ) {
				wp_cache_set( 'jet-register-messages', $e->getMessage() );
			}

		}

		/**
		 * Reset form handler.
		 *
		 * @return void
		 */
		public function reset_handler() {
			$action = isset( $_REQUEST['jet_reset_action'] ) ? $_REQUEST['jet_reset_action'] : ''; // phpcs:ignore

			if ( 'jet_reset_pass_reset' !== $action ) {
				return;
			}

			if ( ! ( isset( $_REQUEST['jet_reset_nonce'] )
				&& wp_verify_nonce( wp_unslash( $_REQUEST['jet_reset_nonce'] ), 'jet_reset_pass_reset' ) ) ) { // phpcs:ignore
				$args       = array();
				$error      = new \WP_Error( 'jet_reset_error', '<strong>ERROR</strong>: ' . esc_html__( 'something went wrong with that!', 'jet-blocks' ) );
				$site_title = get_bloginfo( 'name', 'display' );
				wp_die( $error, $site_title . ' - ' . esc_html__( 'Error', 'jet-blocks' ), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$recaptcha_token    = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
			$recaptcha_settings = jet_blocks_settings()->get( 'captcha' ); // phpcs:ignore

			if ( $recaptcha_settings ) {

				if ( 'true' === $recaptcha_settings['enable']) {

					$recaptcha_instance = jet_blocks()->integration_manager->get_integration_module_instance( 'recaptcha' );

					if ( '' != $recaptcha_token ) {
						$recaptcha_verify = $recaptcha_instance->maybe_verify( $recaptcha_token );

						if( true != $recaptcha_verify ) {
							return;
						}
					}
				}

			}

			$errors           = array();
			$user_pass        = isset( $_POST['jet_reset_new_user_pass'] )
				? trim( (string) wp_unslash( $_POST['jet_reset_new_user_pass'] ) ) // phpcs:ignore
				: ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$user_pass_repeat = isset( $_POST['jet_reset_new_user_pass_again'] )
				? trim( (string) wp_unslash( $_POST['jet_reset_new_user_pass_again'] ) ) // phpcs:ignore
				: ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( empty( $user_pass ) || empty( $user_pass_repeat ) ) {
				$errors['no_password'] = esc_html__( 'Please enter a new password.', 'jet-blocks' );
				$_REQUEST['errors']    = $errors;
				return;
			} elseif ( $user_pass !== $user_pass_repeat ) {
				$errors['password_mismatch'] = esc_html__( 'The passwords don\'t match.', 'jet-blocks' );
				$_REQUEST['errors']          = $errors;
				return;
			}

			$key     = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
			$user_id = isset( $_GET['uid'] ) ? sanitize_text_field( wp_unslash( $_GET['uid'] ) ) : '';

			if ( empty( $key ) || empty( $user_id ) ) {
				$errors['key_login'] = esc_html__( 'The reset link is not valid.', 'jet-blocks' );
				$_REQUEST['errors']  = $errors;
				wp_safe_redirect( get_permalink() );
				exit;
			}

			$userdata = get_userdata( absint( $user_id ) );
			$login    = $userdata ? $userdata->user_login : '';
			$user     = check_password_reset_key( $key, $login );

			if ( is_wp_error( $user ) ) {

				if ( $user->get_error_code() === 'expired_key' ) {

					$errors['expired_key'] = esc_html__( 'Sorry, that key has expired. Please reset your password again.', 'jet-blocks' );

				} else {

					$errors['invalid_key'] = esc_html__( 'Sorry, that key does not appear to be valid. Please reset your password again.', 'jet-blocks' );

				}

			}

			if ( ! empty( $errors ) ) {
				$_REQUEST['errors'] = $errors;
				return;
			}


			do_action( 'validate_password_reset', new \WP_Error(), $user );

			reset_password( $user, $user_pass );

			$raw_success = isset( $_POST['jet-reset-success-redirect'] )
				? wp_unslash( $_POST['jet-reset-success-redirect'] ) // phpcs:ignore
				: '';

			$redirect_page_url = wp_validate_redirect( $raw_success, get_permalink( 0 ) );
			wp_safe_redirect( $redirect_page_url );
			exit;

		}

		/**
		 * Create new user function
		 *
		 * @param  [type] $username [description]
		 * @param  [type] $email    [description]
		 * @param  [type] $password [description]
		 * @return [type]           [description]
		 */
		public function create_user( $username, $email, $password ) {

			// Check username
			if ( empty( $username ) || ! validate_username( $username ) ) {
				return new WP_Error(
					'registration-error-invalid-username',
					__( 'Please enter a valid account username.', 'jet-blocks' )
				);
			}

			if ( username_exists( $username ) ) {
				return new WP_Error(
					'registration-error-username-exists',
					__( 'An account is already registered with that username. Please choose another.', 'jet-blocks' )
				);
			}

			// Check the email address.
			if ( empty( $email ) || ! is_email( $email ) ) {
				return new WP_Error(
					'registration-error-invalid-email',
					__( 'Please provide a valid email address.', 'jet-blocks' )
				);
			}

			if ( email_exists( $email ) ) {
				return new WP_Error(
					'registration-error-email-exists',
					__( 'An account is already registered with your email address. Please log in.', 'jet-blocks' )
				);
			}

			// Check password
			if ( empty( $password ) ) {
				return new WP_Error(
					'registration-error-missing-password',
					__( 'Please enter an account password.', 'jet-blocks' )
				);
			}

			$custom_error = apply_filters( 'jet_register_form_custom_error', null );

			if ( is_wp_error( $custom_error ) ){
				return $custom_error;
			}

			$new_user_data = array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email,
			);

			$user_id = wp_insert_user( $new_user_data );

			if ( is_wp_error( $user_id ) ) {
				return new WP_Error(
					'registration-error',
					'<strong>' . __( 'Error:', 'jet-blocks' ) . '</strong> ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'jet-blocks' )
				);
			}

			return $user_id;

		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}
	}

}

/**
 * Returns instance of Jet_Blocks_Handlers
 *
 * @return object
 */
function jet_blocks_handlers() {
	return Jet_Blocks_Handlers::get_instance();
}
