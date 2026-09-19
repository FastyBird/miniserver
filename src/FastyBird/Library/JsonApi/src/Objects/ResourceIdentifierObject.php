<?php declare(strict_types = 1);

/**
 * ResourceIdentifierObject.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.0.1
 *
 * @date           05.05.18
 */

namespace FastyBird\Library\JsonApi\Objects;

use FastyBird\Library\JsonApi;
use FastyBird\Library\JsonApi\Exceptions;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Resource identifier object
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ResourceIdentifierObject implements IResourceIdentifierObject
{

	private string $type;

	private string|null $id;

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

	public function getId(): string|null
	{
		return $this->id;
	}

	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isType($typeOrTypes): bool
	{
		return in_array($this->type, is_array($typeOrTypes) ? $typeOrTypes : [$typeOrTypes], true);
	}

	/**
	 * {@inheritDoc}
	 */
	public function mapType(array $types): string
	{
		if (array_key_exists($this->type, $types)) {
			return $types[$this->type];
		}

		throw new Exceptions\Runtime(sprintf('Type "%s" is not in the supplied map.', $this->type));
	}

	public function isSame(IResourceIdentifierObject $identifier): bool
	{
		return $this->type === $identifier->getType() &&
			$this->id === $identifier->getId();
	}

	public function toString(): string
	{
		return sprintf('%s:%s', $this->type, $this->id);
	}

	public function __toString(): string
	{
		return $this->toString();
	}

}
