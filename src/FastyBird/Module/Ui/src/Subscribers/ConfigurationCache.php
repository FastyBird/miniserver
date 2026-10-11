<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Subscribers;

use FastyBird\Module\Ui\Caching as UiCaching;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Events;
use FastyBird\Module\Ui\Types;
use Nette;
use Nette\Caching as NetteCaching;
use Override;
use Symfony\Component\EventDispatcher;

/**
 * Module entities events
 */
final readonly class ConfigurationCache implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(private UiCaching\Container $moduleCaching)
	{
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			Events\EntityCreated::class => 'entityChanged',
			Events\EntityUpdated::class => 'entityChanged',
			Events\EntityDeleted::class => 'entityChanged',
		];
	}

	public function entityChanged(Events\EntityCreated|Events\EntityUpdated|Events\EntityDeleted $event): void
	{
		$entity = $event->getEntity();
		if ($entity instanceof Entities\Dashboards\Dashboard) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::DASHBOARDS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::DASHBOARDS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Dashboards\Tabs\Tab) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::DASHBOARDS_TABS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::DASHBOARDS_TABS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Groups\Group) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::GROUPS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::GROUPS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Widgets\Widget) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::WIDGETS->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::WIDGETS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Widgets\DataSources\DataSource) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::WIDGETS_DATA_SOURCES->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::WIDGETS_DATA_SOURCES->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Widgets\Displays\Display) {
			$this->moduleCaching->getConfigurationBuilderCache()->clean([
				NetteCaching\Cache::Tags => [Types\ConfigurationType::WIDGETS_DISPLAY->value],
			]);

			$this->moduleCaching->getConfigurationRepositoryCache()->clean([
				NetteCaching\Cache::Tags => [
					Types\ConfigurationType::WIDGETS_DISPLAY->value,
					$entity->getId()->toString(),
				],
			]);
		}
	}

}
