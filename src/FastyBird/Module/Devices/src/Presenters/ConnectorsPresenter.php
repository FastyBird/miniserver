<?php declare(strict_types = 1);

/**
 * ConnectorsPresenter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 * @since          1.0.0
 *
 * @date           30.06.24
 */

namespace FastyBird\Module\Devices\Presenters;

use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Nette\Application;
use Nette\Utils;
use Ramsey\Uuid;
use TypeError;
use ValueError;

/**
 * Connectors presenter
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
class ConnectorsPresenter extends BasePresenter
{

	use TConnectors;
	use TDevices;
	use TChannels;

	public function __construct(
		protected readonly Models\Configuration\Connectors\Repository $connectorsRepository,
		protected readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesRepository,
		protected readonly Models\Configuration\Connectors\Controls\Repository $connectorControlsRepository,
		protected readonly Models\Configuration\Devices\Repository $devicesRepository,
		protected readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesRepository,
		protected readonly Models\Configuration\Devices\Controls\Repository $deviceControlsRepository,
		protected readonly Models\Configuration\Channels\Repository $channelsRepository,
		protected readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesRepository,
		protected readonly Models\Configuration\Channels\Controls\Repository $channelControlsRepository,
	)
	{
		parent::__construct();
	}

	/**
	 * @throws Application\BadRequestException
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function actionDefault(string|null $id): void
	{
		if ($id !== null) {
			$findConnectorQuery = new Queries\Configuration\FindConnectors();
			$findConnectorQuery->byId(Uuid\Uuid::fromString($id));

			$connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

			if ($connector === null) {
				throw new Application\BadRequestException('Connector not found');
			}

			$this->loadConnector($connector);
			$this->loadDevices($connector);

		} else {
			$this->loadConnectors();
			$this->loadDevices();
		}
	}

	/**
	 * @throws Application\BadRequestException
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @Secured\Role(manager,administrator)
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function actionSetting(string $id, string|null $deviceId = null, string|null $channelId = null): void
	{
		$this->loadConnectors();

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId(Uuid\Uuid::fromString($id));

		$connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			throw new Application\BadRequestException('Connector not found');
		}

		$this->loadConnector($connector);
		$this->loadDevices($connector);

		if ($deviceId !== null) {
			$findDeviceQuery = new Queries\Configuration\FindDevices();
			$findDeviceQuery->byId(Uuid\Uuid::fromString($deviceId));
			$findDeviceQuery->forConnector($connector);

			$device = $this->devicesRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				throw new Application\BadRequestException('Device not found');
			}

			$this->loadDevice($device);

			if ($channelId !== null) {
				$findChannelQuery = new Queries\Configuration\FindChannels();
				$findChannelQuery->byId(Uuid\Uuid::fromString($channelId));
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel === null) {
					throw new Application\BadRequestException('Channel not found');
				}

				$this->loadChannel($channel);
			}
		}
	}

}
