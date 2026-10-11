<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Schemas\Connectors\Properties;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Types;
use Neomerx\JsonApi;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Connector property entity schema
 *
 * @template T of Entities\Connectors\Properties\Variable
 * @extends Property<T>
 */
final class Variable extends Property
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::DEVICES->value . '/property/connector/' . Types\PropertyType::VARIABLE->value;

	public function getEntityClass(): string
	{
		return Entities\Connectors\Properties\Variable::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, (string|bool|int|float|array<string>|array<int, (int|float|array<int, (string|int|float|null)>|null)>|array<int, array<int, (string|array<int, (string|int|float|bool)>|null)>>|null)>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @throws InvalidArgumentException
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'value' => Utilities\Value::flattenValue($resource->getValue()),
		]);
	}

}
