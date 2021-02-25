<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\MiniServer\Exchange;
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

		$redisPublisher = Mockery::mock(Exchange\RedisPublisher::class);
		$redisPublisher
			->shouldReceive('publish');

		$this->mockContainerService(
			Exchange\RedisPublisher::class,
			$redisPublisher
		);
	}

	public function testDeleteDevice(): void
	{
		/** @var DevicesModuleModels\Channels\Properties\PropertyRepository $propertyRepository */
		$propertyRepository = $this->getContainer()->getByType(DevicesModuleModels\Channels\Properties\PropertyRepository::class);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$property = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($property);

		/** @var TriggersModuleModels\Conditions\ConditionRepository $conditionRepository */
		$conditionRepository = $this->getContainer()->getByType(TriggersModuleModels\Conditions\ConditionRepository::class);

		/** @var TriggersModuleModels\Actions\ActionRepository $actionRepository */
		$actionRepository = $this->getContainer()->getByType(TriggersModuleModels\Actions\ActionRepository::class);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice('bLikkz');
		$findCondition->forChannel('bLikxq');
		$findCondition->forProperty('bLikxE');

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::notNull($condition);

		/** @var DevicesModuleModels\Devices\DevicesManager $devicesManager */
		$devicesManager = $this->getContainer()->getByType(DevicesModuleModels\Devices\DevicesManager::class);

		$devicesManager->delete($property->getChannel()->getDevice());

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$property = $propertyRepository->findOneBy($findProperty);

		Assert::null($property);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice('bLikkz');
		$findCondition->forChannel('bLikxq');
		$findCondition->forProperty('bLikxE');

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::null($condition);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findAction->forDevice('bLikkz');
		$findAction->forChannel('bLikxh');
		$findAction->forChannelProperty('bLikx4');

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::null($action);
	}

	public function testDeleteChannel(): void
	{
		/** @var DevicesModuleModels\Channels\Properties\PropertyRepository $propertyRepository */
		$propertyRepository = $this->getContainer()->getByType(DevicesModuleModels\Channels\Properties\PropertyRepository::class);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$property = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($property);

		/** @var TriggersModuleModels\Conditions\ConditionRepository $conditionRepository */
		$conditionRepository = $this->getContainer()->getByType(TriggersModuleModels\Conditions\ConditionRepository::class);

		/** @var TriggersModuleModels\Actions\ActionRepository $actionRepository */
		$actionRepository = $this->getContainer()->getByType(TriggersModuleModels\Actions\ActionRepository::class);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice('bLikkz');
		$findCondition->forChannel('bLikxq');
		$findCondition->forProperty('bLikxE');

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::notNull($condition);

		/** @var DevicesModuleModels\Channels\ChannelsManager $channelsManager */
		$channelsManager = $this->getContainer()->getByType(DevicesModuleModels\Channels\ChannelsManager::class);

		$channelsManager->delete($property->getChannel());

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$property = $propertyRepository->findOneBy($findProperty);

		Assert::null($property);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice('bLikkz');
		$findCondition->forChannel('bLikxq');
		$findCondition->forProperty('bLikxE');

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::null($condition);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findAction->forDevice('bLikkz');
		$findAction->forChannel('bLikxh');
		$findAction->forChannelProperty('bLikx4');

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::notNull($action);
	}

	public function testDeleteChannelProperty(): void
	{
		/** @var DevicesModuleModels\Channels\Properties\PropertyRepository $propertyRepository */
		$propertyRepository = $this->getContainer()->getByType(DevicesModuleModels\Channels\Properties\PropertyRepository::class);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$property = $propertyRepository->findOneBy($findProperty);

		Assert::notNull($property);

		/** @var TriggersModuleModels\Conditions\ConditionRepository $conditionRepository */
		$conditionRepository = $this->getContainer()->getByType(TriggersModuleModels\Conditions\ConditionRepository::class);

		/** @var TriggersModuleModels\Actions\ActionRepository $actionRepository */
		$actionRepository = $this->getContainer()->getByType(TriggersModuleModels\Actions\ActionRepository::class);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice('bLikkz');
		$findCondition->forChannel('bLikxq');
		$findCondition->forProperty('bLikxE');

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::notNull($condition);

		/** @var DevicesModuleModels\Channels\Properties\PropertiesManager $propertiesManager */
		$propertiesManager = $this->getContainer()->getByType(DevicesModuleModels\Channels\Properties\PropertiesManager::class);

		$propertiesManager->delete($property);

		$findProperty = new DevicesModuleQueries\FindChannelPropertiesQuery();
		$findProperty->byKey('bLikxE');

		$property = $propertyRepository->findOneBy($findProperty);

		Assert::null($property);

		$findCondition = new TriggersModuleQueries\FindConditionsQuery();
		$findCondition->forDevice('bLikkz');
		$findCondition->forChannel('bLikxq');
		$findCondition->forProperty('bLikxE');

		$condition = $conditionRepository->findOneBy(
			$findCondition,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		Assert::null($condition);

		$findAction = new TriggersModuleQueries\FindActionsQuery();
		$findAction->forDevice('bLikkz');
		$findAction->forChannel('bLikxh');
		$findAction->forChannelProperty('bLikx4');

		$action = $actionRepository->findOneBy(
			$findAction,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		Assert::notNull($action);
	}

}

$test_case = new EntitiesSubscriberTest();
$test_case->run();
