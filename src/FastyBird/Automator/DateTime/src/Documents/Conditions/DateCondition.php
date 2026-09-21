<?php declare(strict_types = 1);

/**
 * DateCondition.php
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
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Date condition document
 *
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\Document(entity: Entities\Conditions\DateCondition::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Conditions\DateCondition::TYPE)]
final class DateCondition extends TriggersDocuments\Conditions\Condition
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		bool $enabled,
		#[ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM)]
		private readonly DateTimeInterface $date,
		bool|null $isFulfilled = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $enabled, $isFulfilled, $owner);
	}

	public static function getType(): string
	{
		return Entities\Conditions\DateCondition::TYPE;
	}

	public function getDate(): DateTimeInterface
	{
		return $this->date;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'date' => $this->getDate()->format(DateTimeInterface::ATOM),
		]);
	}

}
