<?php declare(strict_types = 1);

/**
 * TConnectors.php
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

use FastyBird\Core\Exceptions as ApplicationExceptions;
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
 * Connectors loader trait
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Models\Configuration\Connectors\Repository $connectorsRepository
 * @property-read Models\Configuration\Connectors\Properties\Repository $connectorPropertiesRepository
 * @property-read Models\Configuration\Connectors\Controls\Repository $connectorControlsRepository
 * @property Application\UI\Template $template
 */
trait TConnectors
{

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function loadConnectors(): void
	{
		$findConnectorsQuery = new Queries\Configuration\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery);

		$this->template->connectors = Utils\Json::encode(array_map(
			static fn (Documents\Connectors\Connector $connector): array => $connector->toArray(),
			$connectors,
		));

		$this->template->connectorsProperties = Utils\Json::encode(array_merge(...array_map(
			function (Documents\Connectors\Connector $connector): array {
				$findConnectorsPropertiesQuery = new Queries\Configuration\FindConnectorProperties();
				$findConnectorsPropertiesQuery->forConnector($connector);

				$properties = $this->connectorPropertiesRepository->findAllBy($findConnectorsPropertiesQuery);

				return array_map(
					static fn (Documents\Connectors\Properties\Property $property): array => $property->toArray(),
					$properties,
				);
			},
			$connectors,
		)));

		$this->template->connectorsControls = Utils\Json::encode(array_merge(...array_map(
			function (Documents\Connectors\Connector $connector): array {
				$findConnectorsControlsQuery = new Queries\Configuration\FindConnectorControls();
				$findConnectorsControlsQuery->forConnector($connector);

				$controls = $this->connectorControlsRepository->findAllBy($findConnectorsControlsQuery);

				return array_map(
					static fn (Documents\Connectors\Controls\Control $control): array => $control->toArray(),
					$controls,
				);
			},
			$connectors,
		)));
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function loadConnector(Documents\Connectors\Connector $connector): void
	{
		$this->template->connector = Utils\Json::encode($connector->toArray());

		$findConnectorsPropertiesQuery = new Queries\Configuration\FindConnectorProperties();
		$findConnectorsPropertiesQuery->forConnector($connector);

		$properties = $this->connectorPropertiesRepository->findAllBy($findConnectorsPropertiesQuery);

		$this->template->connectorProperties = Utils\Json::encode(
			array_map(
				static fn (Documents\Connectors\Properties\Property $property): array => $property->toArray(),
				$properties,
			),
		);

		$findConnectorsControlsQuery = new Queries\Configuration\FindConnectorControls();
		$findConnectorsControlsQuery->forConnector($connector);

		$controls = $this->connectorControlsRepository->findAllBy($findConnectorsControlsQuery);

		$this->template->connectorControls = Utils\Json::encode(
			array_map(
				static fn (Documents\Connectors\Controls\Control $control): array => $control->toArray(),
				$controls,
			),
		);
	}

}
