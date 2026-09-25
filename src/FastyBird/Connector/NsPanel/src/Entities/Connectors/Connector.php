<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\Connectors;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use TypeError;
use ValueError;
use function is_int;
use function is_string;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Connector extends DevicesEntities\Connectors\Connector
{

	public const TYPE = 'ns-panel-connector';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): Sources\Connector
	{
		return Sources\Connector::NS_PANEL;
	}

	/**
	 * @return array<Entities\Devices\Device>
	 */
	public function getDevices(): array
	{
		$devices = [];

		foreach (parent::getDevices() as $device) {
			if ($device instanceof Entities\Devices\Device) {
				$devices[] = $device;
			}
		}

		return $devices;
	}

	/**
	 * @throws NsPanelExceptions\InvalidArgument
	 */
	public function addDevice(DevicesEntities\Devices\Device $device): void
	{
		if (!$device instanceof Entities\Devices\Device) {
			throw new NsPanelExceptions\InvalidArgument('Provided device type is not valid');
		}

		parent::addDevice($device);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getPort(): int
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::PORT->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_int($property->getValue())
		) {
			return $property->getValue();
		}

		return NsPanel\Constants::DEFAULT_SERVER_PORT;
	}

	/**
	 * @throws NsPanelExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getClientMode(): Types\ClientMode
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Connectors\Properties\Property $property): bool => $property->getIdentifier() === Types\ConnectorPropertyIdentifier::CLIENT_MODE->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Connectors\Properties\Variable
			&& is_string($property->getValue())
			&& Types\ClientMode::tryFrom($property->getValue()) !== null
		) {
			return Types\ClientMode::from($property->getValue());
		}

		throw new NsPanelExceptions\InvalidState('Connector mode is not configured');
	}

}
