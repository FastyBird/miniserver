<?php declare(strict_types = 1);

/**
 * RangeMinFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           16.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;
use function assert;
use function floatval;
use function is_float;
use function is_int;
use function is_numeric;

/**
 * NS panel range min value capability configuration factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RangeMinFactory implements ConfigurationFactory
{

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
	): RangeMin
	{
		assert(is_int($value) || is_float($value));
		assert(is_numeric($minValue));
		assert(is_numeric($maxValue));

		return new RangeMin(
			$id,
			$capability,
			$value,
			floatval($minValue),
			floatval($maxValue),
		);
	}

	public function getType(): NsPanelTypes\Configuration
	{
		return NsPanelTypes\Configuration::RANGE_MIN;
	}

}
