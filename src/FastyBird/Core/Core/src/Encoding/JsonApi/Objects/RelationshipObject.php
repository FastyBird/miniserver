<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use Override;
use function is_array;
use function is_string;

/**
 * Relationship object
 */
final class RelationshipObject implements IRelationshipObject
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private Objects\IStandardObject $data)
	{
		if (
			!$data->has(JsonApi\IDocument::KEYWORD_LINKS)
			&& !$data->has(JsonApi\IDocument::KEYWORD_DATA)
			&& !$data->has(JsonApi\IDocument::KEYWORD_META)
		) {
			throw new Exceptions\InvalidArgument('Provided data object is not valid relationship object');
		}
	}

	#[Override]
	public function hasLinks(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_LINKS);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getLinks(): ILinkObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_LINKS);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Links member is not an object.');
		}

		return LinkObjectCollection::create($raw);
	}

	#[Override]
	public function hasData(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_DATA);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getData(): IResourceIdentifierCollection|IResourceIdentifierObject|null
	{
		if ($this->isHasMany()) {
			return $this->getIdentifiers();
		} elseif ($this->isHasOne()) {
			return $this->hasIdentifier() ? $this->getIdentifier() : null;
		}

		throw new Exceptions\Runtime('No data member or data member is not a valid relationship.');
	}

	#[Override]
	public function hasMeta(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_META);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getMeta(): IMetaObjectCollection
	{
		$raw = $this->data->get(JsonApi\IDocument::KEYWORD_META);

		if (!$raw instanceof Objects\IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

	#[Override]
	public function isHasMany(): bool
	{
		return is_array($this->data->get(JsonApi\IDocument::KEYWORD_DATA));
	}

	#[Override]
	public function isHasOne(): bool
	{
		if (!$this->data->has(JsonApi\IDocument::KEYWORD_DATA)) {
			return false;
		}

		$data = $this->data->get(JsonApi\IDocument::KEYWORD_DATA);

		return $data === null || $data instanceof Objects\IStandardObject;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getIdentifiers(): IResourceIdentifierCollection
	{
		if (!$this->isHasMany()) {
			throw new Exceptions\Runtime('No data member or data member is not a valid has-many relationship.');
		}

		$data = $this->data->get(JsonApi\IDocument::KEYWORD_DATA);

		if (!is_array($data)) {
			throw new Exceptions\Runtime('Data member has invalid format');
		}

		return ResourceIdentifierCollection::create($data);
	}

	#[Override]
	public function hasIdentifier(): bool
	{
		$data = $this->data->get(JsonApi\IDocument::KEYWORD_DATA);

		return $data instanceof Objects\IStandardObject
			&& $data->has(JsonApi\IDocument::KEYWORD_TYPE)
			&& $data->has(JsonApi\IDocument::KEYWORD_ID);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getIdentifier(): IResourceIdentifierObject
	{
		if (!$this->isHasOne()) {
			throw new Exceptions\Runtime('No data member or data member is not a valid has-one relationship.');
		}

		if (!$this->hasIdentifier()) {
			throw new Exceptions\Runtime('No resource identifier - relationship is empty.');
		}

		$data = $this->data->get(JsonApi\IDocument::KEYWORD_DATA);

		if (!$data instanceof Objects\IStandardObject) {
			throw new Exceptions\Runtime('Data member has invalid format');
		}

		$type = $data->get(JsonApi\IDocument::KEYWORD_TYPE);
		$id = $data->get(JsonApi\IDocument::KEYWORD_ID);

		if (!is_string($type) || !is_string($id)) {
			throw new Exceptions\Runtime('Data member has invalid format');
		}

		return new ResourceIdentifierObject($data);
	}

}
