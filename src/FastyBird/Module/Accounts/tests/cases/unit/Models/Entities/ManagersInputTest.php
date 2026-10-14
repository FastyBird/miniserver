<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Models\Entities;

use BackedEnum;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Tests;
use FastyBird\Module\Accounts\Types;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use function array_map;
use function assert;
use function is_array;
use function is_object;
use function ksort;
use function sort;
use function sprintf;
use function str_replace;

/**
 * Accounts' three entity managers take a plain array as well as an ArrayHash (#723).
 *
 * Each test makes one change twice: once from ArrayHash values, which is rolled back, and once
 * from the equal plain array. It compares the two results field by field: every mapped field and
 * every association of the entities the change reaches, as the manager returns them and again as
 * read back from the database. Against the code before #723 the plain array is a TypeError.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ManagersInputTest extends Tests\Cases\Unit\DbTestCase
{

	// John Doe in the fixtures, with his identity and his second email address
	private const string ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string IDENTITY = '77331268-efbf-bd34-49ef-bfbdefbfbd04';

	private const string EMAIL = '32ebe3c3-0238-482e-ab79-6b1d9ee2147c';

	private const string NEW_ACCOUNT = 'c1f7f4a2-8d3b-4e8a-9b51-6a0d2f3e4b01';

	private const string NEW_EMAIL = 'c1f7f4a2-8d3b-4e8a-9b51-6a0d2f3e4b02';

	private const string NEW_IDENTITY = 'c1f7f4a2-8d3b-4e8a-9b51-6a0d2f3e4b03';

	private const string PASSWORD = 'aNewPassword';

	/**
	 * An account with nested details (a one-to-one created with it) and nested params
	 *
	 * @throws Throwable
	 */
	public function testAnAccountCreatedFromAnArrayIsTheOneAnArrayHashCreates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Accounts\AccountsManager::class);

		$values = static fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_ACCOUNT),
			'entity' => Entities\Accounts\Account::class,
			'state' => Types\AccountState::NOT_ACTIVATED,
			'requestHash' => 'aRequestHash',
			'details' => [
				'entity' => Entities\Details\Details::class,
				'firstName' => 'Tony',
				'lastName' => 'Stark',
				'middleName' => 'Edward',
			],
			'params' => [
				'datetime' => [
					'zone' => 'Europe/Prague',
					'format' => ['date' => 'DD.MM.YYYY', 'time' => 'HH:mm'],
					'week_start' => 1,
				],
			],
		];

		$fromArrayHash = $this->change(
			static fn (): array => [$manager->create(Utils\ArrayHash::from($values()))],
		);
		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		self::assertSame($fromArrayHash, $fromArray);

		$account = $fromArray['returned']['Accounts\Account:' . self::NEW_ACCOUNT];
		self::assertSame('not_activated', $account['state']);
		self::assertSame('Details\Details of ' . self::NEW_ACCOUNT, $account['details']);
		self::assertSame(
			['datetime' => [
				'zone' => 'Europe/Prague',
				'format' => ['date' => 'DD.MM.YYYY', 'time' => 'HH:mm'],
				'week_start' => 1,
			]],
			$account['params'],
		);
		self::assertSame('Tony', $fromArray['reloaded']['Details\Details of ' . self::NEW_ACCOUNT]['firstName']);
	}

	/**
	 * State, request hash, a nested change of the existing details, and params merged into the
	 * stored ones
	 *
	 * @throws Throwable
	 */
	public function testAnAccountUpdatedFromAnArrayIsTheOneAnArrayHashUpdates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Accounts\AccountsManager::class);

		$values = static fn (): array => [
			'state' => Types\AccountState::BLOCKED,
			'requestHash' => 'anotherRequestHash',
			'details' => [
				'firstName' => 'Johnny',
				'middleName' => 'Middle',
			],
			'params' => [
				'datetime' => [
					'format' => ['time' => 'H:mm'],
					'week_start' => 0,
				],
			],
		];

		$fromArrayHash = $this->change(function () use ($manager, $values): array {
			$account = $this->find(Entities\Accounts\Account::class, self::ACCOUNT);

			return [$manager->update($account, Utils\ArrayHash::from($values()))];
		});
		$fromArray = $this->change(function () use ($manager, $values): array {
			$account = $this->find(Entities\Accounts\Account::class, self::ACCOUNT);

			return [$manager->update($account, $values())];
		});

		self::assertSame($fromArrayHash, $fromArray);

		$account = $fromArray['reloaded']['Accounts\Account:' . self::ACCOUNT];
		self::assertSame('blocked', $account['state']);
		self::assertSame('anotherRequestHash', $account['requestHash']);
		self::assertSame('Johnny', $fromArray['reloaded']['Details\Details of ' . self::ACCOUNT]['firstName']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnEmailCreatedFromAnArrayIsTheOneAnArrayHashCreates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Emails\EmailsManager::class);

		$values = fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_EMAIL),
			'account' => $this->find(Entities\Accounts\Account::class, self::ACCOUNT),
			'address' => 'john.doe@fastybird.net',
			'verified' => true,
			'visibility' => Types\EmailVisibility::PRIVATE,
		];

		$fromArrayHash = $this->change(
			static fn (): array => [$manager->create(Utils\ArrayHash::from($values()))],
		);
		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		self::assertSame($fromArrayHash, $fromArray);

		$email = $fromArray['reloaded']['Emails\Email:' . self::NEW_EMAIL];
		self::assertSame('john.doe@fastybird.net', $email['address']);
		self::assertSame('Accounts\Account:' . self::ACCOUNT, $email['account']);
		self::assertTrue($email['verified']);
		self::assertSame('private', $email['visibility']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnEmailUpdatedFromAnArrayIsTheOneAnArrayHashUpdates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Emails\EmailsManager::class);

		$values = static fn (): array => [
			'verified' => false,
			'verificationHash' => 'aVerificationHash',
			'visibility' => Types\EmailVisibility::PRIVATE,
		];

		$fromArrayHash = $this->change(function () use ($manager, $values): array {
			$email = $this->find(Entities\Emails\Email::class, self::EMAIL);

			return [$manager->update($email, Utils\ArrayHash::from($values()))];
		});
		$fromArray = $this->change(function () use ($manager, $values): array {
			$email = $this->find(Entities\Emails\Email::class, self::EMAIL);

			return [$manager->update($email, $values())];
		});

		self::assertSame($fromArrayHash, $fromArray);

		$email = $fromArray['reloaded']['Emails\Email:' . self::EMAIL];
		self::assertFalse($email['verified']);
		self::assertSame('aVerificationHash', $email['verificationHash']);
	}

	/**
	 * The password is stored salted, so it is compared by what it verifies
	 *
	 * @throws Throwable
	 */
	public function testAnIdentityCreatedFromAnArrayIsTheOneAnArrayHashCreates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Identities\IdentitiesManager::class);

		$values = fn (): array => [
			'id' => Uuid\Uuid::fromString(self::NEW_IDENTITY),
			'entity' => Entities\Identities\Identity::class,
			'account' => $this->find(Entities\Accounts\Account::class, self::ACCOUNT),
			'uid' => 'john.doe@fastybird.net',
			'password' => self::PASSWORD,
			'state' => Types\IdentityState::INVALID,
		];

		$fromArrayHash = $this->change(
			static fn (): array => [$manager->create(Utils\ArrayHash::from($values()))],
		);
		$fromArray = $this->change(static fn (): array => [$manager->create($values())]);

		self::assertSame($fromArrayHash, $fromArray);

		$identity = $fromArray['reloaded']['Identities\Identity:' . self::NEW_IDENTITY];
		self::assertSame('john.doe@fastybird.net', $identity['uid']);
		self::assertSame('invalid', $identity['state']);
		self::assertTrue($identity['verifies the password']);
	}

	/**
	 * @throws Throwable
	 */
	public function testAnIdentityUpdatedFromAnArrayIsTheOneAnArrayHashUpdates(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Identities\IdentitiesManager::class);

		$values = static fn (): array => [
			'password' => self::PASSWORD,
			'state' => Types\IdentityState::BLOCKED,
		];

		$fromArrayHash = $this->change(function () use ($manager, $values): array {
			$identity = $this->find(Entities\Identities\Identity::class, self::IDENTITY);

			return [$manager->update($identity, Utils\ArrayHash::from($values()))];
		});
		$fromArray = $this->change(function () use ($manager, $values): array {
			$identity = $this->find(Entities\Identities\Identity::class, self::IDENTITY);

			return [$manager->update($identity, $values())];
		});

		self::assertSame($fromArrayHash, $fromArray);

		$identity = $fromArray['reloaded']['Identities\Identity:' . self::IDENTITY];
		self::assertSame('blocked', $identity['state']);
		self::assertTrue($identity['verifies the password']);
	}

	/**
	 * Makes a change inside a transaction it rolls back, and records the entities the change
	 * returns (and an account's details) as returned and as read back from the database
	 *
	 * @param callable(): list<object> $change
	 *
	 * @return array{returned: array<string, array<string, mixed>>, reloaded: array<string, array<string, mixed>>}
	 *
	 * @throws Throwable
	 */
	private function change(callable $change): array
	{
		$entityManager = $this->getEntityManager();
		$connection = $entityManager->getConnection();

		$connection->beginTransaction();

		try {
			$entities = $change();

			$returned = $this->dump($entities);

			$entityManager->clear();

			$reloaded = $this->dump(array_map(
				function (object $entity): object {
					assert($entity instanceof Entities\Entity);

					return $this->find($entity::class, $entity->getId()->toString());
				},
				$entities,
			));
		} finally {
			$connection->rollBack();
			$entityManager->clear();
		}

		return ['returned' => $returned, 'reloaded' => $reloaded];
	}

	/**
	 * @param list<object> $entities
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws Throwable
	 */
	private function dump(array $entities): array
	{
		$entityManager = $this->getEntityManager();

		$records = [];

		foreach ($entities as $entity) {
			if ($entity instanceof Entities\Accounts\Account) {
				$entities[] = $entity->getDetails();
			}
		}

		foreach ($entities as $entity) {
			$metadata = $entityManager->getClassMetadata($entity::class);

			$record = [];

			foreach ($metadata->getFieldNames() as $field) {
				$record[$field] = $this->normalize($metadata->getFieldValue($entity, $field));
			}

			foreach ($metadata->getAssociationNames() as $association) {
				$value = $metadata->getFieldValue($entity, $association);

				if ($value instanceof Collection) {
					$references = [];

					foreach ($value as $item) {
						assert(is_object($item));

						$references[] = $this->reference($item);
					}

					sort($references);

					$record[$association] = $references;
				} elseif (is_object($value)) {
					$record[$association] = $this->reference($value);
				} else {
					$record[$association] = null;
				}
			}

			if ($entity instanceof Entities\Details\Details) {
				// Always generated, so the reference names the details by their account
				unset($record['id']);
			}

			if ($entity instanceof Entities\Identities\Identity) {
				// Hashed with a random salt, which is kept in the params
				unset($record['password'], $record['params']);

				$record['verifies the password'] = $entity->verifyPassword(self::PASSWORD);
			}

			ksort($record);

			$records[$this->reference($entity)] = $record;
		}

		ksort($records);

		return $records;
	}

	/**
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws Throwable
	 */
	private function find(string $class, string $id): object
	{
		$entity = $this->getEntityManager()->find($class, Uuid\Uuid::fromString($id));
		assert($entity instanceof $class);

		return $entity;
	}

	private function reference(object $entity): string
	{
		$class = str_replace('FastyBird\\Module\\Accounts\\Entities\\', '', $entity::class);

		if ($entity instanceof Entities\Details\Details) {
			return sprintf('%s of %s', $class, $entity->getAccount()->getId()->toString());
		}

		assert($entity instanceof Entities\Entity);

		return sprintf('%s:%s', $class, $entity->getId()->toString());
	}

	private function normalize(mixed $value): mixed
	{
		if ($value instanceof Utils\ArrayHash) {
			return ['ArrayHash' => $this->normalize((array) $value)];
		}

		if (is_array($value)) {
			return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
		}

		if ($value instanceof Uuid\UuidInterface) {
			return $value->toString();
		}

		if ($value instanceof BackedEnum) {
			return sprintf(
				'%s::%s',
				str_replace('FastyBird\\Module\\Accounts\\', '', $value::class),
				(string) $value->value,
			);
		}

		if ($value instanceof DateTimeInterface) {
			return $value->format(DateTimeInterface::ATOM);
		}

		if (is_object($value)) {
			return sprintf('object:%s', $value::class);
		}

		return $value;
	}

}
