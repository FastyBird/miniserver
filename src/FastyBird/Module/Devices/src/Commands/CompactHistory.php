<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Commands;

use FastyBird\Module\Devices\Models;
use Nette\Localization;
use Override;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use function sprintf;

/**
 * Rolls the property value history up and deletes what has expired, once
 *
 * `fb:devices-module:exchange` does this every 5 minutes. The command is for a setup that runs no exchange
 * process: call it from cron. A run can be repeated at any time, and can overlap another.
 */
final class CompactHistory extends Console\Command\Command
{

	public const string NAME = 'fb:devices-module:history:compact';

	public function __construct(
		private readonly Models\History\Compactor $compactor,
		private readonly Localization\Translator $translator,
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	#[Override]
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Devices module property value history compaction');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	#[Override]
	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$io = new Style\SymfonyStyle($input, $output);
		$quiet = $input->getOption('quiet') !== false;

		if (!$quiet) {
			$io->title((string) $this->translator->translate('//devices-module.cmd.history.title'));

			$io->note((string) $this->translator->translate('//devices-module.cmd.history.subtitle'));
		}

		$compaction = $this->compactor->compact();

		if (!$quiet) {
			$io->text(
				sprintf(
					(string) $this->translator->translate('//devices-module.cmd.history.messages.done'),
					$compaction->properties,
					$compaction->fiveMinutes,
					$compaction->hours,
					$compaction->expiredSamples,
					$compaction->expiredFiveMinutes,
					$compaction->expiredHours,
					$compaction->purged,
				),
			);
		}

		if ($compaction->failed > 0) {
			if (!$quiet) {
				$io->error(
					sprintf(
						(string) $this->translator->translate('//devices-module.cmd.history.messages.failed'),
						$compaction->failed,
					),
				);
			}

			return Console\Command\Command::FAILURE;
		}

		return Console\Command\Command::SUCCESS;
	}

}
