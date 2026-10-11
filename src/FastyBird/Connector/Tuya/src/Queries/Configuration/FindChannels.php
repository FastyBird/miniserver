<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Queries\Configuration;

use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find device channels entities query
 *
 * @template T of Documents\Channels\Channel
 * @extends  DevicesQueries\Configuration\FindChannels<T>
 */
class FindChannels extends DevicesQueries\Configuration\FindChannels
{

	/**
	 * @phpstan-param Types\DataPoint $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
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
