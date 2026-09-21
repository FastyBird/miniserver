<?php declare(strict_types = 1);

/**
 * DocumentsMapper.php
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
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Events as ApplicationEvents;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Ramsey\Uuid;
use Symfony\Component\EventDispatcher;
use TypeError;
use ValueError;
use function array_key_exists;
use function in_array;

/**
 * Module documents mapper events
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DocumentsMapper implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Configuration\Connectors\Properties\Repository $connectorsPropertiesRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesRepository,
		private readonly DevicesModels\States\ConnectorPropertiesManager $connectorPropertiesManager,
		private readonly DevicesModels\States\DevicePropertiesManager $devicePropertiesManager,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesManager,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEvents\PreLoad::class => 'preLoad',
		];
	}

	/**
	 * @param ApplicationEvents\PreLoad<ApplicationDocuments\Document> $event
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function preLoad(ApplicationEvents\PreLoad $event): void
	{
		if (
			!in_array(
				$event->getClass(),
				[
					Documents\Widgets\DataSources\ConnectorProperty::class,
					Documents\Widgets\DataSources\DeviceProperty::class,
					Documents\Widgets\DataSources\ChannelProperty::class,
				],
				true,
			)
			|| !array_key_exists('property', $event->getData())
			|| !$event->getData()['property'] instanceof Uuid\UuidInterface
		) {
			return;
		}

		$state = null;

		if ($event->getClass() === Documents\Widgets\DataSources\ConnectorProperty::class) {
			$findPropertyQuery = new DevicesQueries\Configuration\FindConnectorProperties();
			$findPropertyQuery->byId($event->getData()['property']);

			$property = $this->connectorsPropertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			if ($property instanceof DevicesDocuments\Connectors\Properties\Dynamic) {
				$state = $this->connectorPropertiesManager->readState($property);

				if ($state === null) {
					return;
				}
			}
		} elseif ($event->getClass() === Documents\Widgets\DataSources\DeviceProperty::class) {
			$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
			$findPropertyQuery->byId($event->getData()['property']);

			$property = $this->devicesPropertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			if (
				$property instanceof DevicesDocuments\Devices\Properties\Dynamic
				|| $property instanceof DevicesDocuments\Devices\Properties\Mapped
			) {
				$state = $this->devicePropertiesManager->readState($property);

				if ($state === null) {
					return;
				}
			}
		} else {
			$findPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
			$findPropertyQuery->byId($event->getData()['property']);

			$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			if (
				$property instanceof DevicesDocuments\Channels\Properties\Dynamic
				|| $property instanceof DevicesDocuments\Channels\Properties\Mapped
			) {
				$state = $this->channelPropertiesManager->readState($property);

				if ($state === null) {
					return;
				}
			}
		}

		$data = $event->getData();

		if (
			$property instanceof DevicesDocuments\Connectors\Properties\Variable
			|| $property instanceof DevicesDocuments\Devices\Properties\Variable
			|| $property instanceof DevicesDocuments\Channels\Properties\Variable
		) {
			$data['value'] = $property->getValue();

		} elseif ($state !== null && $state->isValid()) {
			$data['value'] = $state->getRead()->getExpectedValue() ?? $state->getRead()->getActualValue();
		}

		$event->setData($data);
	}

}
