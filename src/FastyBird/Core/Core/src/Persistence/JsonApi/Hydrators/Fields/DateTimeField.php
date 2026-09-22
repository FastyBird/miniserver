<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\JsonApi\Hydrators\Fields;

use DateTimeInterface;
use FastyBird\Core\Encoding\JsonApi;
use Nette\Utils;
use function is_scalar;

/**
 * Entity datetime field
 */
final class DateTimeField extends Field
{

	public function __construct(
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
	 */
	public function getValue(JsonApi\Objects\IStandardObject $attributes): DateTimeInterface|null
	{
		$value = $attributes->get($this->getMappedName());

		if ($value !== null && is_scalar($value)) {
			$date = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, (string) $value);

			if ($date instanceof DateTimeInterface && $date->format(DateTimeInterface::ATOM) === $value) {
				return $date;
			}
		}

		return null;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
