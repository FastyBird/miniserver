<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Module\Devices\States;

use DateTimeInterface;
use FastyBird\Core\Values\Types\Payloads;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Property interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Property extends ObjectMapper\MappedObject
{

	public const ACTUAL_VALUE_FIELD = 'actual_value';

	public const EXPECTED_VALUE_FIELD = 'expected_value';

	public const PENDING_FIELD = 'pending';

	public const VALID_FIELD = 'valid';

	public const CREATED_AT = 'created_at';

	public const UPDATED_AT = 'updated_at';

	public function getId(): Uuid\UuidInterface;

	public function getActualValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null;

	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null;

	public function getPending(): bool|DateTimeInterface;

	public function isPending(): bool;

	public function isValid(): bool;

	public function getCreatedAt(): DateTimeInterface|null;

	public function getUpdatedAt(): DateTimeInterface|null;

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
