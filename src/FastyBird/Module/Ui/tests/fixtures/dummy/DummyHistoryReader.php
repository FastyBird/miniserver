<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Fixtures\Dummy;

use DateTimeImmutable;
use DateTimeInterface;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Types;
use Override;

/**
 * Stands for the module that knows what a data source is bound to. It echoes what it was asked for, so that a test
 * sees what the controller made of a request, and has no history for one data source
 */
final class DummyHistoryReader implements Models\History\Reader
{

	public const string WITHOUT_HISTORY = 'ffe067c8-8c02-4c2c-b8dd-05256a121215';

	#[Override]
	public function read(
		Documents\Widgets\DataSources\DataSource $dataSource,
		DateTimeInterface $from,
		DateTimeInterface $to,
		int $points,
	): Models\History\Series|null
	{
		if ($dataSource->getId()->toString() === self::WITHOUT_HISTORY) {
			return null;
		}

		return new Models\History\Series(
			$dataSource->getId()->toString(),
			DateTimeImmutable::createFromInterface($from),
			DateTimeImmutable::createFromInterface($to),
			$points,
			Types\HistoryTier::SAMPLES,
			'°C',
			'float',
			[$from->getTimestamp(), $to->getTimestamp()],
			[1.5, null],
			[2.5, null],
			[2.0, null],
			[2.5, null],
		);
	}

}
