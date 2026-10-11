<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Queries\Configuration;

use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find channel dynamic properties configuration query
 *
 * @template T of DevicesDocuments\Channels\Properties\Dynamic
 * @extends  DevicesQueries\Configuration\FindChannelDynamicProperties<T>
 */
class FindChannelDynamicProperties extends DevicesQueries\Configuration\FindChannelDynamicProperties
{

	/**
	 * @phpstan-param Types\ChannelPropertyIdentifier $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function byIdentifier(Types\ChannelPropertyIdentifier|string $identifier): void
	{
		if (!$identifier instanceof Types\ChannelPropertyIdentifier) {
			throw new Exceptions\InvalidArgument(
				sprintf('Only instances of: %s are allowed', Types\ChannelPropertyIdentifier::class),
			);
		}

		parent::byIdentifier($identifier->value);
	}

}
