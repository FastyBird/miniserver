<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Queries\Configuration;

use FastyBird\Connector\Viera\Documents;
use FastyBird\Connector\Viera\Exceptions;
use FastyBird\Connector\Viera\Types;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find device channels entities query
 *
 * @template T of Documents\Channels\Channel
 * @extends  DevicesQueries\Configuration\FindChannels<T>
 */
final class FindChannels extends DevicesQueries\Configuration\FindChannels
{

	/**
	 * @phpstan-param Types\ChannelType $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function byIdentifier(Types\ChannelType|string $identifier): void
	{
		if (!$identifier instanceof Types\ChannelType) {
			throw new Exceptions\InvalidArgument(
				sprintf('Only instances of: %s are allowed', Types\ChannelType::class),
			);
		}

		parent::byIdentifier($identifier->value);
	}

}
