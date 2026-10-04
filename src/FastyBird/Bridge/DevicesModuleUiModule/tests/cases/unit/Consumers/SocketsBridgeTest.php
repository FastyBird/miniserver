<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\Consumers;

use ArrayObject;
use Error;
use FastyBird\Bridge\DevicesModuleUiModule;
use FastyBird\Bridge\DevicesModuleUiModule\Consumers as DevicesModuleUiModuleConsumers;
use FastyBird\Bridge\DevicesModuleUiModule\Exceptions as DevicesModuleUiModuleExceptions;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Entities\Topics as EntitiesTopics;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Topics as WebSocketsTopics;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Consumers as UiConsumers;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models as UiModels;
use Nette\DI;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use function array_map;
use function is_array;
use function is_string;

/**
 * Each SocketsBridge, consuming an exchange document, broadcasts it to the WAMP subscribers of
 * its own module's exchange topic, and of no other (#625).
 *
 * This package's test container compiles the Devices and Ui modules with the WAMP server, so it
 * has both module routes, the link generator, the topics storage and the Devices and Ui bridges.
 * Its own bridge is not registered here (census X4: this extension loads before fbCore, so the
 * services it checks for do not exist yet); the test builds it from the container's services,
 * which are the ones production injects.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class SocketsBridgeTest extends Tests\Cases\Unit\DbTestCase
{

	private const string DEVICES_TOPIC = '/devices-module/v1/exchange';

	private const string UI_TOPIC = '/ui-module/v1/exchange';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesModuleUiModuleExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Utils\JsonException
	 * @throws WebSocketsExceptions\Storage
	 */
	public function testTheDevicesBridgeBroadcastsToTheDevicesExchangeTopic(): void
	{
		$devices = $this->subscribe(self::DEVICES_TOPIC);
		$ui = $this->subscribe(self::UI_TOPIC);

		$document = $this->channelPropertyState(
			'28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
			'6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a',
			21.5,
		);

		$this->service(
			'fbDevicesModule.exchange.consumer.socketsBridge',
			DevicesConsumers\SocketsBridge::class,
		)->consume(
			Sources\Connector::VIRTUAL,
			Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
			$document,
		);

		self::assertSame(
			[
				[
					'topic' => self::DEVICES_TOPIC,
					'message' => [
						'routing_key' => Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
						'source' => Sources\Connector::VIRTUAL->value,
						'data' => $document->toArray(),
					],
				],
			],
			$this->events($devices),
		);
		self::assertSame([], $this->events($ui));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesModuleUiModuleExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Utils\JsonException
	 * @throws WebSocketsExceptions\Storage
	 */
	public function testTheUiBridgeBroadcastsToTheUiExchangeTopic(): void
	{
		$devices = $this->subscribe(self::DEVICES_TOPIC);
		$ui = $this->subscribe(self::UI_TOPIC);

		$document = new UiDocuments\Groups\Group(
			Uuid\Uuid::fromString('89f4a14f-7f78-4216-99b8-584ab9229f1c'),
			'living-room',
			'Living room',
		);

		$this->service('fbUiModule.exchange.consumer.socketsBridge', UiConsumers\SocketsBridge::class)->consume(
			Sources\Module::UI,
			Ui\Constants::MESSAGE_BUS_GROUP_DOCUMENT_REPORTED_ROUTING_KEY,
			$document,
		);

		self::assertSame(
			[
				[
					'topic' => self::UI_TOPIC,
					'message' => [
						'routing_key' => Ui\Constants::MESSAGE_BUS_GROUP_DOCUMENT_REPORTED_ROUTING_KEY,
						'source' => Sources\Module::UI->value,
						'data' => $document->toArray(),
					],
				],
			],
			$this->events($ui),
		);
		self::assertSame([], $this->events($devices));
	}

	/**
	 * A channel property's state reaches the Ui subscribers as the state of the widget data source
	 * reading it (fixture data source 32dd50e4-..., reading channel property bbcccf8c-...).
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesModuleUiModuleExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws UiExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws WebSocketsExceptions\Storage
	 */
	public function testTheDevicesModuleUiModuleBridgeBroadcastsToTheUiExchangeTopic(): void
	{
		$devices = $this->subscribe(self::DEVICES_TOPIC);
		$ui = $this->subscribe(self::UI_TOPIC);

		$container = $this->getContainer();

		self::assertSame([], $container->findByType(DevicesModuleUiModuleConsumers\SocketsBridge::class));

		$bridge = new DevicesModuleUiModuleConsumers\SocketsBridge(
			$container->getByType(UiModels\Configuration\Widgets\DataSources\Repository::class),
			$this->service('fbDevicesModuleUiModuleBridge.logger', DevicesModuleUiModule\Logger::class),
			$container->getByType(Routing\LinkGenerator::class),
			$container->getByType(WebSocketsTopics\Storage::class),
		);

		$bridge->consume(
			Sources\Module::DEVICES,
			Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
			$this->channelPropertyState(
				'bbcccf8c-33ab-431b-a795-d7bb38b6b6db',
				'17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'on',
			),
		);

		$events = $this->events($ui);

		self::assertCount(1, $events);
		self::assertSame(self::UI_TOPIC, $events[0]['topic']);

		$message = $events[0]['message'];

		self::assertIsArray($message);
		self::assertSame(
			Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
			$message['routing_key'] ?? null,
		);
		self::assertSame(Sources\Bridge::DEVICES_MODULE_UI_MODULE->value, $message['source'] ?? null);

		$data = $message['data'] ?? null;

		self::assertIsArray($data);
		self::assertSame('32dd50e4-4b66-4dea-9bc5-e835f8543dc4', $data['id'] ?? null);
		self::assertSame('1d600901-54e7-43ee-8f5d-a9e22663ddd7', $data['widget'] ?? null);
		self::assertSame('on', $data['value'] ?? null);

		self::assertSame([], $this->events($devices));
	}

	/**
	 * A service by name: the consumers and loggers are not autowired
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $type
	 *
	 * @return T
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesModuleUiModuleExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws RuntimeException
	 */
	private function service(string $name, string $type): object
	{
		$service = $this->getContainer()->getService($name);

		self::assertInstanceOf($type, $service);

		return $service;
	}

	/**
	 * Registers a topic with one subscribed client, and returns what that client is sent
	 *
	 * @return ArrayObject<int, string>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesModuleUiModuleExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws RuntimeException
	 * @throws WebSocketsExceptions\Storage
	 */
	private function subscribe(string $topicId): ArrayObject
	{
		/** @var ArrayObject<int, string> $sent */
		$sent = new ArrayObject();

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client
			->method('send')
			->willReturnCallback(static function (mixed $response) use ($sent): void {
				$sent->append(is_string($response) ? $response : 'not a string');
			});

		$topic = new EntitiesTopics\Topic($topicId);
		$topic->add($client);

		$this->getContainer()->getByType(WebSocketsTopics\Storage::class)->addTopic($topicId, $topic);

		return $sent;
	}

	/**
	 * Decodes the WAMP EVENT frames a subscribed client was sent
	 *
	 * @param ArrayObject<int, string> $sent
	 *
	 * @return array<int, array{topic: mixed, message: mixed}>
	 *
	 * @throws Utils\JsonException
	 */
	private function events(ArrayObject $sent): array
	{
		return array_map(
			static function (string $frame): array {
				$event = Utils\Json::decode($frame, forceArrays: true);

				self::assertTrue(
					is_array($event) && ($event[0] ?? null) === Controllers\WampApplication::MSG_EVENT,
					'Not a WAMP EVENT frame: ' . $frame,
				);

				return [
					'topic' => $event[1] ?? null,
					'message' => is_string($event[2] ?? null)
						? Utils\Json::decode($event[2], forceArrays: true)
						: null,
				];
			},
			$sent->getArrayCopy(),
		);
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function channelPropertyState(
		string $id,
		string $channel,
		bool|float|int|string|null $actualValue,
	): DevicesDocuments\States\Channels\Properties\Property
	{
		return new DevicesDocuments\States\Channels\Properties\Property(
			Uuid\Uuid::fromString($id),
			Uuid\Uuid::fromString($channel),
			new DevicesDocuments\States\StateValues($actualValue, null),
			new DevicesDocuments\States\StateValues(null, null),
			false,
			true,
		);
	}

}
