<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Topics;

use ArrayIterator;
use FastyBird\Core\WebSockets\Entities\Topics;
use FastyBird\Core\WebSockets\Exceptions;
use IteratorAggregate;
use Override;
use Psr\Log;
use Throwable;
use function sprintf;

/**
 * Storage for manage all topics
 *
 * @implements IteratorAggregate<int, Topics\Topic>
 */
final class Storage implements IteratorAggregate
{

	private Drivers\Driver $driver;

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

	public function setStorageDriver(Drivers\Driver $driver): void
	{
		$this->driver = $driver;
	}

	public static function getStorageId(Topics\Topic $topic): string
	{
		return $topic->getId();
	}

	/**
	 * @throws Exceptions\Storage
	 * @throws Exceptions\TopicNotFound
	 */
	public function getTopic(string $identifier): Topics\Topic
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
	 * @throws Exceptions\Storage
	 */
	public function addTopic(string $identifier, Topics\Topic $topic): void
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
	 * @return array<Topics\Topic>|ArrayIterator
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->driver->fetchAll());
	}

	public function setLogger(Log\LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

}
