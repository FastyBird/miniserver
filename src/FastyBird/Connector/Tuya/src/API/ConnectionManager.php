<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Documents as TuyaDocuments;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions as TuyaExceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Connector\Tuya\ValueObjects;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_map;
use function assert;
use function is_string;

/**
 * API connections manager
 */
final class ConnectionManager
{

	/** @var array<string, LocalApi> */
	private array $localConnections = [];

	private OpenApi|null $cloudApiConnection = null;

	private OpenPulsar|null $cloudWsConnection = null;

	public function __construct(
		private readonly LocalApiFactory $localApiFactory,
		private readonly OpenApiFactory $openApiFactory,
		private readonly OpenPulsarFactory $openPulsarFactory,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly CoreDocuments\Mapping\Mapper $objectMapper,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidState
	 * @throws TuyaExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getLocalConnection(TuyaDocuments\Devices\Device $device): LocalApi
	{
		$connector = $device->getConnector();
		assert($connector instanceof Entities\Connectors\Connector);

		if (!array_key_exists($device->getId()->toString(), $this->localConnections)) {
			$findChildrenDevicesQuery = new Queries\Configuration\FindDevices();
			$findChildrenDevicesQuery->forParent($device);

			$children = $this->devicesConfigurationRepository->findAllBy(
				$findChildrenDevicesQuery,
				TuyaDocuments\Devices\Device::class,
			);

			assert(is_string($this->deviceHelper->getLocalKey($device)));
			assert(is_string($this->deviceHelper->getIpAddress($device)));

			$connection = $this->localApiFactory->create(
				$device->getIdentifier(),
				null,
				null,
				$this->deviceHelper->getLocalKey($device),
				$this->deviceHelper->getIpAddress($device),
				$this->deviceHelper->getProtocolVersion($device),
				array_map(
					function (TuyaDocuments\Devices\Device $child): ValueObjects\LocalChild {
						assert(is_string($this->deviceHelper->getNodeId($child)));

						try {
							return $this->objectMapper->map(
								[
									'identifier' => $child->getIdentifier(),
									'node_id' => $this->deviceHelper->getNodeId($child),
									'type' => Types\LocalDeviceType::ZIGBEE->value,
								],
								ValueObjects\LocalChild::class,
							);
						} catch (Throwable $ex) {
							throw new TuyaExceptions\Runtime('Connection could not be configured', $ex->getCode(), $ex);
						}
					},
					$children,
				),
			);

			$this->localConnections[$device->getId()->toString()] = $connection;
		}

		return $this->localConnections[$device->getId()->toString()];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCloudApiConnection(TuyaDocuments\Connectors\Connector $connector): OpenApi
	{
		if ($this->cloudApiConnection === null) {
			assert(is_string($this->connectorHelper->getAccessId($connector)));
			assert(is_string($this->connectorHelper->getAccessSecret($connector)));

			$this->cloudApiConnection = $this->openApiFactory->create(
				$connector->getIdentifier(),
				$this->connectorHelper->getAccessId($connector),
				$this->connectorHelper->getAccessSecret($connector),
				$this->connectorHelper->getOpenApiEndpoint($connector),
			);
		}

		return $this->cloudApiConnection;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCloudWsConnection(TuyaDocuments\Connectors\Connector $connector): OpenPulsar
	{
		if ($this->cloudWsConnection === null) {
			assert(is_string($this->connectorHelper->getAccessId($connector)));
			assert(is_string($this->connectorHelper->getAccessSecret($connector)));

			$this->cloudWsConnection = $this->openPulsarFactory->create(
				$connector->getIdentifier(),
				$this->connectorHelper->getAccessId($connector),
				$this->connectorHelper->getAccessSecret($connector),
				$this->connectorHelper->getOpenPulsarTopic($connector),
				$this->connectorHelper->getOpenPulsarEndpoint($connector),
			);
		}

		return $this->cloudWsConnection;
	}

	public function __destruct()
	{
		foreach ($this->localConnections as $key => $connection) {
			$connection->disconnect();

			unset($this->localConnections[$key]);
		}

		$this->cloudApiConnection?->disconnect();
		$this->cloudWsConnection?->disconnect();
	}

}
