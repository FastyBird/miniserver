<?php declare(strict_types = 1);

/**
 * TDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 * @since          1.0.0
 *
 * @date           22.06.24
 */

namespace FastyBird\Module\Devices\Presenters;

use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Nette\Application;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_map;
use function array_merge;

/**
 * Devices loader trait
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Models\Configuration\Devices\Repository $devicesRepository
 * @property-read Models\Configuration\Devices\Properties\Repository $devicePropertiesRepository
 * @property-read Models\Configuration\Devices\Controls\Repository $deviceControlsRepository
 * @property Application\UI\Template $template
 */
trait TDevices
{

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function loadDevices(Documents\Connectors\Connector|null $connector = null): void
	{
		$findDevicesQuery = new Queries\Configuration\FindDevices();

		if ($connector !== null) {
			$findDevicesQuery->forConnector($connector);
		}

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery);

		$this->template->devices = Utils\Json::encode(array_map(
			static fn (Documents\Devices\Device $device): array => $device->toArray(),
			$devices,
		));

		$this->template->devicesProperties = Utils\Json::encode(array_merge(...array_map(
			function (Documents\Devices\Device $device): array {
				$findDevicesPropertiesQuery = new Queries\Configuration\FindDeviceProperties();
				$findDevicesPropertiesQuery->forDevice($device);

				$properties = $this->devicePropertiesRepository->findAllBy($findDevicesPropertiesQuery);

				return array_map(
					static fn (Documents\Devices\Properties\Property $property): array => $property->toArray(),
					$properties,
				);
			},
			$devices,
		)));

		$this->template->devicesControls = Utils\Json::encode(array_merge(...array_map(
			function (Documents\Devices\Device $device): array {
				$findDevicesControlsQuery = new Queries\Configuration\FindDeviceControls();
				$findDevicesControlsQuery->forDevice($device);

				$controls = $this->deviceControlsRepository->findAllBy($findDevicesControlsQuery);

				return array_map(
					static fn (Documents\Devices\Controls\Control $control): array => $control->toArray(),
					$controls,
				);
			},
			$devices,
		)));
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function loadDevice(Documents\Devices\Device $device): void
	{
		$this->template->device = Utils\Json::encode($device->toArray());

		$findDevicesPropertiesQuery = new Queries\Configuration\FindDeviceProperties();
		$findDevicesPropertiesQuery->forDevice($device);

		$properties = $this->devicePropertiesRepository->findAllBy($findDevicesPropertiesQuery);

		$this->template->deviceProperties = Utils\Json::encode(
			array_map(
				static fn (Documents\Devices\Properties\Property $property): array => $property->toArray(),
				$properties,
			),
		);

		$findDevicesControlsQuery = new Queries\Configuration\FindDeviceControls();
		$findDevicesControlsQuery->forDevice($device);

		$controls = $this->deviceControlsRepository->findAllBy($findDevicesControlsQuery);

		$this->template->deviceControls = Utils\Json::encode(
			array_map(
				static fn (Documents\Devices\Controls\Control $control): array => $control->toArray(),
				$controls,
			),
		);
	}

}
