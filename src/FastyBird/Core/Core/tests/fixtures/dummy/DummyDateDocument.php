<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents;
use Override;

/**
 * A Valinor-mapped document with a date, a nullable date and a value which is a text or a date, the last as the value
 * of a state is
 */
final readonly class DummyDateDocument implements Documents\Document
{

	public function __construct(
		private DateTimeInterface $date,
		#[Configurator\MapFromKey('maybe_date')]
		private DateTimeInterface|null $maybeDate = null,
		private string|DateTimeInterface|null $value = null,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'date' => $this->date->format(DateTimeInterface::ATOM),
			'maybe_date' => $this->maybeDate?->format(DateTimeInterface::ATOM),
			'value' => $this->value instanceof DateTimeInterface
				? $this->value->format(DateTimeInterface::ATOM)
				: $this->value,
		];
	}

}
