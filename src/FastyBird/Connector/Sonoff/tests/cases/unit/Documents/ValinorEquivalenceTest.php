<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Documents;

use CuyZ\Valinor;
use DateTimeInterface;
use FastyBird\Connector\Sonoff\Documents as SonoffDocuments;
use FastyBird\Connector\Sonoff\Entities as SonoffEntities;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette\Utils;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use function array_merge;
use function assert;
use function is_array;
use function is_string;
use function serialize;

/**
 * The Valinor spike (#724 PR0) on the Sonoff connector's documents, the connector, device and channel documents the
 * Devices module builds for the configuration repositories, publishes to the exchange and hands the WebSockets. Each
 * input goes through Core's DocumentFactory, which maps it with orisai, and through Valinor configured as below; both
 * documents must serialise to the same JSON, through the encoder the exchange and the WebSockets use, and hold the same
 * state, compared with serialize() so that an int is not a float and an unset property is not a null one.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ValinorEquivalenceTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '63a28696-cbea-4757-84d7-156e52ade4d3';

	private const string DEVICE_ABC = '1e4b1d2f-6e3c-4b9f-ad8e-7c6b5a4f3e2d';

	private const string ID = '9b6a4e2c-1f3d-4c5b-8a7e-6d5c4b3a2f1e';

	private const string OWNER = '5e4d3c2b-1a0f-4e9d-8c7b-6a5f4e3d2c1b';

	#[Override]
	public function setUp(): void
	{
		// The files load in the reverse order they are registered in, so this one comes after the connectors
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/identifiers.data.sql');

		parent::setUp();
	}

	/**
	 * Every stored Sonoff connector, device and channel, the way each path hands it to the factory: the entity's array
	 * as the configuration repositories read it, the JSON of an exchange message, the routed exchange consumer, and
	 * the document's own array as it is published again
	 *
	 * @throws Throwable
	 */
	public function testStoredEntitiesMapToTheSameDocuments(): void
	{
		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->find(
			Uuid\Uuid::fromString(self::DEVICE_ABC),
		);
		self::assertNotNull($device);

		$this->getContainer()->getByType(DevicesModels\Entities\Channels\ChannelsManager::class)->create([
			'entity' => SonoffEntities\Channels\Channel::class,
			'device' => $device,
			'identifier' => 'switch_0',
			'name' => 'Switch',
			'comment' => ' ',
		]);

		$this->getEntityManager()->clear();

		$entities = [
			[
				DevicesDocuments\Connectors\Connector::class,
				DevicesTypes\RoutingKey::CONNECTOR_DOCUMENT_UPDATED,
				DevicesEntities\Connectors\Connector::class,
			],
			[
				DevicesDocuments\Devices\Device::class,
				DevicesTypes\RoutingKey::DEVICE_DOCUMENT_UPDATED,
				DevicesEntities\Devices\Device::class,
			],
			[
				DevicesDocuments\Channels\Channel::class,
				DevicesTypes\RoutingKey::CHANNEL_DOCUMENT_UPDATED,
				DevicesEntities\Channels\Channel::class,
			],
		];

		$mapped = 0;

		foreach ($entities as [$documentClass, $routingKey, $entityClass]) {
			foreach ($this->getEntityManager()->getRepository($entityClass)->findAll() as $entity) {
				$data = $entity->toArray();

				$document = $this->assertSameDocument($documentClass, $data);
				self::assertStringStartsWith('FastyBird\\Connector\\Sonoff\\Documents\\', $document::class);

				$this->assertSameDocument($documentClass, Utils\Json::encode($data));
				$this->assertSameDocument($documentClass, $document->toArray());

				$routed = $this->getContainer()->getByType(CoreDocuments\RoutingDocumentFactory::class)->create(
					$data,
					$routingKey->value,
				);

				$this->assertSameMapping($routed, Utils\ArrayHash::from($data));

				$mapped++;
			}
		}

		// Two connectors, two devices and the channel
		self::assertSame(5, $mapped);
	}

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('documents')]
	public function testValinorMapsTheSameDocument(string $class, array $data): void
	{
		$this->assertSameDocument($class, $data);
	}

	/**
	 * The known-positive control: the comparison has to tell two documents apart when one field differs
	 *
	 * @throws Throwable
	 */
	public function testOneChangedFieldIsTold(): void
	{
		$data = self::device();

		$orisai = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class)->create(
			DevicesDocuments\Devices\Device::class,
			$data,
		);
		$valinor = self::valinor()->map($orisai::class, array_merge($data, ['connector' => self::OWNER]));

		self::assertNotSame(Utils\Json::encode($orisai->toArray()), Utils\Json::encode($valinor->toArray()));
		self::assertNotSame(serialize($orisai), serialize($valinor));
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('invalid')]
	public function testValinorRefusesWhatOrisaiRefuses(array $data): void
	{
		$orisaiRefused = false;
		$valinorRefused = false;

		try {
			$this->getContainer()->getByType(CoreDocuments\DocumentFactory::class)->create(
				DevicesDocuments\Devices\Device::class,
				$data,
			);
		} catch (CoreExceptions\InvalidArgument) {
			$orisaiRefused = true;
		}

		try {
			self::valinor()->map(SonoffDocuments\Devices\Device::class, $data);
		} catch (Valinor\Mapper\MappingError) {
			$valinorRefused = true;
		}

		self::assertTrue($orisaiRefused);
		self::assertTrue($valinorRefused);
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>}>
	 *
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function documents(): array
	{
		return [
			'device, every field' => [DevicesDocuments\Devices\Device::class, self::device()],
			'device, only the required fields' => [
				DevicesDocuments\Devices\Device::class,
				[
					'id' => self::ID,
					'type' => SonoffEntities\Devices\Device::TYPE,
					'category' => DevicesTypes\DeviceCategory::GENERIC->value,
					'identifier' => 'required',
					'connector' => self::CONNECTOR_ID,
				],
			],
			'device, blank strings' => [
				DevicesDocuments\Devices\Device::class,
				array_merge(self::device(), ['name' => '', 'comment' => "\t ", 'owner' => '']),
			],
			'device, nulls' => [
				DevicesDocuments\Devices\Device::class,
				array_merge(
					self::device(),
					['name' => null, 'comment' => null, 'owner' => null, 'created_at' => null, 'updated_at' => null],
				),
			],
			'device, objects' => [
				DevicesDocuments\Devices\Device::class,
				array_merge(self::device(), [
					'id' => Uuid\Uuid::fromString(self::ID),
					'category' => DevicesTypes\DeviceCategory::GENERIC,
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'owner' => Uuid\Uuid::fromString(self::OWNER),
				]),
			],
			'connector, every field' => [
				DevicesDocuments\Connectors\Connector::class,
				[
					'id' => self::CONNECTOR_ID,
					'type' => SonoffEntities\Connectors\Connector::TYPE,
					'category' => DevicesTypes\ConnectorCategory::GENERIC->value,
					'identifier' => 'sonoff-local',
					'name' => 'Sonoff Local',
					'comment' => 'Comment',
					'enabled' => true,
					'properties' => ['1ad67aa5-cc34-4c94-b0f7-a2ebe099a71c'],
					'controls' => ['0a0cf4be-c6fc-48f0-9399-da2113ac867c'],
					'devices' => [self::DEVICE_ABC, self::ID],
					'owner' => self::OWNER,
					'created_at' => '2023-10-10T20:00:00+02:00',
					'updated_at' => '2023-10-11T08:30:00+00:00',
				],
			],
			'connector, disabled, blank strings' => [
				DevicesDocuments\Connectors\Connector::class,
				[
					'id' => self::CONNECTOR_ID,
					'type' => SonoffEntities\Connectors\Connector::TYPE,
					'category' => DevicesTypes\ConnectorCategory::GENERIC->value,
					'identifier' => 'sonoff-local',
					'name' => ' ',
					'enabled' => false,
				],
			],
			'channel, every field' => [
				DevicesDocuments\Channels\Channel::class,
				[
					'id' => self::ID,
					'type' => SonoffEntities\Channels\Channel::TYPE,
					'category' => DevicesTypes\ChannelCategory::GENERIC->value,
					'identifier' => 'switch_0',
					'name' => 'Switch',
					'comment' => '',
					'properties' => ['3b6d3f4a-8a5e-4dbf-9fa0-9e8d7c6b5a4f'],
					'controls' => [],
					'device' => self::DEVICE_ABC,
					'connector' => self::CONNECTOR_ID,
					'owner' => self::OWNER,
					'created_at' => '2023-10-10T20:00:00+02:00',
					'updated_at' => null,
				],
			],
		];
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function invalid(): array
	{
		return [
			'an identifier which is not a UUID' => [array_merge(self::device(), ['id' => 'not-a-uuid'])],
			'an unknown category' => [array_merge(self::device(), ['category' => 'unknown'])],
			'no connector' => [
				[
					'id' => self::ID,
					'type' => SonoffEntities\Devices\Device::TYPE,
					'category' => DevicesTypes\DeviceCategory::GENERIC->value,
					'identifier' => 'abc',
				],
			],
			'children which are not a list of UUIDs' => [array_merge(self::device(), ['children' => [42]])],
			'a date in another format' => [array_merge(self::device(), ['created_at' => '2023-10-10 20:00:00'])],
		];
	}

	/**
	 * Maps the input with orisai through DocumentFactory and with Valinor, and compares the two documents
	 *
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed>|string $data
	 *
	 * @throws Throwable
	 */
	private function assertSameDocument(string $class, array|string $data): CoreDocuments\Document
	{
		$orisai = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class)->create($class, $data);

		$this->assertSameMapping($orisai, $data);

		return $orisai;
	}

	/**
	 * @param array<string, mixed>|string|object $data what was handed to DocumentFactory, which decodes a string and
	 *                                                 serialises an object to JSON and back before it maps the result
	 *
	 * @throws Throwable
	 */
	private function assertSameMapping(CoreDocuments\Document $orisai, array|string|object $data): void
	{
		if (!is_array($data)) {
			$data = Utils\Json::decode(is_string($data) ? $data : Utils\Json::encode($data), forceArrays: true);
			assert(is_array($data));
		}

		$valinor = self::valinor()->map($orisai::class, $data);

		self::assertSame(Utils\Json::encode($orisai->toArray()), Utils\Json::encode($valinor->toArray()));
		self::assertSame(serialize($orisai), serialize($valinor));
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function device(): array
	{
		return [
			'id' => self::ID,
			'type' => SonoffEntities\Devices\Device::TYPE,
			'category' => DevicesTypes\DeviceCategory::GENERIC->value,
			'identifier' => 'every-field',
			'name' => 'Name',
			'comment' => 'Comment',
			'connector' => self::CONNECTOR_ID,
			'parents' => [self::DEVICE_ABC],
			'children' => ['0f3a0c1e-5d2b-4a8e-9c7d-6b5a4f3e2d1c'],
			'properties' => ['3b6d3f4a-8a5e-4dbf-9fa0-9e8d7c6b5a4f', '2a5c2e3f-7f4d-4caf-8e9f-8d7c6b5a4f3e'],
			'controls' => [],
			'channels' => [self::ID],
			'owner' => self::OWNER,
			'created_at' => '2023-10-10T20:00:00+02:00',
			'updated_at' => '2024-02-29T23:59:59-05:00',
		];
	}

	private static function valinor(): Valinor\Mapper\TreeMapper
	{
		return (new Valinor\MapperBuilder())
			->allowSuperfluousKeys()
			->supportDateFormats(DateTimeInterface::ATOM)
			->registerConstructor(static fn (string $value): Uuid\UuidInterface => Uuid\Uuid::fromString($value))
			->filterExceptions(
				static function (Throwable $exception): Valinor\Mapper\Tree\Message\ErrorMessage {
					if ($exception instanceof Uuid\Exception\InvalidUuidStringException) {
						return Valinor\Mapper\Tree\Message\MessageBuilder::from($exception);
					}

					throw $exception;
				},
			)
			->mapper();
	}

}
