<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\States;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;

/**
 * Property value document
 */
#[CoreDocuments\Mapping\Document]
final readonly class StateValues implements CoreDocuments\Document
{

	public function __construct(
		#[DevicesDocuments\Mapping\MapStateValue]
		#[Configurator\MapFromKey('actual_value')]
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $actualValue,
		#[DevicesDocuments\Mapping\MapStateValue]
		#[Configurator\MapFromKey('expected_value')]
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $expectedValue,
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

	#[Override]
	public function toArray(): array
	{
		return [
			'actual_value' => Utilities\Value::flattenValue($this->getActualValue()),
			'expected_value' => Utilities\Value::flattenValue($this->getExpectedValue()),
		];
	}

}
