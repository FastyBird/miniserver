<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Entities\Conditions;

use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Automator\DateTime\Exceptions;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Override;
use Ramsey\Uuid;
use function array_merge;
use function assert;
use function in_array;
use function intval;
use function is_array;
use function method_exists;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class TimeCondition extends TriggersEntities\Conditions\Condition
{

	public const string TYPE = 'time';

	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'condition_time', type: 'time', nullable: true)]
	private DateTimeInterface|null $time;

	/** @var array<int>|null */
	#[Attribute\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'condition_days', type: 'simple_array', nullable: true)]
	private array|null $days;

	/**
	 * @param array<int> $days
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		DateTimeInterface $time,
		array $days,
		TriggersEntities\Triggers\Automatic $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->setTime($time);
		$this->setDays($days);
	}

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * @return array<int>
	 */
	public function getDays(): array
	{
		assert(is_array($this->days));

		$days = [];

		foreach ($this->days as $day) {
			$days[] = intval($day);
		}

		return $days;
	}

	/**
	 * @param array<int> $days
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function setDays(array $days): void
	{
		foreach ($days as $day) {
			if (!in_array($day, [1, 2, 3, 4, 5, 6, 7], true)) {
				throw new Exceptions\InvalidArgument('Provided days array is not valid.');
			}
		}

		$this->days = $days;
	}

	public function getTime(): DateTimeInterface
	{
		assert($this->time instanceof DateTimeInterface);

		return $this->time;
	}

	public function setTime(DateTimeInterface $time): void
	{
		if (method_exists($time, 'setTimezone')) {
			$time->setTimezone(new DateTimeZone('UTC'));
		}

		$this->time = $time;
	}

	public function validate(DateTimeInterface $date): bool
	{
		if (in_array((int) $date->format('N'), $this->getDays(), true) === false) {
			return false;
		}

		return $date->format('h:i:s') === $this->getTime()->format('h:i:s');
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'time' => $this->getTime()->format(DateTimeInterface::ATOM),
			'days' => $this->getDays(),
		]);
	}

}
