<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Documents;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Exchange\Publisher\Async;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Entities;
use Override;
use ReflectionClass;
use function count;
use function is_a;
use function str_starts_with;

/**
 * Doctrine entities events
 */
final readonly class ModuleEntities implements Common\EventSubscriber
{

	private const string ACTION_CREATED = 'created';

	private const string ACTION_UPDATED = 'updated';

	private const string ACTION_DELETED = 'deleted';

	private const array MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Dashboards\Dashboard::class => Ui\Types\RoutingKey::DASHBOARD_DOCUMENT_CREATED->value,
			Entities\Dashboards\Tabs\Tab::class => Ui\Types\RoutingKey::DASHBOARD_TAB_DOCUMENT_CREATED->value,
			Entities\Groups\Group::class => Ui\Types\RoutingKey::GROUP_DOCUMENT_CREATED->value,
			Entities\Widgets\Widget::class => Ui\Types\RoutingKey::WIDGET_DOCUMENT_CREATED->value,
			Entities\Widgets\DataSources\DataSource::class => Ui\Types\RoutingKey::WIDGET_DATA_SOURCE_DOCUMENT_CREATED->value,
			Entities\Widgets\Displays\Display::class => Ui\Types\RoutingKey::WIDGET_DISPLAY_DOCUMENT_CREATED->value,
		];

	private const array MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Dashboards\Dashboard::class => Ui\Types\RoutingKey::DASHBOARD_DOCUMENT_UPDATED->value,
			Entities\Dashboards\Tabs\Tab::class => Ui\Types\RoutingKey::DASHBOARD_TAB_DOCUMENT_UPDATED->value,
			Entities\Groups\Group::class => Ui\Types\RoutingKey::GROUP_DOCUMENT_UPDATED->value,
			Entities\Widgets\Widget::class => Ui\Types\RoutingKey::WIDGET_DOCUMENT_UPDATED->value,
			Entities\Widgets\DataSources\DataSource::class => Ui\Types\RoutingKey::WIDGET_DATA_SOURCE_DOCUMENT_UPDATED->value,
			Entities\Widgets\Displays\Display::class => Ui\Types\RoutingKey::WIDGET_DISPLAY_DOCUMENT_UPDATED->value,
		];

	private const array MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Dashboards\Dashboard::class => Ui\Types\RoutingKey::DASHBOARD_DOCUMENT_DELETED->value,
			Entities\Dashboards\Tabs\Tab::class => Ui\Types\RoutingKey::DASHBOARD_TAB_DOCUMENT_DELETED->value,
			Entities\Groups\Group::class => Ui\Types\RoutingKey::GROUP_DOCUMENT_DELETED->value,
			Entities\Widgets\Widget::class => Ui\Types\RoutingKey::WIDGET_DOCUMENT_DELETED->value,
			Entities\Widgets\DataSources\DataSource::class => Ui\Types\RoutingKey::WIDGET_DATA_SOURCE_DOCUMENT_DELETED->value,
			Entities\Widgets\Displays\Display::class => Ui\Types\RoutingKey::WIDGET_DISPLAY_DOCUMENT_DELETED->value,
		];

	public function __construct(
		private ORM\EntityManagerInterface $entityManager,
		private EventLoop\Status $eventLoopStatus,
		private Documents\RoutingDocumentFactory $documentFactory,
		private Publisher\MessagePublisher $publisher,
		private Async\MessagePublisher $asyncPublisher,
	)
	{
	}

	#[Override]
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
			ORM\Events::postRemove,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_CREATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidState
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Get changes => should be already computed here (is a listener)
		$changeSet = $uow->getEntityChangeSet($entity);

		// If we have no changes left => don't create revision log
		if (count($changeSet) === 0) {
			return;
		}

		// Check for valid entity
		if (
			!$entity instanceof Entities\Entity
			|| !$this->validateNamespace($entity)
			|| $uow->isScheduledForDelete($entity)
		) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_UPDATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidState
	 */
	public function postRemove(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_DELETED);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidState
	 */
	private function publishEntity(Entities\Entity $entity, string $action): void
	{
		$publishRoutingKey = null;

		switch ($action) {
			case self::ACTION_CREATED:
				foreach (self::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
			case self::ACTION_UPDATED:
				foreach (self::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
			case self::ACTION_DELETED:
				foreach (self::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
		}

		if ($publishRoutingKey !== null) {
			$this->getPublisher($this->eventLoopStatus->running)->publish(
				Sources\Module::DEVICES,
				$publishRoutingKey,
				$this->documentFactory->create(
					$entity->toArray(),
					$publishRoutingKey,
				),
			);
		}
	}

	private function validateNamespace(object $entity): bool
	{
		$rc = new ReflectionClass($entity);

		if (str_starts_with($rc->getNamespaceName(), 'FastyBird\Module\Ui')) {
			return true;
		}

		foreach ($rc->getInterfaces() as $interface) {
			if (str_starts_with($interface->getNamespaceName(), 'FastyBird\Module\Ui')) {
				return true;
			}
		}

		return false;
	}

	private function getPublisher(bool $async): Publisher\MessagePublisher|Async\MessagePublisher
	{
		return $async ? $this->asyncPublisher : $this->publisher;
	}

}
