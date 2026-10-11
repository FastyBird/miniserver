<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Queries\Configuration;

use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find channel variable properties configuration query
 *
 * @template T of DevicesDocuments\Channels\Properties\Variable
 * @extends  DevicesQueries\Configuration\FindChannelVariableProperties<T>
 */
class FindChannelVariableProperties extends DevicesQueries\Configuration\FindChannelVariableProperties
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
