<?php
require_once 'fiat-rates.php';
require_once 'helio-api.php';


class WC_Helio extends WC_Payment_Gateway {


	public static $PRICING_CURRENCY = 'USDC';

	/**
	 * Is using Helio devnet (testnet) Pay Link
	 *
	 * @var bool
	 */
	private $is_devnet;
	/**
	 * Currency for Pay Link
	 *
	 * @var string
	 */
	private $currency;
	/**
	 * Pay Link ID
	 *
	 * @var string
	 */
	private $paylink_id;

	/**
	 * Is dark mode turned on?
	 *
	 * @var bool
	 */
	private $is_dark_mode;
	/**
	 * Is logging enabled?
	 *
	 * @var bool
	 */
	public $logging;
	/**
	 * Woo Commerce supports array
	 *
	 * @var array|string[]
	 */
	public $supports;
	/**
	 * Description of our plugin
	 *
	 * @var string
	 */
	public $method_description;
	/**
	 * Helio payment provider title
	 *
	 * @var string
	 */
	public $method_title;
	/**
	 * Order button text
	 *
	 * @var string
	 */
	public $order_button_text;
	/**
	 * Helio ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * API client
	 *
	 * @var HelioApi
	 */
	private $helio_api;

	public function __construct() {
		$this->id                 = 'helio';
		$this->order_button_text  = 'Pay with crypto using Helio';
		$this->method_title       = 'Helio';
		$this->method_description = 'Pay with crypto using Helio';
		$this->supports           = array( 'products' );
		$this->description        = "<div><a href='https://hel.io?utm_source=woo-checkout&utm_medium=woo-checkout&utm_campaign=what-is-helio&utm_id=woo-checkout' target='_blank'>What is Helio?</a></div>";
		$this->logging            = 'yes' === $this->get_option(HELIO_LOGGING_ENABLED);
		$this->is_devnet          = 'yes' === $this->get_option(HELIO_DEVNET_ENABLED);
		$this->is_dark_mode       = 'on' === $this->get_option(HELIO_MODE_THEME);
		$this->paylink_id         = $this->is_devnet ? $this->get_option(HELIO_PAYLINK_ID_DEVNET) : $this->get_option(HELIO_PAYLINK_ID_MAINNET);
		$this->currency           = self::$PRICING_CURRENCY;

		$this->helio_api = new HelioApi(
			$this->is_devnet,
			array(
				'api_key' => $this->get_option($this->is_devnet ? HELIO_API_KEY_DEVNET : HELIO_API_KEY_MAINNET),
				'api_secret' => $this->get_option($this->is_devnet ? HELIO_API_SECRET_DEVNET : HELIO_API_SECRET_MAINNET),
			)
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
	}

	public function get_icon() {
		return "<div style='display: flex; flex-wrap: wrap; justify-content: center;'>
<img src='https://helio-assets.s3.eu-west-1.amazonaws.com/images/woo-commerce-helio-logo.png' style='width: 164px; height: 26px' alt='Helio' /></div>";
	}


	public function init_form_fields() {
		$fields = array(
			'logo' => array(
				'title' => __('Activate Helio Pay', 'helio'),
				'label' => __('Enable Helio Pay', 'helio'),
				'type' => 'logo',
			),
			'enabled' => array(
				'title' => __('Activate Helio Pay', 'helio'),
				'label' => __('Enable Helio Pay', 'helio'),
				'type' => 'checkbox',
				'description' => '',
				'default' => 'no',
			),
			'title' => array(
				'title' => 'Title',
				'type' => 'text',
				'default' => 'Pay with your crypto wallet',
				'desc_tip' => true,
			),
			HELIO_PAYLINK_ID_MAINNET => array(
				'title' => __('Pay Link request ID', 'helio'),
				'type' => 'text',
				'description' => __('Your Pay Link request ID from integration step <br><a target="_blank" href="https://docs.hel.io/developers/helio-pay-button">Need help with integration?</a><br><a target="_blank" href="https://docs.hel.io/developers/test-on-dev.hel.io ">Create Your Paylink on Helio</a>',
					'helio'),
				'default' => '',
			),
			HELIO_API_KEY_MAINNET => array(
				'title' => __('API Key (Mainnet/production)', 'helio'),
				'type' => 'text',
				'description' => __('Api key from Helio dashboard (mainnet/production)',
					'helio'),
				'default' => '',
			),
			HELIO_API_SECRET_MAINNET => array(
				'title' => __('Secret API Key (Mainnet/production)', 'helio'),
				'type' => 'text',
				'description' => __('Secret API key from Helio dashboard (Mainnet/production)',
					'helio'),
				'default' => '',
			),

			HELIO_MODE_THEME => array(
				'title' => __('Light/dark theme', 'helio'),
				'type' => 'select',
				'options' => array(
					'off' => __('Light mode (default)', 'helio'),
					'on' => __('Dark mode', 'helio'),
				),
				'default' => 'browser',
			),
			HELIO_DEVNET_ENABLED => array(
				'title' => __('Use devnet for payments', 'helio'),
				'label' => __('Enabled', 'helio'),
				'type' => 'checkbox',
				'description' => '',
				'default' => 'no',
			),
			HELIO_PAYLINK_ID_DEVNET => array(
				'title' => __('Paylink request ID for Devnet', 'helio'),
				'type' => 'text',
				'description' => __('Your paylink request ID from integration step <br><a target="_blank" href="https://docs.hel.io/developers/woocommerce-plugin">Learn more about testing</a>',
					'helio'),
				'default' => '',
			),
			HELIO_API_KEY_DEVNET => array(
				'title' => __('API Key (Devnet/testnet)', 'helio'),
				'type' => 'text',
				'description' => __('Api key from Helio dashboard (devnet/testnet)',
					'helio'),
				'default' => '',
			),
			HELIO_API_SECRET_DEVNET => array(
				'title' => __('Secret API Key (Devnet/testnet)', 'helio'),
				'type' => 'text',
				'description' => __('Secret API key from Helio dashboard (devnet/testnet)',
					'helio'),
				'default' => '',
			),

			HELIO_LOGGING_ENABLED => array(
				'title' => __('Enable logging', 'helio'),
				'label' => __('Enabled', 'helio'),
				'type' => 'checkbox',
				'description' => '',
				'default' => 'no',
			),
		);

		$this->form_fields = $fields;
	}

	/**
	 * Generate WebHook Status HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 *
	 * @return string
	 * @see WC_Settings_API::generate_settings_html
	 */
	public function generate_logo_html( $key, $data ) {
		ob_start();
		?>
		<img
			alt="Helio"
			src="<?php echo esc_attr(Helio::$plugin_url . 'assets/img/logo.png'); ?>">
		<?php

		return ob_get_clean();
	}

	protected function get_total_in_usdc( $order ) {
		$orderTotal    = $order->get_total();
		$orderCurrency = get_option('woocommerce_currency');

		try {
			$fiatRates = new FiatRates();
			return $fiatRates->get_usdc_amount($orderCurrency, $orderTotal);
		} catch (Exception $error) {
			$this->log('Error getting fiat currency exchange rate for ' . $orderCurrency . ' ' . $error->getMessage());
			throw $error;
		}
	}

	public function get_modal( $order ) {
		ob_start();

		wc_get_template(
			'modal.php',
			array(),
			'',
			Helio::$plugin_path . 'templates/'
		);

		return ob_get_clean();
	}

	private function submitted_post_data() {
		if (isset($_SERVER['CONTENT_TYPE']) && 'application/json' === $_SERVER['CONTENT_TYPE'] && empty($_POST)) {
			try {
				return json_decode(file_get_contents('php://input'), true);
			} catch (Exception $e) {
				throw new Exception(esc_attr(__('Request error. Try again or contact us', 'helio')));
			}
		}

		if (!isset($_POST['_wpnonce'])) {
			// never return data for blocks if no nonce is sent.
			return '';
		}

		if (wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), HELIO_WOO_NONCE)) {
			return $_POST;
		} else {
			throw new Exception('Invalid request - incorrect nonce value');
		}
	}

	public function process_payment( $order_id ) {
		if (empty($this->paylink_id)) {
			wc_add_notice(__('Payment method not configured', 'helio'), 'error');
			return false;
		}

		if ($this->is_devnet) {
			if (empty($this->get_option(HELIO_API_KEY_DEVNET))) {
				wc_add_notice(__('Devnet api key not configured for Helio checkout. Please go to app.dev.hel.io and configure a set of API keys to enable.', 'helio'), 'error');
				return false;
			}
			if (empty($this->get_option(HELIO_API_SECRET_DEVNET))) {
				wc_add_notice(__('Devnet api secret not configured for Helio checkout. Please go to app.dev.hel.io and configure a set of API keys to enable', 'helio'), 'error');
				return false;
			}
		} else {
			if (empty($this->get_option(HELIO_API_KEY_MAINNET))) {
				wc_add_notice(__('Mainnet api key not configured for Helio checkout. Please go to app.hel.io and configure a set of API keys to enable', 'helio'), 'error');
				return false;
			}
			if (empty($this->get_option(HELIO_API_SECRET_MAINNET))) {
				wc_add_notice(__('Mainnet api secret not configured for Helio checkout. Please go to app.hel.io and configure a set of API keys to enable', 'helio'), 'error');
				return false;
			}
		}


		$order = wc_get_order($order_id);

		if (empty($this->get_submitted_transaction_id())) {
			return $this->send_modal_params($order);
		}

		return $this->received_payment($order);
	}

	private function get_submitted_transaction_id() {
		return $this->get_post_field('transactionId');
	}

	private function get_post_field( $fieldName ) {
		$post_data = $this->submitted_post_data();

		if (isset($post_data[$fieldName])) {
			return wc_clean(wp_unslash($post_data[$fieldName]));
		}

		return '';
	}


	private function numPostsWithTransactionId( $id ) {
		$postsWithSameTransactionIdQuery = new WP_Query(
			array(
				'post_status' => 'any',
				'post_type' => get_post_types('', 'names'),

				'meta_query' => array(
					array(
						'key' => HELIO_TX_ID,
						'value' => $id,
						'compare' => '=',
					),
				),
			)
		);

		return $postsWithSameTransactionIdQuery->post_count;
	}

	private function validate( $order_id, $submittedData, $apiData ) {
		$order = wc_get_order( $order_id );

		$original_total       = $order->get_meta(HELIO_ORDER_TOTAL, true);
		$original_currency    =  $order->get_meta( HELIO_ORDER_CURRENCY, true);
		$original_quoted_at   =  $order->get_meta( HELIO_QUOTED_AT, true);
		$original_usdc_amount =  $order->get_meta( HELIO_USDC_AMOUNT, true);
		$original_nonce       =  $order->get_meta( HELIO_NONCE, true);

		if ($this->numPostsWithTransactionId($submittedData['transactionId']) > 0) {
			return 'Transaction ' . esc_html(substr($submittedData['transactionId'], 0, 6)) . '... already has an order associated with it';
		}

		if (!$submittedData['nonce']) {
			return 'Missing validation security nonce. Payment went through on blockchain, but unable to process';
		}

		if ($submittedData['nonce'] !== $original_nonce) {
			return 'Security nonce value does not match. Payment went through on blockchain, but unable to process';
		}

		if (!$original_currency || $submittedData['orderCurrency'] !== $original_currency) {
			return 'Currency has changed since giving quote. Payment went through on blockchain, but unable to process';
		}

		if (!$original_total || $original_total !== $submittedData['total']) {
			return 'Order total has changed since giving quote. Payment went through on blockchain, but unable to process';
		}

		if ($original_quoted_at < $submittedData['expiresAt']) {
			return 'Order quoted at has expired. Payment went through on blockchain, but unable to process.';
		}

		if ($apiData['additionalJSON']->checkoutNonce !== $original_nonce) {
			return 'AdditionalJSON nonce ' . esc_html($apiData['additionalJSON']->checkoutNonce) . ' did not match expected nonce value of ' . esc_html($original_nonce);
		}

		if ((string) $apiData['additionalJSON']->orderID !== (string) $order_id) {
			return 'Order id ' . esc_html($apiData['additionalJSON']->orderID) . ' did not match expected nonce value of ' . esc_html($order_id);
		}

		if (empty($apiData['tokenQuote'])) {
			return 'Missing token quote information, cannot validate payment';
		}

		if ($apiData['tokenQuote']->from !== self::$PRICING_CURRENCY) {
			return 'Can only process ' . self::$PRICING_CURRENCY . ' payments, but payment pricing currency was ' . esc_html($apiData['tokenQuote']->from);
		}

		if ($apiData['paylinkId'] !== $this->paylink_id) {
			return 'Incorrect Pay Link id - was expecting ' . esc_html($this->paylink_id) . ' but was for ' . esc_html($apiData['paylinkId']);
		}

		if ((float) $apiData['tokenQuote']->fromAmountDecimal !== (float) $original_usdc_amount) {
			return "From amount was not expected amount. Received: '" . esc_html($apiData['tokenQuote']->fromAmountDecimal) . " but expected '" . esc_html($original_usdc_amount);
		}

		return null;
	}

	public function log( $data, $prefix = '' ) {
		if ($this->logging) {
			$context = array( 'source' => $this->id );
			wc_get_logger()->debug($prefix . "\n" . print_r($data, 1), $context); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
	}

	public function send_modal_params( $order ) {
		$order_currency  = get_option('woocommerce_currency');
		$order_total     = $order->get_total();
		$orderTotalUsdc  = $this->get_total_in_usdc($order);
		$newPaymentNonce = wp_create_nonce(HELIO_WOO_NONCE);

		$order = wc_get_order( $order->get_id() );
		$order->update_meta_data( HELIO_USDC_AMOUNT, $orderTotalUsdc );
		$order->update_meta_data( HELIO_ORDER_TOTAL, $order_total );
		$order->update_meta_data( HELIO_ORDER_CURRENCY, $order_currency );
		$order->update_meta_data( HELIO_QUOTED_AT, time() );

		$order->update_meta_data( HELIO_NONCE, $newPaymentNonce );
		$order->save();

		return array(
			'result' => 'success',
			'refresh' => true,
			'messages' => ' ',
			'modal' => $this->get_modal($order),
			'paylink_id' => $this->paylink_id,
			'is_devnet' => $this->is_devnet,
			'is_darkmode' => $this->is_dark_mode,

			'nonce' => $newPaymentNonce,

			'network' => $this->is_devnet ? 'test' : 'main',
			'plugin_version' => HELIO_WOO_PLUGIN_VERSION,

			'total' => (string) $orderTotalUsdc,
			'order_id' => $order->get_id(),
		);
	}

	public function received_payment( $order ) {
		$submitted_transaction_id = $this->get_submitted_transaction_id();

		$submitted_blockchain_symbol = $this->get_post_field('blockchainSymbol');
		$submitted_nonce             = $this->get_post_field('_wpnonce');
		$order_total                 = $order->get_total();
		$order_currency              = get_option('woocommerce_currency');

		$params = array(
			'transactionId' => $submitted_transaction_id,
			'blockchainSymbol' => $submitted_blockchain_symbol,
			'orderId' => $order->get_id(),
			'nonce' => $submitted_nonce,
			'orderCurrency' => $order_currency,
			'total' => $order_total,
			'expiresAt' => time() - HELIO_TIMEOUT,
		);

		$apiData = $this->helio_api->get_transaction($submitted_transaction_id);

		$errorMessage = $this->validate(
			$order->get_id(),
			$params,
			$apiData
		);

		if ($errorMessage) {
			$this->log($errorMessage);
			$this->log('Transaction id: ' . $submitted_transaction_id);
			$this->log('Blockchain: ' . $submitted_blockchain_symbol);
			$fullErrorMessage =
				'<div>' .
				"<p>There was an error processing your payment on our end, even though it succeeded on the blockchain. \n</p>" .
				"<p>Error details: <b>{$errorMessage}</b>. \n</p>" .
				"<p>Please contact the merchant for assistance. \n</p>" .
				"<p>Reattempting payment will result in duplicate payments. \n</p>" .
				"<hr />\n" .
				"<p>Shop Order ID {$order->get_id()}.</p>\n" .
				"<p>Blockchain: {$submitted_blockchain_symbol}.</p>\n" .
				"<p>Blockchain transaction ID: </p>\n" .
				"<div style='block; overflow: auto; width: 200px; max-width: 100%'><code>{$submitted_transaction_id}</code></div>" .
				'</div>';
			wc_add_notice(__('Payment error:', 'woothemes') . ' ' . $fullErrorMessage, 'error');
			return null;
		}

		update_post_meta($order->get_id(), HELIO_TX_ID, $submitted_transaction_id);
		update_post_meta($order->get_id(), HELIO_CURRENCY_SYMBOL, $apiData['currencySymbol']);
		update_post_meta($order->get_id(), HELIO_BLOCKCHAIN, $apiData['blockchainSymbol']);

		$order->payment_complete($submitted_transaction_id);

		return array(
			'result' => 'success',
			'redirect' => $order->get_checkout_order_received_url(),
		);
	}
}
