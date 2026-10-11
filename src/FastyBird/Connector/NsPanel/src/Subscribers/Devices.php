<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\NsPanel\Entities as NsPanelEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;

/**
 * Doctrine entities events
 */
final class Devices implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws ORM\ORMInvalidArgumentException
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if ($entity instanceof NsPanelEntities\Devices\ThirdPartyDevice) {
			$this->checkAndAssignThirdPartyDeviceParents($entity, $eventArgs);
		}
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws ORM\ORMInvalidArgumentException
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if ($entity instanceof NsPanelEntities\Devices\ThirdPartyDevice) {
			$this->checkAndAssignThirdPartyDeviceParents($entity, $eventArgs);
		}
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws ORM\ORMInvalidArgumentException
	 */
	private function checkAndAssignThirdPartyDeviceParents(
		NsPanelEntities\Devices\ThirdPartyDevice $device,
		Persistence\Event\LifecycleEventArgs $eventArgs,
	): void
	{
		$entityManager = $eventArgs->getObjectManager();

		$parents = [];

		foreach ($device->getChannels() as $channel) {
			foreach ($channel->getProperties() as $property) {
				if ($property instanceof DevicesEntities\Channels\Properties\Mapped) {
					$parents[] = $property->getParent()->getChannel()->getDevice();
				}
			}
		}

		$device->setParents($parents);

		$uow = $entityManager->getUnitOfWork();

		$metaData = $entityManager->getClassMetadata($device::class);

		$uow->recomputeSingleEntityChangeSet($metaData, $device);
	}

}
