<?php
/**
 * CoCart - Clear Cart controller
 *
 * Handles the request to clear the cart with /cart/clear endpoint.
 *
 * @author  Sébastien Dumont
 * @package CoCart\API\v2
 * @since   3.0.0
 * @version 3.1.0
 * @license GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CoCart REST API v2 - Clear Cart controller class.
 *
 * @package CoCart\API
 * @extends CoCart_Cart_V2_Controller
 */
class CoCart_Clear_Cart_V2_Controller extends CoCart_Cart_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'cocart/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cart/clear';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Clear Cart - cocart/v2/cart/clear (POST).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clear_cart' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
				'allow_batch' => array( 'v1' => true ),
			)
		);
	} // END register_routes()

	/**
	 * Clears the cart.
	 *
	 * @throws CoCart_Data_Exception Exception if invalid data is detected.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 3.1.0
	 * @param   WP_REST_Request $request Full details about the request.
	 * @return  WP_REST_Response
	 */
	public function clear_cart( $request = array() ) {
		try {
			// We need the cart key to force a session save later.
			$cart_key = WC()->session->get_customer_unique_id();

			do_action( 'cocart_before_cart_emptied' );

			// Clear all cart fees via session as we cant do it via the fee api.
			WC()->session->set( 'cart_fees', array() );

			// Clear cart.
			WC()->cart->cart_contents = array();
			WC()->session->cart       = array();

			// Clear removed items if not kept.
			if ( ! $request['keep_removed_items'] ) {
				WC()->cart->removed_cart_contents = array();
			}

			// Reset everything.
			WC()->cart->shipping_methods           = array();
			WC()->cart->coupon_discount_totals     = array();
			WC()->cart->coupon_discount_tax_totals = array();
			WC()->cart->applied_coupons            = array();
			WC()->cart->totals                     = array(
				'subtotal'            => 0,
				'subtotal_tax'        => 0,
				'shipping_total'      => 0,
				'shipping_tax'        => 0,
				'shipping_taxes'      => array(),
				'discount_total'      => 0,
				'discount_tax'        => 0,
				'cart_contents_total' => 0,
				'cart_contents_tax'   => 0,
				'cart_contents_taxes' => array(),
				'fee_total'           => 0,
				'fee_tax'             => 0,
				'fee_taxes'           => array(),
				'total'               => 0,
				'total_tax'           => 0,
			);

			/**
			 * If the user is authorized and `woocommerce_persistent_cart_enabled` filter is left enabled
			 * then we will delete the persistent cart as well.
			 */
			if ( get_current_user_id() && apply_filters( 'woocommerce_persistent_cart_enabled', true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				delete_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id() );
			}

			do_action( 'cocart_cart_emptied' );

			/**
			 * We force the session to update in the database as we
			 * cannot wait for PHP to shutdown to trigger the save
			 * should it fail to do so later.
			 */
			WC()->session->update_cart( $cart_key );

			if ( WC()->cart->is_empty() ) {
				do_action( 'cocart_cart_cleared' );

				$message = __( 'Cart is cleared.', 'cart-rest-api-for-woocommerce' );

				/**
				 * Filters message about the cart being cleared.
				 *
				 * @since 2.1.0
				 * @param string $message Message.
				 */
				$message = apply_filters( 'cocart_cart_cleared_message', $message );

				// Add notice.
				wc_add_notice( $message );

				// Return cart response.
				$controller = new CoCart_Cart_V2_Controller();
				$response   = $controller->get_cart_contents( $request );

				return CoCart_Response::get_response( $response, $this->namespace, $this->rest_base );
			} else {
				$message = __( 'Clearing the cart failed!', 'cart-rest-api-for-woocommerce' );

				/**
				 * Filters message about the cart failing to clear.
				 *
				 * @since 2.1.0
				 * @param string $message Message.
				 */
				$message = apply_filters( 'cocart_clear_cart_failed_message', $message );

				throw new CoCart_Data_Exception( 'cocart_clear_cart_failed', $message, 404 );
			}
		} catch ( CoCart_Data_Exception $e ) {
			return CoCart_Response::get_error_response( $e->getErrorCode(), $e->getMessage(), $e->getCode(), $e->getAdditionalData() );
		}
	} // END clear_cart()

	/**
	 * Get the query params for clearing the cart.
	 *
	 * @access public
	 * @since  3.1.0
	 * @return array $params
	 */
	public function get_collection_params() {
		// Cart query parameters.
		$params = parent::get_collection_params();

		// Add to cart query parameters.
		$params += array(
			'keep_removed_items' => array(
				'required'          => false,
				'default'           => false,
				'description'       => __( 'Keeps removed items in session when clearing the cart.', 'cart-rest-api-for-woocommerce' ),
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		return $params;
	} // END get_collection_params()
} // END class
