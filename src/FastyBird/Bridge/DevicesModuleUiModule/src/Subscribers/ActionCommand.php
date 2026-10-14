<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Subscribers;

use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Ui\Events as UiEvents;
use FastyBird\Module\Ui\Types as UiTypes;
use Override;
use Ramsey\Uuid;
use Symfony\Component\EventDispatcher;
use TypeError;
use ValueError;

/**
 * Module documents mapper events
 */
final readonly class ActionCommand implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(
		private DevicesModels\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private DevicesModels\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private DevicesModels\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private DevicesModels\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private DevicesModels\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			UiEvents\ActionCommandReceived::class => 'actionReceived',
		];
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function actionReceived(UiEvents\ActionCommandReceived $event): void
	{
		if (
			$event->getAction()->getAction() !== UiTypes\DataSourceAction::SET
			|| !$event->getDataSource() instanceof DevicesModuleUiModuleDocuments\Widgets\DataSources\Property
		) {
			return;
		}

		$property = $event->getDataSource()->getProperty();
		$value = $event->getAction()->getExpectedValue();

		$data = DevicesStates\PropertyUpdate::create();

		if ($value !== Utilities\Value::NOT_SET) {
			$data = $data->withExpectedValue($value);
		}

		if ($data->isEmpty()) {
			return;
		}

		if ($event->getDataSource() instanceof DevicesModuleUiModuleDocuments\Widgets\DataSources\ConnectorProperty) {
			$this->handleConnectorAction($property, $data);

		} elseif ($event->getDataSource() instanceof DevicesModuleUiModuleDocuments\Widgets\DataSources\DeviceProperty) {
			$this->handleDeviceAction($property, $data);

		} elseif ($event->getDataSource() instanceof DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty) {
			$this->handleChannelAction($property, $data);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleConnectorAction(
		Uuid\UuidInterface $propertyId,
		DevicesStates\PropertyUpdate $data,
	): void
	{
		$property = $this->connectorPropertiesConfigurationRepository->find($propertyId);

		if (!$property instanceof DevicesDocuments\Connectors\Properties\Dynamic) {
			return;
		}

		$this->connectorPropertiesStatesManager->write(
			$property,
			$data,
			Sources\Module::DEVICES,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleDeviceAction(
		Uuid\UuidInterface $propertyId,
		DevicesStates\PropertyUpdate $data,
	): void
	{
		$property = $this->devicePropertiesConfigurationRepository->find($propertyId);

		if (
			!$property instanceof DevicesDocuments\Devices\Properties\Dynamic
			&& !$property instanceof DevicesDocuments\Devices\Properties\Mapped
		) {
			return;
		}

		$this->devicePropertiesStatesManager->write(
			$property,
			$data,
			Sources\Module::DEVICES,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleChannelAction(
		Uuid\UuidInterface $propertyId,
		DevicesStates\PropertyUpdate $data,
	): void
	{
		$property = $this->channelPropertiesConfigurationRepository->find($propertyId);

		if (
			!$property instanceof DevicesDocuments\Channels\Properties\Dynamic
			&& !$property instanceof DevicesDocuments\Channels\Properties\Mapped
		) {
			return;
		}

		$this->channelPropertiesStatesManager->write(
			$property,
			$data,
			Sources\Module::DEVICES,
		);
	}

}
