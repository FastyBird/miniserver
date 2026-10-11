<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Queries\Configuration;

use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find device properties entities query
 *
 * @template T of DevicesDocuments\Devices\Properties\Variable
 * @extends  DevicesQueries\Configuration\FindDeviceVariableProperties<T>
 */
final class FindDeviceVariableProperties extends DevicesQueries\Configuration\FindDeviceVariableProperties
{

	/**
	 * @phpstan-param Types\DevicePropertyIdentifier $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function byIdentifier(Types\DevicePropertyIdentifier|string $identifier): void
	{
		if (!$identifier instanceof Types\DevicePropertyIdentifier) {
			throw new Exceptions\InvalidArgument(
				sprintf('Only instances of: %s are allowed', Types\DevicePropertyIdentifier::class),
			);
		}

		parent::byIdentifier($identifier->value);
	}

}
