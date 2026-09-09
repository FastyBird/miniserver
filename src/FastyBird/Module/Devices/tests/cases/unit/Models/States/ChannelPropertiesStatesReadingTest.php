<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\States;

use DateTimeInterface;
use Error;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use Nette\DI;
use Ramsey\Uuid;

final class ChannelPropertiesStatesReadingTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Mapping
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 *
	 * @dataProvider readStates
	 */
	public function testReadState(
		Documents\Channels\Properties\Dynamic|Documents\Channels\Properties\Mapped $property,
		Documents\Channels\Properties\Dynamic|null $parent,
		States\ChannelProperty $stored,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $actual,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $expected,
	): void
	{
		$channelPropertiesConfigurationRepository = $this->createMock(
			Models\Configuration\Channels\Properties\Repository::class,
		);
		$channelPropertiesConfigurationRepository
			->expects(self::exactly($parent !== null ? 1 : 0))
			->method('find')
			->willReturn($parent);

		$this->mockContainerService(
			Models\Configuration\Channels\Properties\Repository::class,
			$channelPropertiesConfigurationRepository,
		);

		$channelPropertyStateRepository = $this->createMock(Models\States\Channels\Repository::class);
		$channelPropertyStateRepository
			->expects(self::exactly(1))
			->method('find')
			->willReturn($stored);

		$this->mockContainerService(
			Models\States\Channels\Repository::class,
			$channelPropertyStateRepository,
		);

		$channelPropertiesStatesManager = $this->getContainer()->getByType(
			Models\States\ChannelPropertiesManager::class,
		);

		$state = $channelPropertiesStatesManager->read(
			$property,
			MetadataTypes\Sources\Module::DEVICES,
		);

		self::assertInstanceOf(Documents\States\Channels\Properties\Property::class, $state);
		self::assertSame($actual, $state->getRead()->getActualValue(), 'actual value check');
		self::assertSame($expected, $state->getRead()->getExpectedValue(), 'expected value check');
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Mapping
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 *
	 * @dataProvider getStates
	 */
	public function testGetState(
		Documents\Channels\Properties\Dynamic|Documents\Channels\Properties\Mapped $property,
		Documents\Channels\Properties\Dynamic|null $parent,
		States\ChannelProperty $stored,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $actual,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $expected,
	): void
	{
		$channelPropertiesConfigurationRepository = $this->createMock(
			Models\Configuration\Channels\Properties\Repository::class,
		);
		$channelPropertiesConfigurationRepository
			->expects(self::exactly($parent !== null ? 1 : 0))
			->method('find')
			->willReturn($parent);

		$this->mockContainerService(
			Models\Configuration\Channels\Properties\Repository::class,
			$channelPropertiesConfigurationRepository,
		);

		$channelPropertyStateRepository = $this->createMock(Models\States\Channels\Repository::class);
		$channelPropertyStateRepository
			->expects(self::exactly(1))
			->method('find')
			->willReturn($stored);

		$this->mockContainerService(
			Models\States\Channels\Repository::class,
			$channelPropertyStateRepository,
		);

		$channelPropertiesStatesManager = $this->getContainer()->getByType(
			Models\States\ChannelPropertiesManager::class,
		);

		$state = $channelPropertiesStatesManager->read(
			$property,
			MetadataTypes\Sources\Module::DEVICES,
		);

		self::assertInstanceOf(Documents\States\Channels\Properties\Property::class, $state);
		self::assertSame($actual, $state->getGet()->getActualValue(), 'actual value check');
		self::assertSame($expected, $state->getGet()->getExpectedValue(), 'expected value check');
	}

	/**
	 * @return array<string, array<Documents\Channels\Properties\Dynamic|Documents\Channels\Properties\Mapped|States\ChannelProperty|bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function readStates(): array
	{
		$property01 = Uuid\Uuid::fromString('108e4a68-e184-44f2-b1ab-134f5b65dc6b');
		$child01 = Uuid\Uuid::fromString('a0f77991-1ad0-4940-aa6b-ad10094b2b2c');

		$channel01 = Uuid\Uuid::fromString('1fbc5210-01e0-412d-bdc1-5ffc7b16a098');
		$channel02 = Uuid\Uuid::fromString('8a032f16-2d54-43e5-827f-102ee9cc6e71');

		return [
			/**
			 * Classic property - no scale, no transformer.
			 */
			'read_01' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-01',
					'Testing Property 01',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					127,
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Classic property with scale transformer.
			 * Scale transformer is applied because state is loaded for reading/displaying.
			 */
			'read_02' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-02',
					'Testing Property 02',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					1,
					null,
					null,
					null,
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					127.0,
					false,
					true,
				),
				25.4,
				12.7,
			],
			/**
			 * Classic property with equation transformer.
			 * Equation transformer is applied because state is loaded for reading/displaying.
			 */
			'read_03' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-03',
					'Testing Property 03',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					null,
					null,
					null,
					'equation:x=y*10|y=x/10',
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'0127',
					false,
					true,
				),
				25.4,
				12.7,
			],
			/**
			 * Classic property with both scale and equation transformer.
			 * Both transformers are applied because state is loaded for reading/displaying.
			 */
			'read_04' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-04',
					'Testing Property 04',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					1,
					null,
					null,
					'equation:x=y*10|y=x/10',
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				2.54,
				1.27,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 */
			'read_05' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-05',
					'Child Property 05',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-05',
					'Testing Property 05',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Mapped property with scale transformer on mapped property.
			 * Scale transformer is applied because state is loaded for reading/displaying.
			 */
			'read_06' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-06',
					'Child Property 06',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					1,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-06',
					'Testing Property 06',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				25.4,
				12.7,
			],
			/**
			 * Mapped property with scale transformer on mapped property and on parent property.
			 * Scale transformer is applied on both properties because state is loaded for reading/displaying.
			 */
			'read_07' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-07',
					'Child Property 07',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					1,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-07',
					'Testing Property 07',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					1,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				2.5,
				1.2,
			],
			/**
			 * Mapped property with equation transformer on mapped property.
			 * Equation transformer is applied because equation transformers is used always on mapped properties.
			 */
			'read_08' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-08',
					'Child Property 08',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 100],
					null,
					null,
					null,
					null,
					'equation:x=y/2.54|y=x*2.54',
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-08',
					'Testing Property 08',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				98,
				47,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 * Value is rest because of different value ranges: [10, 1000] vs [0, 100]
			 * and stored value is 1000 which is over mapped property accepted range.
			 */
			'read_09' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-09',
					'Child Property 09',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 100],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-09',
					'Testing Property 09',
					MetadataTypes\DataType::INT,
					null,
					[10, 1_000],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'1000',
					'1000',
					false,
					true,
				),
				null,
				null,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * System value is returned because state is loaded for reading/displaying.
			 */
			'read_10' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-10',
					'Testing Property 10',
					MetadataTypes\DataType::SWITCH,
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON->value,
							'ON',
							'true',
						],
						[
							MetadataTypes\Payloads\Switcher::OFF->value,
							'OFF',
							'false',
						],
					],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\Payloads\Switcher::ON,
					MetadataTypes\Payloads\Switcher::OFF,
					false,
					true,
				),
				MetadataTypes\Payloads\Switcher::ON,
				MetadataTypes\Payloads\Switcher::OFF,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 * System value is returned because state is loaded for reading/displaying.
			 */
			'read_11' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-11',
					'Child Property 11',
					MetadataTypes\DataType::SWITCH,
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON->value,
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'true',
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'true',
							],
						],
						[
							MetadataTypes\Payloads\Switcher::OFF->value,
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'false',
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'false',
							],
						],
					],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-11',
					'Testing Property 11',
					MetadataTypes\DataType::SWITCH,
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON->value,
							'ON',
							'true',
						],
						[
							MetadataTypes\Payloads\Switcher::OFF->value,
							'OFF',
							'false',
						],
					],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\Payloads\Switcher::ON,
					MetadataTypes\Payloads\Switcher::OFF,
					false,
					true,
				),
				MetadataTypes\Payloads\Switcher::ON,
				MetadataTypes\Payloads\Switcher::OFF,
			],
		];
	}

	/**
	 * @return array<string, array<Documents\Channels\Properties\Dynamic|Documents\Channels\Properties\Mapped|States\ChannelProperty|bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null>>
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function getStates(): array
	{
		$property01 = Uuid\Uuid::fromString('108e4a68-e184-44f2-b1ab-134f5b65dc6b');
		$child01 = Uuid\Uuid::fromString('a0f77991-1ad0-4940-aa6b-ad10094b2b2c');

		$channel01 = Uuid\Uuid::fromString('1fbc5210-01e0-412d-bdc1-5ffc7b16a098');
		$channel02 = Uuid\Uuid::fromString('8a032f16-2d54-43e5-827f-102ee9cc6e71');

		return [
			/**
			 * Classic property - no scale, no transformer.
			 */
			'get_01' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-01',
					'Testing Property 01',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Classic property with scale transformer.
			 * Scale transformer is NOT applied because state is loaded for using.
			 */
			'get_02' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-02',
					'Testing Property 02',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					1,
					null,
					null,
					null,
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Classic property with equation transformer.
			 * Equation transformer is NOT applied because state is loaded for using.
			 */
			'get_03' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-03',
					'Testing Property 03',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					null,
					null,
					null,
					'equation:x=y*10|y=x/10',
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Classic property with both scale and equation transformer.
			 * Both transformers are NOT applied because state is loaded for using.
			 */
			'get_04' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-04',
					'Testing Property 04',
					MetadataTypes\DataType::FLOAT,
					null,
					null,
					null,
					1,
					null,
					null,
					'equation:x=y*10|y=x/10',
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'254',
					'127',
					false,
					true,
				),
				254.0,
				127.0,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 */
			'get_05' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-05',
					'Child Property 05',
					MetadataTypes\DataType::UCHAR,
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-05',
					'Testing Property 05',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				250,
				120,
			],
			/**
			 * Mapped property with scale transformer on mapped property.
			 * Scale transformer is NOT applied because state is loaded for using.
			 */
			'get_06' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-06',
					'Child Property 06',
					MetadataTypes\DataType::UCHAR,
					null,
					null,
					null,
					1,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-06',
					'Testing Property 06',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				250,
				120,
			],
			/**
			 * Mapped property with scale transformer on parent property.
			 * Scale transformer is applied because all transformers are used on parent properties.
			 */
			'get_07' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-07',
					'Child Property 07',
					MetadataTypes\DataType::UCHAR,
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-07',
					'Testing Property 07',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 254],
					null,
					1,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				25,
				12,
			],
			/**
			 * Mapped property with equation transformer on parent property.
			 * Equation transformer is applied because all transformers are used on parent properties.
			 */
			'get_08' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-08',
					'Child Property 08',
					MetadataTypes\DataType::UCHAR,
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-08',
					'Testing Property 08',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					'equation:x=y*2.54|y=x/2.54',
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				98,
				47,
			],
			/**
			 * Mapped property with equation transformer on mapped property.
			 * Equation transformer is applied because equation transformers is used always on mapped properties
			 */
			'get_09' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-09',
					'Child Property 09',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 100],
					null,
					null,
					null,
					null,
					'equation:x=y/2.54|y=x*2.54',
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-09',
					'Testing Property 09',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 254],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'250',
					'120',
					false,
					true,
				),
				98,
				47,
			],
			/**
			 * Mapped property with both scale and equation transformer on mapped property and with scale transformer on parent property.
			 * Mapped property equation transformer is applied because equation transformers is used always on mapped properties,
			 * scale transformer is NOT applied because state is loaded for using
			 * and parent property scale transformer is applied, because parent property transformers are used always on mapped properties,
			 */
			'get_10' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-10',
					'Child Property 10',
					MetadataTypes\DataType::UCHAR,
					null,
					[0, 100],
					null,
					1,
					null,
					null,
					'equation:x=y/10|y=x*10',
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-10',
					'Testing Property 10',
					MetadataTypes\DataType::INT,
					null,
					[10, 1_000],
					null,
					1,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					'100',
					'1000',
					false,
					true,
				),
				1,
				10,
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Device value is returned because state is loaded for using.
			 */
			'get_11' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-11',
					'Testing Property 11',
					MetadataTypes\DataType::SWITCH,
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON->value,
							'ON',
							'true',
						],
						[
							MetadataTypes\Payloads\Switcher::OFF->value,
							'OFF',
							'false',
						],
					],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\Payloads\Switcher::ON,
					MetadataTypes\Payloads\Switcher::OFF,
					false,
					true,
				),
				'true',
				'false',
			],
			/**
			 * Classic property - no scale, no transformer.
			 * Device value with data type conversion is returned because state is loaded for using.
			 */
			'get_12' => [
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-12',
					'Testing Property 12',
					MetadataTypes\DataType::SWITCH,
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON->value,
							'ON',
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'true',
							],
						],
						[
							MetadataTypes\Payloads\Switcher::OFF->value,
							'OFF',
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'false',
							],
						],
					],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				null,
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\Payloads\Switcher::ON,
					MetadataTypes\Payloads\Switcher::OFF,
					false,
					true,
				),
				true,
				false,
			],
			/**
			 * Mapped property - no scale, no transformer.
			 * Device value is returned because state is loaded for using.
			 */
			'get_13' => [
				new Documents\Channels\Properties\Mapped(
					$child01,
					$channel02,
					$property01,
					Types\PropertyCategory::GENERIC,
					'child-property-13',
					'Child Property 13',
					MetadataTypes\DataType::SWITCH,
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON->value,
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'true',
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'true',
							],
						],
						[
							MetadataTypes\Payloads\Switcher::OFF->value,
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'false',
							],
							[
								MetadataTypes\DataTypeShort::BOOLEAN->value,
								'false',
							],
						],
					],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Documents\Channels\Properties\Dynamic(
					$property01,
					$channel01,
					Types\PropertyCategory::GENERIC,
					'test-property-13',
					'Testing Property 13',
					MetadataTypes\DataType::SWITCH,
					null,
					[
						[
							MetadataTypes\Payloads\Switcher::ON->value,
							'ON',
							'true',
						],
						[
							MetadataTypes\Payloads\Switcher::OFF->value,
							'OFF',
							'false',
						],
					],
					null,
					null,
					null,
					null,
					null,
					true,
				),
				new Tests\Fixtures\Dummy\ChannelPropertyState(
					$property01,
					MetadataTypes\Payloads\Switcher::ON,
					MetadataTypes\Payloads\Switcher::OFF,
					false,
					true,
				),
				true,
				false,
			],
		];
	}

}
