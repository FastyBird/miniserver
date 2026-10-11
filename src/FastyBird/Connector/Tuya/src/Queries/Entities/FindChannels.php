<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Queries\Entities;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use function sprintf;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Channels\Channel
 * @extends  DevicesQueries\Entities\FindChannels<T>
 */
class FindChannels extends DevicesQueries\Entities\FindChannels
{

	/**
	 * @phpstan-param Types\DataPoint $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function byIdentifier(Types\DataPoint|string $identifier): void
	{
		if (!$identifier instanceof Types\DataPoint) {
			throw new Exceptions\InvalidArgument(
				sprintf('Only instances of: %s are allowed', Types\DataPoint::class),
			);
		}

		parent::byIdentifier($identifier->value);
	}

}
