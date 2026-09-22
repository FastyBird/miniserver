<?php declare(strict_types = 1);

/**
 * Number.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Core\Persistence\JsonApi\Hydrators\Fields;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use function is_numeric;
use function is_scalar;
use function strval;

/**
 * Entity numeric field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
	 * @param  JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 *
	 * @throws Exceptions\JsonApiError
	 */
	public function getValue(JsonApi\Objects\IStandardObject $attributes): float|int|null
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
