<?php declare(strict_types = 1);

/**
 * TimeCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Automator\DateTime\Documents\Conditions;

use DateTimeInterface;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Time condition document
 *
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Conditions\TimeCondition::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Conditions\TimeCondition::TYPE)]
final class TimeCondition extends TriggersDocuments\Conditions\Condition
{

	/**
	 * @param array<int> $days
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		#[ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM)]
		private readonly DateTimeInterface $time,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\IntValue(min: 1, max: 7, unsigned: true),
			minItems: 1,
			maxItems: 7,
		)]
		private readonly array $days,
		bool|null $isFulfilled = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	public static function getType(): string
	{
		return Entities\Conditions\TimeCondition::TYPE;
	}

	public function getTime(): DateTimeInterface
	{
		return $this->time;
	}

	/**
	 * @return array<int>
	 */
	public function getDays(): array
	{
		return $this->days;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'time' => $this->getTime()->format(DateTimeInterface::ATOM),
			'days' => $this->getDays(),
		]);
	}

}
