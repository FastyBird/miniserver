<?php declare(strict_types = 1);

namespace FastyBird\Core\Subscribers\WsServer;

use Doctrine\DBAL;
use FastyBird\Core\Constants as WsServer;
use FastyBird\Core\Controllers\WebSockets\Responses;
use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Events;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as WebSocketsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Http;
use FastyBird\Core\Server\WsServer as Server;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Override;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use function explode;
use function in_array;

/**
 * WS client events subscriber
 */
final class Client implements EventDispatcher\EventSubscriberInterface
{

	/** @var array<string> */
	private array $wsKeys;

	/** @var array<string> */
	private array $allowedOrigins;

	public function __construct(
		private readonly ToolsHelpers\Database $database,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
		string|null $wsKeys = null,
		string|null $allowedOrigins = null,
	)
	{
		$this->wsKeys = $wsKeys !== null ? explode(',', $wsKeys) : [];
		$this->allowedOrigins = $allowedOrigins !== null ? explode(',', $allowedOrigins) : [];
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			Events\ClientConnected::class => 'clientConnected',
			Events\IncomingMessage::class => 'incomingMessage',
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function clientConnected(Events\ClientConnected $event): void
	{
		$this->checkSecurity($event->getClient(), $event->getHttpRequest(), $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws WebSocketsExceptions\Terminate
	 */
	public function incomingMessage(Events\IncomingMessage $event): void
	{
		// Check if ping to DB is possible...
		if (!$this->database->ping()) {
			// ...if not, try to reconnect
			$this->database->reconnect();

			// ...and ping again
			if (!$this->database->ping()) {
				throw new WebSocketsExceptions\Terminate(
					'Connection to database could not be re-established',
				);
			}
		}

		$this->checkSecurity($event->getClient(), $event->getHttpRequest(), $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @param array<string> $allowedWsKeys
	 * @param array<string> $allowedOrigins
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function checkSecurity(
		Entities\IClient $client,
		Http\IRequest $httpRequest,
		array $allowedWsKeys,
		array $allowedOrigins,
	): bool
	{
		$wsKey = $httpRequest->getHeader(WsServer\Constants::WS_HEADER_WS_KEY);

		if (
			($wsKey === null && $allowedWsKeys !== [])
			|| (!in_array($wsKey, $allowedWsKeys, true) && $allowedWsKeys !== [])
		) {
			$this->closeSession($client);

			$this->logger->warning('Client used invalid WS key', [
				'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
				'type' => 'subscriber',
				'ws_key' => $wsKey,
			]);

			return false;
		}

		$origin = $httpRequest->getHeader(WsServer\Constants::WS_HEADER_ORIGIN);

		if (
			($origin === null && $allowedOrigins !== [])
			|| (!in_array($origin, $allowedOrigins, true) && $allowedOrigins !== [])
		) {
			$this->closeSession($client);

			$this->logger->warning('Client is connecting from not allowed origin', [
				'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
				'type' => 'subscriber',
				'origin' => $origin,
			]);

			return false;
		}

		$authToken = $httpRequest->getHeader(WsServer\Constants::WS_HEADER_AUTHORIZATION);

		if ($authToken === null) {
			$cookieToken = $httpRequest->getCookie('token');

			if ($cookieToken === null) {
				$this->logger->warning('Client access token is missing', [
					'source' => MetadataTypes\Sources\Plugin::WS_SERVER->value,
					'type' => 'subscriber',
				]);

				$this->closeSession($client);

				return false;
			}
		}

		return true;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function closeSession(Entities\IClient $client): void
	{
		$headers = [
			'X-Powered-By' => Server\Server::VERSION,
		];

		$response = new Responses\ErrorResponse(401, $headers);

		$client->send($response);
		$client->close();
	}

}
