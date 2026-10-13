<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Helpers;

use FastyBird\Connector\Sonoff\Types;
use function base64_decode;
use function base64_encode;
use function md5;
use function openssl_decrypt;
use function openssl_encrypt;
use function strlen;
use function strval;
use const OPENSSL_RAW_DATA;

/**
 * Devices data transformers
 */
final class Transformer
{

	private const int IV_LENGTH = 16;

	/**
	 * The vector comes with the message, from whoever sent it. One that is not sixteen bytes would be padded or cut by
	 * OpenSSL, with a warning, so it makes the message one that cannot be decrypted
	 */
	public static function decryptMessage(string $message, string $key, string $iv = ''): string|false
	{
		$vector = base64_decode($iv, true);

		if ($vector === false || strlen($vector) !== self::IV_LENGTH) {
			return false;
		}

		$cipher = base64_decode($message, true);

		if ($cipher === false || $cipher === '') {
			return false;
		}

		return openssl_decrypt(
			$cipher,
			'AES-128-CBC',
			md5($key, true),
			OPENSSL_RAW_DATA,
			$vector,
		);
	}

	public static function encryptMessage(string $message, string $key, string $iv = ''): string|false
	{
		$encrypted = openssl_encrypt(
			$message,
			'AES-128-CBC',
			md5($key, true),
			OPENSSL_RAW_DATA,
			strval(base64_decode($iv, true)),
		);

		if ($encrypted === false) {
			return false;
		}

		return base64_encode($encrypted);
	}

	public static function deviceParameterToProperty(string $identifier): string
	{
		if ($identifier === Types\Parameter::STATUS_LED->value) {
			return Types\DevicePropertyIdentifier::STATUS_LED->value;
		}

		if ($identifier === Types\Parameter::FIRMWARE_VERSION->value) {
			return Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value;
		}

		if ($identifier === Types\Parameter::RSSI->value) {
			return Types\DevicePropertyIdentifier::RSSI->value;
		}

		if ($identifier === Types\Parameter::SSID->value) {
			return Types\DevicePropertyIdentifier::SSID->value;
		}

		if ($identifier === Types\Parameter::BSSID->value) {
			return Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;
		}

		return $identifier;
	}

	public static function devicePropertyToParameter(string $identifier): string
	{
		if ($identifier === Types\DevicePropertyIdentifier::STATUS_LED->value) {
			return Types\Parameter::STATUS_LED->value;
		}

		if ($identifier === Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value) {
			return Types\Parameter::FIRMWARE_VERSION->value;
		}

		if ($identifier === Types\DevicePropertyIdentifier::RSSI->value) {
			return Types\Parameter::RSSI->value;
		}

		if ($identifier === Types\DevicePropertyIdentifier::SSID->value) {
			return Types\Parameter::SSID->value;
		}

		if ($identifier === Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value) {
			return Types\Parameter::BSSID->value;
		}

		return $identifier;
	}

}
