<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Configuration;

use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use Flow\JSONPath;
use Nette\Utils;
use Override;
use Ramsey\Uuid;
use function bin2hex;
use function count;
use function implode;
use function preg_replace_callback;
use function serialize;

/**
 * Find devices configuration query
 *
 * @template T of Documents\Devices\Device
 * @extends  QueryObject<T>
 */
class FindDevices extends QueryObject
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

	/**
	 * The identifier is matched as it is, ignoring case, the way the database compares it, not as a pattern
	 */
	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i)^' . self::literal($identifier) . '\z/)]';
	}

	public function startWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /(?i).*^' . $identifier . '*[\w\d\-_]+$/)]';
	}

	public function endWithIdentifier(string $identifier): void
	{
		$this->filter[] = '.[?(@.identifier =~ /^[\w\d\-_]+(?i).*' . $identifier . '*$/)]';
	}

	public function forConnector(Documents\Connectors\Connector $connector): void
	{
		$this->filter[] = '.[?(@.connector =~ /(?i).*^' . $connector->getId()->toString() . '*$/)]';
	}

	public function byConnectorId(Uuid\UuidInterface $connectorId): void
	{
		$this->filter[] = '.[?(@.connector =~ /(?i).*^' . $connectorId->toString() . '*$/)]';
	}

	public function forParent(Documents\Devices\Device $parent): void
	{
		$this->filter[] = '.[?(@.parents in "' . $parent->getId()->toString() . '")]';
	}

	public function withoutParents(): void
	{
		$this->filter[] = '.[?(@.parents == 0)]';
	}

	public function withParents(): void
	{
		$this->filter[] = '.[?(@.parents > 0)]';
	}

	public function forChild(Documents\Devices\Device $child): void
	{
		$this->filter[] = '.[?(@.children in "' . $child->getId()->toString() . '")]';
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function withSettableChannelProperties(string $deviceIdentifier): void
	{
		throw new Exceptions\NotImplemented(
			'Query by "withSettableChannelProperties" is not supported by this type of repository',
		);
	}

	public function withChannels(): void
	{
		$this->filter[] = '.[?(@.channels > 0)]';
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

	/**
	 * Every character but a letter, a digit, `-` and `_` is written as its byte value: none of them can then mean
	 * anything to the pattern, nor open or close a bracket of the path expression the pattern sits in
	 */
	private static function literal(string $value): string
	{
		return (string) preg_replace_callback(
			'/[^A-Za-z0-9\-_]/',
			static fn (array $match): string => '\\x' . bin2hex($match[0]),
			$value,
		);
	}

}
