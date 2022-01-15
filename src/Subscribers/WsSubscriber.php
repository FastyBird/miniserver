<?php declare(strict_types = 1);

/**
 * WsSubscriber.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           15.04.20
 */

namespace FastyBird\MiniServer\Subscribers;

use FastyBird\MiniServer;
use FastyBird\MiniServer\Events;
use IPub\WebSockets;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * WS events subscriber
 *
 * @package         FastyBird:MiniServer!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var Log\LoggerInterface */
	protected Log\LoggerInterface $logger;

	/** @var string[] */
	private array $wsKeys;

	/** @var string[] */
	private array $allowedOrigins;

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			Events\WsClientConnectedEvent::class  => 'clientConnected',
			Events\WsIncomingMessage::class  => 'incomingMessage',
		];
	}

	public function __construct(
		?Log\LoggerInterface $logger,
		?string $wsKeys = null,
		?string $allowedOrigins = null
	) {
		$this->wsKeys = $wsKeys !== null ? explode(',', $wsKeys) : [];
		$this->allowedOrigins = $allowedOrigins !== null ? explode(',', $allowedOrigins) : [];

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @return void
	 */
	public function clientConnected(
		WebSockets\Entities\Clients\IClient $client,
		WebSockets\Http\IRequest $httpRequest
	): void {
		$this->checkSecurity($client, $httpRequest, $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @return void
	 */
	public function incomingMessage(
		WebSockets\Entities\Clients\IClient $client,
		WebSockets\Http\IRequest $httpRequest
	): void {
		$this->checkSecurity($client, $httpRequest, $this->wsKeys, $this->allowedOrigins);
	}

	/**
	 * @param WebSockets\Entities\Clients\IClient $client
	 * @param WebSockets\Http\IRequest $httpRequest
	 * @param string[] $allowedWsKeys
	 * @param string[] $allowedOrigins
	 *
	 * @return bool
	 *
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	public function checkSecurity(
		WebSockets\Entities\Clients\IClient $client,
		WebSockets\Http\IRequest $httpRequest,
		array $allowedWsKeys,
		array $allowedOrigins
	): bool {
		$wsKey = $httpRequest->getHeader(MiniServer\Constants::WS_HEADER_WS_KEY);

		if (
			($wsKey === null && $allowedWsKeys !== [])
			|| (!in_array($wsKey, $allowedWsKeys, true) && $allowedWsKeys !== [])
		) {
			$this->closeSession($client);

			$this->logger->warning('Client used invalid WS key', [
				'source' => 'ws-server-plugin-security',
				'type'   => 'validate',
				'ws_key' => $wsKey,
			]);

			return false;
		}

		$origin = $httpRequest->getHeader(MiniServer\Constants::WS_HEADER_ORIGIN);

		if (
			($origin === null && $allowedOrigins !== [])
			|| (!in_array($origin, $allowedOrigins, true) && $allowedOrigins !== [])
		) {
			$this->closeSession($client);

			$this->logger->warning('Client is connecting from not allowed origin', [
				'source' => 'ws-server-plugin-security',
				'type'   => 'validate',
				'origin' => $origin,
			]);

			return false;
		}

		$authToken = $httpRequest->getHeader(MiniServer\Constants::WS_HEADER_AUTHORIZATION);

		if ($authToken === null) {
			$this->logger->warning('Client access token is missing', [
				'source' => 'ws-server-plugin-security',
				'type'   => 'validate',
			]);

			$this->closeSession($client);

			return false;
		}

		return true;
	}

	/**
	 * @param WebSockets\Entities\Clients\IClient $client
	 *
	 * @throws WebSockets\Exceptions\InvalidArgumentException
	 */
	private function closeSession(WebSockets\Entities\Clients\IClient $client): void
	{
		$headers = [
			'X-Powered-By' => WebSockets\Server\Server::VERSION,
		];

		$response = new WebSockets\Application\Responses\ErrorResponse(401, $headers);

		$client->send($response);
		$client->close();
	}

}
