<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\Configuration;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
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
 */
final readonly class Builder
{

	public function __construct(
		private Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private Models\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private Models\Entities\Connectors\Controls\ControlsRepository $connectorsControlsRepository,
		private Models\Entities\Devices\DevicesRepository $devicesRepository,
		private Models\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private Models\Entities\Devices\Controls\ControlsRepository $devicesControlsRepository,
		private Models\Entities\Channels\ChannelsRepository $channelsRepository,
		private Models\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private Models\Entities\Channels\Controls\ControlsRepository $channelsControlsRepository,
		private DevicesCaching\Container $moduleCaching,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
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
			throw new DevicesExceptions\InvalidState('Module configuration could not be read', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
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
