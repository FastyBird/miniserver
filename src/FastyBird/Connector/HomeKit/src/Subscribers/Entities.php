<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Subscribers;

use FastyBird\Connector\HomeKit\Events as HomeKitEvents;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Override;
use Psr\EventDispatcher as PsrEventDispatcher;
use Symfony\Component\EventDispatcher as ComponentEventDispatcher;

/**
 * Doctrine entities events
 */
final class Entities implements ComponentEventDispatcher\EventSubscriberInterface
{

	public function __construct(
		private readonly PsrEventDispatcher\EventDispatcherInterface $dispatcher,
	)
	{
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\EntityCreated::class => 'entityCreated',
			DevicesEvents\EntityUpdated::class => 'entityUpdated',
			DevicesEvents\EntityDeleted::class => 'entityDeleted',
		];
	}

	public function entityCreated(DevicesEvents\EntityCreated $event): void
	{
		if ($event->getEntity() instanceof DevicesEntities\Connectors\Properties\Variable) {
			if ($event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::SHARED_KEY->value) {
				$this->dispatcher->dispatch(new HomeKitEvents\SharedKeyChanged($event->getEntity()));
			}

			if (
				$event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::PAIRED->value
				|| $event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::CONFIG_VERSION->value
			) {
				$this->dispatcher->dispatch(new HomeKitEvents\AccessoryConfigurationChanged($event->getEntity()));
			}
		}
	}

	public function entityUpdated(DevicesEvents\EntityUpdated $event): void
	{
		if ($event->getEntity() instanceof DevicesEntities\Connectors\Properties\Variable) {
			if ($event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::SHARED_KEY->value) {
				$this->dispatcher->dispatch(new HomeKitEvents\SharedKeyChanged($event->getEntity()));
			}

			if (
				$event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::PAIRED->value
				|| $event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::CONFIG_VERSION->value
			) {
				$this->dispatcher->dispatch(new HomeKitEvents\AccessoryConfigurationChanged($event->getEntity()));
			}
		}
	}

	public function entityDeleted(DevicesEvents\EntityDeleted $event): void
	{
		if ($event->getEntity() instanceof DevicesEntities\Connectors\Properties\Variable) {
			if (
				$event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::PAIRED->value
				|| $event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::CONFIG_VERSION->value
			) {
				$this->dispatcher->dispatch(new HomeKitEvents\AccessoryConfigurationChanged($event->getEntity()));
			}
		}
	}

}
