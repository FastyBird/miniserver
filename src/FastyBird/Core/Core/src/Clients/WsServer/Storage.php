<?php declare(strict_types = 1);

namespace FastyBird\Core\Clients\WsServer;

use ArrayIterator;
use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Exceptions;
use Nette;
use Psr\Log;
use Throwable;
use function sprintf;

/**
 * Storage for manage all connections
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Storage
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Storage implements IStorage
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private Drivers\IDriver $driver;

	private int|null $ttl = null;

	private Log\LoggerInterface|Log\NullLogger|null $logger = null;

	/**
	 * @param int|null $ttl
	 */
	public function __construct(int $ttl = 0, Log\LoggerInterface|null $logger = null)
	{
		$this->ttl = $ttl;
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function setStorageDriver(Drivers\IDriver $driver): void
	{
		$this->driver = $driver;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\ClientNotFound
	 * @throws Exceptions\Storage
	 */
	public function getClient(int $identifier): Entities\IClient
	{
		try {
			$result = $this->driver->fetch($identifier);

		} catch (Throwable $ex) {
			throw new Exceptions\Storage(sprintf('Driver %s failed', self::class), $ex->getCode(), $ex);
		}

		$this->logger->debug('GET CLIENT ' . $identifier);

		if ($result === false) {
			throw new Exceptions\ClientNotFound(sprintf('Client %s not found', $identifier));
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Storage
	 */
	public function addClient(int $identifier, Entities\IClient $client): void
	{
		$context = [
			'user' => $client->getUser(),
		];

		if ($client->getUser() instanceof Nette\Security\User) {
			$context['userId'] = $client->getUser()->getId();
		}

		$this->logger->debug(sprintf('INSERT CLIENT ' . $identifier), $context);

		try {
			$result = $this->driver->save($identifier, $client, $this->ttl);

		} catch (Throwable $ex) {
			throw new Exceptions\Storage(sprintf('Driver %s failed', self::class), $ex->getCode(), $ex);
		}

		if ($result === false) {
			throw new Exceptions\Storage('Unable add client');
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Storage
	 */
	public function hasClient(int $identifier): bool
	{
		try {
			$result = $this->driver->contains($identifier);

		} catch (Throwable $ex) {
			throw new Exceptions\Storage(sprintf('Driver %s failed', self::class), $ex->getCode(), $ex);
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Storage
	 */
	public function removeClient(int $identifier): bool
	{
		$this->logger->debug('REMOVE CLIENT ' . $identifier);

		try {
			$result = $this->driver->delete($identifier);

		} catch (Throwable $ex) {
			throw new Exceptions\Storage(sprintf('Driver %s failed', self::class), $ex->getCode(), $ex);
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Storage
	 */
	public function refreshClient(Entities\IClient $client): void
	{
		if ($this->hasClient($client->getId())) {
			$this->driver->save($client->getId(), $client, $this->ttl);

			$this->logger->debug(sprintf('REFRESH CLIENT ' . $client->getId()));
		}
	}

	/**
	 * @return array<Entities\IClient>|ArrayIterator
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->driver->fetchAll());
	}

}
