<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use FastyBird\Core\Documents;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers as WebSocketsControllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Controllers as DevicesControllers;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use Nette\Http;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Throwable;
use function is_array;
use function is_string;

/**
 * A WAMP RPC call to the devices exchange applies the role rule the HTTP API applies to the
 * same kind of request. Reading a property state is what the property state controllers
 * serve to any signed-in user; setting one, or running a connector, device or channel
 * control, changes state, which over HTTP takes the manager or administrator role. A refused
 * call is answered with a WAMP call error, and nothing is published to the exchange.
 *
 * Every call goes through the real WAMP application, the module's real socket router and the
 * module's real exchange controller, with the exchange on as the module ships. What is
 * asserted is the frame the client is sent and what reaches the exchange publisher.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ExchangeV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const string TOPIC = '/devices-module/v1/exchange';

	private const string RPC_ID = 'call-1';

	private const string USER = '9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a';

	private const string CONNECTOR = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	// Inserted by connectorProperty(): the fixtures hold no dynamic connector property
	private const string CONNECTOR_PROPERTY = 'f5c2d8b1-3a4e-4b6f-9c7d-2e1a0b9c8d7f';

	private const string DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const string DEVICE_PROPERTY = 'bbcccf8c-33ab-431b-a795-d7bb38b6b6db';

	private const string CHANNEL = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	private const string CHANNEL_PROPERTY = 'bbcccf8c-33ab-431b-a795-d7bb38b6b6db';

	/** @var list<mixed> */
	private array $sent = [];

	/** @var list<string> */
	private array $published = [];

	public function setUp(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/exchange.neon');

		parent::setUp();
	}

	/**
	 * @return array<string, array{string, Types\PropertyAction, list<string>|null, bool}>
	 */
	public static function propertyActions(): array
	{
		$cases = [];

		foreach (['connector', 'device', 'channel'] as $owner) {
			foreach (self::principals() as $principal => $roles) {
				$cases[$owner . ' property get by ' . $principal] = [
					$owner,
					Types\PropertyAction::GET,
					$roles,
					$roles !== null,
				];

				$cases[$owner . ' property set by ' . $principal] = [
					$owner,
					Types\PropertyAction::SET,
					$roles,
					$principal === 'manager' || $principal === 'administrator',
				];
			}
		}

		return $cases;
	}

	/**
	 * @return array<string, array{string, list<string>|null, bool}>
	 */
	public static function controlActions(): array
	{
		$routingKeys = [
			'connector' => Devices\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY,
			'device' => Devices\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY,
			'channel' => Devices\Constants::MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY,
		];

		$cases = [];

		foreach ($routingKeys as $owner => $routingKey) {
			foreach (self::principals() as $principal => $roles) {
				$cases[$owner . ' control by ' . $principal] = [
					$routingKey,
					$roles,
					$principal === 'manager' || $principal === 'administrator',
				];
			}
		}

		return $cases;
	}

	/**
	 * @param list<string>|null $roles
	 *
	 * @throws Throwable
	 */
	#[DataProvider('propertyActions')]
	public function testPropertyAction(
		string $owner,
		Types\PropertyAction $action,
		array|null $roles,
		bool $allowed,
	): void
	{
		$calls = [
			'connector' => [
				Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY,
				['connector' => self::CONNECTOR, 'property' => self::CONNECTOR_PROPERTY],
				true,
			],
			'device' => [
				Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY,
				['device' => self::DEVICE, 'property' => self::DEVICE_PROPERTY],
				10,
			],
			'channel' => [
				Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY,
				['channel' => self::CHANNEL, 'property' => self::CHANNEL_PROPERTY],
				'on',
			],
		];

		[$routingKey, $ids, $value] = $calls[$owner];

		$data = ['action' => $action->value] + $ids;

		if ($action === Types\PropertyAction::SET) {
			$data['set'] = ['expected_value' => $value];
		}

		$this->connectorProperty();

		$frame = $this->call($this->client($roles), [
			'routing_key' => $routingKey,
			'source' => Sources\Module::DEVICES->value,
			'data' => $data,
		]);

		$this->assertAnswer($frame, $roles, $allowed);

		// Only an allowed SET is carried on, as the property action it was
		self::assertSame(
			$allowed && $action === Types\PropertyAction::SET ? [$routingKey] : [],
			$this->published,
		);
	}

	/**
	 * No HTTP endpoint runs a control; it changes state, so it takes the rule HTTP applies to
	 * every change. Nothing is carried on for a control at all, allowed or not.
	 *
	 * @param list<string>|null $roles
	 *
	 * @throws Throwable
	 */
	#[DataProvider('controlActions')]
	public function testControlAction(string $routingKey, array|null $roles, bool $allowed): void
	{
		$frame = $this->call($this->client($roles), [
			'routing_key' => $routingKey,
			'source' => Sources\Module::DEVICES->value,
			'data' => [
				'control' => 'reset',
				'device' => self::DEVICE,
			],
		]);

		$this->assertAnswer($frame, $roles, $allowed);
		self::assertSame([], $this->published);
	}

	/**
	 * Only a property action that reads as a GET is a read. A property call whose action
	 * cannot be read takes the rule for a change.
	 *
	 * @throws Throwable
	 */
	public function testAPropertyCallWithoutAnActionIsRefusedToAUser(): void
	{
		$frame = $this->call($this->client(['user']), [
			'routing_key' => Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY,
			'source' => Sources\Module::DEVICES->value,
		]);

		$this->assertAnswer($frame, ['user'], false);
		self::assertSame([], $this->published);
	}

	/**
	 * @return array<string, list<string>|null>
	 */
	private static function principals(): array
	{
		return [
			'no identity' => null,
			'user' => ['user'],
			'manager' => ['manager'],
			'administrator' => ['administrator'],
		];
	}

	/**
	 * @throws Throwable
	 */
	private function connectorProperty(): void
	{
		$this->getContainer();

		$this->getDb()->executeStatement(
			'INSERT INTO `fb_devices_module_connectors_properties` (`property_id`, `connector_id`, `property_type`,'
			. ' `property_identifier`, `property_name`, `property_settable`, `property_queryable`,'
			. ' `property_data_type`, `created_at`, `updated_at`)'
			. ' VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, 1, 1, ?, ?, ?)',
			[
				Utils\Strings::replace(self::CONNECTOR_PROPERTY, '/-/', ''),
				Utils\Strings::replace(self::CONNECTOR, '/-/', ''),
				'dynamic',
				'state',
				'state',
				'bool',
				'2026-09-26 12:00:00',
				'2026-09-26 12:00:00',
			],
		);
	}

	/**
	 * A connected client holding what the ws-server's client subscriber stores on it. The
	 * identity itself claims no roles: the controller has to read the roles the client holds.
	 *
	 * @param list<string>|null $roles null for a client that holds no identity
	 *
	 * @throws Throwable
	 */
	private function client(array|null $roles): Entities\ConnectedClient
	{
		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('getId')->willReturn(1);
		$client->method('getIdentity')->willReturn(
			$roles !== null ? new Identity\PlainIdentity(self::USER) : null,
		);
		$client->method('getRoles')->willReturn($roles ?? []);
		$client->expects(self::never())->method('close');
		$client->method('send')->willReturnCallback(function (mixed $message): void {
			self::assertIsString($message);

			$this->sent[] = Utils\Json::decode($message, forceArrays: true);
		});

		return $client;
	}

	/**
	 * Sends one WAMP CALL frame through the application to the module's exchange controller,
	 * recording what it publishes to the exchange, and returns the answer frame
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<mixed>
	 *
	 * @throws Throwable
	 */
	private function call(Entities\ConnectedClient $client, array $args): array
	{
		$recorder = $this->createMock(Publisher\MessagePublisher::class);
		$recorder->method('publish')->willReturnCallback(
			function (Sources\Source $source, string $routingKey, Documents\Document|null $entity): bool {
				$this->published[] = $routingKey;

				return true;
			},
		);

		$this->getContainer()->getByType(Publisher\Container::class)->register($recorder);

		$controller = $this->getContainer()->getByType(DevicesControllers\ExchangeV1::class);

		$controllerFactory = $this->createMock(WebSocketsControllers\IControllerFactory::class);
		$controllerFactory->method('getControllerClass')->willReturn(DevicesControllers\ExchangeV1::class);
		$controllerFactory->method('createController')->willReturn($controller);

		$topicsStorage = $this->createMock(Topics\IStorage::class);
		$topicsStorage->method('hasTopic')->willReturn(true);
		$topicsStorage->method('getTopic')->willReturn(new Entities\Topics\Topic(self::TOPIC));

		$application = new WebSocketsControllers\WampApplication(
			$topicsStorage,
			Router\SocketRoutes::createRouter(),
			$controllerFactory,
			$this->createMock(Clients\IStorage::class),
		);

		$application->handleMessage(
			$client,
			new Handshake\Request(new Http\UrlScript('ws://localhost:8888/')),
			Utils\Json::encode([WebSocketsControllers\WampApplication::MSG_CALL, self::RPC_ID, self::TOPIC, $args]),
		);

		self::assertCount(1, $this->sent);
		self::assertIsArray($this->sent[0]);

		return $this->sent[0];
	}

	/**
	 * @param array<mixed> $frame
	 * @param list<string>|null $roles
	 */
	private function assertAnswer(array $frame, array|null $roles, bool $allowed): void
	{
		if ($allowed) {
			self::assertSame(
				[WebSocketsControllers\WampApplication::MSG_CALL_RESULT, self::RPC_ID, ['response' => 'accepted']],
				$frame,
			);

			return;
		}

		self::assertSame(WebSocketsControllers\WampApplication::MSG_CALL_ERROR, $frame[0] ?? null);
		self::assertSame(self::RPC_ID, $frame[1] ?? null);
		self::assertSame(self::TOPIC, $frame[2] ?? null);
		self::assertTrue(is_string($frame[3] ?? null));
		self::assertTrue(is_array($frame[4] ?? null));
		self::assertSame($roles === null ? 401 : 403, $frame[4]['code'] ?? null);
	}

}
