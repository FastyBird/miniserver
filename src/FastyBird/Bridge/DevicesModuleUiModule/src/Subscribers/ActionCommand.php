<?php declare(strict_types = 1);

/**
 * ActionCommand.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           10.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Subscribers;

use FastyBird\Bridge\DevicesModuleUiModule\Documents;
use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Ui\Events as UiEvents;
use FastyBird\Module\Ui\Types as UiTypes;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use Symfony\Component\EventDispatcher;
use TypeError;
use ValueError;

/**
 * Module documents mapper events
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionCommand implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly DevicesModels\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly DevicesModels\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			UiEvents\ActionCommandReceived::class => 'actionReceived',
		];
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function actionReceived(UiEvents\ActionCommandReceived $event): void
	{
		if (
			$event->getAction()->getAction() !== UiTypes\DataSourceAction::SET
			|| !$event->getDataSource() instanceof Documents\Widgets\DataSources\Property
		) {
			return;
		}

		$property = $event->getDataSource()->getProperty();
		$value = $event->getAction()->getExpectedValue();

		$data = [];

		if ($value !== Metadata\Constants::VALUE_NOT_SET) {
			$data[DevicesStates\Property::EXPECTED_VALUE_FIELD] = $value;
		}

		if ($data === []) {
			return;
		}

		if ($event->getDataSource() instanceof Documents\Widgets\DataSources\ConnectorProperty) {
			$this->handleConnectorAction($property, $data);

		} elseif ($event->getDataSource() instanceof Documents\Widgets\DataSources\DeviceProperty) {
			$this->handleDeviceAction($property, $data);

		} elseif ($event->getDataSource() instanceof Documents\Widgets\DataSources\ChannelProperty) {
			$this->handleChannelAction($property, $data);
		}
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleConnectorAction(
		Uuid\UuidInterface $propertyId,
		array $data,
	): void
	{
		$property = $this->connectorPropertiesConfigurationRepository->find($propertyId);

		if (!$property instanceof DevicesDocuments\Connectors\Properties\Dynamic) {
			return;
		}

		$this->connectorPropertiesStatesManager->write(
			$property,
			Utils\ArrayHash::from($data),
			MetadataTypes\Sources\Module::DEVICES,
		);
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleDeviceAction(
		Uuid\UuidInterface $propertyId,
		array $data,
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
			Utils\ArrayHash::from($data),
			MetadataTypes\Sources\Module::DEVICES,
		);
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleChannelAction(
		Uuid\UuidInterface $propertyId,
		array $data,
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
			Utils\ArrayHash::from($data),
			MetadataTypes\Sources\Module::DEVICES,
		);
	}

}
