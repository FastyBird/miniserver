<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Subscribers;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Nette\Utils;
use Override;
use ReflectionException;

/**
 * Doctrine entities events
 */
final class Controls implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\Controls\ControlsRepository $controlsRepository,
		private readonly DevicesModels\Entities\Connectors\Controls\ControlsManager $controlsManager,
	)
	{
	}

	#[Override]
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws ReflectionException
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\Connectors\Connector) {
			$findConnectorControlQuery = new DevicesQueries\Entities\FindConnectorControls();
			$findConnectorControlQuery->forConnector($entity);
			$findConnectorControlQuery->byName(DevicesTypes\ControlName::REBOOT->value);

			$rebootControl = $this->controlsRepository->findOneBy($findConnectorControlQuery);

			if ($rebootControl === null) {
				$this->controlsManager->create(Utils\ArrayHash::from([
					'name' => DevicesTypes\ControlName::REBOOT->value,
					'connector' => $entity,
				]));
			}
		}
	}

}
