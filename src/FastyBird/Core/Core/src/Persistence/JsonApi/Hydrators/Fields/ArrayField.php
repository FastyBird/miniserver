<?php declare(strict_types = 1);

/**
 * ArrayField.php
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
use function is_array;
use function strval;

/**
 * Entity array field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ArrayField extends Field
{

	public function __construct(
		private readonly Localization\Translator $translator,
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
	 * @param JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 *
	 * @return array<mixed>|null
	 *
	 * @throws Exceptions\JsonApiError
	 */
	public function getValue(JsonApi\Objects\IStandardObject $attributes): array|null
	{
		$value = $attributes->get($this->getMappedName());

		if ($value instanceof JsonApi\Objects\IStandardObject) {
			return $value->toArray();
		}

		if ($value === null) {
			return $this->isNullable ? [] : null;
		}

		if (!is_array($value)) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//jsonApi.hydrator.invalidAttribute.heading')),
				strval($this->translator->translate('//jsonApi.hydrator.invalidAttribute.message')),
				[
					'pointer' => '/data/attributes/' . $this->getMappedName(),
				],
			);
		}

		return $value;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
