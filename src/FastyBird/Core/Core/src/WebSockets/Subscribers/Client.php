<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Subscribers;

use Doctrine\DBAL;
use FastyBird\Core\Constants as WsServer;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Security\SimpleAuth;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Controllers\Responses;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use Override;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use function explode;
use function in_array;
use function is_string;

/**
 * WS client events subscriber
 */
final class Client implements EventDispatcher\EventSubscriberInterface
{

	/** @var array<string> */
	private array $wsKeys;

	/** @var array<string> */
	private array $allowedOrigins;

	/**
	 * The token services are registered only when an application signature is configured,
	 * and the identity factory only by whatever provides identities (the accounts module).
	 * Without them no token can be validated, so every client is refused.
	 */
	public function __construct(
		private readonly Helpers\Database $database,
		private readonly SimpleAuth\TokenReader|null $tokenReader = null,
		private readonly SimpleAuth\TokenValidator|null $tokenValidator = null,
		private readonly SimpleAuth\IIdentityFactory|null $identityFactory = null,
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
	 * @throws CoreExceptions\InvalidArgument
	 */
	public function clientConnected(Events\ClientConnected $event): void
	{
		$this->checkSecurity($event->getClient(), $event->getHttpRequest(), $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
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
	 * @throws CoreExceptions\InvalidArgument
	 */
	public function checkSecurity(
		Entities\ConnectedClient $client,
		Handshake\IRequest $httpRequest,
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
				'source' => Sources\Plugin::WS_SERVER->value,
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
				'source' => Sources\Plugin::WS_SERVER->value,
				'type' => 'subscriber',
				'origin' => $origin,
			]);

			return false;
		}

		$headerToken = $httpRequest->getHeader(WsServer\Constants::WS_HEADER_AUTHORIZATION);
		$cookieToken = $httpRequest->getCookie(WsServer\Constants::ACCESS_TOKEN_COOKIE);

		if ($headerToken === null && $cookieToken === null) {
			$this->logger->warning('Client access token is missing', [
				'source' => Sources\Plugin::WS_SERVER->value,
				'type' => 'subscriber',
			]);

			$this->closeSession($client);

			return false;
		}

		if ($this->tokenReader === null || $this->tokenValidator === null || $this->identityFactory === null) {
			$this->logger->warning('Client access token can not be validated, authentication is not configured', [
				'source' => Sources\Plugin::WS_SERVER->value,
				'type' => 'subscriber',
			]);

			$this->closeSession($client);

			return false;
		}

		// The same criteria the HTTP side applies: the bearer header exactly as the JSON:API
		// user middleware reads it (TokenReader), the cookie exactly as the presenter
		// subscriber reads it (TokenValidator on the raw value), and in both cases the token
		// has to resolve to an identity -- which is where the accounts module checks that the
		// token is still persisted, i.e. was issued and has not been revoked since.
		try {
			$token = $headerToken !== null
				? $this->tokenReader->readHeader($headerToken)
				: (is_string($cookieToken) ? $this->tokenValidator->validate($cookieToken) : null);
		} catch (CoreExceptions\UnauthorizedAccess) {
			$token = null;
		}

		$identity = $token !== null ? $this->identityFactory->create($token) : null;

		if ($identity === null) {
			$this->logger->warning('Client access token is not valid', [
				'source' => Sources\Plugin::WS_SERVER->value,
				'type' => 'subscriber',
				'token_source' => $headerToken !== null ? 'header' : 'cookie',
			]);

			$this->closeSession($client);

			return false;
		}

		return true;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 */
	private function closeSession(Entities\ConnectedClient $client): void
	{
		$headers = [
			'X-Powered-By' => Server\ServerRuntime::VERSION,
		];

		$response = new Responses\ErrorResponse(401, $headers);

		$client->send($response);
		$client->close();
	}

}
