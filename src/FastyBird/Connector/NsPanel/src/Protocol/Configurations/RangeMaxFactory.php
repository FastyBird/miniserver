<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Override;
use Ramsey\Uuid;
use function assert;
use function floatval;
use function is_float;
use function is_int;
use function is_numeric;

/**
 * NS panel range max value capability configuration factory
 */
final class RangeMaxFactory implements ConfigurationFactory
{

	#[Override]
	public function create(
		Uuid\UuidInterface $id,
		NsPanelTypes\Configuration $type,
		ValuesTypes\DataType $dataType,
		Protocol\Capabilities\Capability $capability,
		float|int|bool|string|array|null $value,
		array|null $validValues = [],
		int|null $maxLength = null,
		float|null $minValue = null,
		float|null $maxValue = null,
		float|null $minStep = null,
		string|null $unit = null,
	): RangeMax
	{
		assert(is_int($value) || is_float($value));
		assert(is_numeric($minValue));
		assert(is_numeric($maxValue));

		return new RangeMax(
			$id,
			$capability,
			$value,
			floatval($minValue),
			floatval($maxValue),
		);
	}

	#[Override]
	public function getType(): NsPanelTypes\Configuration
	{
		return NsPanelTypes\Configuration::RANGE_MAX;
	}

}
