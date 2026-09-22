<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use function is_string;

/**
 * Resource
 */
class ResourceObject implements IResourceObject
{

	private IResourceIdentifierObject $identifier;

	public function __construct(private Objects\IStandardObject $data)
	{
		if (
			(
				$data->has(JsonApi\IDocument::KEYWORD_ID)
				&& !is_string($data->get(JsonApi\IDocument::KEYWORD_ID))
			)
			|| !$data->has(JsonApi\IDocument::KEYWORD_TYPE)
			|| !is_string($data->get(JsonApi\IDocument::KEYWORD_TYPE))
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
		return $this->data->has(JsonApi\IDocument::KEYWORD_ATTRIBUTES);
	}

	public function getAttributes(): Objects\IStandardObject
	{
		$data = $this->data->get(JsonApi\IDocument::KEYWORD_ATTRIBUTES);

		if (!$data instanceof Objects\IStandardObject) {
			throw new Exceptions\Runtime('Data member is not an object.');
		}

		return $data;
	}

	public function hasRelationships(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_RELATIONSHIPS);
	}

	public function getRelationships(): IRelationshipObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_RELATIONSHIPS);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Relationships member is not an object.');
		}

		return RelationshipObjectCollection::create($raw);
	}

	public function hasLinks(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_LINKS);
	}

	public function getLinks(): ILinkObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_LINKS);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Links member is not an object.');
		}

		return LinkObjectCollection::create($raw);
	}

	public function hasMeta(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_META);
	}

	public function getMeta(): IMetaObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_META);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

}
