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
 * Find channels properties configuration query
 *
 * @template T of Documents\Channels\Properties\Property
 * @extends  QueryObject<T>
 */
class FindChannelProperties extends QueryObject
{

	/** @var array<string> */
	protected array $filter = [];

	public function __construct()
	{
		$this->filter[] = '.[?(@.channel != "")]';
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

	public function forChannel(Documents\Channels\Channel $channel): void
	{
		$this->filter[] = '.[?(@.channel =~ /(?i).*^' . $channel->getId()->toString() . '*$/)]';
	}

	/**
	 * @param array<Documents\Channels\Channel> $channels
	 */
	public function byChannels(array $channels): void
	{
		$this->filter[] = '.[?(@.channel in [' . (count($channels) > 0 ? implode(
			'","',
			array_map(
				static fn (Documents\Channels\Channel $channel): string => $channel->getId()->toString(),
				$channels,
			),
		) : '') . '])]';
	}

	public function byChannelId(Uuid\UuidInterface $channelId): void
	{
		$this->filter[] = '.[?(@.channel =~ /(?i).*^' . $channelId->toString() . '*$/)]';
	}

	/**
	 * @param array<Uuid\UuidInterface> $channelsId
	 */
	public function byChannelsId(array $channelsId): void
	{
		$this->filter[] = '.[?(@.channel in [' . (count(
			$channelsId,
		) > 0 ? implode(
			'","',
			array_map(static fn (Uuid\UuidInterface $id): string => $id->toString(), $channelsId),
		) : '') . '])]';
	}

	public function forParent(
		Documents\Channels\Properties\Dynamic|Documents\Channels\Properties\Variable $parent,
	): void
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
