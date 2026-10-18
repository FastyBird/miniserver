<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\States;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;
use function array_merge;

/**
 * Property set action value document
 */
#[CoreDocuments\Mapping\Document]
final readonly class ActionValues implements CoreDocuments\Document
{

	public function __construct(
		#[DevicesDocuments\Mapping\MapStateValue]
		#[Configurator\MapFromKey('actual_value')]
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $actualValue = Utilities\Value::NOT_SET,
		#[DevicesDocuments\Mapping\MapStateValue]
		#[Configurator\MapFromKey('expected_value')]
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $expectedValue = Utilities\Value::NOT_SET,
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
		$data = [];

		if ($this->getActualValue() !== Utilities\Value::NOT_SET) {
			$data = array_merge($data, [
				'actual_value' => Utilities\Value::flattenValue($this->getActualValue()),
			]);
		}

		if ($this->getExpectedValue() !== Utilities\Value::NOT_SET) {
			$data = array_merge($data, [
				'expected_value' => Utilities\Value::flattenValue($this->getExpectedValue()),
			]);
		}

		return $data;
	}

}
