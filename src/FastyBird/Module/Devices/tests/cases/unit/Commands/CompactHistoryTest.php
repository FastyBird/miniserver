<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Commands;

use FastyBird\Module\Devices\Commands;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use Nette\Localization;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function sprintf;

/**
 * `fb:devices-module:history:compact`, the cron fallback of the exchange process's timer (#624)
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class CompactHistoryTest extends Tests\Cases\Unit\HistoryTestCase
{

	/**
	 * @throws Throwable
	 */
	public function testCompactsAndReportsWhatItDid(): void
	{
		$this->insertSample(self::NOW - 1_800, 10.0);

		$tester = $this->tester();

		$status = $tester->execute([]);

		self::assertSame(Console\Command\Command::SUCCESS, $status);
		self::assertCount(5, $this->rollups(Types\HistoryResolution::FIVE_MINUTES));
		self::assertStringContainsString(
			'Properties: 1. Rollups written: 5 of 5 minutes, 0 of 1 hour.',
			$tester->getDisplay(),
		);
		self::assertStringContainsString('Purged, of properties that no longer exist: 0.', $tester->getDisplay());
	}

	/**
	 * @throws Throwable
	 */
	public function testQuietPrintsNothing(): void
	{
		$this->insertSample(self::NOW - 1_800, 10.0);

		$tester = $this->tester();

		self::assertSame(Console\Command\Command::SUCCESS, $tester->execute(['--quiet' => true]));
		self::assertSame('', $tester->getDisplay());
	}

	/**
	 * @throws Throwable
	 */
	public function testFailsWhenTheHistoryCannotBeCompacted(): void
	{
		$this->insertSample(self::NOW - 1_800, 10.0);

		$this->getDb()->executeStatement(
			sprintf('ALTER TABLE %s ADD CONSTRAINT never CHECK (value_avg <> 10)', Entities\History\Rollup::TABLE),
		);

		$tester = $this->tester();

		self::assertSame(Console\Command\Command::FAILURE, $tester->execute([]));
		self::assertStringContainsString('The history of 1 properties could not be compacted', $tester->getDisplay());
	}

	/**
	 * @throws Throwable
	 */
	private function tester(): CommandTester
	{
		$application = new Console\Application();
		$application->add(
			new Commands\CompactHistory(
				$this->compactor(),
				$this->getContainer()->getByType(Localization\Translator::class),
			),
		);

		return new CommandTester($application->find(Commands\CompactHistory::NAME));
	}

}
