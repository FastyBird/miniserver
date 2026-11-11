<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Documents\Conditions;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Date condition document
 */
#[CoreDocuments\Mapping\Document(entity: Entities\Conditions\DateCondition::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Conditions\DateCondition::TYPE)]
final class DateCondition extends TriggersDocuments\Conditions\Condition
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		private readonly DateTimeInterface $date,
		#[CoreDocuments\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('is_fulfilled')]
		bool|null $isFulfilled = null,
		#[CoreDocuments\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	#[Override]
	public static function getType(): string
	{
		return Entities\Conditions\DateCondition::TYPE;
	}

	public function getDate(): DateTimeInterface
	{
		return $this->date;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'date' => $this->getDate()->format(DateTimeInterface::ATOM),
		]);
	}

}
