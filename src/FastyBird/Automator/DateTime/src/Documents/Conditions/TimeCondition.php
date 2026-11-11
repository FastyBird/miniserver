<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Documents\Conditions;

use CuyZ\Valinor\Mapper\Configurator;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use DateTimeInterface;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use Override;
use Ramsey\Uuid;
use Throwable;
use function array_merge;
use function count;

/**
 * Time condition document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Conditions\TimeCondition::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Conditions\TimeCondition::TYPE)]
final class TimeCondition extends TriggersDocuments\Conditions\Condition
{

	/**
	 * @param non-empty-array<int<1, 7>> $days
	 *
	 * @throws Throwable
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		private readonly DateTimeInterface $time,
		private readonly array $days,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('is_fulfilled')]
		bool|null $isFulfilled = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
	)
	{
		// A week has seven days, and Valinor has no type for the most items of an array
		if (count($days) > 7) {
			throw MessageBuilder::newError('A condition holds at most 7 days, {days} given.')
				->withParameter('days', (string) count($days))
				->build();
		}

		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	#[Override]
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

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'time' => $this->getTime()->format(DateTimeInterface::ATOM),
			'days' => $this->getDays(),
		]);
	}

}
