<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Subscribers;

use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Bridge\DevicesModuleUiModule\Queries;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Exchange\Publisher\Async;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Caching as UiCaching;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models as UiModels;
use Nette\Caching as NetteCaching;
use Override;
use Symfony\Component\EventDispatcher;
use function array_map;
use function assert;

/**
 * Devices state entities events
 */
final readonly class StateEntities implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(
		private UiModels\Configuration\Widgets\DataSources\Repository $dataSourcesRepository,
		private UiCaching\Container $uiModuleCaching,
		private EventLoop\Status $eventLoopStatus,
		private Publisher\MessagePublisher $publisher,
		private Async\MessagePublisher $asyncPublisher,
	)
	{
	}

	#[Override]
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
		$this->processProperty($event->getProperty(), $event->getSource());
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	public function stateUpdated(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEvents\ConnectorPropertyStateEntityUpdated|DevicesEvents\DevicePropertyStateEntityUpdated|DevicesEvents\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$this->processProperty($event->getProperty(), $event->getSource());
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	private function processProperty(DevicesDocuments\Property $property, Sources\Source $source): void
	{
		$findDataSources = new Queries\Configuration\FindWidgetDataSources();
		$findDataSources->forProperty($property);

		$dataSources = $this->dataSourcesRepository->findAllBy(
			$findDataSources,
			DevicesModuleUiModuleDocuments\Widgets\DataSources\Property::class,
		);

		if ($dataSources === []) {
			return;
		}

		$this->uiModuleCaching->getConfigurationBuilderCache()->clean([
			NetteCaching\Cache::Tags => array_map(
				static fn (DevicesModuleUiModuleDocuments\Widgets\DataSources\Property $dataSource): string => $dataSource->getId()->toString(),
				$dataSources,
			),
		]);

		$this->uiModuleCaching->getConfigurationRepositoryCache()->clean([
			NetteCaching\Cache::Tags => array_map(
				static fn (DevicesModuleUiModuleDocuments\Widgets\DataSources\Property $dataSource): string => $dataSource->getId()->toString(),
				$dataSources,
			),
		]);

		foreach ($dataSources as $dataSource) {
			$findDataSources = new Queries\Configuration\FindWidgetDataSources();
			$findDataSources->byId($dataSource->getId());

			$dataSource = $this->dataSourcesRepository->findOneBy(
				$findDataSources,
				DevicesModuleUiModuleDocuments\Widgets\DataSources\Property::class,
			);
			assert($dataSource !== null);

			// The data source document is the widget's state, built to carry the state's value (the
			// expected one, if it has one). With the Devices source the state is a command's, and a
			// command is not pushed to the widgets, as the SocketsBridges do not forward it: they see
			// the result when the target reports it. The caches above are cleaned whatever the
			// source, so that what is read from them stays current.
			if ($source === Sources\Module::DEVICES) {
				continue;
			}

			$this->publishDocument($dataSource);
		}
	}

	private function publishDocument(
		DevicesModuleUiModuleDocuments\Widgets\DataSources\Property $dataSource,
	): void
	{
		$this->getPublisher($this->eventLoopStatus->running)->publish(
			Sources\Bridge::DEVICES_MODULE_UI_MODULE,
			Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
			$dataSource,
		);
	}

	private function getPublisher(bool $async): Publisher\MessagePublisher|Async\MessagePublisher
	{
		return $async ? $this->asyncPublisher : $this->publisher;
	}

}
