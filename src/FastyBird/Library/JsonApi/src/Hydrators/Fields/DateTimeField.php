<?php declare(strict_types = 1);

/**
 * DateTime.php
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

namespace FastyBird\Library\JsonApi\Hydrators\Fields;

use DateTimeInterface;
use IPub\JsonAPIDocument;
use Nette\Utils;
use function is_scalar;

/**
 * Entity datetime field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
	 * @param JsonAPIDocument\Objects\IStandardObject<string, mixed> $attributes
	 */
	public function getValue(JsonAPIDocument\Objects\IStandardObject $attributes): DateTimeInterface|null
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
