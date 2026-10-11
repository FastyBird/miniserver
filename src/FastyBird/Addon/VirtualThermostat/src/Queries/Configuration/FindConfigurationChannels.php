<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Queries\Configuration;

use FastyBird\Addon\VirtualThermostat\Documents;
use FastyBird\Addon\VirtualThermostat\Exceptions;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;
use function sprintf;

/**
 * Find device thermostat channels entities query
 *
 * @template T of Documents\Channels\Configuration
 * @extends  VirtualQueries\Configuration\FindChannels<T>
 */
class FindConfigurationChannels extends VirtualQueries\Configuration\FindChannels
{

	/**
	 * @phpstan-param Types\ChannelIdentifier $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
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
