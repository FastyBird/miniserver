<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Accounts\Commands;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Middleware;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Subscribers;
use FastyBird\Module\Accounts\Tests;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class AccountsModuleExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Middleware\Access::class, false));
		self::assertNotNull($this->getContainer()->getByType(Middleware\UrlFormat::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Accounts\Create::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\ModuleEntities::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\AccountEntity::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\EmailEntity::class, false));

		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Accounts\AccountsRepository::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Emails\EmailsRepository::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Identities\IdentitiesRepository::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Accounts\AccountsManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Emails\EmailsManager::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Identities\IdentitiesManager::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Controllers\AccountV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\AccountEmailsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\SessionV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\AccountIdentitiesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\AccountsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\EmailsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\IdentitiesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\RolesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\RoleChildrenV1::class, false));

		self::assertNotNull($this->getContainer()->getByType(Router\Validator::class, false));
		self::assertNotNull($this->getContainer()->getByType(Router\ApiRoutes::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Accounts\Account::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Emails\Email::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Sessions\Session::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Identities\Identity::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Roles\Role::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Accounts\ProfileAccount::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Accounts\Account::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Identities\Identity::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Emails\ProfileEmail::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Emails\Email::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Roles\Role::class, false));
	}

}
