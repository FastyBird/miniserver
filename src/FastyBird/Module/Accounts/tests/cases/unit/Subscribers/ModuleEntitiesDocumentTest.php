<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Documents as AccountsDocuments;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Subscribers;
use FastyBird\Module\Accounts\Tests;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;

/**
 * The document ModuleEntities publishes for an Accounts entity, built by the real routing document
 * factory from the entity's array (#723: a plain array since, an ArrayHash of it before). The
 * documents are pinned field by field and pass on both.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ModuleEntitiesDocumentTest extends Tests\Cases\Unit\DbTestCase
{

	private const string ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string EMAIL = '0b46d3d6-c980-494a-8b40-f19e6095e610';

	private const string IDENTITY = '77331268-efbf-bd34-49ef-bfbdefbfbd04';

	/** @var list<array{int|string, string, class-string|null, array<string, mixed>|null}> */
	private array $published = [];

	/**
	 * @throws Throwable
	 */
	public function testACreatedAccountIsPublishedAsItsDocument(): void
	{
		$this->persisted($this->find(Entities\Accounts\Account::class, self::ACCOUNT));

		self::assertSame(
			[
				[
					Sources\Module::ACCOUNTS->value,
					Accounts\Types\RoutingKey::ACCOUNT_DOCUMENT_CREATED->value,
					AccountsDocuments\Accounts\Account::class,
					[
						'id' => self::ACCOUNT,
						'source' => Sources\Module::ACCOUNTS->value,
						'first_name' => 'John',
						'last_name' => 'Doe',
						'middle_name' => null,
						'email' => 'john.doe@fastybird.com',
						'state' => 'active',
						'language' => 'en',
						'registered' => '2017-01-03T11:30:00+01:00',
						'last_visit' => '2019-11-07T22:30:56+01:00',
						'parent' => null,
						'children' => [],
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testACreatedEmailIsPublishedAsItsDocument(): void
	{
		$this->persisted($this->find(Entities\Emails\Email::class, self::EMAIL));

		self::assertSame(
			[
				[
					Sources\Module::ACCOUNTS->value,
					Accounts\Types\RoutingKey::EMAIL_DOCUMENT_CREATED->value,
					AccountsDocuments\Emails\Email::class,
					[
						'id' => self::EMAIL,
						'source' => Sources\Module::ACCOUNTS->value,
						'account' => self::ACCOUNT,
						'address' => 'john.doe@fastybird.com',
						'default' => true,
						'verified' => true,
						'private' => false,
						'public' => true,
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testACreatedIdentityIsPublishedAsItsDocument(): void
	{
		$this->persisted($this->find(Entities\Identities\Identity::class, self::IDENTITY));

		self::assertSame(
			[
				[
					Sources\Module::ACCOUNTS->value,
					Accounts\Types\RoutingKey::IDENTITY_DOCUMENT_CREATED->value,
					AccountsDocuments\Identities\Identity::class,
					[
						'id' => self::IDENTITY,
						'source' => Sources\Module::ACCOUNTS->value,
						'account' => self::ACCOUNT,
						'state' => 'active',
						'uid' => 'john.doe@fastybird.com',
						'hash' => null,
					],
				],
			],
			$this->published,
		);
	}

	/**
	 * Hands the entity to the subscriber as Doctrine does after persisting it, with a publisher
	 * that records what it is given
	 *
	 * @throws Throwable
	 */
	private function persisted(object $entity): void
	{
		$publisher = $this->createMock(Publisher\MessagePublisher::class);
		$publisher->method('publish')->willReturnCallback(
			function (
				Sources\Source $source,
				string $routingKey,
				CoreDocuments\Document|null $document,
			): bool {
				$this->published[] = [
					$source->value,
					$routingKey,
					$document !== null ? $document::class : null,
					$document?->toArray(),
				];

				return true;
			},
		);

		$entityManager = $this->getEntityManager();

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$this->getContainer()->getByType(EventLoop\Status::class),
			$this->getContainer()->getByType(CoreDocuments\RoutingDocumentFactory::class),
			$publisher,
			$this->createMock(Publisher\Async\MessagePublisher::class),
		);

		$subscriber->postPersist(new ORM\Event\PostPersistEventArgs($entity, $entityManager));
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
		self::assertInstanceOf($class, $entity);

		return $entity;
	}

}
