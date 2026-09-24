<?php declare(strict_types = 1);

/**
 * RangeMin.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           05.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Range minimal value configuration
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RangeMin extends Configuration
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		float|int $value,
		float $minValue,
		float $maxValue,
	)
	{
		if ($capability->getType() === NsPanelTypes\Capability::TEMPERATURE) {
			parent::__construct(
				$id,
				NsPanelTypes\Configuration::RANGE_MIN,
				ValuesTypes\DataType::FLOAT,
				$capability,
				$value,
				[],
				null,
				$minValue,
				$maxValue,
				0.1,
			);

		} else {
			parent::__construct(
				$id,
				NsPanelTypes\Configuration::RANGE_MIN,
				ValuesTypes\DataType::CHAR,
				$capability,
				$value,
				[],
				null,
				$minValue,
				$maxValue,
				1,
			);
		}
	}

}
