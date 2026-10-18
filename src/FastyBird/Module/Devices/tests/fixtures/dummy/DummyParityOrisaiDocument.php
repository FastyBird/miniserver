<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use DateTimeInterface;
use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * The three fields of a property and of a state that took more than a type from orisai, with the rules they had when
 * orisai mapped them (the format and the value transformer of Devices\Documents\Property, the value of
 * Devices\Documents\States\StateValues), copied word for word. DummyParityValinorDocument is the same three fields as
 * Valinor reads them, and the parity test holds the two to the same answers
 */
final readonly class DummyParityOrisaiDocument implements Documents\Document, ObjectMapper\MappedObject
{

	/**
	 * @param string|array<mixed>|null $format
	 */
	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\StringValue(notEmpty: true),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\ArrayOf(
					item: new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\IntValue(),
						new ObjectMapper\Rules\FloatValue(),
						new ObjectMapper\Rules\StringValue(notEmpty: true),
						new ObjectMapper\Rules\ArrayOf(
							item: new ObjectMapper\Rules\AnyOf([
								new ObjectMapper\Rules\ArrayEnumValue(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									cases: [ValuesTypes\DataTypeShort::CHAR->value, ValuesTypes\DataTypeShort::UCHAR->value, ValuesTypes\DataTypeShort::SHORT->value, ValuesTypes\DataTypeShort::USHORT->value, ValuesTypes\DataTypeShort::INT->value, ValuesTypes\DataTypeShort::UINT->value, ValuesTypes\DataTypeShort::FLOAT->value, ValuesTypes\DataTypeShort::BOOLEAN->value, ValuesTypes\DataTypeShort::STRING->value, ValuesTypes\DataTypeShort::BUTTON->value, ValuesTypes\DataTypeShort::SWITCH->value, ValuesTypes\DataTypeShort::COVER->value],
								),
								new ObjectMapper\Rules\StringValue(notEmpty: true),
								new ObjectMapper\Rules\IntValue(),
								new ObjectMapper\Rules\FloatValue(),
								new ObjectMapper\Rules\BoolValue(),
							]),
							key: new ObjectMapper\Rules\IntValue(unsigned: true),
							minItems: 2,
							maxItems: 2,
						),
						new ObjectMapper\Rules\NullValue(castEmptyString: true),
					]),
					key: new ObjectMapper\Rules\IntValue(unsigned: true),
					minItems: 3,
					maxItems: 3,
				),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\AnyOf([
					new ObjectMapper\Rules\IntValue(),
					new ObjectMapper\Rules\FloatValue(),
					new ObjectMapper\Rules\ArrayOf(
						item: new ObjectMapper\Rules\AnyOf([
							new ObjectMapper\Rules\ArrayEnumValue(
								// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
								cases: [ValuesTypes\DataTypeShort::CHAR->value, ValuesTypes\DataTypeShort::UCHAR->value, ValuesTypes\DataTypeShort::SHORT->value, ValuesTypes\DataTypeShort::USHORT->value, ValuesTypes\DataTypeShort::INT->value, ValuesTypes\DataTypeShort::UINT->value, ValuesTypes\DataTypeShort::FLOAT->value],
							),
							new ObjectMapper\Rules\IntValue(),
							new ObjectMapper\Rules\FloatValue(),
						]),
						key: new ObjectMapper\Rules\IntValue(unsigned: true),
						minItems: 2,
						maxItems: 2,
					),
					new ObjectMapper\Rules\NullValue(castEmptyString: true),
				]),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
				minItems: 2,
				maxItems: 2,
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|array|null $format = null,
		#[ObjectMapper\Rules\AnyOf([
			new Rules\UuidValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_transformer')]
		private Uuid\UuidInterface|string|null $valueTransformer = null,
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
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value = null,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return ['format' => $this->format, 'value_transformer' => $this->valueTransformer, 'value' => $this->value];
	}

}
