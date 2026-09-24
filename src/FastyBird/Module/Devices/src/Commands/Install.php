<?php declare(strict_types = 1);

/**
 * Install.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           08.08.20
 */

namespace FastyBird\Module\Devices\Commands;

use FastyBird\Core\Logging;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices;
use Nette\Localization;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;

/**
 * Module install command
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:devices-module:install';

	public function __construct(
		private readonly Devices\Logger $logger,
		private readonly Localization\Translator $translator,
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Devices module installer');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return Console\Command\Command::FAILURE;
		}

		$io = new Style\SymfonyStyle($input, $output);

		if ($input->getOption('quiet') === false) {
			$io->title((string) $this->translator->translate('//devices-module.cmd.install.title'));

			$io->note((string) $this->translator->translate('//devices-module.cmd.install.subtitle'));
		}

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//devices-module.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		try {
			$this->initializeDatabase($io, $input, $output);

			if ($input->getOption('quiet') === false) {
				$io->success((string) $this->translator->translate('//devices-module.cmd.install.messages.success'));
			}

			return Console\Command\Command::SUCCESS;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			if ($input->getOption('quiet') === false) {
				$io->error((string) $this->translator->translate('//devices-module.cmd.install.messages.error'));
			}

			return Console\Command\Command::FAILURE;
		}
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 */
	private function initializeDatabase(
		Style\SymfonyStyle $io,
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): void
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return;
		}

		if ($input->getOption('quiet') === false) {
			$io->section((string) $this->translator->translate('//devices-module.cmd.install.info.database'));
		}

		$databaseCmd = $symfonyApp->find('orm:schema-tool:update');

		$result = $databaseCmd->run(new Input\ArrayInput([
			'--force' => true,
		]), $output);

		if ($result !== Console\Command\Command::SUCCESS) {
			if ($input->getOption('quiet') === false) {
				$io->error(
					(string) $this->translator->translate('//devices-module.cmd.install.messages.initialisationFailed'),
				);
			}

			return;
		}

		$databaseProxiesCmd = $symfonyApp->find('orm:generate-proxies');

		$result = $databaseProxiesCmd->run(new Input\ArrayInput([
			'--quiet' => true,
		]), $output);

		if ($result !== 0) {
			if ($input->getOption('quiet') === false) {
				$io->error(
					(string) $this->translator->translate('//devices-module.cmd.install.messages.databaseFailed'),
				);
			}

			return;
		}

		if ($input->getOption('quiet') === false) {
			$io->success((string) $this->translator->translate('//devices-module.cmd.install.messages.databaseReady'));
		}
	}

}
