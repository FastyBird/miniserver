<?php declare(strict_types = 1);

/**
 * RedisPublisher.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Publisher
 * @since          0.1.0
 *
 * @date           23.02.21
 */

namespace FastyBird\MiniServer\Exchange;

use FastyBird\ApplicationExchange;
use FastyBird\MiniServer;
use Nette;
use Nette\Utils;
use Predis;
use Psr\Log;

/**
 * Redis exchange publisher
 *
 * @package         FastyBird:MiniServer!
 * @subpackage      Publisher
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisPublisher implements ApplicationExchange\Publisher\IPublisher
{

	use Nette\SmartObject;

	/** @var Predis\Client<mixed> */
	private Predis\Client $redis;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		Log\LoggerInterface $logger,
		string $host,
		int $port,
		?string $username = null,
		?string $password = null
	) {
		$this->logger = $logger;

		$options = [
			'scheme' => 'tcp',
			'host'   => $host,
			'port'   => $port,
		];

		if ($username !== null) {
			$options['username'] = $username;
		}

		if ($password !== null) {
			$options['password'] = $password;
		}

		$this->redis = new Predis\Client($options);
	}

	/**
	 * {@inheritDoc}
	 */
	public function publish(string $origin, string $routingKey, array $data): void
	{
		try {
			// Compose message
			$message = Utils\Json::encode([
				'routing_key' => $routingKey,
				'origin'      => $origin,
				'data'        => $data,
			]);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('[FB:MINISERVER:EXCHANGE] Data could not be converted to message', [
				'message'   => [
					'routingKey' => $routingKey,
					'origin'     => $origin,
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}

		$result = $this->redis->executeRaw(['PUBLISH', MiniServer\Constants::PUB_SUB_EXCHANGE_CHANNEL, $message]);

		if (is_numeric($result)) {
			$this->logger->info('[FB:MINISERVER:EXCHANGE] Received message was pushed into data exchange', [
				'message' => [
					'routingKey' => $routingKey,
					'origin'     => $origin,
					'content'    => $data,
				],
			]);

		} else {
			$this->logger->error('[FB:MINISERVER:EXCHANGE] Received message could not be pushed into data exchange', [
				'message' => [
					'routingKey' => $routingKey,
					'origin'     => $origin,
					'content'    => $data,
				],
			]);
		}
	}

}
