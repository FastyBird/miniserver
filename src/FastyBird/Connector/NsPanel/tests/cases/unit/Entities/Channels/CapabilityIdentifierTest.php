<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Entities\Channels;

use Error;
use FastyBird\Connector\NsPanel\Entities as NsPanelEntities;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Hydrators;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;
use Throwable;
use function array_keys;
use function array_map;
use function assert;
use function in_array;
use function is_string;
use function ksort;

/**
 * Pins #591: an NS Panel capability channel whose entity constructor fixes the identifier to the
 * capability value keeps that value whatever a write says. Through the JSON:API path -- the
 * channel hydrator the controller would pick, then the Devices channels manager, whose entity
 * creator and updater run the real EntityMapper against the test database -- the identifier is
 * optional on create and ignored when sent, and an update cannot change it. The persistence layer
 * holds the same rule on its own, for a caller that skips the hydrator. The two capabilities whose
 * identifier carries a client-chosen suffix (toggle, startup) still store what is sent and still
 * reject a create without it.
 */
final class CapabilityIdentifierTest extends Tests\Cases\Unit\DbTestCase
{

	private const string DEVICE_ID = '896a5f35-7c9a-47f2-9c72-f1520d503364';

	/**
	 * Every NS Panel channel entity whose constructor fixes the identifier, with that identifier
	 */
	private const array FIXED = [
		NsPanelEntities\Channels\Battery::class => 'battery',
		NsPanelEntities\Channels\Brightness::class => 'brightness',
		NsPanelEntities\Channels\CameraStream::class => 'camera-stream',
		NsPanelEntities\Channels\ColorRgb::class => 'color-rgb',
		NsPanelEntities\Channels\ColorTemperature::class => 'color-temperature',
		NsPanelEntities\Channels\Detect::class => 'detect',
		NsPanelEntities\Channels\Fault::class => 'fault',
		NsPanelEntities\Channels\Humidity::class => 'humidity',
		NsPanelEntities\Channels\IlluminationLevel::class => 'illumination-level',
		NsPanelEntities\Channels\MotorCalibration::class => 'motor-clb',
		NsPanelEntities\Channels\MotorControl::class => 'motor-control',
		NsPanelEntities\Channels\MotorReverse::class => 'motor-reverse',
		NsPanelEntities\Channels\Percentage::class => 'percentage',
		NsPanelEntities\Channels\Power::class => 'power',
		NsPanelEntities\Channels\Press::class => 'press',
		NsPanelEntities\Channels\Rssi::class => 'rssi',
		NsPanelEntities\Channels\Temperature::class => 'temperature',
		NsPanelEntities\Channels\Thermostat::class => 'thermostat',
		NsPanelEntities\Channels\ThermostatModeDetect::class => 'thermostat-mode-detect',
		NsPanelEntities\Channels\ThermostatTargetSetPoint::class => 'thermostat-target-setpoint',
	];

	/**
	 * The NS Panel channel entities whose identifier the client supplies
	 */
	private const array SUPPLIED = [
		NsPanelEntities\Channels\Startup::class,
		NsPanelEntities\Channels\Toggle::class,
	];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Error
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testEveryChannelHydratorIsClassified(): void
	{
		$fixed = [];
		$supplied = [];

		foreach ($this->hydrators() as $entityClass => $hydrator) {
			$constructor = (new ReflectionClass($entityClass))->getConstructor();
			assert($constructor !== null);

			$parameters = array_map(
				static fn (ReflectionParameter $parameter): string => $parameter->getName(),
				$constructor->getParameters(),
			);

			if (in_array('identifier', $parameters, true)) {
				$supplied[] = $entityClass;
			} else {
				$fixed[] = $entityClass;
			}
		}

		self::assertSame(array_keys(self::FIXED), $fixed);
		self::assertSame(self::SUPPLIED, $supplied);
	}

	/**
	 * @throws Throwable
	 */
	public function testApiCreateWithoutIdentifierStoresTheCapability(): void
	{
		$hydrators = $this->hydrators();

		foreach (self::FIXED as $entityClass => $capability) {
			$values = $hydrators[$entityClass]->hydrate($this->document(['name' => 'Created']));

			$channel = $this->channelsManager()->create($values);

			self::assertInstanceOf($entityClass, $channel);
			self::assertSame($capability, $this->storedIdentifier($channel), $entityClass);
		}
	}

	/**
	 * @throws Throwable
	 */
	public function testApiCreateIgnoresADifferentIdentifier(): void
	{
		$hydrators = $this->hydrators();

		foreach (self::FIXED as $entityClass => $capability) {
			$values = $hydrators[$entityClass]->hydrate($this->document(['identifier' => 'live591-other']));

			$channel = $this->channelsManager()->create($values);

			self::assertInstanceOf($entityClass, $channel);
			self::assertSame($capability, $this->storedIdentifier($channel), $entityClass);
		}
	}

	/**
	 * @throws Throwable
	 */
	public function testApiUpdateCannotChangeTheIdentifier(): void
	{
		$hydrator = $this->hydrators()[NsPanelEntities\Channels\Battery::class];

		// Created with the capability value sent, so this pins the update on its own
		$channel = $this->channelsManager()->create($hydrator->hydrate($this->document(['identifier' => 'battery'])));
		self::assertInstanceOf(NsPanelEntities\Channels\Battery::class, $channel);

		$channel = $this->channelsManager()->update(
			$channel,
			$hydrator->hydrate($this->document(['identifier' => 'renamed', 'name' => 'Renamed']), $channel),
		);

		self::assertSame('Renamed', $channel->getName());
		self::assertSame('battery', $this->storedIdentifier($channel));
	}

	/**
	 * @throws Throwable
	 */
	public function testPersistenceCreateKeepsTheCapabilityWithOrWithoutIdentifier(): void
	{
		$device = $this->getEntityManager()->find(
			NsPanelEntities\Devices\Device::class,
			Uuid\Uuid::fromString(self::DEVICE_ID),
		);
		self::assertInstanceOf(NsPanelEntities\Devices\Device::class, $device);

		$withIdentifier = false;

		foreach (self::FIXED as $entityClass => $capability) {
			$values = ['entity' => $entityClass, 'device' => $device];

			if ($withIdentifier) {
				$values['identifier'] = 'live591-other';
			}

			$withIdentifier = !$withIdentifier;

			$channel = $this->channelsManager()->create(Utils\ArrayHash::from($values));

			self::assertSame($capability, $this->storedIdentifier($channel), $entityClass);
		}
	}

	/**
	 * @throws Throwable
	 */
	public function testSuppliedIdentifierIsStoredAndRequired(): void
	{
		$hydrators = $this->hydrators();

		$supplied = [
			'toggle_1' => NsPanelEntities\Channels\Toggle::class,
			'startup_1' => NsPanelEntities\Channels\Startup::class,
		];

		foreach ($supplied as $identifier => $entityClass) {
			$channel = $this->channelsManager()->create(
				$hydrators[$entityClass]->hydrate($this->document(['identifier' => $identifier])),
			);

			self::assertSame($identifier, $this->storedIdentifier($channel));

			// ChannelsV1::create() answers both exceptions with 422 at /data/attributes/<field>
			try {
				$this->channelsManager()->create($hydrators[$entityClass]->hydrate($this->document()));

				self::fail('A ' . $entityClass . ' create without an identifier was accepted.');
			} catch (PersistenceExceptions\MissingRequiredField | PersistenceExceptions\EntityCreation $ex) {
				self::assertSame('identifier', $ex->getField());
			}
		}
	}

	/**
	 * @return array<class-string<NsPanelEntities\Channels\Channel>, Hydrators\Channels\Channel<NsPanelEntities\Channels\Channel>>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Error
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	private function hydrators(): array
	{
		$hydrators = [];

		foreach ($this->getContainer()->findByType(Hydrators\Channels\Channel::class) as $serviceName) {
			$hydrator = $this->getContainer()->getService($serviceName);
			assert($hydrator instanceof Hydrators\Channels\Channel);

			$hydrators[$hydrator->getEntityName()] = $hydrator;
		}

		ksort($hydrators);

		return $hydrators;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Error
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	private function channelsManager(): DevicesModels\Entities\Channels\ChannelsManager
	{
		return $this->getContainer()->getByType(DevicesModels\Entities\Channels\ChannelsManager::class);
	}

	/**
	 * Reads the identifier back from the table, so neither the entity manager's identity map nor
	 * the entity object itself can answer for what was written
	 *
	 * @throws Throwable
	 */
	private function storedIdentifier(DevicesEntities\Channels\Channel $channel): string|false
	{
		$identifier = $this->getDb()->fetchOne(
			'SELECT channel_identifier FROM fb_devices_module_channels WHERE channel_id = :id',
			['id' => $channel->getId()->getBytes()],
		);
		assert($identifier === false || is_string($identifier));

		return $identifier;
	}

	/**
	 * @param array<string, string> $attributes
	 *
	 * @throws Throwable
	 */
	private function document(array $attributes = []): Encoding\IDocument
	{
		return Encoding\Document::create(Utils\Json::encode([
			'data' => [
				'type' => 'ns-panel-connector/channel',
				'id' => Uuid\Uuid::uuid4()->toString(),
				'attributes' => (object) $attributes,
				'relationships' => [
					'device' => [
						'data' => [
							'type' => 'ns-panel-connector/device/ns-panel-connector-gateway',
							'id' => self::DEVICE_ID,
						],
					],
				],
			],
		]));
	}

}
