<?php

class HelioApi {

	/**
	 * Helio api base path
	 *
	 * @var string
	 */
	private $api_base_path;

	/**
	 * API key for Helio
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Secret API key for Helio
	 *
	 * @var string
	 */
	private $secret_api_key;

	public function __construct( $is_devnet, $credentials ) {
		$this->api_base_path = defined('HELIO_BASE_URL')
			? HELIO_BASE_URL
			: ( $is_devnet ? 'https://api.dev.hel.io/v1' : 'https://api.hel.io/v1' );

		$this->api_key        = $credentials['api_key'];
		$this->secret_api_key = $credentials['api_secret'];
	}

	public function get_transaction( $txId ) {
		$url = "{$this->api_base_path}/transactions/signature/{$txId}?publicKey={$this->api_key}";

		$data = wp_remote_get($url, array(
			'headers' => array(
				'Content-Type' => 'application/json; charset=utf-8',
				'accept' => 'application/json',
				'Authorization' => 'Bearer ' . $this->secret_api_key,
			),
		));

		if (is_wp_error($data)) {
			throw new Exception('Unable to contact Helio API');
		}

		$body           = json_decode($data['body']);
		$meta           = $body->meta;
		$additionalJSON = json_decode($meta->customerDetails->additionalJSON);

		if (json_last_error()) {
			if (strpos((string) $body->message, 'token is invalid') !== false) {
				throw new Exception(
					'Unable to confirm payment due to API key issue. The payment potentially is confirmed on the blockchain. Please reach out to support, and quote transaction id '
					. esc_attr($txId)
				);
			}

			throw new Exception(
				'Unable to process additional JSON from Helio API: '
				. esc_attr(json_last_error_msg())
				. ' '
				. esc_attr($data['body'])
			);
		}

		$data = array(
			'paylinkId' => $body->paylink->id,
			'amount' => $meta->amount,
			'totalAmount' => $meta->totalAmount,
			'currencyId' => $meta->currency->id,
			'currencyName' => $meta->currency->name,
			'currencySymbol' => $meta->currency->symbol,
			'senderPK' => $meta->senderPK,
			'recipientPK' => $meta->recipientPK,
			'additionalJSON' => $additionalJSON,
			'tokenQuote' => $meta->tokenQuote,
			'blockchainSymbol' => $this->getMatchingRecipientBlockchain($body->paylink->recipients, $meta->currency->id),
		);

		return $data;
	}

	private function getMatchingRecipientBlockchain( $recipients, $metaCurrencyId ) {
		foreach ($recipients as $recipient) {
			if ($recipient->currency->id === $metaCurrencyId) {
				return $recipient->currency->blockchain->symbol;
			}
		}

		return '';
	}
}
