<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Triggers\Commands;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\Exceptions as TriggersExceptions;
use FastyBird\Module\Triggers\Hydrators;
use FastyBird\Module\Triggers\Middleware;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use FastyBird\Module\Triggers\Subscribers;
use FastyBird\Module\Triggers\Tests;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class TriggersModuleExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TriggersExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Commands\Install::class, false));

		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Triggers\TriggersRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Triggers\Controls\ControlsRepository::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Actions\ActionsRepository::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Notifications\NotificationsRepository::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Conditions\ConditionsRepository::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Triggers\TriggersManager::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Triggers\Controls\ControlsManager::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Models\Entities\Actions\ActionsManager::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Notifications\NotificationsManager::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Models\Entities\Conditions\ConditionsManager::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Models\States\ActionsRepository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\ConditionsRepository::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\States\ActionsManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\States\ConditionsManager::class, false));

		self::assertNotNull($this->getContainer()->getByType(Controllers\TriggersV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\TriggerControlsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ActionsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\NotificationsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\ConditionsV1::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Triggers\Automatic::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Triggers\Manual::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Triggers\Controls\Control::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Notifications\Email::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Notifications\Sms::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Triggers\AutomaticTrigger::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Triggers\ManualTrigger::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Notifications\Email::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Notifications\Sms::class, false));

		self::assertNotNull($this->getContainer()->getByType(Router\Validator::class, false));
		self::assertNotNull($this->getContainer()->getByType(Router\ApiRoutes::class, false));

		self::assertNotNull($this->getContainer()->getByType(Middleware\Access::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\ModuleEntities::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\NotificationEntity::class, false));
	}

}
