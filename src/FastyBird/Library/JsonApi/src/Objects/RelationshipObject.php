<?php declare(strict_types = 1);

/**
 * RelationshipObject.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.2.0
 *
 * @date           19.05.21
 */

namespace FastyBird\Library\JsonApi\Objects;

use FastyBird\Library\JsonApi;
use FastyBird\Library\JsonApi\Exceptions;
use FastyBird\Library\JsonApi\Objects;
use function is_array;
use function is_string;

/**
 * Relationship object
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class RelationshipObject implements IRelationshipObject
{

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

	public function hasData(): bool
	{
		return $this->data->has(JsonApi\IDocument::KEYWORD_DATA);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getData()
	{
		if ($this->isHasMany()) {
			return $this->getIdentifiers();
		} elseif ($this->isHasOne()) {
			return $this->hasIdentifier() ? $this->getIdentifier() : null;
		}

		throw new Exceptions\Runtime('No data member or data member is not a valid relationship.');
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

	public function isHasMany(): bool
	{
		return is_array($this->data->get(JsonApi\IDocument::KEYWORD_DATA));
	}

	public function isHasOne(): bool
	{
		if (!$this->data->has(JsonApi\IDocument::KEYWORD_DATA)) {
			return false;
		}

		$data = $this->data->get(JsonApi\IDocument::KEYWORD_DATA);

		return $data === null || $data instanceof Objects\IStandardObject;
	}

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

	public function hasIdentifier(): bool
	{
		$data = $this->data->get(JsonApi\IDocument::KEYWORD_DATA);

		return $data instanceof Objects\IStandardObject
			&& $data->has(JsonApi\IDocument::KEYWORD_TYPE)
			&& $data->has(JsonApi\IDocument::KEYWORD_ID);
	}

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
