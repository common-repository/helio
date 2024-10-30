<?php
/**
 * Woocommerce checkout block integration
 */

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Blocks\Registry\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Class HelioWooBlocksIntegration
 */
class HelioWooBlocksIntegration {
	/**
	 * WooBlocksIntegration constructor.
	 */
	public function __construct() {
		new HelioWooBlocksIntegrationAjax();

		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_payment_method_integrations' ) );
		add_action( 'wp_print_scripts', array( $this, 'add_styles' ), 0 , PHP_INT_MAX );
	}

	/**
	 * Register payment method integrations bundled with blocks.
	 *
	 * @param PaymentMethodRegistry $payment_method_registry Payment method registry instance.
	 */
	public function register_payment_method_integrations( PaymentMethodRegistry $payment_method_registry ) {
		$payment_method = 'helio';

		Package::container()->register(
			$payment_method,
			function () use ( $payment_method ) {
				return new HelioWooBlocksPaymentMethod( $payment_method );
			}
		);

		try {
			$payment_method_registry->register(
				Package::container()->get( $payment_method )
			);
		} catch ( Exception $e ) { //phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	public function add_styles() {
		if ( wp_script_is( 'helio-script', 'registered' ) ) {
			wp_enqueue_style('helio');
		}
	}
}
