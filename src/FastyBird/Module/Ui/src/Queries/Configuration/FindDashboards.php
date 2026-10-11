<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Queries\Configuration;

use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Ramsey\Uuid;
use function serialize;

/**
 * Find dashboards configuration query
 *
 * @template T of Documents\Dashboards\Dashboard
 * @extends  QueryObject<T>
 */
class FindDashboards extends QueryObject
{

	/** @var array<string> */
	private array $filter = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = '.[?(@.id =~ /(?i).*^' . $id->toString() . '*$/)]';
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i).*^' . $identifier . '*$/)]';
	}

	public function byName(string|null $name): void
	{
		$this->filter[] = $name === null ? '.[?(@.name == null)]' : '.[?(@.name =~ /(?i).*^' . $name . '*$/)]';
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
