<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Documents;

use Error;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use Nette;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;
use function file_get_contents;
use function method_exists;

final class ChannelPropertyDocumentTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @param class-string<ApplicationDocuments\Document> $class
	 * @param array<string, mixed> $fixture
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('channelProperty')]
	public function testCreateDocument(string $data, string $class, array $fixture): void
	{
		$factory = $this->getContainer()->getByType(ApplicationDocuments\DocumentFactory::class);

		$document = $factory->create($class, $data);

		self::assertTrue($document instanceof $class);
		self::assertTrue(method_exists($document, 'getChannel'));
		self::assertTrue($document->getChannel() instanceof Uuid\UuidInterface);
		self::assertEquals($fixture, $document->toArray());
	}

	/**
	 * @param class-string<ApplicationDocuments\Document> $class
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('channelPropertyInvalid')]
	public function testCreateDocumentInvalid(string $data, string $class): void
	{
		$factory = $this->getContainer()->getByType(ApplicationDocuments\DocumentFactory::class);

		$this->expectException(ApplicationExceptions\InvalidArgument::class);

		$factory->create($class, $data);
	}

	/**
	 * @return array<string, array<string|bool|array<string, mixed>>>
	 */
	public static function channelProperty(): array
	{
		return [
			'dynamic' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.dynamic.json'),
				Documents\Channels\Properties\Dynamic::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => Documents\Channels\Properties\Dynamic::getType(),
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'category' => Types\PropertyCategory::GENERIC->value,
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => MetadataTypes\DataType::INT->value,
					'unit' => '%',
					'format' => [[MetadataTypes\DataTypeShort::UCHAR->value, 10], 50.0],
					'invalid' => 99,
					'scale' => 0,
					'step' => null,
					'default' => null,
					'value_transformer' => null,
					'owner' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'created_at' => null,
					'updated_at' => null,

				],
			],
			'variable' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.variable.json'),
				Documents\Channels\Properties\Variable::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => Documents\Channels\Properties\Variable::getType(),
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'category' => Types\PropertyCategory::GENERIC->value,
					'identifier' => 'property-identifier',
					'name' => null,
					'data_type' => MetadataTypes\DataType::ENUM->value,
					'unit' => null,
					'format' => ['one','two','three'],
					'invalid' => 99,
					'scale' => null,
					'step' => null,
					'value_transformer' => null,
					'owner' => null,
					'value' => 'two',
					'default' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'mapped' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.mapped.json'),
				Documents\Channels\Properties\Mapped::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => Documents\Channels\Properties\Mapped::getType(),
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'category' => Types\PropertyCategory::GENERIC->value,
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => MetadataTypes\DataType::SWITCH->value,
					'unit' => null,
					'format' => [
						[
							['sw', MetadataTypes\Payloads\Switcher::ON->value],
							'1000',
							['s', 'on'],
						],
						[
							['sw', MetadataTypes\Payloads\Switcher::OFF->value],
							'2000',
							['s', 'off'],
						],
						[
							['sw', MetadataTypes\Payloads\Switcher::TOGGLE->value],
							null,
							['s', 'toggle'],
						],
					],
					'invalid' => 99,
					'scale' => null,
					'step' => null,
					'value_transformer' => null,
					'owner' => null,
					'value' => null,
					'default' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'parent' => 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1',
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dynamic-created' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.dynamic.json'),
				Documents\Channels\Properties\Dynamic::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => Documents\Channels\Properties\Dynamic::getType(),
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'category' => Types\PropertyCategory::GENERIC->value,
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => MetadataTypes\DataType::INT->value,
					'unit' => '%',
					'format' => [[MetadataTypes\DataTypeShort::UCHAR->value, 10], 50.0],
					'invalid' => 99,
					'scale' => 0,
					'step' => null,
					'default' => null,
					'value_transformer' => null,
					'owner' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dynamic-updated' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.dynamic.json'),
				Documents\Channels\Properties\Dynamic::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => Documents\Channels\Properties\Dynamic::getType(),
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'category' => Types\PropertyCategory::GENERIC->value,
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => MetadataTypes\DataType::INT->value,
					'unit' => '%',
					'format' => [[MetadataTypes\DataTypeShort::UCHAR->value, 10], 50.0],
					'invalid' => 99,
					'scale' => 0,
					'step' => null,
					'default' => null,
					'value_transformer' => null,
					'owner' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dynamic-deleted' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.dynamic.json'),
				Documents\Channels\Properties\Dynamic::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => Documents\Channels\Properties\Dynamic::getType(),
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'category' => Types\PropertyCategory::GENERIC->value,
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => MetadataTypes\DataType::INT->value,
					'unit' => '%',
					'format' => [[MetadataTypes\DataTypeShort::UCHAR->value, 10], 50.0],
					'invalid' => 99,
					'scale' => 0,
					'step' => null,
					'default' => null,
					'value_transformer' => null,
					'owner' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'created_at' => null,
					'updated_at' => null,
				],
			],
		];
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelPropertyInvalid(): array
	{
		return [
			'missing' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.missing.json'),
				Documents\Channels\Properties\Dynamic::class,
			],
			'type-mismatch' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.mismatch.json'),
				Documents\Channels\Properties\Dynamic::class,
			],
		];
	}

}
