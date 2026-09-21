<?php declare(strict_types = 1);

/**
 * StateRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           25.02.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Models;

use DateTimeInterface;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Ramsey\Uuid;
use function array_key_exists;

/**
 * Property states cache repository
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StateRepository
{

	/** @var array<string, bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null> */
	private array $states = [];

	public function set(
		Uuid\UuidInterface $id,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
	): void
	{
		$this->states[$id->toString()] = $value;
	}

	/**
	 * @throws Exceptions\MissingValue
	 */
	public function get(
		Uuid\UuidInterface $id,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if (array_key_exists($id->toString(), $this->states)) {
			return $this->states[$id->toString()];
		}

		throw new Exceptions\MissingValue('State for provided identifier is not stored');
	}

}
