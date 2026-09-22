<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\JsonApi\Hydrators\Fields;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use function is_bool;
use function strval;

/**
 * Entity boolean field
 */
final class BooleanField extends Field
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
	 * @throws Exceptions\JsonApiError
	 */
	public function getValue(JsonApi\Objects\IStandardObject $attributes): bool|null
	{
		$value = $attributes->get($this->getMappedName());

		if ($value === null) {
			return $this->isNullable ? null : false;
		}

		if (!is_bool($value)) {
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
