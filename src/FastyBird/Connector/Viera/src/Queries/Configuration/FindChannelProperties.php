<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Queries\Configuration;

use FastyBird\Connector\Viera\Exceptions;
use FastyBird\Connector\Viera\Types;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find channels properties configuration query
 *
 * @template T of DevicesDocuments\Channels\Properties\Property
 * @extends  DevicesQueries\Configuration\FindChannelProperties<T>
 */
class FindChannelProperties extends DevicesQueries\Configuration\FindChannelProperties
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
