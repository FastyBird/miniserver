<?php declare(strict_types = 1);

/**
 * TChannels.php
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

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Nette\Application;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_map;
use function array_merge;

/**
 * Channels loader trait
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Models\Configuration\Channels\Repository $channelsRepository
 * @property-read Models\Configuration\Channels\Properties\Repository $channelPropertiesRepository
 * @property-read Models\Configuration\Channels\Controls\Repository $channelControlsRepository
 * @property Application\UI\Template $template
 */
trait TChannels
{

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function loadChannels(Documents\Devices\Device|null $device = null): void
	{
		$findChannelsQuery = new Queries\Configuration\FindChannels();

		if ($device !== null) {
			$findChannelsQuery->forDevice($device);
		}

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

		$this->template->channels = Utils\Json::encode(array_map(
			static fn (Documents\Channels\Channel $channel): array => $channel->toArray(),
			$channels,
		));

		$this->template->channelsProperties = Utils\Json::encode(array_merge(...array_map(
			function (Documents\Channels\Channel $channel): array {
				$findChannelsPropertiesQuery = new Queries\Configuration\FindChannelProperties();
				$findChannelsPropertiesQuery->forChannel($channel);

				$properties = $this->channelPropertiesRepository->findAllBy($findChannelsPropertiesQuery);

				return array_map(
					static fn (Documents\Channels\Properties\Property $property): array => $property->toArray(),
					$properties,
				);
			},
			$channels,
		)));

		$this->template->channelsControls = Utils\Json::encode(array_merge(...array_map(
			function (Documents\Channels\Channel $channel): array {
				$findChannelsControlsQuery = new Queries\Configuration\FindChannelControls();
				$findChannelsControlsQuery->forChannel($channel);

				$controls = $this->channelControlsRepository->findAllBy($findChannelsControlsQuery);

				return array_map(
					static fn (Documents\Channels\Controls\Control $control): array => $control->toArray(),
					$controls,
				);
			},
			$channels,
		)));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function loadChannel(Documents\Channels\Channel $channel): void
	{
		$this->template->channel = Utils\Json::encode($channel->toArray());

		$findChannelsPropertiesQuery = new Queries\Configuration\FindChannelProperties();
		$findChannelsPropertiesQuery->forChannel($channel);

		$properties = $this->channelPropertiesRepository->findAllBy($findChannelsPropertiesQuery);

		$this->template->channelProperties = Utils\Json::encode(
			array_map(
				static fn (Documents\Channels\Properties\Property $property): array => $property->toArray(),
				$properties,
			),
		);

		$findChannelsControlsQuery = new Queries\Configuration\FindChannelControls();
		$findChannelsControlsQuery->forChannel($channel);

		$controls = $this->channelControlsRepository->findAllBy($findChannelsControlsQuery);

		$this->template->channelControls = Utils\Json::encode(
			array_map(
				static fn (Documents\Channels\Controls\Control $control): array => $control->toArray(),
				$controls,
			),
		);
	}

}
