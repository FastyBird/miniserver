<?php declare(strict_types = 1);

/**
 * StateEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           09.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Subscribers;

use FastyBird\Bridge\DevicesModuleUiModule\Documents;
use FastyBird\Bridge\DevicesModuleUiModule\Queries;
use FastyBird\Core\EventLoop\Application as ApplicationEventLoop;
use FastyBird\Core\Messaging\Exchange\Publisher as ExchangePublisher;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Caching as UiCaching;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models as UiModels;
use Nette;
use Nette\Caching;
use Symfony\Component\EventDispatcher;
use function array_map;
use function assert;

/**
 * Devices state entities events
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StateEntities implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly UiModels\Configuration\Widgets\DataSources\Repository $dataSourcesRepository,
		private readonly UiCaching\Container $uiModuleCaching,
		private readonly ApplicationEventLoop\Status $eventLoopStatus,
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangePublisher\Async\Publisher $asyncPublisher,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\ConnectorPropertyStateEntityCreated::class => 'stateCreated',
			DevicesEvents\ConnectorPropertyStateEntityUpdated::class => 'stateUpdated',
			DevicesEvents\DevicePropertyStateEntityCreated::class => 'stateCreated',
			DevicesEvents\DevicePropertyStateEntityUpdated::class => 'stateUpdated',
			DevicesEvents\ChannelPropertyStateEntityCreated::class => 'stateCreated',
			DevicesEvents\ChannelPropertyStateEntityUpdated::class => 'stateUpdated',
		];
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	public function stateCreated(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEvents\ConnectorPropertyStateEntityCreated|DevicesEvents\DevicePropertyStateEntityCreated|DevicesEvents\ChannelPropertyStateEntityCreated $event,
	): void
	{
		$this->processProperty($event->getProperty());
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	public function stateUpdated(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEvents\ConnectorPropertyStateEntityUpdated|DevicesEvents\DevicePropertyStateEntityUpdated|DevicesEvents\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$this->processProperty($event->getProperty());
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	private function processProperty(DevicesDocuments\Property $property): void
	{
		$findDataSources = new Queries\Configuration\FindWidgetDataSources();
		$findDataSources->forProperty($property);

		$dataSources = $this->dataSourcesRepository->findAllBy(
			$findDataSources,
			Documents\Widgets\DataSources\Property::class,
		);

		if ($dataSources === []) {
			return;
		}

		$this->uiModuleCaching->getConfigurationBuilderCache()->clean([
			Caching\Cache::Tags => array_map(
				static fn (Documents\Widgets\DataSources\Property $dataSource): string => $dataSource->getId()->toString(),
				$dataSources,
			),
		]);

		$this->uiModuleCaching->getConfigurationRepositoryCache()->clean([
			Caching\Cache::Tags => array_map(
				static fn (Documents\Widgets\DataSources\Property $dataSource): string => $dataSource->getId()->toString(),
				$dataSources,
			),
		]);

		foreach ($dataSources as $dataSource) {
			$findDataSources = new Queries\Configuration\FindWidgetDataSources();
			$findDataSources->byId($dataSource->getId());

			$dataSource = $this->dataSourcesRepository->findOneBy(
				$findDataSources,
				Documents\Widgets\DataSources\Property::class,
			);
			assert($dataSource !== null);

			$this->publishDocument($dataSource);
		}
	}

	private function publishDocument(
		Documents\Widgets\DataSources\Property $dataSource,
	): void
	{
		$this->getPublisher($this->eventLoopStatus->isRunning())->publish(
			MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE,
			Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
			$dataSource,
		);
	}

	private function getPublisher(bool $async): ExchangePublisher\Publisher|ExchangePublisher\Async\Publisher
	{
		return $async ? $this->asyncPublisher : $this->publisher;
	}

}
