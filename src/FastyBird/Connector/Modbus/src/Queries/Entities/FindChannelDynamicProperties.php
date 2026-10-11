<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Queries\Entities;

use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find channel dynamic properties entities query
 *
 * @template T of DevicesEntities\Channels\Properties\Dynamic
 * @extends  DevicesQueries\Entities\FindChannelDynamicProperties<T>
 */
final class FindChannelDynamicProperties extends DevicesQueries\Entities\FindChannelDynamicProperties
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
