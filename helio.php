<?php
/*
 * Plugin Name: Helio
 * Description: Start paying with crypto for goods and services without risk.
 * Author: Hel.io
 * Author URI: https://www.hel.io/
 * Version: 2.1.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * WC requires at least: 8.4.0
 * WC tested up to: 8.4.0
 * Text Domain: helio
 */

require_once 'includes/constants.php';

class Helio {
	private static $instance;
	public static $plugin_url;
	public static $images_url;
	public static $gateway_id;
	public static $plugin_path;
	public static $version;

	private function __construct() {
		self::$gateway_id  = 'helio';
		self::$plugin_url  = plugin_dir_url( __FILE__ );
		self::$images_url  = plugin_dir_url( __FILE__ ) . 'assets/img/';
		self::$plugin_path = plugin_dir_path( __FILE__ );
		self::$version     = '2.1.0';
		// Check if WooCommerce is active
		include_once ABSPATH . 'wp-admin/includes/plugin.php' ;
		if ( is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) || is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_action( 'plugins_loaded', array( $this, 'pluginsLoaded' ) );
			add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommercePaymentGateways' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ), 5 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 5 );
			add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'add_input_total' ), 5 );
			add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'check_total' ) );
			add_filter( 'woocommerce_locate_template', array( $this, 'woocommerce_locate_template' ), 10, 3 );

			add_action( 'before_woocommerce_init', function () {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				}
			} );
		}
	}

	public function woocommerce_locate_template( $template, $template_name, $template_path ) {
		global $woocommerce;
		$_template = $template;

		if ( ! $template_path ) {
			$template_path = $woocommerce->template_url;
		}

		$plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woocommerce/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		// Modification: Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}

		if ( ! $template ) {
			$template = $_template;
		}

		return $template;
	}

	public function check_total( $fragments ) {
		$total                     = floor(WC()->cart->get_total( 'float' ) * 1000000);
		$fragments['#helio-total'] = '<input type="hidden" id="helio-total" value="' . $total . '">';

		return $fragments;
	}

	public function add_input_total() {
		?>
		<input type="hidden" id="helio-total" value="<?php echo esc_attr( floor(WC()->cart->get_total( 'float' ) * 1000000) ); ?>">
		<?php
	}

	public function admin_enqueue_scripts() {
		if ( ! empty( $_GET['section'] ) && 'helio' === $_GET['section'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style(
					'helio-admin',
				self::$plugin_url . 'assets/css/style-admin.css',
				array(),
				self::$version
			);
		}
	}

	public function frontend_enqueue_scripts() {
		$settings = get_option( 'woocommerce_helio_settings' );
		$is_dev   = 'yes' === $settings[HELIO_DEVNET_ENABLED];
		$id       = $is_dev ? $settings[HELIO_PAYLINK_ID_DEVNET] : $settings[HELIO_PAYLINK_ID_MAINNET];

		if ( ! empty( $id ) ) {
			wp_register_script(
				'helio-script',
				self::$plugin_url . 'assets/helio.js?' . self::$version,
				array( 'jquery' ),
				self::$version,
				true
			);

			wp_localize_script( 'helio-script', 'JsData', array());
		}

		if ( is_checkout() ) {
			wp_enqueue_style('helio');

			wp_enqueue_script('helio-script');
		}
	}

	public function pluginsLoaded() {
		require_once 'includes/class-wc-helio-gateway.php';
		require_once 'includes/integrations/WooBlocks/HelioWooBlocksIntegration.php';
		require_once 'includes/integrations/WooBlocks/HelioWooBlocksPaymentMethod.php';
		require_once 'includes/integrations/WooBlocks/HelioWooBlocksIntegrationAjax.php';

		new HelioWooBlocksIntegration();
	}

	public function woocommercePaymentGateways( $gateways ) {
		$gateways[] = 'WC_Helio';

		return $gateways;
	}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}


Helio::getInstance();
