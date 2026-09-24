<?php declare(strict_types = 1);

/**
 * DateCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DateTime\Entities\Conditions;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Ramsey\Uuid;
use function array_merge;
use function assert;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class DateCondition extends TriggersEntities\Conditions\Condition
{

	public const TYPE = 'date';

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'condition_date', type: 'datetime_immutable', nullable: true)]
	private DateTimeInterface|null $date;

	public function __construct(
		DateTimeInterface $date,
		TriggersEntities\Triggers\Automatic $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->date = $date;
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDate(): DateTimeInterface
	{
		assert($this->date instanceof DateTimeInterface);

		return $this->date;
	}

	public function setDate(DateTimeInterface $date): void
	{
		$this->date = $date;
	}

	public function validate(DateTimeInterface $date): bool
	{
		assert($this->date instanceof DateTimeInterface);

		return $date->getTimestamp() === $this->date->getTimestamp();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'date' => $this->getDate()->format(DateTimeInterface::ATOM),
		]);
	}

}
