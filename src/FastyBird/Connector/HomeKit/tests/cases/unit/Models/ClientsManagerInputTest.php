<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Tests\Cases\Unit\Models;

use Doctrine\DBAL;
use Error;
use Exception;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Models as HomeKitModels;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use function str_repeat;

/**
 * The clients manager takes a plain array as well as an ArrayHash (#723). A new client is created as not an admin, which
 * is not the column's default, so a value the array path dropped would show. Each case makes the same change from an
 * ArrayHash, which is rolled back, and from the equal array, and compares what is stored, as returned and as read
 * back.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ClientsManagerInputTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = 'f5a8691b-4917-4866-878f-5217193cf14b';

	private const string CLIENT_UID = 'e348f5fc-42de-459e-926e-2f4cd039c665';

	private const string NEW_CLIENT_UID = '7e11f659-a130-4eb1-b550-dc96c1160c85';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Exception
	 * @throws Error
	 * @throws RuntimeException
	 */
	public function testCreateFromAnArray(): void
	{
		$values = fn (): array => [
			'connector' => $this->connector(),
			'uid' => self::NEW_CLIENT_UID,
			'publicKey' => str_repeat("\x01", 32),
			'admin' => false,
		];

		$fromArrayHash = $this->rolledBack(
			fn (): array => $this->stored($this->manager()->create(Utils\ArrayHash::from($values()))),
		);

		$fromArray = $this->stored($this->manager()->create($values()));

		self::assertSame(
			[
				'uid' => self::NEW_CLIENT_UID,
				'publicKey' => str_repeat("\x01", 32),
				'admin' => false,
				'connector' => self::CONNECTOR_ID,
			],
			$fromArrayHash,
		);
		self::assertSame($fromArrayHash, $fromArray);

		$this->getEntityManager()->clear();

		self::assertSame($fromArrayHash, $this->stored($this->client(self::NEW_CLIENT_UID)));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Exception
	 * @throws Error
	 * @throws RuntimeException
	 */
	public function testUpdateFromAnArray(): void
	{
		$values = [
			'publicKey' => str_repeat("\x02", 32),
			'admin' => false,
		];

		$fromArrayHash = $this->rolledBack(
			fn (): array => $this->stored(
				$this->manager()->update($this->client(self::CLIENT_UID), Utils\ArrayHash::from($values)),
			),
		);

		$this->getEntityManager()->clear();

		$fromArray = $this->stored($this->manager()->update($this->client(self::CLIENT_UID), $values));

		self::assertSame(
			[
				'uid' => self::CLIENT_UID,
				'publicKey' => str_repeat("\x02", 32),
				'admin' => false,
				'connector' => self::CONNECTOR_ID,
			],
			$fromArrayHash,
		);
		self::assertSame($fromArrayHash, $fromArray);

		$this->getEntityManager()->clear();

		self::assertSame($fromArrayHash, $this->stored($this->client(self::CLIENT_UID)));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function stored(Entities\Clients\Client $client): array
	{
		return [
			'uid' => $client->getUid(),
			'publicKey' => $client->getPublicKey(),
			'admin' => $client->isAdmin(),
			'connector' => $client->getConnector()->getId()->toString(),
		];
	}

	/**
	 * @param callable(): array<string, mixed> $change
	 *
	 * @return array<string, mixed>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Exception
	 */
	private function rolledBack(callable $change): array
	{
		$connection = $this->getEntityManager()->getConnection();

		$connection->beginTransaction();

		try {
			return $change();
		} finally {
			$connection->rollBack();

			$this->getEntityManager()->clear();
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function manager(): HomeKitModels\Entities\Clients\ClientsManager
	{
		return $this->getContainer()->getByType(HomeKitModels\Entities\Clients\ClientsManager::class);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function connector(): Entities\Connectors\Connector
	{
		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->find(
			Uuid\Uuid::fromString(self::CONNECTOR_ID),
			Entities\Connectors\Connector::class,
		);

		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		return $connector;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function client(string $uid): Entities\Clients\Client
	{
		$findQuery = new Queries\Entities\FindClients();
		$findQuery->byUid($uid);

		$client = $this->getContainer()->getByType(HomeKitModels\Entities\Clients\ClientsRepository::class)->findOneBy(
			$findQuery,
		);

		self::assertInstanceOf(Entities\Clients\Client::class, $client);

		return $client;
	}

}
