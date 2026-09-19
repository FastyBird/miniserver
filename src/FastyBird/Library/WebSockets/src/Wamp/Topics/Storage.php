<?php declare(strict_types = 1);

/**
 * Storage.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Topics
 * @since          1.0.0
 *
 * @date           14.02.17
 */

namespace FastyBird\Library\WebSockets\Wamp\Topics;

use ArrayIterator;
use FastyBird\Library\WebSockets\Wamp\Entities;
use FastyBird\Library\WebSockets\Wamp\Exceptions;
use FastyBird\Library\WebSockets\Wamp\Topics;
use Nette;
use Psr\Log;
use Throwable;
use function sprintf;

/**
 * Storage for manage all topics
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Topics
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Storage implements IStorage
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private Topics\Drivers\IDriver $driver;

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

	public function setStorageDriver(Topics\Drivers\IDriver $driver): void
	{
		$this->driver = $driver;
	}

	public static function getStorageId(Entities\Topics\ITopic $topic): string
	{
		return $topic->getId();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Storage
	 * @throws Exceptions\TopicNotFound
	 */
	public function getTopic(string $identifier): Entities\Topics\ITopic
	{
		try {
			$result = $this->driver->fetch($identifier);

		} catch (Throwable $ex) {
			throw new Exceptions\Storage(sprintf('Driver %s failed', self::class), $ex->getCode(), $ex);
		}

		$this->logger->debug('GET TOPIC ' . $identifier);

		if ($result === false) {
			throw new Exceptions\TopicNotFound(sprintf('Topic %s not found', $identifier));
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Storage
	 */
	public function addTopic(string $identifier, Entities\Topics\ITopic $topic): void
	{
		$context = [
			'topic' => $identifier,
		];

		$this->logger->debug(sprintf('INSERT TOPIC ' . $identifier), $context);

		try {
			$result = $this->driver->save($identifier, $topic, $this->ttl);

		} catch (Throwable $ex) {
			throw new Exceptions\Storage(sprintf('Driver %s failed', self::class), $ex->getCode(), $ex);
		}

		if ($result === false) {
			throw new Exceptions\Storage('Unable add topic');
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Storage
	 */
	public function hasTopic(string $identifier): bool
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
	public function removeTopic(string $identifier): bool
	{
		$this->logger->debug('REMOVE TOPIC ' . $identifier);

		try {
			$result = $this->driver->delete($identifier);

		} catch (Throwable $ex) {
			throw new Exceptions\Storage(sprintf('Driver %s failed', self::class), $ex->getCode(), $ex);
		}

		return $result;
	}

	/**
	 * @return array<Entities\Topics\ITopic>|ArrayIterator
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->driver->fetchAll());
	}

	public function setLogger(Log\LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

}
