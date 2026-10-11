<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Ramsey\Uuid;
use function serialize;

/**
 * Find devices controls configuration query
 *
 * @template T of Documents\Devices\Controls\Control
 * @extends  QueryObject<T>
 */
final class FindDeviceControls extends QueryObject
{

	/** @var array<string> */
	private array $filter = [];

	public function __construct()
	{
		$this->filter[] = '.[?(@.device != "")]';
	}

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id =~ /(?i).*^' . $id->toString() . '*$/)]';
	}

	public function byName(string $name): void
	{
		$this->filter[] = '.[?(@.name =~ /(?i).*^' . $name . '*$/)]';
	}

	public function forDevice(Documents\Devices\Device $device): void
	{
		$this->filter[] = '.[?(@.device =~ /(?i).*^' . $device->getId()->toString() . '*$/)]';
	}

	public function byDeviceId(Uuid\UuidInterface $deviceId): void
	{
		$this->filter[] = '.[?(@.device =~ /(?i).*^' . $deviceId->toString() . '*$/)]';
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
