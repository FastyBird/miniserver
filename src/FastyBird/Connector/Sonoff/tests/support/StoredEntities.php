<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Support;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use TypeError;
use ValueError;
use function array_map;
use function ksort;
use function sort;

/**
 * What the producers stored, as plain arrays without the generated identifiers and timestamps, so that a test can
 * pin it literally, field by field
 */
final class StoredEntities
{

	/**
	 * @return array<string, mixed>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function connector(DevicesEntities\Connectors\Connector $connector): array
	{
		return [
			'type' => $connector::getType(),
			'identifier' => $connector->getIdentifier(),
			'name' => $connector->getName(),
			'comment' => $connector->getComment(),
			'enabled' => $connector->isEnabled(),
			'properties' => self::properties($connector->getProperties()),
			'controls' => self::controls($connector->getControls()),
		];
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function device(DevicesEntities\Devices\Device $device): array
	{
		$channels = [];

		foreach ($device->getChannels() as $channel) {
			$channels[$channel->getIdentifier()] = [
				'type' => $channel::getType(),
				'name' => $channel->getName(),
				'comment' => $channel->getComment(),
				'properties' => self::properties($channel->getProperties()),
				'controls' => self::controls($channel->getControls()),
			];
		}

		ksort($channels);

		return [
			'type' => $device::getType(),
			'identifier' => $device->getIdentifier(),
			'name' => $device->getName(),
			'comment' => $device->getComment(),
			'connector' => $device->getConnector()->getIdentifier(),
			'parents' => array_map(
				static fn (DevicesEntities\Devices\Device $parent): string => $parent->getIdentifier(),
				$device->getParents(),
			),
			'properties' => self::properties($device->getProperties()),
			'controls' => self::controls($device->getControls()),
			'channels' => $channels,
		];
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function property(DevicesEntities\Property $property): array
	{
		$data = $property->toArray();

		unset(
			$data['id'],
			$data['created_at'],
			$data['updated_at'],
			$data['connector'],
			$data['device'],
			$data['channel'],
			$data['owner'],
		);

		return $data;
	}

	/**
	 * @param array<DevicesEntities\Property> $properties
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private static function properties(array $properties): array
	{
		$data = [];

		foreach ($properties as $property) {
			$data[$property->getIdentifier()] = self::property($property);
		}

		ksort($data);

		return $data;
	}

	/**
	 * @param array<DevicesEntities\Connectors\Controls\Control|DevicesEntities\Devices\Controls\Control|DevicesEntities\Channels\Controls\Control> $controls
	 *
	 * @return array<string>
	 */
	private static function controls(array $controls): array
	{
		$names = [];

		foreach ($controls as $control) {
			$names[] = $control->getName();
		}

		sort($names);

		return $names;
	}

}
