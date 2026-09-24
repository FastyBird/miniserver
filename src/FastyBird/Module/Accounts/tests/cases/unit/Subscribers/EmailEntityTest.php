<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Subscribers;

use Doctrine\DBAL;
use Error;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Tests;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class EmailEntityTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws ApplicationExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws InvalidArgumentException
	 */
	public function testChangeDefault(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Emails\EmailsRepository::class);

		$manager = $this->getContainer()->getByType(Models\Entities\Emails\EmailsManager::class);

		$defaultEmail = $repository->findOneByAddress('john.doe@fastybird.com');

		self::assertNotNull($defaultEmail);
		self::assertTrue($defaultEmail->isDefault());

		$email = $repository->findOneByAddress('john.doe@fastybird.ovh');

		self::assertNotNull($email);

		$manager->update($email, Utils\ArrayHash::from([
			'default' => true,
		]));

		$defaultEmail = $repository->findOneByAddress('john.doe@fastybird.com');

		self::assertNotNull($defaultEmail);
		self::assertFalse($defaultEmail->isDefault());

		$findEntityQuery = new Queries\Entities\FindIdentities();
		$findEntityQuery->forAccount($defaultEmail->getAccount());

		$repository = $this->getContainer()->getByType(Models\Entities\Identities\IdentitiesRepository::class);

		$identity = $repository->findOneBy($findEntityQuery);

		self::assertNotNull($identity);
		self::assertNotNull($identity->getAccount()->getEmail());
		self::assertSame('john.doe@fastybird.ovh', $identity->getAccount()->getEmail()->getAddress());
		self::assertSame('john.doe@fastybird.com', $identity->getUid());
	}

}
