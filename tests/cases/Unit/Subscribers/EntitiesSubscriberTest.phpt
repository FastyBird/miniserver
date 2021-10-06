<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\ExchangePlugin\Publisher as ExchangePluginPublisher;
use FastyBird\MiniServer\Models;
use FastyBird\TriggersModule\Entities as TriggersModuleEntities;
use FastyBird\TriggersModule\Models as TriggersModuleModels;
use FastyBird\TriggersModule\Queries as TriggersModuleQueries;
use Mockery;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class EntitiesSubscriberTest extends DbTestCase
{

	public function setUp(): void
	{
		parent::setUp();

		$redisPublisher = Mockery::mock(ExchangePluginPublisher\PublisherProxy::class);
		$redisPublisher
			->shouldReceive('publish');

		$this->mockContainerService(
			ExchangePluginPublisher\PublisherProxy::class,
			$redisPublisher
		);

		$propertyRepository = Mockery::mock(Models\PropertyRepository::class);
		$propertyRepository
			->shouldReceive('findOne')
			->andReturn(null);

		$this->mockContainerService(
			Models\PropertyRepository::class,
			$propertyRepository
		);

		$triggerItemRepository = Mockery::mock(Models\TriggerItemRepository::class);
		$triggerItemRepository
			->shouldReceive('findOne')
			->andReturn(null);

		$this->mockContainerService(
			Models\TriggerItemRepository::class,
			$triggerItemRepository
		);
	}

	public function testDeleteDevice(): void
	{
		/** @var DevicesModuleModels\Channels\Properties\PropertyRepository $propertyRepository */
		$propertyRepository = $this->getContainer()->getByType(DevicesModuleModels\Channels\Properties\PropertyRepository::class);

		/** @var TriggersModuleModels\Conditions\ConditionRepository $conditionRepository */
		$conditionRepository = $this->getContainer()->getByType(TriggersModuleModels\Conditions\ConditionRepository::class);

		/** @var TriggersModuleModels\Actions\ActionRepository $actionRepository */
		$actionRepository = $this->getContainer()->getByType(TriggersModuleModels\Actions\ActionRepository::class);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$firstProperty = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($firstProperty);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikx4');

		$secondProperty = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($secondProperty);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findAction->forDevice($secondProperty->getChannel()->getDevice()->getId());
		$findAction->forChannel($secondProperty->getChannel()->getId());
		$findAction->forProperty($secondProperty->getId());

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::notNull($action);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice($firstProperty->getChannel()->getDevice()->getId());
		$findCondition->forChannel($firstProperty->getChannel()->getId());
		$findCondition->forProperty($firstProperty->getId());

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::notNull($condition);

		/** @var DevicesModuleModels\Devices\DevicesManager $devicesManager */
		$devicesManager = $this->getContainer()->getByType(DevicesModuleModels\Devices\DevicesManager::class);

		$devicesManager->delete($firstProperty->getChannel()->getDevice());

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$deletedProperty = $propertyRepository->findOneBy($findProperty);

		Assert::null($deletedProperty);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findAction->forDevice($secondProperty->getChannel()->getDevice()->getId());
		$findAction->forChannel($secondProperty->getChannel()->getId());
		$findAction->forProperty($secondProperty->getId());

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::null($action);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice($firstProperty->getChannel()->getDevice()->getId());
		$findCondition->forChannel($firstProperty->getChannel()->getId());
		$findCondition->forProperty($firstProperty->getId());

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::null($condition);
	}

	public function testDeleteChannel(): void
	{
		/** @var DevicesModuleModels\Channels\Properties\PropertyRepository $propertyRepository */
		$propertyRepository = $this->getContainer()->getByType(DevicesModuleModels\Channels\Properties\PropertyRepository::class);

		/** @var TriggersModuleModels\Conditions\ConditionRepository $conditionRepository */
		$conditionRepository = $this->getContainer()->getByType(TriggersModuleModels\Conditions\ConditionRepository::class);

		/** @var TriggersModuleModels\Actions\ActionRepository $actionRepository */
		$actionRepository = $this->getContainer()->getByType(TriggersModuleModels\Actions\ActionRepository::class);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$firstProperty = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($firstProperty);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikx4');

		$secondProperty = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($secondProperty);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findAction->forDevice($secondProperty->getChannel()->getDevice()->getId());
		$findAction->forChannel($secondProperty->getChannel()->getId());
		$findAction->forProperty($secondProperty->getId());

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::notNull($action);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice($firstProperty->getChannel()->getDevice()->getId());
		$findCondition->forChannel($firstProperty->getChannel()->getId());
		$findCondition->forProperty($firstProperty->getId());

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::notNull($condition);

		/** @var DevicesModuleModels\Channels\ChannelsManager $channelsManager */
		$channelsManager = $this->getContainer()->getByType(DevicesModuleModels\Channels\ChannelsManager::class);

		$channelsManager->delete($firstProperty->getChannel());

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$deletedProperty = $propertyRepository->findOneBy($findProperty);

		Assert::null($deletedProperty);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findCondition->forDevice($secondProperty->getChannel()->getDevice()->getId());
		$findCondition->forChannel($secondProperty->getChannel()->getId());
		$findCondition->forProperty($secondProperty->getId());

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::notNull($action);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice($firstProperty->getChannel()->getDevice()->getId());
		$findCondition->forChannel($firstProperty->getChannel()->getId());
		$findCondition->forProperty($firstProperty->getId());

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::null($condition);
	}

	public function testDeleteChannelProperty(): void
	{
		/** @var DevicesModuleModels\Channels\Properties\PropertyRepository $propertyRepository */
		$propertyRepository = $this->getContainer()->getByType(DevicesModuleModels\Channels\Properties\PropertyRepository::class);

		/** @var TriggersModuleModels\Conditions\ConditionRepository $conditionRepository */
		$conditionRepository = $this->getContainer()->getByType(TriggersModuleModels\Conditions\ConditionRepository::class);

		/** @var TriggersModuleModels\Actions\ActionRepository $actionRepository */
		$actionRepository = $this->getContainer()->getByType(TriggersModuleModels\Actions\ActionRepository::class);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$firstProperty = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($firstProperty);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikx4');

		$secondProperty = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($secondProperty);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findAction->forDevice($secondProperty->getChannel()->getDevice()->getId());
		$findAction->forChannel($secondProperty->getChannel()->getId());
		$findAction->forProperty($secondProperty->getId());

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::notNull($action);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice($firstProperty->getChannel()->getDevice()->getId());
		$findCondition->forChannel($firstProperty->getChannel()->getId());
		$findCondition->forProperty($firstProperty->getId());

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::notNull($condition);

		/** @var DevicesModuleModels\Channels\Properties\PropertiesManager $propertiesManager */
		$propertiesManager = $this->getContainer()->getByType(DevicesModuleModels\Channels\Properties\PropertiesManager::class);

		$propertiesManager->delete($firstProperty);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$deletedProperty = $propertyRepository->findOneBy($findProperty);

		Assert::null($deletedProperty);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findAction->forDevice($secondProperty->getChannel()->getDevice()->getId());
		$findAction->forChannel($secondProperty->getChannel()->getId());
		$findAction->forProperty($secondProperty->getId());

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::notNull($action);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice($firstProperty->getChannel()->getDevice()->getId());
		$findCondition->forChannel($firstProperty->getChannel()->getId());
		$findCondition->forProperty($firstProperty->getId());

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::null($condition);
	}

}

$test_case = new EntitiesSubscriberTest();
$test_case->run();
