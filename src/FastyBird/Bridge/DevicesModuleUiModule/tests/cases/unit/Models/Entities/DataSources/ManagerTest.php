<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\Models\Entities\DataSources;

use Doctrine\DBAL;
use Error;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Bridge\DevicesModuleUiModule\Exceptions;
use FastyBird\Bridge\DevicesModuleUiModule\Queries;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Ui\Entities as UiEntities;
use FastyBird\Module\Ui\Models as UiModels;
use FastyBird\Module\Ui\Queries as UiQueries;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\DI;
use Nette\Utils;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ManagerTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DI\MissingServiceException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testCreate(): void
	{
		$channelPropertiesRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\Properties\PropertiesRepository::class,
		);

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
		$findChannelPropertiesQuery->byId(Uuid\Uuid::fromString('24c436f4-a2e4-4d2b-b910-1a3ff785b784'));

		$property = $channelPropertiesRepository->findOneBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		self::assertNotNull($property);

		$widgetsRepository = $this->getContainer()->getByType(UiModels\Entities\Widgets\Repository::class);

		$findWidgetQuery = new UiQueries\Entities\FindWidgets();
		$findWidgetQuery->byId(Uuid\Uuid::fromString('15553443-4564-454d-af04-0dfeef08aa96'));

		$widget = $widgetsRepository->findOneBy(
			$findWidgetQuery,
			UiEntities\Widgets\AnalogSensor::class,
		);

		self::assertNotNull($widget);

		$dataSourcesManager = $this->getContainer()->getByType(UiModels\Entities\Widgets\DataSources\Manager::class);

		$dataSource = $dataSourcesManager->create(Utils\ArrayHash::from([
			'entity' => Entities\Widgets\DataSources\ChannelProperty::class,
			'property' => $property,
			'widget' => $widget,
		]));

		self::assertInstanceOf(Entities\Widgets\DataSources\ChannelProperty::class, $dataSource);
		self::assertSame($property, $dataSource->getProperty());
		self::assertSame($widget, $dataSource->getWidget());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DI\MissingServiceException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testDelete(): void
	{
		$dataSourcesRepository = $this->getContainer()->getByType(
			UiModels\Entities\Widgets\DataSources\Repository::class,
		);

		$findDataSourceQuery = new Queries\Entities\FindWidgetChannelPropertyDataSources();
		$findDataSourceQuery->byId(Uuid\Uuid::fromString('764937a7-8565-472e-8e12-fe97cd55a377'));

		$dataSource = $dataSourcesRepository->findOneBy(
			$findDataSourceQuery,
			Entities\Widgets\DataSources\ChannelProperty::class,
		);

		self::assertNotNull($dataSource);

		$channelPropertiesRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\Properties\PropertiesRepository::class,
		);

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
		$findChannelPropertiesQuery->byId(Uuid\Uuid::fromString('28bc0d38-2f7c-4a71-aa74-27b102f8df4c'));

		$property = $channelPropertiesRepository->findOneBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		self::assertNotNull($property);

		$dataSourcesManager = $this->getContainer()->getByType(UiModels\Entities\Widgets\DataSources\Manager::class);

		$dataSourcesManager->delete($dataSource);

		$findDataSourceQuery = new Queries\Entities\FindWidgetChannelPropertyDataSources();
		$findDataSourceQuery->byId(Uuid\Uuid::fromString('764937a7-8565-472e-8e12-fe97cd55a377'));

		$dataSource = $dataSourcesRepository->findOneBy(
			$findDataSourceQuery,
			Entities\Widgets\DataSources\ChannelProperty::class,
		);

		self::assertNull($dataSource);

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
		$findChannelPropertiesQuery->byId(Uuid\Uuid::fromString('28bc0d38-2f7c-4a71-aa74-27b102f8df4c'));

		$property = $channelPropertiesRepository->findOneBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		self::assertNotNull($property);

		$widgetsRepository = $this->getContainer()->getByType(UiModels\Entities\Widgets\Repository::class);

		$findWidgetQuery = new UiQueries\Entities\FindWidgets();
		$findWidgetQuery->byId(Uuid\Uuid::fromString('15553443-4564-454d-af04-0dfeef08aa96'));

		$widget = $widgetsRepository->findOneBy(
			$findWidgetQuery,
			UiEntities\Widgets\AnalogSensor::class,
		);

		self::assertNotNull($widget);

		$dataSource = $dataSourcesManager->create(Utils\ArrayHash::from([
			'id' => Uuid\Uuid::fromString('764937a7-8565-472e-8e12-fe97cd55a377'),
			'entity' => Entities\Widgets\DataSources\ChannelProperty::class,
			'property' => $property,
			'widget' => $widget,
		]));

		self::assertInstanceOf(Entities\Widgets\DataSources\ChannelProperty::class, $dataSource);
		self::assertSame($property, $dataSource->getProperty());
		self::assertSame('764937a7-8565-472e-8e12-fe97cd55a377', $dataSource->getId()->toString());

		$channelPropertiesManager = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\Properties\PropertiesManager::class,
		);

		$channelPropertiesManager->delete($property);

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
		$findChannelPropertiesQuery->byId(Uuid\Uuid::fromString('28bc0d38-2f7c-4a71-aa74-27b102f8df4c'));

		$property = $channelPropertiesRepository->findOneBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		self::assertNull($property);

		$findDataSourceQuery = new Queries\Entities\FindWidgetChannelPropertyDataSources();
		$findDataSourceQuery->byId(Uuid\Uuid::fromString('764937a7-8565-472e-8e12-fe97cd55a377'));

		$dataSource = $dataSourcesRepository->findOneBy(
			$findDataSourceQuery,
			Entities\Widgets\DataSources\ChannelProperty::class,
		);

		self::assertNull($dataSource);
	}

}
