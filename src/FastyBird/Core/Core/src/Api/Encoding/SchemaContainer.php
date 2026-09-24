<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding;

use FastyBird\Core\Api\Schemas;
use FastyBird\Core\Exceptions;
use Neomerx\JsonApi;
use Override;
use function interface_exists;
use function strrpos;
use function substr;

/**
 * Json:API schemas container
 *
 * @template     T of object
 */
final class SchemaContainer extends JsonApi\Schema\SchemaContainer
{

	private const string DOCTRINE_MARKER = '__CG__';

	private const int DOCTRINE_MARKER_LENGTH = 6;

	public function __construct()
	{
		parent::__construct(new JsonApi\Factories\Factory(), []);
	}

	/**
	 * @param Schemas\JsonApiSchema<T> $schema
	 */
	public function add(Schemas\JsonApiSchema $schema): void
	{
		$this->setProviderMapping($schema->getEntityClass(), $schema::class);
		$this->setResourceToJsonTypeMapping($schema->getType(), $schema->getEntityClass());
		$this->setCreatedProvider($schema->getEntityClass(), $schema);
	}

	/**
	 * @return Schemas\JsonApiSchema<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getSchemaByClassName(string $resourceType): Schemas\JsonApiSchema
	{
		$schema = $this->getSchemaByType($resourceType);

		if ($schema instanceof Schemas\JsonApiSchema) {
			return $schema;
		}

		throw new Exceptions\InvalidState('Schema for given resource could not be loaded');
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	protected function getResourceType($resource): string
	{
		if (
			interface_exists('\Doctrine\Persistence\Proxy')
			|| interface_exists('\Doctrine\Common\Persistence\Proxy')
		) {
			$class = $resource::class;

			$pos = strrpos($class, '\\' . self::DOCTRINE_MARKER . '\\');

			if ($pos === false) {
				return $class;
			}

			return substr($class, $pos + self::DOCTRINE_MARKER_LENGTH + 2);
		}

		return parent::getResourceType($resource);
	}

}
