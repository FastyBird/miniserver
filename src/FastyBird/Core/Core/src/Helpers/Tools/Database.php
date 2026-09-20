<?php declare(strict_types = 1);

/**
 * Database.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           15.04.20
 */

namespace FastyBird\Core\Helpers\Tools;

use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Events\Tools as Events;
use FastyBird\Core\Exceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use Throwable;
use function gc_collect_cycles;
use function is_int;

/**
 * Database connection helpers
 *
 * @package        FastyBird:Tools!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Database
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Persistence\ManagerRegistry|null $managerRegistry = null,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @template T
	 *
	 * @param callable(): T $callback
	 *
	 * @return T
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function query(callable $callback)
	{
		try {
			$this->pingAndReconnect();

			return $callback();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState(
				'An error occurred: ' . $ex->getMessage(),
				is_int($ex->getCode()) ? $ex->getCode() : 0,
				$ex,
			);
		}
	}

	/**
	 * @param callable(): T $callback
	 *
	 * @return T
	 *
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 *
	 * @template T
	 */
	public function transaction(callable $callback)
	{
		$connection = $this->getConnection();

		if ($connection === null) {
			throw new Exceptions\Runtime('Entity manager could not be loaded');
		}

		try {
			$this->pingAndReconnect();

			// Start transaction connection to the database
			$connection->beginTransaction();

			$this->dispatcher?->dispatch(new Events\DbTransactionStarted());

			$result = $callback();

			if ($connection->isRollbackOnly()) {
				$connection->rollBack();

				throw new Exceptions\InvalidState('Transaction was roll backed');
			} else {
				// Commit all changes into database
				$connection->commit();
			}

			$this->dispatcher?->dispatch(new Events\DbTransactionFinished());

			return $result;
		} catch (Throwable $ex) {
			// Revert all changes when error occur
			if ($connection->isTransactionActive()) {
				$connection->rollBack();
			}

			throw new Exceptions\InvalidState(
				'An error occurred: ' . $ex->getMessage(),
				is_int($ex->getCode()) ? $ex->getCode() : 0,
				$ex,
			);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function beginTransaction(): void
	{
		$connection = $this->getConnection();

		if ($connection === null) {
			throw new Exceptions\Runtime('Entity manager could not be loaded');
		}

		try {
			$this->pingAndReconnect();

			$connection->beginTransaction();

			$this->dispatcher?->dispatch(new Events\DbTransactionStarted());
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState(
				'An error occurred: ' . $ex->getMessage(),
				is_int($ex->getCode()) ? $ex->getCode() : 0,
				$ex,
			);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function commitTransaction(): void
	{
		$connection = $this->getConnection();

		if ($connection === null) {
			throw new Exceptions\Runtime('Entity manager could not be loaded');
		}

		try {
			if ($connection->isRollbackOnly()) {
				$connection->rollBack();
			} else {
				// Commit all changes into database
				$connection->commit();

				$this->dispatcher?->dispatch(new Events\DbTransactionFinished());
			}
		} catch (Throwable $ex) {
			// Revert all changes when error occur
			if ($connection->isTransactionActive()) {
				$connection->rollBack();
			}

			throw new Exceptions\InvalidState(
				'An error occurred: ' . $ex->getMessage(),
				is_int($ex->getCode()) ? $ex->getCode() : 0,
				$ex,
			);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function ping(): bool
	{
		$connection = $this->getConnection();

		if ($connection !== null) {
			try {
				$connection->executeQuery($connection->getDatabasePlatform()
					->getDummySelectSQL(), [], []);

			} catch (DBAL\Exception) {
				return false;
			}

			return true;
		}

		throw new Exceptions\InvalidState('Database connection not found');
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DBAL\Exception
	 */
	public function reconnect(): void
	{
		$connection = $this->getConnection();

		if ($connection !== null) {
			$connection->close();

			// close() drops the driver connection; the wrapper opens a fresh one lazily on the
			// next statement. This helper is used by the long-running servers before serving a
			// request, so the reconnect has to be eager rather than deferred -- hence the same
			// dummy select ping() issues, which is public API on both DBAL majors.
			//
			// It used to call $connection->connect(). DBAL 4 makes that protected and ships no
			// public equivalent, so the call would stop compiling rather than degrade quietly.
			$connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL(), [], []);

			return;
		}

		throw new Exceptions\InvalidState('Invalid database connection');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function clear(): void
	{
		if ($this->managerRegistry === null) {
			throw new Exceptions\InvalidState('Doctrine Manager registry service is missing');
		}

		foreach ($this->managerRegistry->getManagers() as $name => $manager) {
			if (!$manager instanceof ORM\EntityManagerInterface) {
				continue;
			}

			try {
				$manager->getConnection()->close();
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred during closing entity manager',
					[
						'source' => MetadataTypes\Sources\Module::NOT_SPECIFIED,
						'type' => 'helper',
						'exception' => Logger::buildException($ex),
					],
				);
			}

			try {
				$manager->clear();
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred during clearing entity manager',
					[
						'source' => MetadataTypes\Sources\Module::NOT_SPECIFIED,
						'type' => 'helper',
						'exception' => Logger::buildException($ex),
					],
				);
			}

			if (!$manager->isOpen()) {
				$this->managerRegistry->resetManager($name);
			}
		}

		// Just in case PHP would choose not to run garbage collection,
		// we run it manually at the end of each batch so that memory is
		// regularly released
		gc_collect_cycles();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getConnection(): DBAL\Connection|null
	{
		$em = $this->getEntityManager();

		if ($em instanceof ORM\EntityManagerInterface) {
			return $em->getConnection();
		}

		return null;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function pingAndReconnect(): void
	{
		try {
			// Check if ping to DB is possible...
			if (!$this->ping()) {
				// ...if not, try to reconnect
				$this->reconnect();

				// ...and ping again
				if (!$this->ping()) {
					throw new Exceptions\Runtime('Connection to database could not be re-established');
				}
			}
		} catch (Throwable $ex) {
			throw new Exceptions\Runtime(
				'Connection to database could not be re-established',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getEntityManager(): ORM\EntityManagerInterface|null
	{
		if ($this->managerRegistry === null) {
			throw new Exceptions\InvalidState('Doctrine Manager registry service is missing');
		}

		$em = $this->managerRegistry->getManager();

		if ($em instanceof ORM\EntityManagerInterface) {
			if (!$em->isOpen()) {
				$this->managerRegistry->resetManager();

				$em = $this->managerRegistry->getManager();
			}

			if ($em instanceof ORM\EntityManagerInterface) {
				return $em;
			}
		}

		return null;
	}

}
