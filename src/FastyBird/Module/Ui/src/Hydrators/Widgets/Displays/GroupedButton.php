<?php declare(strict_types = 1);

/**
 * GroupedButton.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Types;
use Fig\Http\Message\StatusCodeInterface;
use TypeError;
use ValueError;
use function is_scalar;
use function strval;

/**
 * Grouped button widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\GroupedButton>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GroupedButton extends Display
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'icon',
	];

	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\GroupedButton::class;
	}

	/**
	 * @throws Exceptions\JsonApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function hydrateIconAttribute(Objects\IStandardObject $attributes): Types\WidgetIcon
	{
		if (
			!is_scalar($attributes->get('icon'))
			|| (string) $attributes->get('icon') === ''
		) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.heading')),
				strval($this->translator->translate('//ui-module.base.messages.missingAttribute.message')),
				[
					'pointer' => '/data/attributes/icon',
				],
			);
		}

		if (Types\WidgetIcon::tryFrom((string) $attributes->get('icon')) === null) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.invalidAttribute.heading')),
				strval($this->translator->translate('//ui-module.base.messages.invalidAttribute.message')),
				[
					'pointer' => '/data/attributes/icon',
				],
			);
		}

		return Types\WidgetIcon::from((string) $attributes->get('icon'));
	}

}
