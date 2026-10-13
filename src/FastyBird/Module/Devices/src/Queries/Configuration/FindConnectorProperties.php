<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Override;
use Ramsey\Uuid;
use function serialize;

/**
 * Find connectors properties configuration query
 *
 * @template T of Documents\Connectors\Properties\Property
 * @extends  QueryObject<T>
 */
class FindConnectorProperties extends QueryObject
{

	/** @var array<string> */
	protected array $filter = [];

	public function __construct()
	{
		$this->filter[] = '.[?(@.connector != "")]';
	}

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id =~ /(?i).*^' . $id->toString() . '*$/)]';
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i)^' . self::literal($identifier) . '\z/)]';
	}

	public function startWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i)^' . self::literal($identifier) . '[\w\d\-_]*\z/)]';
	}

	public function endWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /^[\w\d\-_]+(?i).*' . self::literal($identifier) . '\z/)]';
	}

	public function forConnector(Documents\Connectors\Connector $connector): void
	{
		$this->filter[] = '.[?(@.connector =~ /(?i).*^' . $connector->getId()->toString() . '*$/)]';
	}

	public function byConnectorId(Uuid\UuidInterface $connectorId): void
	{
		$this->filter[] = '.[?(@.connector =~ /(?i).*^' . $connectorId->toString() . '*$/)]';
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
