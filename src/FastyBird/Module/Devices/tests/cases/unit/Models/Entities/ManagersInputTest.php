<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Entities;

use BackedEnum;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Tests;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use ReflectionException;
use RuntimeException;
use function array_key_exists;
use function array_map;
use function array_shift;
use function assert;
use function is_array;
use function is_object;
use function ksort;
use function sort;
use function sprintf;
use function str_replace;

/**
 * Devices' nine entity managers take an array as well as an ArrayHash (#723). Each test fills the
 * same entity graph through the managers, once from ArrayHash values and once from the equal
 * plain arrays, and compares the graph with one pinned record: every mapped field and every
 * association of every entity reachable from the connector, read straight from the entities as
 * the managers return them and again after the entity manager is cleared and the graph is
 * reloaded. The ArrayHash tests pass unchanged against the code before #723.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ManagersInputTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a01';

	private const string CONNECTOR_PROPERTY = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a02';

	private const string CONNECTOR_VARIABLE = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a03';

	private const string PARENT_DEVICE = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a10';

	private const string OTHER_PARENT_DEVICE = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a11';

	private const string CHILD_DEVICE = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a12';

	private const string NESTED_CHANNEL = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a13';

	private const string NESTED_CHANNEL_PROPERTY = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a14';

	private const string DEVICE_PROPERTY = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a20';

	private const string DEVICE_MAPPED = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a21';

	private const string DEVICE_VARIABLE = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a22';

	private const string CHANNEL = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a30';

	private const string CHANNEL_PROPERTY = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a31';

	private const string CHANNEL_MAPPED = '7e9a3b56-5d2e-4f4c-9b70-0c6f1b0f2a32';

	private const string FIXTURES = __DIR__ . '/../../../../fixtures/Models/Entities/';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Utils\JsonException
	 */
	public function testCreateFromAnArrayHashFillsTheWholeGraph(): void
	{
		$this->create(static fn (array $values): Utils\ArrayHash => Utils\ArrayHash::from($values));

		$this->assertGraph('created');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Utils\JsonException
	 */
	public function testCreateFromAnArrayFillsTheSameGraph(): void
	{
		$this->create(static fn (array $values): array => $values);

		$this->assertGraph('created');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Utils\JsonException
	 */
	public function testUpdateFromAnArrayHashChangesTheWholeGraph(): void
	{
		$this->create(static fn (array $values): Utils\ArrayHash => Utils\ArrayHash::from($values));
		$this->update(static fn (array $values): Utils\ArrayHash => Utils\ArrayHash::from($values));

		$this->assertGraph('updated');
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Utils\JsonException
	 */
	public function testUpdateFromAnArrayChangesTheSameGraph(): void
	{
		$this->create(static fn (array $values): Utils\ArrayHash => Utils\ArrayHash::from($values));
		$this->update(static fn (array $values): array => $values);

		$this->assertGraph('updated');
	}

	/**
	 * Every manager's create(), with nested to-many items (a device's channels, their
	 * properties and the device's controls), list and nested-list formats, nested params and
	 * device parents
	 *
	 * @param callable(array<string, mixed>): (array<string, mixed>|Utils\ArrayHash<mixed>) $input
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function create(callable $input): void
	{
		$container = $this->getContainer();

		$connector = $container->getByType(Models\Entities\Connectors\ConnectorsManager::class)->create($input([
			'entity' => Tests\Fixtures\Dummy\DummyConnectorEntity::class,
			'id' => Uuid\Uuid::fromString(self::CONNECTOR),
			'identifier' => 'graph-connector',
			'name' => 'Graph connector',
			'params' => ['transport' => ['host' => 'localhost', 'ports' => [80, 443]], 'retries' => 3],
		]));

		$connectorProperties = $container->getByType(Models\Entities\Connectors\Properties\PropertiesManager::class);

		$connectorProperties->create($input([
			'entity' => Entities\Connectors\Properties\Dynamic::class,
			'id' => Uuid\Uuid::fromString(self::CONNECTOR_PROPERTY),
			'connector' => $connector,
			'identifier' => 'state',
			'dataType' => ValuesTypes\DataType::ENUM,
			'format' => ['running', 'stopped', 'unknown'],
			'settable' => false,
			'queryable' => false,
		]));

		$connectorProperties->create($input([
			'entity' => Entities\Connectors\Properties\Variable::class,
			'id' => Uuid\Uuid::fromString(self::CONNECTOR_VARIABLE),
			'connector' => $connector,
			'identifier' => 'username',
			'dataType' => ValuesTypes\DataType::STRING,
			'value' => 'graph-user',
		]));

		$container->getByType(Models\Entities\Connectors\Controls\ControlsManager::class)->create($input([
			'name' => 'discover',
			'connector' => $connector,
		]));

		$devices = $container->getByType(Models\Entities\Devices\DevicesManager::class);

		$parent = $devices->create($input([
			'entity' => Tests\Fixtures\Dummy\DummyDeviceEntity::class,
			'id' => Uuid\Uuid::fromString(self::PARENT_DEVICE),
			'identifier' => 'graph-parent',
			'connector' => $connector,
			'name' => 'Graph parent',
			'comment' => 'Parent of the graph',
			'params' => ['address' => ['ip' => '10.0.0.1', 'mask' => [255, 255, 255, 0]]],
			'channels' => [
				[
					'entity' => Tests\Fixtures\Dummy\DummyChannelEntity::class,
					'id' => Uuid\Uuid::fromString(self::NESTED_CHANNEL),
					'identifier' => 'nested-channel',
					'name' => 'Nested channel',
					'params' => ['index' => ['row' => 1, 'columns' => [1, 2]]],
					'properties' => [
						[
							'entity' => Entities\Channels\Properties\Dynamic::class,
							'id' => Uuid\Uuid::fromString(self::NESTED_CHANNEL_PROPERTY),
							'identifier' => 'nested-switch',
							'dataType' => ValuesTypes\DataType::SWITCH,
							'format' => [['switch_on', 'on', 'on'], ['switch_off', 'off', 'off']],
							'settable' => true,
						],
					],
				],
			],
			'controls' => [
				['name' => 'reboot'],
			],
		]));

		$otherParent = $devices->create($input([
			'entity' => Tests\Fixtures\Dummy\DummyDeviceEntity::class,
			'id' => Uuid\Uuid::fromString(self::OTHER_PARENT_DEVICE),
			'identifier' => 'graph-other-parent',
			'connector' => $connector,
		]));

		$devices->create($input([
			'entity' => Tests\Fixtures\Dummy\DummyDeviceEntity::class,
			'id' => Uuid\Uuid::fromString(self::CHILD_DEVICE),
			'identifier' => 'graph-child',
			'connector' => $connector,
			'parents' => [$parent],
		]));

		$deviceProperties = $container->getByType(Models\Entities\Devices\Properties\PropertiesManager::class);

		$deviceProperty = $deviceProperties->create($input([
			'entity' => Entities\Devices\Properties\Dynamic::class,
			'id' => Uuid\Uuid::fromString(self::DEVICE_PROPERTY),
			'device' => $parent,
			'identifier' => 'power',
			'name' => 'Power',
			'dataType' => ValuesTypes\DataType::ENUM,
			'format' => [['on', '1', 'true'], ['off', '0', 'false']],
			'settable' => true,
			'queryable' => true,
			'params' => ['source' => ['register' => 12, 'mask' => [1, 2, 4]]],
		]));

		$deviceProperties->create($input([
			'entity' => Entities\Devices\Properties\Mapped::class,
			'id' => Uuid\Uuid::fromString(self::DEVICE_MAPPED),
			'device' => $otherParent,
			'parent' => $deviceProperty,
			'identifier' => 'power-mirror',
			'dataType' => ValuesTypes\DataType::ENUM,
			'format' => [['on', '1', 'true'], ['off', '0', 'false']],
		]));

		$deviceProperties->create($input([
			'entity' => Entities\Devices\Properties\Variable::class,
			'id' => Uuid\Uuid::fromString(self::DEVICE_VARIABLE),
			'device' => $parent,
			'identifier' => 'hardware',
			'dataType' => ValuesTypes\DataType::STRING,
			'value' => 'rev-b',
		]));

		$container->getByType(Models\Entities\Devices\Controls\ControlsManager::class)->create($input([
			'name' => 'identify',
			'device' => $parent,
		]));

		$channel = $container->getByType(Models\Entities\Channels\ChannelsManager::class)->create($input([
			'entity' => Tests\Fixtures\Dummy\DummyChannelEntity::class,
			'id' => Uuid\Uuid::fromString(self::CHANNEL),
			'device' => $parent,
			'identifier' => 'sensor',
			'name' => 'Sensor',
			'params' => ['calibration' => ['offset' => 1.5, 'points' => [0.0, 50.5]]],
		]));

		$channelProperties = $container->getByType(Models\Entities\Channels\Properties\PropertiesManager::class);

		$channelProperty = $channelProperties->create($input([
			'entity' => Entities\Channels\Properties\Dynamic::class,
			'id' => Uuid\Uuid::fromString(self::CHANNEL_PROPERTY),
			'channel' => $channel,
			'identifier' => 'temperature',
			'dataType' => ValuesTypes\DataType::FLOAT,
			'unit' => '°C',
			'format' => [-40, 125],
			'scale' => 1,
			'step' => 0.5,
			'invalid' => '-1000',
		]));

		$channelProperties->create($input([
			'entity' => Entities\Channels\Properties\Mapped::class,
			'id' => Uuid\Uuid::fromString(self::CHANNEL_MAPPED),
			'channel' => $channel,
			'parent' => $channelProperty,
			'identifier' => 'temperature-mirror',
			'dataType' => ValuesTypes\DataType::FLOAT,
			'format' => [-40, 125],
		]));

		$container->getByType(Models\Entities\Channels\Controls\ControlsManager::class)->create($input([
			'name' => 'calibrate',
			'channel' => $channel,
		]));
	}

	/**
	 * Every manager's update(): scalar fields, merged nested params, a changed format and a
	 * device's parents replaced
	 *
	 * @param callable(array<string, mixed>): (array<string, mixed>|Utils\ArrayHash<mixed>) $input
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function update(callable $input): void
	{
		$container = $this->getContainer();

		$connector = $container->getByType(Models\Entities\Connectors\ConnectorsRepository::class)->find(
			Uuid\Uuid::fromString(self::CONNECTOR),
		);
		assert($connector instanceof Entities\Connectors\Connector);

		$container->getByType(Models\Entities\Connectors\ConnectorsManager::class)->update($connector, $input([
			'name' => 'Graph connector renamed',
			'comment' => 'Updated',
			'enabled' => false,
			'params' => ['transport' => ['host' => 'remote', 'ports' => [8_080]], 'timeout' => 5],
		]));

		$connectorProperty = $this->find(Entities\Connectors\Properties\Property::class, self::CONNECTOR_PROPERTY);

		$container->getByType(Models\Entities\Connectors\Properties\PropertiesManager::class)->update(
			$connectorProperty,
			$input([
				'name' => 'State',
				'format' => ['running', 'stopped'],
				'queryable' => true,
			]),
		);

		$connectorControl = $connector->getControls()[0];

		$container->getByType(Models\Entities\Connectors\Controls\ControlsManager::class)->update(
			$connectorControl,
			$input(['name' => 'not-writable']),
		);

		$devices = $container->getByType(Models\Entities\Devices\DevicesManager::class);

		$parent = $this->find(Entities\Devices\Device::class, self::PARENT_DEVICE);
		$otherParent = $this->find(Entities\Devices\Device::class, self::OTHER_PARENT_DEVICE);

		$devices->update($parent, $input([
			'name' => 'Graph parent renamed',
			'comment' => null,
			'params' => ['address' => ['ip' => '10.0.0.2'], 'zone' => ['floor' => [1, 2]]],
		]));

		$devices->update($this->find(Entities\Devices\Device::class, self::CHILD_DEVICE), $input([
			'name' => 'Graph child',
			'parents' => [$otherParent],
		]));

		$container->getByType(Models\Entities\Devices\Properties\PropertiesManager::class)->update(
			$this->find(Entities\Devices\Properties\Property::class, self::DEVICE_PROPERTY),
			$input([
				'name' => 'Power state',
				'format' => [['on', 'true', '1'], ['off', 'false', '0'], ['toggle', 'toggle', null]],
				'params' => ['source' => ['register' => 13], 'extra' => ['flags' => [true, false]]],
			]),
		);

		$deviceControl = $parent->getControls()[0];

		$container->getByType(Models\Entities\Devices\Controls\ControlsManager::class)->update(
			$deviceControl,
			$input(['name' => 'not-writable']),
		);

		$container->getByType(Models\Entities\Channels\ChannelsManager::class)->update(
			$this->find(Entities\Channels\Channel::class, self::CHANNEL),
			$input([
				'name' => 'Sensor renamed',
				'comment' => 'Calibrated',
				'params' => ['calibration' => ['offset' => -0.5], 'history' => [['at' => 1, 'value' => 2.5]]],
			]),
		);

		$container->getByType(Models\Entities\Channels\Properties\PropertiesManager::class)->update(
			$this->find(Entities\Channels\Properties\Property::class, self::CHANNEL_PROPERTY),
			$input([
				'format' => [['f', -20], ['f', 80]],
				'scale' => 2,
				'step' => 0.1,
				'invalid' => null,
				'settable' => true,
			]),
		);

		$channelControl = $this->find(Entities\Channels\Channel::class, self::CHANNEL)->getControls()[0];

		$container->getByType(Models\Entities\Channels\Controls\ControlsManager::class)->update(
			$channelControl,
			$input(['name' => 'not-writable']),
		);
	}

	/**
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function find(string $class, string $id): object
	{
		$entity = $this->getEntityManager()->find($class, Uuid\Uuid::fromString($id));
		assert($entity instanceof $class);

		return $entity;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Utils\JsonException
	 */
	private function assertGraph(string $fixture): void
	{
		self::assertSame(
			Utils\Json::decode(Utils\FileSystem::read(self::FIXTURES . $fixture . '.returned.json'), forceArrays: true),
			$this->dumpGraph(),
			'The graph as the managers returned it',
		);

		$this->getEntityManager()->clear();

		self::assertSame(
			Utils\Json::decode(Utils\FileSystem::read(self::FIXTURES . $fixture . '.reloaded.json'), forceArrays: true),
			$this->dumpGraph(),
			'The graph read back from the database',
		);
	}

	/**
	 * Every entity created here and every entity reachable from one, keyed by a reference, with
	 * every mapped field and every association (as references). The walk starts from each of
	 * them, not only from the connector, because an inverse side is not filled in memory: the
	 * connector's devices are empty until the graph is read back.
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Utils\JsonException
	 */
	private function dumpGraph(): array
	{
		$entityManager = $this->getEntityManager();

		$queue = [
			$this->find(Entities\Connectors\Connector::class, self::CONNECTOR),
			$this->find(Entities\Connectors\Properties\Property::class, self::CONNECTOR_PROPERTY),
			$this->find(Entities\Connectors\Properties\Property::class, self::CONNECTOR_VARIABLE),
			$this->find(Entities\Devices\Device::class, self::PARENT_DEVICE),
			$this->find(Entities\Devices\Device::class, self::OTHER_PARENT_DEVICE),
			$this->find(Entities\Devices\Device::class, self::CHILD_DEVICE),
			$this->find(Entities\Channels\Channel::class, self::NESTED_CHANNEL),
			$this->find(Entities\Channels\Properties\Property::class, self::NESTED_CHANNEL_PROPERTY),
			$this->find(Entities\Devices\Properties\Property::class, self::DEVICE_PROPERTY),
			$this->find(Entities\Devices\Properties\Property::class, self::DEVICE_MAPPED),
			$this->find(Entities\Devices\Properties\Property::class, self::DEVICE_VARIABLE),
			$this->find(Entities\Channels\Channel::class, self::CHANNEL),
			$this->find(Entities\Channels\Properties\Property::class, self::CHANNEL_PROPERTY),
			$this->find(Entities\Channels\Properties\Property::class, self::CHANNEL_MAPPED),
		];
		$graph = [];

		while ($queue !== []) {
			$entity = array_shift($queue);

			$reference = $this->reference($entity);

			if (array_key_exists($reference, $graph)) {
				continue;
			}

			$metadata = $entityManager->getClassMetadata($entity::class);

			$record = [];

			foreach ($metadata->getFieldNames() as $field) {
				$record[$field] = $this->normalize($metadata->getFieldValue($entity, $field));
			}

			if ($this->isControl($entity)) {
				// Always generated, so the reference names a control instead
				unset($record['id']);
			}

			foreach ($metadata->getAssociationNames() as $association) {
				$value = $metadata->getFieldValue($entity, $association);

				if ($value instanceof Collection) {
					$references = [];

					foreach ($value as $item) {
						assert(is_object($item));

						$references[] = $this->reference($item);
						$queue[] = $item;
					}

					sort($references);

					$record[$association] = $references;
				} elseif (is_object($value)) {
					$record[$association] = $this->reference($value);
					$queue[] = $value;
				} else {
					$record[$association] = null;
				}
			}

			ksort($record);

			$graph[$reference] = $record;
		}

		ksort($graph);

		// Through JSON and back, as the pinned record is read, so both sides compare alike
		/** @var array<string, array<string, mixed>> $graph */
		$graph = Utils\Json::decode(Utils\Json::encode($graph), forceArrays: true);

		return $graph;
	}

	private function reference(object $entity): string
	{
		$class = str_replace('FastyBird\\Module\\Devices\\', '', $entity::class);

		// A control's id is always generated, so it is named by its owner and its name
		if ($entity instanceof Entities\Connectors\Controls\Control) {
			return sprintf('%s:%s/%s', $class, $entity->getConnector()->getId()->toString(), $entity->getName());
		}

		if ($entity instanceof Entities\Devices\Controls\Control) {
			return sprintf('%s:%s/%s', $class, $entity->getDevice()->getId()->toString(), $entity->getName());
		}

		if ($entity instanceof Entities\Channels\Controls\Control) {
			return sprintf('%s:%s/%s', $class, $entity->getChannel()->getId()->toString(), $entity->getName());
		}

		assert($entity instanceof Entities\Entity);

		return sprintf('%s:%s', $class, $entity->getId()->toString());
	}

	private function isControl(object $entity): bool
	{
		return $entity instanceof Entities\Connectors\Controls\Control
			|| $entity instanceof Entities\Devices\Controls\Control
			|| $entity instanceof Entities\Channels\Controls\Control;
	}

	private function normalize(mixed $value): mixed
	{
		if ($value instanceof Utils\ArrayHash) {
			return ['ArrayHash' => $this->normalize((array) $value)];
		}

		if (is_array($value)) {
			return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
		}

		if ($value instanceof Uuid\UuidInterface) {
			return $value->toString();
		}

		if ($value instanceof BackedEnum) {
			return sprintf('%s::%s', $value::class, (string) $value->value);
		}

		if ($value instanceof DateTimeInterface) {
			return $value->format(DateTimeInterface::ATOM);
		}

		if (is_object($value)) {
			return sprintf('object:%s', $value::class);
		}

		return $value;
	}

}
