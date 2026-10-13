<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Commands;

use FastyBird\Core\Logging;
use FastyBird\Core\Security\Models;
use FastyBird\Core\Security\Types;
use FastyBird\Core\Values\Types\Sources;
use Nette\Utils;
use Override;
use Psr\Log;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function is_string;
use function sprintf;

/**
 * Suspends an API key: from then on it no longer authenticates
 */
final class SuspendApiKey extends Console\Command\Command
{

	public const string NAME = 'fb:api-key:suspend';

	public function __construct(
		private readonly Models\ApiKeys\Repository $repository,
		private readonly Models\ApiKeys\Manager $manager,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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
			->setDescription('Suspend an API key')
			->addArgument('id', Input\InputArgument::REQUIRED, 'Identifier (UUID) of the key');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	#[Override]
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$id = $input->getArgument('id');

		if (!is_string($id) || !Uuid\Uuid::isValid($id)) {
			$io->error('Provide the key identifier (UUID).');

			return self::FAILURE;
		}

		try {
			$apiKey = $this->repository->findOneById(Uuid\Uuid::fromString($id));

			if ($apiKey === null) {
				$io->error(sprintf('API key %s was not found.', $id));

				return self::FAILURE;
			}

			if ($apiKey->getState() === Types\ApiKeyState::DELETED) {
				$io->error(sprintf('API key %s is deleted.', $id));

				return self::FAILURE;
			}

			$this->manager->update($apiKey, Utils\ArrayHash::from([
				'state' => Types\ApiKeyState::SUSPENDED,
			]));
		} catch (Throwable $ex) {
			$this->logger->error('API key could not be suspended', [
				'source' => Sources\Core::CORE->value,
				'capability' => 'security',
				'type' => 'api-key-suspend-command',
				'exception' => Logging\Logger::buildException($ex, false),
			]);

			$io->error('The API key could not be suspended. Please try again later.');

			return self::FAILURE;
		}

		$io->success(sprintf('API key %s is suspended.', $id));

		return self::SUCCESS;
	}

}
