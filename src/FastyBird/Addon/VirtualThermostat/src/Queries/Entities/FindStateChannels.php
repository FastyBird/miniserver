<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Queries\Entities;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Exceptions;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;
use Override;
use function sprintf;

/**
 * Find device thermostat channels entities query
 *
 * @template T of Entities\Channels\State
 * @extends  VirtualQueries\Entities\FindChannels<T>
 */
class FindStateChannels extends VirtualQueries\Entities\FindChannels
{

	/**
	 * @phpstan-param Types\ChannelIdentifier $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function byIdentifier(Types\ChannelIdentifier|string $identifier): void
	{
		if (!$identifier instanceof Types\ChannelIdentifier) {
			throw new Exceptions\InvalidArgument(
				sprintf('Only instances of: %s are allowed', Types\ChannelIdentifier::class),
			);
		}

		parent::byIdentifier($identifier->value);
	}

}
