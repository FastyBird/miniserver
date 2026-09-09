<?php declare(strict_types = 1);

/**
 * DevicesPresenter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 * @since          1.0.0
 *
 * @date           21.06.24
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
 * Devices presenter
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
class DevicesPresenter extends BasePresenter
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
		$this->loadConnectors();

		if ($id !== null) {
			$findDeviceQuery = new Queries\Configuration\FindDevices();
			$findDeviceQuery->byId(Uuid\Uuid::fromString($id));

			$device = $this->devicesRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				throw new Application\BadRequestException('Device not found');
			}

			$this->loadDevice($device);
			$this->loadChannels($device);

		} else {
			$this->loadDevices();
			$this->loadChannels();
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
	public function actionSetting(string $id): void
	{
		$this->loadConnectors();

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->byId(Uuid\Uuid::fromString($id));

		$device = $this->devicesRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			throw new Application\BadRequestException('Device not found');
		}

		$this->loadDevice($device);
		$this->loadChannels($device);
	}

}
