<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use function is_array;
use function is_string;

/**
 * Relationship object
 */
final class RelationshipObject
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private StandardObject $data)
	{
		if (
			!$data->has(Encoding\Document::KEYWORD_LINKS)
			&& !$data->has(Encoding\Document::KEYWORD_DATA)
			&& !$data->has(Encoding\Document::KEYWORD_META)
		) {
			throw new Exceptions\InvalidArgument('Provided data object is not valid relationship object');
		}
	}

	public function hasLinks(): bool
	{
		return $this->data->has(Encoding\Document::KEYWORD_LINKS);
	}

	/**
	 * @phpstan-return LinkObjectCollection
	 *
	 * @return LinkObjectCollection<string, LinkObject|string>
	 *
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

	public function hasData(): bool
	{
		return $this->data->has(Encoding\Document::KEYWORD_DATA);
	}

	/**
	 * @phpstan-return ResourceIdentifierCollection<int, ResourceIdentifierObject>|ResourceIdentifierObject|null
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getData(): ResourceIdentifierCollection|ResourceIdentifierObject|null
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
		return $this->data->has(Encoding\Document::KEYWORD_META);
	}

	/**
	 * @phpstan-return MetaObjectCollection<string, MetaObject>
	 *
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

	public function isHasMany(): bool
	{
		return is_array($this->data->get(Encoding\Document::KEYWORD_DATA));
	}

	public function isHasOne(): bool
	{
		if (!$this->data->has(Encoding\Document::KEYWORD_DATA)) {
			return false;
		}

		$data = $this->data->get(Encoding\Document::KEYWORD_DATA);

		return $data === null || $data instanceof StandardObject;
	}

	/**
	 * @phpstan-return ResourceIdentifierCollection<int, ResourceIdentifierObject>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getIdentifiers(): ResourceIdentifierCollection
	{
		if (!$this->isHasMany()) {
			throw new Exceptions\Runtime('No data member or data member is not a valid has-many relationship.');
		}

		$data = $this->data->get(Encoding\Document::KEYWORD_DATA);

		if (!is_array($data)) {
			throw new Exceptions\Runtime('Data member has invalid format');
		}

		return ResourceIdentifierCollection::create($data);
	}

	public function hasIdentifier(): bool
	{
		$data = $this->data->get(Encoding\Document::KEYWORD_DATA);

		return $data instanceof StandardObject
			&& $data->has(Encoding\Document::KEYWORD_TYPE)
			&& $data->has(Encoding\Document::KEYWORD_ID);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function getIdentifier(): ResourceIdentifierObject
	{
		if (!$this->isHasOne()) {
			throw new Exceptions\Runtime('No data member or data member is not a valid has-one relationship.');
		}

		if (!$this->hasIdentifier()) {
			throw new Exceptions\Runtime('No resource identifier - relationship is empty.');
		}

		$data = $this->data->get(Encoding\Document::KEYWORD_DATA);

		if (!$data instanceof StandardObject) {
			throw new Exceptions\Runtime('Data member has invalid format');
		}

		$type = $data->get(Encoding\Document::KEYWORD_TYPE);
		$id = $data->get(Encoding\Document::KEYWORD_ID);

		if (!is_string($type) || !is_string($id)) {
			throw new Exceptions\Runtime('Data member has invalid format');
		}

		return new ResourceIdentifierObject($data);
	}

}
