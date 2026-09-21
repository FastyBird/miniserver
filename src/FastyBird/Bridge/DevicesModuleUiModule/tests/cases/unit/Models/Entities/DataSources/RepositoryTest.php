<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\Models\Entities\DataSources;

use Error;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Bridge\DevicesModuleUiModule\Exceptions;
use FastyBird\Bridge\DevicesModuleUiModule\Queries;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Ui\Models as UiModels;
use Nette\DI;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class RepositoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testRead(): void
	{
		$repository = $this->getContainer()->getByType(UiModels\Entities\Widgets\DataSources\Repository::class);

		$findDataSourceQuery = new Queries\Entities\FindWidgetChannelPropertyDataSources();
		$findDataSourceQuery->byId(Uuid\Uuid::fromString('764937a7-8565-472e-8e12-fe97cd55a377'));

		$dataSource = $repository->findOneBy(
			$findDataSourceQuery,
			Entities\Widgets\DataSources\ChannelProperty::class,
		);

		self::assertNotNull($dataSource);
		self::assertInstanceOf(DevicesEntities\Channels\Properties\Dynamic::class, $dataSource->getProperty());
	}

}
