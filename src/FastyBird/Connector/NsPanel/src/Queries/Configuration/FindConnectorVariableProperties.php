<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Queries\Configuration;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;
use function sprintf;

/**
 * Find connector variable properties entities query
 *
 * @template T of DevicesDocuments\Connectors\Properties\Variable
 * @extends  FindConnectorProperties<T>
 */
final class FindConnectorVariableProperties extends FindConnectorProperties
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

		parent::byIdentifier($identifier);
	}

}
