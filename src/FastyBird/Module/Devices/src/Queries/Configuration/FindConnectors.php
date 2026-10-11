<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Ramsey\Uuid;
use function count;
use function implode;
use function serialize;

/**
 * Find connectors configuration query
 *
 * @template T of Documents\Connectors\Connector
 * @extends  QueryObject<T>
 */
class FindConnectors extends QueryObject
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
