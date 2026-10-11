<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Override;
use Ramsey\Uuid;
use function array_map;
use function count;
use function implode;
use function serialize;

/**
 * Find channels configuration query
 *
 * @template T of Documents\Channels\Channel
 * @extends  QueryObject<T>
 */
class FindChannels extends QueryObject
{

	/** @var array<string> */
	private array $filter = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id =~ /(?i).*^' . $id->toString() . '*$/)]';
	}

	/**
	 * @interal
	 */
	public function byType(string|int $type): void
	{
		$this->filter[] = '.[?(@.type =~ /(?i).*^' . $type . '*$/)]';
	}

	/**
	 * @param array<string> $types
	 */
	public function byTypes(array $types): void
	{
		$this->filter[] = '.[?(@.type in [' . (count($types) > 0 ? ('"' . implode('","', $types) . '"') : '') . '])]';
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i).*^' . $identifier . '*$/)]';
	}

	public function startWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i).*^' . $identifier . '*[\w\d\-_]+$/)]';
	}

	public function endWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /^[\w\d\-_]+(?i).*' . $identifier . '*$/)]';
	}

	public function forDevice(Documents\Devices\Device $device): void
	{
		$this->filter[] = '.[?(@.device =~ /(?i).*^' . $device->getId()->toString() . '*$/)]';
	}

	/**
	 * @param array<Documents\Devices\Device> $devices
	 */
	public function byDevices(array $devices): void
	{
		$this->filter[] = '.[?(@.device in [' . (count($devices) > 0 ? implode(
			'","',
			array_map(static fn (Documents\Devices\Device $device): string => $device->getId()->toString(), $devices),
		) : '') . '])]';
	}

	public function byDeviceId(Uuid\UuidInterface $deviceId): void
	{
		$this->filter[] = '.[?(@.device =~ /(?i).*^' . $deviceId->toString() . '*$/)]';
	}

	/**
	 * @param array<Uuid\UuidInterface> $devicesId
	 */
	public function byDevicesId(array $devicesId): void
	{
		$this->filter[] = '.[?(@.device in [' . (count(
			$devicesId,
		) > 0 ? implode(
			'","',
			array_map(static fn (Uuid\UuidInterface $id): string => $id->toString(), $devicesId),
		) : '') . '])]';
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function byDeviceIdentifier(string $deviceIdentifier): void
	{
		throw new Exceptions\NotImplemented(
			'Query by "byDeviceIdentifier" is not supported by this type of repository',
		);
	}

	public function withProperties(): void
	{
		$this->filter[] = '.[?(@.properties > 0)]';
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function withSettableProperties(): void
	{
		throw new Exceptions\NotImplemented(
			'Query by "withSettableProperties" is not supported by this type of repository',
		);
	}

	/**
	 * @throws JSONPath\JSONPathException
	 */
	#[Override]
	protected function doCreateQuery(JSONPath\JSONPath $repository): JSONPath\JSONPath
	{
		$filtered = $repository;

		foreach ($this->filter as $filter) {
			$filtered = $filtered->find($filter);
		}

		return $filtered;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function toString(): string
	{
		try {
			return serialize(Utils\Json::encode($this->filter));
		} catch (Utils\JsonException) {
			throw new Exceptions\InvalidState('Cache key could not be generated');
		}
	}

}
