<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities\History;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Devices\Types;
use Ramsey\Uuid;

/**
 * One bucket of a property's value history: the 5-minute and the 1-hour tiers
 *
 * Mapped read-only for the same reason as Sample: the entity owns the schema, and every read and
 * write goes through DBAL.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(
	name: self::TABLE,
	options: [
		'comment' => 'Property value history rollups',
	],
)]
class Rollup
{

	public const string TABLE = 'fb_devices_module_history_rollups';

	#[ORM\Id]
	#[ORM\Column(name: 'property_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	private Uuid\UuidInterface $propertyId;

	/**
	 * Bucket width in seconds
	 */
	#[ORM\Id]
	#[ORM\Column(
		name: 'resolution',
		type: 'integer',
		nullable: false,
		enumType: Types\HistoryResolution::class,
		options: ['unsigned' => true],
	)]
	private Types\HistoryResolution $resolution;

	/**
	 * UTC epoch seconds of the start of the bucket
	 */
	#[ORM\Id]
	#[ORM\Column(name: 'bucket_start', type: 'integer', nullable: false, options: ['unsigned' => true])]
	private int $bucketStart;

	#[ORM\Column(name: 'value_min', type: 'float', nullable: true)]
	private float|null $min;

	#[ORM\Column(name: 'value_max', type: 'float', nullable: true)]
	private float|null $max;

	#[ORM\Column(name: 'value_avg', type: 'float', nullable: true)]
	private float|null $avg;

	#[ORM\Column(name: 'value_last', type: 'float', nullable: true)]
	private float|null $last;

	#[ORM\Column(name: 'samples', type: 'integer', nullable: false, options: ['unsigned' => true])]
	private int $samples;

	public function __construct(
		Uuid\UuidInterface $propertyId,
		Types\HistoryResolution $resolution,
		int $bucketStart,
		float|null $min,
		float|null $max,
		float|null $avg,
		float|null $last,
		int $samples,
	)
	{
		$this->propertyId = $propertyId;
		$this->resolution = $resolution;
		$this->bucketStart = $bucketStart;
		$this->min = $min;
		$this->max = $max;
		$this->avg = $avg;
		$this->last = $last;
		$this->samples = $samples;
	}

	public function getPropertyId(): Uuid\UuidInterface
	{
		return $this->propertyId;
	}

	public function getResolution(): Types\HistoryResolution
	{
		return $this->resolution;
	}

	public function getBucketStart(): int
	{
		return $this->bucketStart;
	}

	public function getMin(): float|null
	{
		return $this->min;
	}

	public function getMax(): float|null
	{
		return $this->max;
	}

	public function getAvg(): float|null
	{
		return $this->avg;
	}

	public function getLast(): float|null
	{
		return $this->last;
	}

	public function getSamples(): int
	{
		return $this->samples;
	}

}
