<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\Subscribers;

use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Bridge\DevicesModuleUiModule\Subscribers;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Events as UiEvents;
use FastyBird\Module\Ui\Types as UiTypes;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;

/**
 * What a Ui data source SET writes to the Devices property it reads (#723).
 *
 * The subscriber hands the property state manager the expected value of the action as the change
 * to write: an ArrayHash of it before #723, a Devices PropertyUpdate since. With the exchange on
 * (the default), the manager publishes the write as a property action document; these tests pin
 * that document, for a connector, a device and a channel property, and pass on both.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ActionCommandTest extends Tests\Cases\Unit\DbTestCase
{

	private const string WIDGET = '15553443-4564-454d-af04-0dfeef08aa96';

	private const string DATA_SOURCE = '32dd50e4-4b66-4dea-9bc5-e835f8543dc4';

	private const string CONNECTOR = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	// Added by connector.dynamic.property.sql
	private const string CONNECTOR_PROPERTY = 'a4c2e6f8-0b1d-4f3a-9c5e-7a9b1d3f5a70';

	private const string DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const string DEVICE_PROPERTY = 'bbcccf8c-33ab-431b-a795-d7bb38b6b6db';

	private const string CHANNEL = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	private const string CHANNEL_PROPERTY = 'bbcccf8c-33ab-431b-a795-d7bb38b6b6db';

	/** @var list<array{int|string, string, class-string|null, array<string, mixed>|null}> */
	private array $published = [];

	#[Override]
	public function setUp(): void
	{
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/connector.dynamic.property.sql');

		parent::setUp();
	}

	/**
	 * @return array<string, array{bool|float|int|string|null}>
	 */
	public static function expectedValues(): array
	{
		return [
			'a string' => ['on'],
			'an integer' => [21],
			'a float' => [21.5],
			'a boolean' => [false],
			'null' => [null],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('expectedValues')]
	public function testASetOfAConnectorPropertyWritesItsExpectedValue(bool|float|int|string|null $expectedValue): void
	{
		$this->dispatch(
			new DevicesModuleUiModuleDocuments\Widgets\DataSources\ConnectorProperty(
				Uuid\Uuid::fromString(self::DATA_SOURCE),
				Uuid\Uuid::fromString(self::WIDGET),
				Uuid\Uuid::fromString(self::CONNECTOR),
				Uuid\Uuid::fromString(self::CONNECTOR_PROPERTY),
			),
			$expectedValue,
		);

		self::assertSame(
			[
				[
					Sources\Module::DEVICES->value,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY,
					DevicesDocuments\States\Connectors\Properties\Actions\Action::class,
					[
						'id' => self::CONNECTOR_PROPERTY,
						'connector' => self::CONNECTOR,
						'property' => self::CONNECTOR_PROPERTY,
						'action' => 'set',
						'write' => ['expected_value' => $expectedValue],
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('expectedValues')]
	public function testASetOfADevicePropertyWritesItsExpectedValue(bool|float|int|string|null $expectedValue): void
	{
		$this->dispatch(
			new DevicesModuleUiModuleDocuments\Widgets\DataSources\DeviceProperty(
				Uuid\Uuid::fromString(self::DATA_SOURCE),
				Uuid\Uuid::fromString(self::WIDGET),
				Uuid\Uuid::fromString(self::DEVICE),
				Uuid\Uuid::fromString(self::DEVICE_PROPERTY),
			),
			$expectedValue,
		);

		self::assertSame(
			[
				[
					Sources\Module::DEVICES->value,
					Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY,
					DevicesDocuments\States\Devices\Properties\Actions\Action::class,
					[
						'id' => self::DEVICE_PROPERTY,
						'device' => self::DEVICE,
						'property' => self::DEVICE_PROPERTY,
						'action' => 'set',
						'write' => ['expected_value' => $expectedValue],
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('expectedValues')]
	public function testASetOfAChannelPropertyWritesItsExpectedValue(bool|float|int|string|null $expectedValue): void
	{
		$this->dispatch(
			new DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty(
				Uuid\Uuid::fromString(self::DATA_SOURCE),
				Uuid\Uuid::fromString(self::WIDGET),
				Uuid\Uuid::fromString(self::CHANNEL),
				Uuid\Uuid::fromString(self::CHANNEL_PROPERTY),
			),
			$expectedValue,
		);

		self::assertSame(
			[
				[
					Sources\Module::DEVICES->value,
					Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY,
					DevicesDocuments\States\Channels\Properties\Actions\Action::class,
					[
						'id' => self::CHANNEL_PROPERTY,
						'channel' => self::CHANNEL,
						'property' => self::CHANNEL_PROPERTY,
						'action' => 'set',
						'write' => ['expected_value' => $expectedValue],
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * The action document's own "not set" marker means there is nothing to write
	 *
	 * @throws Throwable
	 */
	public function testASetWithoutAnExpectedValueWritesNothing(): void
	{
		$this->dispatch(
			new DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty(
				Uuid\Uuid::fromString(self::DATA_SOURCE),
				Uuid\Uuid::fromString(self::WIDGET),
				Uuid\Uuid::fromString(self::CHANNEL),
				Uuid\Uuid::fromString(self::CHANNEL_PROPERTY),
			),
			Utilities\Value::NOT_SET,
		);

		self::assertSame([], $this->published);
	}

	/**
	 * Hands the subscriber a SET of the data source, with a publisher that records what it is given
	 *
	 * @throws Throwable
	 */
	private function dispatch(
		DevicesModuleUiModuleDocuments\Widgets\DataSources\Property $dataSource,
		bool|float|int|string|null $expectedValue,
	): void
	{
		$publisher = $this->createMock(Publisher\MessagePublisher::class);
		$publisher->method('publish')->willReturnCallback(
			function (
				Sources\Source $source,
				string $routingKey,
				CoreDocuments\Document|null $document,
			): bool {
				$this->published[] = [
					$source->value,
					$routingKey,
					$document !== null ? $document::class : null,
					$document?->toArray(),
				];

				return true;
			},
		);
		$this->getContainer()->getByType(Publisher\Container::class)->register($publisher);

		$this->getContainer()->getByType(Subscribers\ActionCommand::class)->actionReceived(
			new UiEvents\ActionCommandReceived(
				new UiDocuments\Widgets\DataSources\Actions\Action(
					UiTypes\DataSourceAction::SET,
					Uuid\Uuid::fromString(self::WIDGET),
					Uuid\Uuid::fromString(self::DATA_SOURCE),
					$expectedValue,
				),
				$dataSource,
			),
		);
	}

}
