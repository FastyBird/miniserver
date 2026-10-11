<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets\Display;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use Neomerx\JsonApi;
use Override;
use function array_merge;

/**
 * Grouped button widget display entity schema
 *
 * @template T of Entities\Widgets\Displays\GroupedButton
 * @extends  Display<T>
 */
final class GroupedButton extends Display
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/display/' . Entities\Widgets\Displays\GroupedButton::TYPE;

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Widgets\Displays\GroupedButton::class;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'icon' => $resource->getIcon()?->value,
		]);
	}

}
