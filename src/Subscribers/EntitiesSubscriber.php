<?php declare(strict_types = 1);

/**
 * EntitiesSubscriber.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           24.02.21
 */

namespace FastyBird\MiniServer\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\TriggersModule\Entities as TriggersModuleEntities;
use FastyBird\TriggersModule\Models as TriggersModuleModels;
use FastyBird\TriggersModule\Queries as TriggersModuleQueries;
use Psr\Log;

/**
 * Exchange publisher subscriber
 *
 * @package         FastyBird:MiniServer!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EntitiesSubscriber implements Common\EventSubscriber
{

	/** @var TriggersModuleModels\Actions\IActionRepository */
	private TriggersModuleModels\Actions\IActionRepository $actionRepository;

	/** @var TriggersModuleModels\Actions\IActionsManager */
	private TriggersModuleModels\Actions\IActionsManager $actionsManager;

	/** @var TriggersModuleModels\Conditions\IConditionRepository */
	private TriggersModuleModels\Conditions\IConditionRepository $conditionRepository;

	/** @var TriggersModuleModels\Conditions\IConditionsManager */
	private TriggersModuleModels\Conditions\IConditionsManager $conditionsManager;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	/**
	 * Register events
	 *
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::preRemove,
		];
	}

	public function __construct(
		TriggersModuleModels\Actions\IActionRepository $actionRepository,
		TriggersModuleModels\Actions\IActionsManager $actionsManager,
		TriggersModuleModels\Conditions\IConditionRepository $conditionRepository,
		TriggersModuleModels\Conditions\IConditionsManager $conditionsManager,
		Log\LoggerInterface $logger
	) {
		$this->actionRepository = $actionRepository;
		$this->actionsManager = $actionsManager;
		$this->conditionRepository = $conditionRepository;
		$this->conditionsManager = $conditionsManager;

		$this->logger = $logger;
	}

	/**
	 * @param ORM\Event\LifecycleEventArgs $event
	 *
	 * @return void
	 */
	public function preRemove(ORM\Event\LifecycleEventArgs $event): void
	{
		$entity = $event->getEntity();

		if ($entity instanceof DevicesModuleEntities\Devices\IDevice) {
			$this->clearDevices($entity);

		} elseif ($entity instanceof DevicesModuleEntities\Devices\Properties\IProperty) {
			$this->clearDeviceProperties($entity);

		} elseif ($entity instanceof DevicesModuleEntities\Channels\IChannel) {
			$this->clearChannels($entity);

		} elseif ($entity instanceof DevicesModuleEntities\Channels\Properties\IProperty) {
			$this->clearChannelProperties($entity);
		}
	}

	/**
	 * @param DevicesModuleEntities\Devices\IDevice $device
	 *
	 * @return void
	 */
	private function clearDevices(DevicesModuleEntities\Devices\IDevice $device): void
	{
		$findQuery = new TriggersModuleQueries\FindActionsQuery();
		$findQuery->forDevice($device->getKey());

		$actions = $this->actionRepository->findAllBy(
			$findQuery,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		foreach ($actions as $action) {
			$this->actionsManager->delete($action);
		}

		$findQuery = new TriggersModuleQueries\FindConditionsQuery();
		$findQuery->forDevice($device->getKey());

		$conditions = $this->conditionRepository->findAllBy(
			$findQuery,
			TriggersModuleEntities\Conditions\DevicePropertyCondition::class
		);

		foreach ($conditions as $condition) {
			$this->conditionsManager->delete($condition);
		}

		$findQuery = new TriggersModuleQueries\FindConditionsQuery();
		$findQuery->forDevice($device->getKey());

		$conditions = $this->conditionRepository->findAllBy(
			$findQuery,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		foreach ($conditions as $condition) {
			$this->conditionsManager->delete($condition);
		}

		$this->logger->info('[FB:MINISERVER:SUBSCRIBER] Successfully processed deleted device entity');
	}

	/**
	 * @param DevicesModuleEntities\Devices\Properties\IProperty $property
	 *
	 * @return void
	 */
	private function clearDeviceProperties(DevicesModuleEntities\Devices\Properties\IProperty $property): void
	{
		$findQuery = new TriggersModuleQueries\FindConditionsQuery();
		$findQuery->forProperty($property->getKey());

		$conditions = $this->conditionRepository->findAllBy(
			$findQuery,
			TriggersModuleEntities\Conditions\DevicePropertyCondition::class
		);

		foreach ($conditions as $condition) {
			$this->conditionsManager->delete($condition);
		}

		$this->logger->info('[FB:MINISERVER:SUBSCRIBER] Successfully processed deleted device property entity');
	}

	/**
	 * @param DevicesModuleEntities\Channels\IChannel $channel
	 *
	 * @return void
	 */
	private function clearChannels(DevicesModuleEntities\Channels\IChannel $channel): void
	{
		$findQuery = new TriggersModuleQueries\FindActionsQuery();
		$findQuery->forChannel($channel->getKey());

		$actions = $this->actionRepository->findAllBy(
			$findQuery,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		foreach ($actions as $action) {
			$this->actionsManager->delete($action);
		}

		$findQuery = new TriggersModuleQueries\FindConditionsQuery();
		$findQuery->forChannel($channel->getKey());

		$conditions = $this->conditionRepository->findAllBy(
			$findQuery,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		foreach ($conditions as $condition) {
			$this->conditionsManager->delete($condition);
		}

		$this->logger->info('[FB:MINISERVER:SUBSCRIBER] Successfully processed deleted channel entity');
	}

	/**
	 * @param DevicesModuleEntities\Channels\Properties\IProperty $property
	 *
	 * @return void
	 */
	private function clearChannelProperties(DevicesModuleEntities\Channels\Properties\IProperty $property): void
	{
		$findQuery = new TriggersModuleQueries\FindActionsQuery();
		$findQuery->forChannelProperty($property->getKey());

		$actions = $this->actionRepository->findAllBy(
			$findQuery,
			TriggersModuleEntities\Actions\ChannelPropertyAction::class
		);

		foreach ($actions as $action) {
			$this->actionsManager->delete($action);
		}

		$findQuery = new TriggersModuleQueries\FindConditionsQuery();
		$findQuery->forProperty($property->getKey());

		$conditions = $this->conditionRepository->findAllBy(
			$findQuery,
			TriggersModuleEntities\Conditions\ChannelPropertyCondition::class
		);

		foreach ($conditions as $condition) {
			$this->conditionsManager->delete($condition);
		}

		$this->logger->info('[FB:MINISERVER:SUBSCRIBER] Successfully processed deleted channel property entity');
	}

}
