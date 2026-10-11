<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Queries\Entities;

use FastyBird\Connector\Viera\Exceptions;
use FastyBird\Connector\Viera\Types;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find device properties entities query
 *
 * @template T of DevicesEntities\Devices\Properties\Property
 * @extends  DevicesQueries\Entities\FindDeviceProperties<T>
 */
class FindDeviceProperties extends DevicesQueries\Entities\FindDeviceProperties
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
