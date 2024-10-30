<?php
/**
 * Gateway wrapper for woocommerce checkout block integration
 */

defined( 'ABSPATH' ) || exit();

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Helio payment methods integration
 */
final class HelioWooBlocksPaymentMethod extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Wrapped gateway
	 *
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * Constructor
	 *
	 * @param string $name Payment method name/id/slug.
	 *
	 * @throws Exception If gateway not exist.
	 */
	public function __construct( $name ) {
		$this->name = $name;

		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$this->gateway    = isset($payment_gateways[$name]) ? $payment_gateways[$name] : null;

		if ( is_null( $this->gateway ) ) {
			throw new Exception( esc_attr("Gateway '$this->name' not found") );
		}
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( "woocommerce_{$this->name}_settings", array() );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$handle = "wc-helio-blocks-payment-method-$this->name";

		/**
		 * Filters the list of script dependencies.
		 *
		 * @param array  $dependencies The list of script dependencies.
		 * @param string $handle       The script's handle.
		 * @since 2.0.0
		 *
		 * @return array
		 */
		$script_dependencies = apply_filters(
			'woocommerce_blocks_register_script_dependencies',
			is_admin() ? array() : array( 'helio-script' ),
			$handle
		);

		wp_register_script(
			$handle,
			Helio::$plugin_url . 'assets/woo-blocks.js?' . Helio::$version,
			$script_dependencies,
			Helio::$version,
			true
		);

		return array( $handle );
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		return array( 'products' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$data = array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
		);

		/**
		 * Adds Helio payment method data
		 *
		 * @since 2.0.0
		 */
		$data = apply_filters( 'helio_blocks_payment_method_data', $data, $this );

		/**
		 * Add helio method data (2)
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'helio_blocks_payment_method_data_' . $this->name, $data, $this );
	}
}
