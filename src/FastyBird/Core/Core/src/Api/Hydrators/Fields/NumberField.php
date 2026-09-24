<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators\Fields;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use function is_numeric;
use function is_scalar;
use function strval;

/**
 * Entity numeric field
 */
final class NumberField extends Field
{

	public function __construct(
		private readonly Localization\Translator $translator,
		private readonly bool $isDecimal,
		private readonly bool $isNullable,
		string $mappedName,
		string $fieldName,
		bool $isRequired,
		bool $isWritable,
	)
	{
		parent::__construct($mappedName, $fieldName, $isRequired, $isWritable);
	}

	/**
	 * @param  Objects\IStandardObject<string, mixed> $attributes
	 *
	 * @throws Exceptions\JsonApiError
	 */
	public function getValue(Objects\IStandardObject $attributes): float|int|null
	{
		$value = $attributes->get($this->getMappedName());

		if ($value === null || !is_scalar($value)) {
			return null;
		}

		if (!is_numeric($value)) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//jsonApi.hydrator.invalidAttribute.heading')),
				strval($this->translator->translate('//jsonApi.hydrator.invalidAttribute.message')),
				[
					'pointer' => '/data/attributes/' . $this->getMappedName(),
				],
			);
		}

		return $this->isDecimal ? (float) $value : (int) $value;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
