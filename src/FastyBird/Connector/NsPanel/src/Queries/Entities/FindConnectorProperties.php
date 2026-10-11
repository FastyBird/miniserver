<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Queries\Entities;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find connector properties entities query
 *
 * @template T of DevicesEntities\Connectors\Properties\Property
 * @extends  DevicesQueries\Entities\FindConnectorProperties<T>
 */
class FindConnectorProperties extends DevicesQueries\Entities\FindConnectorProperties
{

	/**
	 * @phpstan-param Types\ConnectorPropertyIdentifier $identifier
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function byIdentifier(Types\ConnectorPropertyIdentifier|string $identifier): void
	{
		if (!$identifier instanceof Types\ConnectorPropertyIdentifier) {
			throw new Exceptions\InvalidArgument(
				sprintf('Only instances of: %s are allowed', Types\ConnectorPropertyIdentifier::class),
			);
		}

		parent::byIdentifier($identifier->value);
	}

}
