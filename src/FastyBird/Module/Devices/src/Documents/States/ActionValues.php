<?php declare(strict_types = 1);

/**
 * ActionValues.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           23.01.24
 */

namespace FastyBird\Module\Devices\Documents\States;

use DateTimeInterface;
use FastyBird\Core\Constants;
use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * Property set action value document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[Documents\Mapping\Document]
final readonly class ActionValues implements Documents\Document
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Button::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Switcher::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Cover::class),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('actual_value')]
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $actualValue = Constants::VALUE_NOT_SET,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Button::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Switcher::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Cover::class),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('expected_value')]
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $expectedValue = Constants::VALUE_NOT_SET,
	)
	{
	}

	public function getActualValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		return $this->actualValue;
	}

	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		return $this->expectedValue;
	}

	public function toArray(): array
	{
		$data = [];

		if ($this->getActualValue() !== Constants::VALUE_NOT_SET) {
			$data = array_merge($data, [
				'actual_value' => Utilities\Value::flattenValue($this->getActualValue()),
			]);
		}

		if ($this->getExpectedValue() !== Constants::VALUE_NOT_SET) {
			$data = array_merge($data, [
				'expected_value' => Utilities\Value::flattenValue($this->getExpectedValue()),
			]);
		}

		return $data;
	}

}
