<?php declare(strict_types = 1);

/**
 * Builder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           13.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration;

use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Types;
use Flow\JSONPath;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Throwable;
use TypeError;
use ValueError;
use function assert;

/**
 * Configuration builder
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Builder
{

	public function __construct(
		private readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly Models\Entities\Connectors\Controls\ControlsRepository $connectorsControlsRepository,
		private readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly Models\Entities\Devices\Controls\ControlsRepository $devicesControlsRepository,
		private readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly Models\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly Models\Entities\Channels\Controls\ControlsRepository $channelsControlsRepository,
		private readonly Caching\Container $moduleCaching,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function load(Types\ConfigurationType $type, bool $force = false): JSONPath\JSONPath
	{
		try {
			if ($force) {
				$this->moduleCaching->getConfigurationBuilderCache()->remove($type->value);
			}

			$data = $this->moduleCaching->getConfigurationBuilderCache()->load(
				$type->value,
				fn (): JSONPath\JSONPath => new JSONPath\JSONPath($this->build($type)),
				[
					NetteCaching\Cache::Tags => [$type->value],
				],
			);
			assert($data instanceof JSONPath\JSONPath);

			return $data;
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Module configuration could not be read', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws InvalidArgumentException
	 */
	private function build(Types\ConfigurationType $type): array
	{
		$data = [];

		if ($type === Types\ConfigurationType::CONNECTORS) {
			foreach ($this->connectorsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CONNECTORS_PROPERTIES) {
			foreach ($this->connectorsPropertiesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CONNECTORS_CONTROLS) {
			foreach ($this->connectorsControlsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::DEVICES) {
			foreach ($this->devicesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::DEVICES_PROPERTIES) {
			foreach ($this->devicesPropertiesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::DEVICES_CONTROLS) {
			foreach ($this->devicesControlsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CHANNELS) {
			foreach ($this->channelsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CHANNELS_PROPERTIES) {
			foreach ($this->channelsPropertiesRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		} elseif ($type === Types\ConfigurationType::CHANNELS_CONTROLS) {
			foreach ($this->channelsControlsRepository->findAll() as $item) {
				$data[] = $item->toArray();
			}
		}

		return $data;
	}

}
