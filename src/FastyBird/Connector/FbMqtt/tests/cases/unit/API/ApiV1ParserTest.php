<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\API;

use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Types;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use Throwable;

final class ApiV1ParserTest extends TestCase
{

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceAttributesProvider
	 */
	public function testParseDeviceAttribute(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function parseDeviceAttributesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a');

		return [
			'attr-' . Queue\Messages\Attribute::NAME => [
				$connectorId,
				'/fb/v1/device-name/$' . Queue\Messages\Attribute::NAME,
				'Some content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'attribute' => Queue\Messages\Attribute::NAME,
					'value' => 'Some content',
				],
			],
			'attr-' . Queue\Messages\Attribute::PROPERTIES => [
				$connectorId,
				'/fb/v1/device-name/$' . Queue\Messages\Attribute::PROPERTIES,
				'prop1,prop2',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'attribute' => Queue\Messages\Attribute::PROPERTIES,
					'value' => 'prop1,prop2',
				],
			],
			'attr-' . Queue\Messages\Attribute::CHANNELS => [
				$connectorId,
				'/fb/v1/device-name/$' . Queue\Messages\Attribute::CHANNELS,
				'channel-one,channel-two',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'attribute' => Queue\Messages\Attribute::CHANNELS,
					'value' => 'channel-one,channel-two',
				],
			],
			'attr-' . Queue\Messages\Attribute::CONTROLS => [
				$connectorId,
				'/fb/v1/device-name/$' . Queue\Messages\Attribute::CONTROLS,
				'configure,reset',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'attribute' => Queue\Messages\Attribute::CONTROLS,
					'value' => 'configure,reset',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceHardwareInfoProvider
	 */
	public function testParseDeviceHardwareInfo(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function parseDeviceHardwareInfoProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a');

		return [
			'hw-' . Queue\Messages\ExtensionAttribute::MAC_ADDRESS => [
				$connectorId,
				'/fb/v1/device-name/$hw/' . Queue\Messages\ExtensionAttribute::MAC_ADDRESS,
				'00:0a:95:9d:68:16',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => Types\ExtensionType::FASTYBIRD_HARDWARE->value,
					'parameter' => Queue\Messages\ExtensionAttribute::MAC_ADDRESS,
					'value' => '000a959d6816',
				],
			],
			'hw-' . Queue\Messages\ExtensionAttribute::MANUFACTURER => [
				$connectorId,
				'/fb/v1/device-name/$hw/' . Queue\Messages\ExtensionAttribute::MANUFACTURER,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => Types\ExtensionType::FASTYBIRD_HARDWARE->value,
					'parameter' => Queue\Messages\ExtensionAttribute::MANUFACTURER,
					'value' => 'value-content',
				],
			],
			'hw-' . Queue\Messages\ExtensionAttribute::MODEL => [
				$connectorId,
				'/fb/v1/device-name/$hw/' . Queue\Messages\ExtensionAttribute::MODEL,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => Types\ExtensionType::FASTYBIRD_HARDWARE->value,
					'parameter' => Queue\Messages\ExtensionAttribute::MODEL,
					'value' => 'value-content',
				],
			],
			'hw-' . Queue\Messages\ExtensionAttribute::VERSION => [
				$connectorId,
				'/fb/v1/device-name/$hw/' . Queue\Messages\ExtensionAttribute::VERSION,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => Types\ExtensionType::FASTYBIRD_HARDWARE->value,
					'parameter' => Queue\Messages\ExtensionAttribute::VERSION,
					'value' => 'value-content',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceFirmwareInfoProvider
	 */
	public function testParseDeviceFirmwareInfo(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function parseDeviceFirmwareInfoProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a');

		return [
			'fw-' . Queue\Messages\ExtensionAttribute::MANUFACTURER => [
				$connectorId,
				'/fb/v1/device-name/$fw/' . Queue\Messages\ExtensionAttribute::MANUFACTURER,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => Types\ExtensionType::FASTYBIRD_FIRMWARE->value,
					'parameter' => Queue\Messages\ExtensionAttribute::MANUFACTURER,
					'value' => 'value-content',
				],
			],
			'fw-' . Queue\Messages\ExtensionAttribute::VERSION => [
				$connectorId,
				'/fb/v1/device-name/$fw/' . Queue\Messages\ExtensionAttribute::VERSION,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => Types\ExtensionType::FASTYBIRD_FIRMWARE->value,
					'parameter' => Queue\Messages\ExtensionAttribute::VERSION,
					'value' => 'value-content',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDevicePropertiesProvider
	 */
	public function testParseDeviceProperties(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function parseDevicePropertiesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a');

		return [
			'prop-property-name' => [
				$connectorId,
				'/fb/v1/device-name/$property/property-name',
				'content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'property' => 'property-name',
					'value' => 'content',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDevicePropertiesAttributesProvider
	 */
	public function testParseDevicePropertiesAttributes(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, array<int, array<string, string>>|bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function parseDevicePropertiesAttributesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a');

		return [
			'attr-' . Queue\Messages\PropertyAttribute::NAME => [
				$connectorId,
				'/fb/v1/device-name/$property/some-property/$' . Queue\Messages\PropertyAttribute::NAME,
				'payload',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Queue\Messages\PropertyAttribute::NAME,
							'value' => 'payload',
						],
					],
				],
			],
			'attr-' . Queue\Messages\PropertyAttribute::SETTABLE => [
				$connectorId,
				'/fb/v1/device-name/$property/some-property/$' . Queue\Messages\PropertyAttribute::SETTABLE,
				'true',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Queue\Messages\PropertyAttribute::SETTABLE,
							'value' => 'true',
						],
					],
				],
			],
			'attr-' . Queue\Messages\PropertyAttribute::QUERYABLE => [
				$connectorId,
				'/fb/v1/device-name/$property/some-property/$' . Queue\Messages\PropertyAttribute::QUERYABLE,
				'invalid',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Queue\Messages\PropertyAttribute::QUERYABLE,
							'value' => 'invalid',
						],
					],
				],
			],
		];
	}

	/**
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceAttributesInvalidProvider
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testParseDeviceAttributeNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		API\V1Parser::parse(
			Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a'),
			$topic,
			'bar',
		);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function parseDeviceAttributesInvalidProvider(): array
	{
		return [
			'attr-unknown' => [
				'/fb/v1/device-name/$unknown',
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
		];
	}

	/**
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceHardwareInfoInvalidProvider
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testParseDeviceHardwareInfoNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		API\V1Parser::parse(
			Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a'),
			$topic,
			'bar',
		);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function parseDeviceHardwareInfoInvalidProvider(): array
	{
		return [
			'hw-not-valid' => [
				'/fb/v1/device-name/$hw/not-valid',
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
		];
	}

	/**
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceFirmwareInfoInvalidProvider
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testParseDeviceFirmwareInfoNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		API\V1Parser::parse(
			Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a'),
			$topic,
			'bar',
		);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function parseDeviceFirmwareInfoInvalidProvider(): array
	{
		return [
			'fw-not-valid' => [
				'/fb/v1/device-name/$fw/not-valid',
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseChannelAttributesProvider
	 */
	public function testParseChannelAttributes(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function parseChannelAttributesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a');

		return [
			'attr-' . Queue\Messages\Attribute::NAME => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$' . Queue\Messages\Attribute::NAME,
				'Some content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'attribute' => Queue\Messages\Attribute::NAME,
					'value' => 'Some content',
				],
			],
			'attr-' . Queue\Messages\Attribute::PROPERTIES => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$' . Queue\Messages\Attribute::PROPERTIES,
				'prop1,prop2',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'attribute' => Queue\Messages\Attribute::PROPERTIES,
					'value' => 'prop1,prop2',
				],
			],
			'attr-' . Queue\Messages\Attribute::CONTROLS => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$' . Queue\Messages\Attribute::CONTROLS,
				'configure,reset',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'attribute' => Queue\Messages\Attribute::CONTROLS,
					'value' => 'configure,reset',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseChannelPropertiesProvider
	 */
	public function testParseChannelProperties(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function parseChannelPropertiesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a');

		return [
			'prop-property-name' => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$property/property-name',
				'content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'property-name',
					'value' => 'content',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseChannelPropertiesAttributesProvider
	 */
	public function testParseChannelPropertiesAttributes(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, array<int, array<string, bool|string>>|bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function parseChannelPropertiesAttributesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a');

		return [
			'attr-' . Queue\Messages\PropertyAttribute::NAME => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Queue\Messages\PropertyAttribute::NAME,
				'payload',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Queue\Messages\PropertyAttribute::NAME,
							'value' => 'payload',
						],
					],
				],
			],
			'attr-' . Queue\Messages\PropertyAttribute::SETTABLE => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Queue\Messages\PropertyAttribute::SETTABLE,
				'true',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Queue\Messages\PropertyAttribute::SETTABLE,
							'value' => true,
						],
					],
				],
			],
			'attr-' . Queue\Messages\PropertyAttribute::QUERYABLE => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Queue\Messages\PropertyAttribute::QUERYABLE,
				'invalid',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Queue\Messages\PropertyAttribute::QUERYABLE,
							'value' => 'invalid',
						],
					],
				],
			],
		];
	}

	/**
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseChannelAttributesInvalidProvider
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testParseChannelAttributeNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		API\V1Parser::parse(
			Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a'),
			$topic,
			'bar',
		);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function parseChannelAttributesInvalidProvider(): array
	{
		return [
			'attr-' . Queue\Messages\Attribute::CHANNELS => [
				'/fb/v1/device-name/$channel/channel-name/$' . Queue\Messages\Attribute::CHANNELS,
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
			'attr-other' => [
				'/fb/v1/device-name/$channel/channel-name/$other',
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
		];
	}

}
