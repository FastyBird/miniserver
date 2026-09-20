<?php declare(strict_types = 1);

namespace FastyBird\Core\Server\WsServer;

use FastyBird\Core\Clients\WsServer as Clients;
use FastyBird\Core\Exceptions\WebSockets as Exceptions;
use Nette;
use Psr\Log;
use React;
use Throwable;

/**
 * WebSocket server
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Server
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Handlers
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private IWrapper $application;

	private Log\LoggerInterface|Log\NullLogger|null $logger = null;

	private bool $isRunning = false;

	public function __construct(
		Wrapper $application,
		private FlashWrapper $flashApplication,
		private Clients\Storage $clientStorage,
		private Clients\IClientFactory $clientFactory,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->application = $application;
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws Exceptions\Storage
	 */
	public function handleConnect(React\Socket\ConnectionInterface $connection): void
	{
		$client = $this->clientFactory->create((int) $connection->stream, $connection);

		$this->clientStorage->addClient($client->getId(), $client);

		try {
			$this->application->handleOpen($client);

			$connection->on('data', function (string $chunk) use ($connection): void {
				$this->handleData($chunk, $connection, $this->application);
			});

			$connection->on('end', function () use ($connection): void {
				$this->handleEnd($connection, $this->application);
			});

			$connection->on('error', function (Throwable $ex) use ($connection): void {
				$this->handleError($ex, $connection, $this->application);
			});

		} catch (Throwable $ex) {
			$context = [
				'code' => $ex->getCode(),
				'file' => $ex->getFile(),
				'client' => (int) $connection->stream,
			];

			$this->logger->error($ex->getMessage(), $context);

			$connection->end();
		}
	}

	/**
	 * @throws Exceptions\Storage
	 */
	public function handleFlashConnect(React\Socket\ConnectionInterface $connection): void
	{
		$client = $this->clientFactory->create((int) $connection->stream, $connection);

		$this->clientStorage->addClient($client->getId(), $client);

		try {
			$this->flashApplication->handleOpen($client);

			$connection->on('data', function (string $chunk) use ($connection): void {
				$this->handleData($chunk, $connection, $this->flashApplication);
			});

			$connection->on('end', function () use ($connection): void {
				$this->handleEnd($connection, $this->flashApplication);
			});

			$connection->on('error', function (Throwable $ex) use ($connection): void {
				$this->handleError($ex, $connection, $this->flashApplication);
			});

		} catch (Throwable $ex) {
			$context = [
				'code' => $ex->getCode(),
				'file' => $ex->getFile(),
				'client' => (int) $connection->stream,
			];

			$this->logger->error($ex->getMessage(), $context);

			$connection->end();
		}
	}

	private function handleData(string $data, React\Socket\ConnectionInterface $connection, IWrapper $application): void
	{
		try {
			$client = $this->clientStorage->getClient((int) $connection->stream);

			$application->handleMessage($client, $data);

		} catch (Throwable $ex) {
			$this->handleError($ex, $connection, $application);
		}
	}

	private function handleEnd(React\Socket\ConnectionInterface $connection, IWrapper $application): void
	{
		try {
			$client = $this->clientStorage->getClient((int) $connection->stream);

			$application->handleClose($client);

		} catch (Throwable $ex) {
			$this->handleError($ex, $connection, $application);
		}
	}

	private function handleError(
		Throwable $ex,
		React\Socket\ConnectionInterface $connection,
		IWrapper $application,
	): void
	{
		try {
			$client = $this->clientStorage->getClient((int) $connection->stream);

			$context = [
				'code' => $ex->getCode(),
				'file' => $ex->getFile(),
				'client' => (int) $connection->stream,
				'request' => $client->getRequest(),
			];

			$this->logger->error($ex->getMessage(), $context);

			$application->handleError($client, $ex);

		} catch (Throwable $ex) {
			$context = [
				'code' => $ex->getCode(),
				'file' => $ex->getFile(),
				'client' => (int) $connection->stream,
			];

			$this->logger->error($ex->getMessage(), $context);

			$connection->end();
		}
	}

}
