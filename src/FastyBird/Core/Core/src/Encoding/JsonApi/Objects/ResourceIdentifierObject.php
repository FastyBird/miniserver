<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions;
use Override;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Resource identifier object
 */
final class ResourceIdentifierObject implements IResourceIdentifierObject
{

	private string $type;

	private string|null $id;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(IStandardObject $data)
	{
		$type = $data->get(JsonApi\IDocument::KEYWORD_TYPE);
		$id = $data->get(JsonApi\IDocument::KEYWORD_ID);

		if (!is_string($type) || (!is_string($id) && $id !== null)) {
			throw new Exceptions\InvalidArgument('Data member has invalid format');
		}

		$this->type = $type;
		$this->id = $id;
	}

	#[Override]
	public function getId(): string|null
	{
		return $this->id;
	}

	#[Override]
	public function getType(): string
	{
		return $this->type;
	}

	#[Override]
	public function isType(string|array $typeOrTypes): bool
	{
		return in_array($this->type, is_array($typeOrTypes) ? $typeOrTypes : [$typeOrTypes], true);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function mapType(array $types): string
	{
		if (array_key_exists($this->type, $types)) {
			return $types[$this->type];
		}

		throw new Exceptions\Runtime(sprintf('Type "%s" is not in the supplied map.', $this->type));
	}

	#[Override]
	public function isSame(IResourceIdentifierObject $identifier): bool
	{
		return $this->type === $identifier->getType() &&
			$this->id === $identifier->getId();
	}

	#[Override]
	public function toString(): string
	{
		return sprintf('%s:%s', $this->type, $this->id);
	}

	#[Override]
	public function __toString(): string
	{
		return $this->toString();
	}

}
