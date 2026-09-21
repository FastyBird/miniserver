<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           21.11.23
 */

namespace FastyBird\Connector\Virtual\Helpers;

use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use TypeError;
use ValueError;
use function assert;
use function floatval;
use function is_numeric;

/**
 * Device helper
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Device
{

	public function __construct(
		protected DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getStateProcessingDelay(Documents\Devices\Device $device): float
	{
		$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE_PROCESSING_DELAY);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return Entities\Devices\Device::STATE_PROCESSING_DELAY;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

}
