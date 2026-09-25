<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           03.12.23
 */

namespace FastyBird\Connector\FbMqtt\Helpers;

use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Documents;
use FastyBird\Connector\FbMqtt\Exceptions as FbMqttExceptions;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use TypeError;
use ValueError;
use function assert;
use function is_int;
use function is_string;

/**
 * Connector helper
 *
 * @package        FastyBird:FbMqttConnector!
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
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws FbMqttExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getProtocolVersion(
		Documents\Connectors\Connector $connector,
	): Types\ProtocolVersion
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PROTOCOL_VERSION);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		$value = $property?->getValue();

		if (is_string($value) && Types\ProtocolVersion::tryFrom($value) !== null) {
			return Types\ProtocolVersion::from($value);
		}

		throw new FbMqttExceptions\InvalidState('Connector protocol version is not configured');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getServerAddress(Documents\Connectors\Connector $connector): string
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::SERVER);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return FbMqtt\Constants::DEFAULT_SERVER_ADDRESS;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getServerPort(Documents\Connectors\Connector $connector): int
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::SECURED_PORT);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return FbMqtt\Constants::DEFAULT_SERVER_PORT;
		}

		$value = $property->getValue();
		assert(is_int($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getServerSecuredPort(Documents\Connectors\Connector $connector): int
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::SERVER);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return FbMqtt\Constants::DEFAULT_SERVER_PORT;
		}

		$value = $property->getValue();
		assert(is_int($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getUsername(Documents\Connectors\Connector $connector): string|null
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::USERNAME);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value) || $value === null);

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getPassword(Documents\Connectors\Connector $connector): string|null
	{
		$findPropertyQuery = new Queries\Configuration\FindConnectorVariableProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PASSWORD);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Connectors\Properties\Variable::class,
		);

		if ($property === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value) || $value === null);

		return $value;
	}

}
