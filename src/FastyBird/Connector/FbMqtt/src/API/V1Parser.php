<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\API;

use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Types;
use Ramsey\Uuid;
use function array_merge;
use function assert;
use function is_string;
use function preg_match;
use function strtolower;

/**
 * API v1 topic parser
 */
final class V1Parser
{

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\ParseMessage
	 */
	public static function parse(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
		bool $retained = false,
	): array
	{
		if (!V1Validator::validate($topic)) {
			throw new Exceptions\ParseMessage('Provided topic is not valid');
		}

		if (V1Validator::validateDeviceAttribute($topic)) {
			return array_merge(
				self::parseDeviceAttribute($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (V1Validator::validateDeviceHardwareInfo($topic)) {
			return array_merge(
				self::parseDeviceHardwareInfo($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (V1Validator::validateDeviceFirmwareInfo($topic)) {
			return array_merge(
				self::parseDeviceFirmwareInfo($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (V1Validator::validateDeviceProperty($topic)) {
			return array_merge(
				self::parseDeviceProperty($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (V1Validator::validateChannelPart($topic)) {
			preg_match(V1Validator::CHANNEL_PARTIAL_REGEXP, $topic, $matches);
			[, $device] = $matches + [null, null];
			assert(is_string($device));

			if (V1Validator::validateChannelAttribute($topic)) {
				return array_merge(
					self::parseChannelAttribute($connector, $device, $topic, $payload),
					[
						'retained' => $retained,
					],
				);
			}

			if (V1Validator::validateChannelProperty($topic)) {
				return array_merge(
					self::parseChannelProperty($connector, $device, $topic, $payload),
					[
						'retained' => $retained,
					],
				);
			}
		}

		throw new Exceptions\ParseMessage('Provided topic is not valid');
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseDeviceAttribute(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::DEVICE_ATTRIBUTE_REGEXP, $topic, $matches);
		[, $device, $attribute] = $matches + [null, null, null];
		assert(is_string($device) && is_string($attribute));

		return [
			'connector' => $connector,
			'device' => $device,
			'attribute' => $attribute,
			'value' => $payload,
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseDeviceHardwareInfo(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::DEVICE_HW_INFO_REGEXP, $topic, $matches);
		[, $device, $hardware] = $matches + [null, null, null];
		assert(is_string($device) && is_string($hardware));

		return [
			'connector' => $connector,
			'device' => $device,
			'extension' => Types\ExtensionType::FASTYBIRD_HARDWARE->value,
			'parameter' => $hardware,
			'value' => Helpers\Payload::cleanName(strtolower($payload)),
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseDeviceFirmwareInfo(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::DEVICE_FW_INFO_REGEXP, $topic, $matches);
		[, $device, $firmware] = $matches + [null, null, null];
		assert(is_string($device) && is_string($firmware));

		return [
			'connector' => $connector,
			'device' => $device,
			'extension' => Types\ExtensionType::FASTYBIRD_FIRMWARE->value,
			'parameter' => $firmware,
			'value' => Helpers\Payload::cleanName(strtolower($payload)),
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string|array<int, array<string, string>>|null>
	 */
	private static function parseDeviceProperty(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::DEVICE_PROPERTY_REGEXP, $topic, $matches);
		[, $device, $property, , , $attribute] = $matches + [null, null, null, null, null, null];

		$data = [
			'connector' => $connector,
			'device' => $device,
			'property' => $property,
		];

		return $attribute !== null ? array_merge($data, [
			'attributes' => [
				[
					'attribute' => $attribute,
					'value' => Helpers\Payload::cleanPayload($payload),
				],
			],
		]) : array_merge($data, [
			'value' => $payload,
		]);
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseChannelAttribute(
		Uuid\UuidInterface $connector,
		string $device,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::CHANNEL_ATTRIBUTE_REGEXP, $topic, $matches);
		[, , $channel, $attribute] = $matches + [null, null, null, null];
		assert(is_string($channel) && is_string($attribute));

		return [
			'connector' => $connector,
			'device' => $device,
			'channel' => $channel,
			'attribute' => $attribute,
			'value' => $payload,
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string|array<int, array<string, string>>|null>
	 */
	private static function parseChannelProperty(
		Uuid\UuidInterface $connector,
		string $device,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::CHANNEL_PROPERTY_REGEXP, $topic, $matches);
		[, , $channel, $property, , , $attribute] = $matches + [null, null, null, null, null, null, null];

		$data = [
			'connector' => $connector,
			'device' => $device,
			'channel' => $channel,
			'property' => $property,
		];

		return $attribute !== null ? array_merge($data, [
			'attributes' => [
				[
					'attribute' => $attribute,
					'value' => Helpers\Payload::cleanPayload($payload),
				],
			],
		]) : array_merge($data, [
			'value' => $payload,
		]);
	}

}
