<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use DateTime;
use DateTimeInterface;
use FastyBird\Core\Documents;
use Override;

/**
 * A Valinor-mapped document with a mutable date, and a value which is a mutable date or a text, the last as the value
 * of a state is
 */
final readonly class DummyMutableDateDocument implements Documents\Document
{

	public function __construct(
		private DateTime $date,
		private DateTime|string|null $value = null,
	)
	{
	}

	public function getDate(): DateTime
	{
		return $this->date;
	}

	public function getValue(): DateTime|string|null
	{
		return $this->value;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'date' => $this->date->format(DateTimeInterface::ATOM),
			'value' => $this->value instanceof DateTime ? $this->value->format(DateTimeInterface::ATOM) : $this->value,
		];
	}

}
