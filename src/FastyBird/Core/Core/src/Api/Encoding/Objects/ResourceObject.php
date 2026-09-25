<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use Override;
use function is_string;

/**
 * Resource
 */
final class ResourceObject implements IResourceObject
{

	private IResourceIdentifierObject $identifier;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private IStandardObject $data)
	{
		if (
			(
				$data->has(Encoding\IDocument::KEYWORD_ID)
				&& !is_string($data->get(Encoding\IDocument::KEYWORD_ID))
			)
			|| !$data->has(Encoding\IDocument::KEYWORD_TYPE)
			|| !is_string($data->get(Encoding\IDocument::KEYWORD_TYPE))
		) {
			throw new Exceptions\InvalidArgument('Provided data object is not valid resource');
		}

		$this->identifier = new ResourceIdentifierObject($data);
	}

	#[Override]
	public function getId(): string|null
	{
		return $this->identifier->getId();
	}

	#[Override]
	public function getType(): string
	{
		return $this->identifier->getType();
	}

	#[Override]
	public function hasAttributes(): bool
	{
		return $this->data->has(Encoding\IDocument::KEYWORD_ATTRIBUTES);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getAttributes(): IStandardObject
	{
		$data = $this->data->get(Encoding\IDocument::KEYWORD_ATTRIBUTES);

		if (!$data instanceof IStandardObject) {
			throw new Exceptions\Runtime('Data member is not an object.');
		}

		return $data;
	}

	#[Override]
	public function hasRelationships(): bool
	{
		return $this->data->has(Encoding\IDocument::KEYWORD_RELATIONSHIPS);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getRelationships(): IRelationshipObjectCollection
	{
		$raw = $this->data->get(Encoding\IDocument::KEYWORD_RELATIONSHIPS);

		if (!$raw instanceof IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Relationships member is not an object.');
		}

		return RelationshipObjectCollection::create($raw);
	}

	#[Override]
	public function hasLinks(): bool
	{
		return $this->data->has(Encoding\IDocument::KEYWORD_LINKS);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getLinks(): ILinkObjectCollection
	{
		$raw = $this->data->get(Encoding\IDocument::KEYWORD_LINKS);

		if (!$raw instanceof IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Links member is not an object.');
		}

		return LinkObjectCollection::create($raw);
	}

	#[Override]
	public function hasMeta(): bool
	{
		return $this->data->has(Encoding\IDocument::KEYWORD_META);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getMeta(): IMetaObjectCollection
	{
		$raw = $this->data->get(Encoding\IDocument::KEYWORD_META);

		if (!$raw instanceof IStandardObject && $raw !== null) {
			throw new Exceptions\Runtime('Meta member is not an object.');
		}

		return MetaObjectCollection::create($raw);
	}

}
