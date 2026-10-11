<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets\Display;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Gauge widget display entity schema
 *
 * @template T of Entities\Widgets\Displays\Gauge
 * @extends  Display<T>
 */
final class Gauge extends Display
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/display/' . Entities\Widgets\Displays\Gauge::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\Displays\Gauge::class;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'precision' => $resource->getPrecision(),
		]);
	}

}
