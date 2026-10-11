<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Types;
use Fig\Http\Message\StatusCodeInterface;
use Override;
use TypeError;
use ValueError;
use function is_scalar;
use function strval;

/**
 * Button widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\Button>
 */
final class Button extends Display
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'icon',
	];

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\Button::class;
	}

	/**
	 * @throws Exceptions\JsonApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function hydrateIconAttribute(Objects\StandardObject $attributes): Types\WidgetIcon
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
