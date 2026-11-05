<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Models\History;

use DateTimeInterface;
use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models as UiModels;
use FastyBird\Module\Ui\Types as UiTypes;
use Override;

/**
 * Reads the history of the property a data source is bound to, from the Devices module
 *
 * A data source bound to a mapped property shows its parent's series, as the history of the parent is what is
 * recorded: its values are not converted through the scale or the value transformer of the mapped property. A
 * data source that is not bound to a property, one bound to a property that does not exist, and one bound to a
 * variable property, which has no value over time, has no history.
 */
final readonly class PropertyReader implements UiModels\History\Reader
{

	public function __construct(
		private DevicesModels\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private DevicesModels\History\Repository $historyRepository,
	)
	{
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	#[Override]
	public function read(
		UiDocuments\Widgets\DataSources\DataSource $dataSource,
		DateTimeInterface $from,
		DateTimeInterface $to,
		int $points,
	): UiModels\History\Series|null
	{
		try {
			$property = $this->findProperty($dataSource);

			if ($property === null) {
				return null;
			}

			$series = $this->historyRepository->find($property->getId(), $from, $to, $points);
		} catch (DevicesExceptions\Exception | CoreExceptions\Exception $ex) {
			throw new UiExceptions\InvalidState('The history could not be read', $ex->getCode(), $ex);
		}

		return new UiModels\History\Series(
			$dataSource->getId()->toString(),
			$series->from,
			$series->to,
			$series->resolution,
			self::tier($series->tier),
			$property->getUnit(),
			$property->getDataType()->value,
			$series->time,
			$series->min,
			$series->max,
			$series->avg,
			$series->last,
		);
	}

	/**
	 * The property whose history the data source shows: the dynamic property it is bound to, or the parent of the
	 * mapped one, and null for anything else
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findProperty(
		UiDocuments\Widgets\DataSources\DataSource $dataSource,
	): DevicesDocuments\Connectors\Properties\Dynamic|DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Channels\Properties\Dynamic|null
	{
		if ($dataSource instanceof DevicesModuleUiModuleDocuments\Widgets\DataSources\ConnectorProperty) {
			$property = $this->connectorsPropertiesConfigurationRepository->find($dataSource->getProperty());

			return $property instanceof DevicesDocuments\Connectors\Properties\Dynamic ? $property : null;
		}

		if ($dataSource instanceof DevicesModuleUiModuleDocuments\Widgets\DataSources\DeviceProperty) {
			$property = $this->devicesPropertiesConfigurationRepository->find($dataSource->getProperty());

			if ($property instanceof DevicesDocuments\Devices\Properties\Mapped) {
				$property = $this->devicesPropertiesConfigurationRepository->find($property->getParent());
			}

			return $property instanceof DevicesDocuments\Devices\Properties\Dynamic ? $property : null;
		}

		if ($dataSource instanceof DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty) {
			$property = $this->channelsPropertiesConfigurationRepository->find($dataSource->getProperty());

			if ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
				$property = $this->channelsPropertiesConfigurationRepository->find($property->getParent());
			}

			return $property instanceof DevicesDocuments\Channels\Properties\Dynamic ? $property : null;
		}

		return null;
	}

	private static function tier(DevicesTypes\HistoryTier $tier): UiTypes\HistoryTier
	{
		return match ($tier) {
			DevicesTypes\HistoryTier::SAMPLES => UiTypes\HistoryTier::SAMPLES,
			DevicesTypes\HistoryTier::FIVE_MINUTES => UiTypes\HistoryTier::FIVE_MINUTES,
			DevicesTypes\HistoryTier::HOURS => UiTypes\HistoryTier::HOURS,
		};
	}

}
