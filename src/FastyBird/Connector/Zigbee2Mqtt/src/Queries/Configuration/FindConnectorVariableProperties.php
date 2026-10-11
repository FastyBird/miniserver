<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queries\Configuration;

use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Override;
use function sprintf;

/**
 * Find connector variable properties configuration query
 *
 * @template T of DevicesDocuments\Connectors\Properties\Variable
 * @extends  DevicesQueries\Configuration\FindConnectorVariableProperties<T>
 */
class FindConnectorVariableProperties extends DevicesQueries\Configuration\FindConnectorVariableProperties
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
