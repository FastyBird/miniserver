<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\JsonApi\Hydrators\Fields;

use BackedEnum;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use ValueError;
use function call_user_func;
use function is_callable;
use function strval;

/**
 * Entity backed enum field
 */
final class BackedEnumField extends Field
{

	public function __construct(
		private readonly Localization\Translator $translator,
		private readonly string $typeClass,
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
	public function getValue(JsonApi\Objects\IStandardObject $attributes): BackedEnum|null
	{
		$value = $attributes->get($this->getMappedName());

		if ($value === null) {
			return null;
		}

		$callable = [$this->typeClass, 'from'];

		if (!is_callable($callable)) {
			return null;
		}

		try {
			$result = call_user_func($callable, $value);
		} catch (ValueError) {
			throw new Exceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//jsonApi.hydrator.invalidAttribute.heading')),
				strval($this->translator->translate('//jsonApi.hydrator.invalidAttribute.message')),
				[
					'pointer' => '/data/attributes/' . $this->getMappedName(),
				],
			);
		}

		return $result instanceof BackedEnum ? $result : null;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
