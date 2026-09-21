<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           18.11.23
 */

namespace FastyBird\Connector\Shelly\Helpers;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use TypeError;
use ValueError;
use function assert;
use function is_string;

/**
 * Connector helper
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Connector
{

	public function __construct(
		private DevicesModels\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getClientMode(Documents\Connectors\Connector $connector): Types\ClientMode
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLIENT_MODE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		$value = $property?->getValue();

		if (is_string($value) && Types\ClientMode::tryFrom($value) !== null) {
			return Types\ClientMode::from($value);
		}

		throw new Exceptions\InvalidState('Connector mode is not configured');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCloudServerAddress(
		Documents\Connectors\Connector $connector,
	): string|null
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLOUD_SERVER);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCloudAuthKey(Documents\Connectors\Connector $connector): string|null
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLOUD_AUTH_KEY);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

}
