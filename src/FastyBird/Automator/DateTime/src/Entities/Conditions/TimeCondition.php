<?php declare(strict_types = 1);

/**
 * TimeCondition.php
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
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Automator\DateTime\Exceptions;
use FastyBird\Core\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Library\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Nette\Utils;
use Ramsey\Uuid;
use function array_merge;
use function assert;
use function in_array;
use function intval;
use function is_array;
use function method_exists;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class TimeCondition extends TriggersEntities\Conditions\Condition
{

	public const TYPE = 'time';

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'condition_time', type: 'time', nullable: true)]
	private DateTimeInterface|null $time;

	/** @var array<int>|null */
	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'condition_days', type: 'simple_array', nullable: true)]
	private array|null $days;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		DateTimeInterface $time,
		Utils\ArrayHash $days,
		TriggersEntities\Triggers\Automatic $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->setTime($time);
		$this->setDays($days);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDays(): Utils\ArrayHash
	{
		assert(is_array($this->days));

		$days = [];

		foreach ($this->days as $day) {
			$days[] = intval($day);
		}

		return Utils\ArrayHash::from($days);
	}

	/**
	 * @param array<int> $days
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function setDays(Utils\ArrayHash|array $days): void
	{
		foreach ($days as $day) {
			if (!in_array($day, [1, 2, 3, 4, 5, 6, 7], true)) {
				throw new Exceptions\InvalidArgument('Provided days array is not valid.');
			}
		}

		$this->days = (array) $days;
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
		if (in_array((int) $date->format('N'), (array) $this->getDays(), true) === false) {
			return false;
		}

		return $date->format('h:i:s') === $this->getTime()->format('h:i:s');
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'time' => $this->getTime()->format(DateTimeInterface::ATOM),
			'days' => (array) $this->getDays(),
		]);
	}

}
