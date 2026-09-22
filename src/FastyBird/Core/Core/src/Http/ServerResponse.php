<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

use FastyBird\Core\Exceptions;
use function array_key_exists;
use function func_num_args;
use function sprintf;

/**
 * Extended HTTP response, carries a JSON:API entity attribute alongside the PSR-7 payload
 */
class ServerResponse extends Response
{

	/** @var array<mixed> */
	protected array $attributes = [];

	/**
	 * @return array<mixed>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getEntity(): Entity|null
	{
		$entity = $this->getAttribute(ResponseAttributes::ATTR_ENTITY, null);

		return $entity instanceof Entity ? $entity : null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getAttribute(string $name, mixed $default = null): mixed
	{
		if (!$this->hasAttribute($name)) {
			if (func_num_args() < 2) {
				throw new Exceptions\InvalidState(sprintf('No attribute "%s" found', $name));
			}

			return $default;
		}

		return $this->attributes[$name];
	}

	public function hasAttribute(string $name): bool
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * @return static
	 */
	public function withEntity(Entity $entity): self
	{
		return $this->withAttribute(ResponseAttributes::ATTR_ENTITY, $entity);
	}

	/**
	 * @return static
	 */
	public function withAttribute(string $name, mixed $value): self
	{
		$new = clone $this;
		$new->attributes[$name] = $value;

		return $new;
	}

}
