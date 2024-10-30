<?php

class FiatRates {

	const USD_CURRENCY      = 'USD';
	const NUM_DECIMALS_USDC = 6;

	const BASE_URL = 'https://api.hel.io/v1';

	protected function get_base_url() {
		if (defined('HELIO_BASE_URL')) {
			return HELIO_BASE_URL;
		}

		return self::BASE_URL;
	}

	public function get_usdc_amount( $currency, $amount ) {
		if (!$currency) {
			throw new Exception('Missing currency');
		}

		$currencyUpper = strtoupper($currency);

		if (self::USD_CURRENCY === $currency) {
			return $amount;
		}

		$url           = $this->get_base_url() . '/exchange-rates/public-fiat?from=' . rawurlencode($currencyUpper) . '&to=USDC&amount=' . rawurlencode($amount) . '&source=' . rawurlencode(get_site_url());
		$exchangeRates = wp_remote_get($url);

		if (is_wp_error($exchangeRates)) {
			$errorMsg = $exchangeRates->get_error_message();
			throw new Exception(esc_attr($errorMsg));
		}

		$body = $exchangeRates['body'];

		$exchangeRatesJson = json_decode($body);

		if (json_last_error()) {
			throw new Exception('Error decoding fiat currency JSON: ' . esc_attr(json_last_error()));
		}

		$amount_usdc = $exchangeRatesJson->amount;
		if (!is_string($amount_usdc) || !( (float) $amount_usdc )) {
			throw new Exception('Invalid amount in USDC');
		}

		return $this->trimToUsdcDecimals((float) $amount_usdc);
	}


	private function trimToUsdcDecimals( $amount ) {
		$shifted   = $amount * pow(10, self::NUM_DECIMALS_USDC);
		$truncated = intval($shifted);
		return $truncated / pow(10, self::NUM_DECIMALS_USDC);
	}
}
