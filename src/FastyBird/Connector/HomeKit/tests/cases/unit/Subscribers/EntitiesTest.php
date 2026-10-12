<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Tests\Cases\Unit\Subscribers;

use FastyBird\Connector\HomeKit\Events as HomeKitEvents;
use FastyBird\Connector\HomeKit\Subscribers;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher;
use function array_map;

/**
 * The subscriber turns a change of the connector's shared key, paired flag or configuration version
 * into a HomeKit event. It used to invoke its `$onUpdateSharedKey`/`$onRefresh` callback arrays.
 */
final class EntitiesTest extends TestCase
{

	private EventDispatcher\EventDispatcher $dispatcher;

	/** @var array<HomeKitEvents\SharedKeyChanged|HomeKitEvents\AccessoryConfigurationChanged> */
	private array $dispatched = [];

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->dispatcher = new EventDispatcher\EventDispatcher();

		$this->dispatcher->addListener(
			HomeKitEvents\SharedKeyChanged::class,
			function (HomeKitEvents\SharedKeyChanged $event): void {
				$this->dispatched[] = $event;
			},
		);

		$this->dispatcher->addListener(
			HomeKitEvents\AccessoryConfigurationChanged::class,
			function (HomeKitEvents\AccessoryConfigurationChanged $event): void {
				$this->dispatched[] = $event;
			},
		);
	}

	public function testSubscribedEvents(): void
	{
		self::assertSame(
			[
				DevicesEvents\EntityCreated::class => 'entityCreated',
				DevicesEvents\EntityUpdated::class => 'entityUpdated',
				DevicesEvents\EntityDeleted::class => 'entityDeleted',
			],
			Subscribers\Entities::getSubscribedEvents(),
		);
	}

	/**
	 * @param 'created'|'updated'|'deleted' $lifecycle
	 * @param array<class-string<HomeKitEvents\SharedKeyChanged|HomeKitEvents\AccessoryConfigurationChanged>> $expected
	 */
	#[DataProvider('variableChanges')]
	public function testDispatchesHomeKitEvents(
		string $lifecycle,
		Types\ConnectorPropertyIdentifier $identifier,
		array $expected,
	): void
	{
		$property = $this->createMock(DevicesEntities\Connectors\Properties\Variable::class);
		$property
			->method('getIdentifier')
			->willReturn($identifier->value);

		$subscriber = new Subscribers\Entities($this->dispatcher);

		match ($lifecycle) {
			'created' => $subscriber->entityCreated(new DevicesEvents\EntityCreated($property)),
			'updated' => $subscriber->entityUpdated(new DevicesEvents\EntityUpdated($property)),
			'deleted' => $subscriber->entityDeleted(new DevicesEvents\EntityDeleted($property)),
		};

		self::assertSame(
			$expected,
			array_map(static fn (object $event): string => $event::class, $this->dispatched),
		);

		foreach ($this->dispatched as $event) {
			self::assertSame($property, $event->getProperty());
		}
	}

	/**
	 * @return array<string, array{
	 *     'created'|'updated'|'deleted',
	 *     Types\ConnectorPropertyIdentifier,
	 *     array<class-string<HomeKitEvents\SharedKeyChanged|HomeKitEvents\AccessoryConfigurationChanged>>,
	 * }>
	 */
	public static function variableChanges(): array
	{
		return [
			'shared key created' => [
				'created',
				Types\ConnectorPropertyIdentifier::SHARED_KEY,
				[HomeKitEvents\SharedKeyChanged::class],
			],
			'shared key updated' => [
				'updated',
				Types\ConnectorPropertyIdentifier::SHARED_KEY,
				[HomeKitEvents\SharedKeyChanged::class],
			],
			'shared key deleted' => [
				'deleted',
				Types\ConnectorPropertyIdentifier::SHARED_KEY,
				[],
			],
			'paired created' => [
				'created',
				Types\ConnectorPropertyIdentifier::PAIRED,
				[HomeKitEvents\AccessoryConfigurationChanged::class],
			],
			'paired updated' => [
				'updated',
				Types\ConnectorPropertyIdentifier::PAIRED,
				[HomeKitEvents\AccessoryConfigurationChanged::class],
			],
			'paired deleted' => [
				'deleted',
				Types\ConnectorPropertyIdentifier::PAIRED,
				[HomeKitEvents\AccessoryConfigurationChanged::class],
			],
			'configuration version created' => [
				'created',
				Types\ConnectorPropertyIdentifier::CONFIG_VERSION,
				[HomeKitEvents\AccessoryConfigurationChanged::class],
			],
			'configuration version updated' => [
				'updated',
				Types\ConnectorPropertyIdentifier::CONFIG_VERSION,
				[HomeKitEvents\AccessoryConfigurationChanged::class],
			],
			'configuration version deleted' => [
				'deleted',
				Types\ConnectorPropertyIdentifier::CONFIG_VERSION,
				[HomeKitEvents\AccessoryConfigurationChanged::class],
			],
			'pin code updated' => [
				'updated',
				Types\ConnectorPropertyIdentifier::PIN_CODE,
				[],
			],
		];
	}

	public function testIgnoresOtherEntities(): void
	{
		$property = $this->createMock(DevicesEntities\Connectors\Properties\Dynamic::class);
		$property
			->method('getIdentifier')
			->willReturn(Types\ConnectorPropertyIdentifier::SHARED_KEY->value);

		$subscriber = new Subscribers\Entities($this->dispatcher);

		$subscriber->entityCreated(new DevicesEvents\EntityCreated($property));
		$subscriber->entityUpdated(new DevicesEvents\EntityUpdated($property));
		$subscriber->entityDeleted(new DevicesEvents\EntityDeleted($property));

		self::assertSame([], $this->dispatched);
	}

}
