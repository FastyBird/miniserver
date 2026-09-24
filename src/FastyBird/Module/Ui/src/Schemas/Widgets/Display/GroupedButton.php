<?php declare(strict_types = 1);

/**
 * GroupedButton.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Ui\Schemas\Widgets\Display;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Grouped button widget display entity schema
 *
 * @template T of Entities\Widgets\Displays\GroupedButton
 * @extends  Display<T>
 *
 * @package         FastyBird:UIModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GroupedButton extends Display
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::UI->value . '/display/' . Entities\Widgets\Displays\GroupedButton::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

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
