<?php
/**
 * Woocommerce checkout block integration ajax handler
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class HelioWooBlocksIntegrationAjax
 */
class HelioWooBlocksIntegrationAjax {
	public function __construct() {
		add_action( 'wc_ajax_helio_checkout', array( $this, 'checkout' ) );
	}

	public function checkout() {
		if ( isset( $_SERVER['CONTENT_TYPE'] ) && 'application/json' === $_SERVER['CONTENT_TYPE'] && empty( $_POST ) ) {
			try {
				$_POST = json_decode( file_get_contents( 'php://input' ), true );
			} catch ( Exception $e ) {
				wp_send_json(array(
					'success' => false,
					'message' => __( 'Request error. Try again or contact us', 'helio' ),
				));
			}
		}

		$nonce_value = wc_get_var( $_POST['_wpnonce'], '' ); // phpcs:ignore
		$order_id = wc_get_var( $_POST['order_id'], 0 ); // phpcs:ignore
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( empty( $order_id ) || empty( $available_gateways['helio'] ) || empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, HELIO_WOO_NONCE ) ) {
			wp_send_json(array(
				'success' => false,
				'message' => __( 'Request not allowed', 'helio' ),
			));
		}

		$result = $available_gateways['helio']->process_payment( $order_id );

		wp_send_json( is_array( $result ) ? $result : array( 'result' => $result ) );
	}
}
