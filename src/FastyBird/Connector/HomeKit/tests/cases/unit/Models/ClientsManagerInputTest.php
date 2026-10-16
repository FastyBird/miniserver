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
use Throwable;
use TypeError;
use function str_repeat;

/**
 * The clients manager takes a plain array (#723). A new client is created as not an admin, which is not the column's
 * default, so a value the array path dropped would show. Each case makes a change from an array and compares what is
 * stored, as returned and as read back, with the record the manager built from an ArrayHash before #723. An ArrayHash
 * is refused with a TypeError.
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

		$expected = [
			'uid' => self::NEW_CLIENT_UID,
			'publicKey' => str_repeat("\x01", 32),
			'admin' => false,
			'connector' => self::CONNECTOR_ID,
		];

		self::assertSame($expected, $this->stored($this->manager()->create($values())));

		$this->getEntityManager()->clear();

		self::assertSame($expected, $this->stored($this->client(self::NEW_CLIENT_UID)));
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

		$expected = [
			'uid' => self::CLIENT_UID,
			'publicKey' => str_repeat("\x02", 32),
			'admin' => false,
			'connector' => self::CONNECTOR_ID,
		];

		self::assertSame($expected, $this->stored($this->manager()->update($this->client(self::CLIENT_UID), $values)));

		$this->getEntityManager()->clear();

		self::assertSame($expected, $this->stored($this->client(self::CLIENT_UID)));
	}

	/**
	 * The manager takes an array only: an ArrayHash is refused with a TypeError (#723)
	 *
	 * @throws Throwable
	 */
	public function testTheManagerRefusesAnArrayHash(): void
	{
		$hash = Utils\ArrayHash::from(['admin' => true]);
		$client = $this->client(self::CLIENT_UID);

		foreach (
			[
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				fn () => $this->manager()->create($hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				fn () => $this->manager()->update($client, $hash),
			] as $call
		) {
			self::assertTrue(self::refuses($call));
		}
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

	/**
	 * Whether a call is refused with a TypeError
	 *
	 * @param callable(): mixed $call
	 */
	private static function refuses(callable $call): bool
	{
		try {
			$call();
		} catch (TypeError) {
			return true;
		}

		return false;
	}

}
