<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Controllers;

use FastyBird\Core\Security\Identity;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers as WebSocketsControllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Controllers as UiControllers;
use FastyBird\Module\Ui\Events;
use FastyBird\Module\Ui\Router;
use FastyBird\Module\Ui\Tests;
use FastyBird\Module\Ui\Types;
use Nette\Http;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher;
use Throwable;
use function end;
use function is_array;
use function is_string;

/**
 * A WAMP RPC call to the UI exchange applies the role rule the HTTP API applies to the same
 * kind of request. Reading a widget data source is what DataSourcesV1 serves to any signed-in
 * user; setting one changes state, which over HTTP takes the manager or administrator role.
 * A refused call is answered with a WAMP call error, and the action is not carried on: no
 * ActionCommandReceived event is dispatched.
 *
 * Every call goes through the real WAMP application, the module's real socket router and the
 * module's real exchange controller. What is asserted is the frames the client is sent and
 * the events that carry the action on.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ExchangeV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const string TOPIC = '/ui-module/v1/exchange';

	private const string RPC_ID = 'call-1';

	private const string USER = '9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a';

	private const string WIDGET = '1d600901-54e7-43ee-8f5d-a9e22663ddd7';

	private const string DATA_SOURCE = '32dd50e4-4b66-4dea-9bc5-e835f8543dc4';

	/** @var list<mixed> */
	private array $sent = [];

	/** @var list<Types\DataSourceAction> */
	private array $carriedOn = [];

	/**
	 * @return array<string, array{Types\DataSourceAction, list<string>|null, bool}>
	 */
	public static function dataSourceActions(): array
	{
		$principals = [
			'no identity' => null,
			'user' => ['user'],
			'manager' => ['manager'],
			'administrator' => ['administrator'],
		];

		$cases = [];

		foreach ($principals as $principal => $roles) {
			$cases['data source get by ' . $principal] = [
				Types\DataSourceAction::GET,
				$roles,
				$roles !== null,
			];

			$cases['data source set by ' . $principal] = [
				Types\DataSourceAction::SET,
				$roles,
				$principal === 'manager' || $principal === 'administrator',
			];
		}

		return $cases;
	}

	/**
	 * @param list<string>|null $roles
	 *
	 * @throws Throwable
	 */
	#[DataProvider('dataSourceActions')]
	public function testDataSourceAction(Types\DataSourceAction $action, array|null $roles, bool $allowed): void
	{
		$data = [
			'action' => $action->value,
			'widget' => self::WIDGET,
			'data_source' => self::DATA_SOURCE,
		];

		if ($action === Types\DataSourceAction::SET) {
			$data['expected_value'] = 21.5;
		}

		$this->call($this->client($roles), [
			'routing_key' => Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_ACTION_ROUTING_KEY,
			'source' => Sources\Module::UI->value,
			'data' => $data,
		]);

		// An allowed GET is also answered with the data source itself, ahead of the result
		self::assertCount($allowed && $action === Types\DataSourceAction::GET ? 2 : 1, $this->sent);
		self::assertSame($allowed ? [$action] : [], $this->carriedOn);

		$frame = end($this->sent);
		self::assertIsArray($frame);

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

	/**
	 * Only a data source action that reads as a GET is a read. A call whose action cannot be
	 * read takes the rule for a change.
	 *
	 * @throws Throwable
	 */
	public function testADataSourceCallWithoutAnActionIsRefusedToAUser(): void
	{
		$this->call($this->client(['user']), [
			'routing_key' => Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_ACTION_ROUTING_KEY,
			'source' => Sources\Module::UI->value,
		]);

		self::assertCount(1, $this->sent);
		self::assertSame([], $this->carriedOn);

		$frame = $this->sent[0];
		self::assertIsArray($frame);
		self::assertSame(WebSocketsControllers\WampApplication::MSG_CALL_ERROR, $frame[0] ?? null);
		self::assertTrue(is_array($frame[4] ?? null));
		self::assertSame(403, $frame[4]['code'] ?? null);
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
	 * recording every action it carries on
	 *
	 * @param array<string, mixed> $args
	 *
	 * @throws Throwable
	 */
	private function call(Entities\ConnectedClient $client, array $args): void
	{
		$this->getContainer()->getByType(EventDispatcher\EventDispatcherInterface::class)->addListener(
			Events\ActionCommandReceived::class,
			function (Events\ActionCommandReceived $event): void {
				$this->carriedOn[] = $event->getAction()->getAction();
			},
		);

		$controller = $this->getContainer()->getByType(UiControllers\ExchangeV1::class);

		$controllerFactory = $this->createMock(WebSocketsControllers\IControllerFactory::class);
		$controllerFactory->method('getControllerClass')->willReturn(UiControllers\ExchangeV1::class);
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
	}

}
