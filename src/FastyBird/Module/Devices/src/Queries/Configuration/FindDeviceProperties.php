<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function count;
use function implode;
use function serialize;

/**
 * Find devices properties configuration query
 *
 * @template T of Documents\Devices\Properties\Property
 * @extends  QueryObject<T>
 */
class FindDeviceProperties extends QueryObject
{

	/** @var array<string> */
	protected array $filter = [];

	public function __construct()
	{
		$this->filter[] = '.[?(@.device != "")]';
	}

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id =~ /(?i).*^' . $id->toString() . '*$/)]';
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
			array_map(
				static fn (Documents\Devices\Device $device): string => $device->getId()->toString(),
				$devices,
			),
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

	public function forParent(Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Variable $parent): void
	{
		$this->filter[] = '.[?(@.parent =~ /(?i).*^' . $parent->getId()->toString() . '*$/)]';
	}

	public function byParentId(Uuid\UuidInterface $parentId): void
	{
		$this->filter[] = '.[?(@.parent =~ /(?i).*^' . $parentId->toString() . '*$/)]';
	}

	public function settable(bool $state): void
	{
		$this->filter[] = '.[?(@.settable == "' . ($state ? 'true' : 'false') . '")]';
	}

	public function queryable(bool $state): void
	{
		$this->filter[] = '.[?(@.queryable == "' . ($state ? 'true' : 'false') . '")]';
	}

	/**
	 * @throws JSONPath\JSONPathException
	 */
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
	public function toString(): string
	{
		try {
			return serialize(Utils\Json::encode($this->filter));
		} catch (Utils\JsonException) {
			throw new Exceptions\InvalidState('Cache key could not be generated');
		}
	}

}
