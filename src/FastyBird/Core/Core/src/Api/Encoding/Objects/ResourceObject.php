<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use function is_string;

/**
 * Resource
 */
final class ResourceObject
{

	private ResourceIdentifierObject $identifier;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private StandardObject $data)
	{
		if (
			(
				$data->has(Encoding\Document::KEYWORD_ID)
				&& !is_string($data->get(Encoding\Document::KEYWORD_ID))
			)
			|| !$data->has(Encoding\Document::KEYWORD_TYPE)
			|| !is_string($data->get(Encoding\Document::KEYWORD_TYPE))
		) {
			throw new Exceptions\InvalidArgument('Provided data object is not valid resource');
		}

		$this->identifier = new ResourceIdentifierObject($data);
	}

	public function getId(): string|null
	{
		return $this->identifier->getId();
	}

	public function getType(): string
	{
		return $this->identifier->getType();
	}

	public function hasAttributes(): bool
	{
		return $this->data->has(Encoding\Document::KEYWORD_ATTRIBUTES);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getAttributes(): StandardObject
	{
		$data = $this->data->get(Encoding\Document::KEYWORD_ATTRIBUTES);

		if (!$data instanceof StandardObject) {
			throw new Exceptions\Runtime('Data member is not an object.');
		}

		return $data;
	}

	public function hasRelationships(): bool
	{
		return $this->data->has(Encoding\Document::KEYWORD_RELATIONSHIPS);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getRelationships(): RelationshipObjectCollection
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_RELATIONSHIPS);

		if (!$raw instanceof StandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Relationships member is not an object.');
		}

		return RelationshipObjectCollection::create($raw);
	}

	public function hasLinks(): bool
	{
		return $this->data->has(Encoding\Document::KEYWORD_LINKS);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getLinks(): LinkObjectCollection
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_LINKS);

		if (!$raw instanceof StandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Links member is not an object.');
		}

		return LinkObjectCollection::create($raw);
	}

	public function hasMeta(): bool
	{
		return $this->data->has(Encoding\Document::KEYWORD_META);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getMeta(): MetaObjectCollection
	{
		$raw = $this->data->get(Encoding\Document::KEYWORD_META);

		if (!$raw instanceof StandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

}
