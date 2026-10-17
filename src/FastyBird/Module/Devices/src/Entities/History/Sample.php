<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities\History;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid;

/**
 * One stored value of a property: the raw tier of the property value history
 *
 * The entity owns the schema, the drift gate and the test DDL, and nothing else. It is mapped read-only
 * because the history is written and read through DBAL (a multi-row INSERT, aggregate reads), never
 * hydrated. There is no foreign key: a property id lives in three tables, and the rows of a deleted
 * property age out through retention.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(
	name: self::TABLE,
	options: [
		'comment' => 'Property value history',
	],
)]
class Sample
{

	public const string TABLE = 'fb_devices_module_history_samples';

	#[ORM\Id]
	#[ORM\Column(name: 'property_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	private Uuid\UuidInterface $propertyId;

	/**
	 * UTC epoch seconds
	 */
	#[ORM\Id]
	#[ORM\Column(name: 'sampled_at', type: 'integer', nullable: false, options: ['unsigned' => true])]
	private int $sampledAt;

	/**
	 * NULL marks a gap: the state was invalid
	 */
	#[ORM\Column(name: 'value', type: 'float', nullable: true)]
	private float|null $value;

	public function __construct(Uuid\UuidInterface $propertyId, int $sampledAt, float|null $value)
	{
		$this->propertyId = $propertyId;
		$this->sampledAt = $sampledAt;
		$this->value = $value;
	}

	public function getPropertyId(): Uuid\UuidInterface
	{
		return $this->propertyId;
	}

	public function getSampledAt(): int
	{
		return $this->sampledAt;
	}

	public function getValue(): float|null
	{
		return $this->value;
	}

}
