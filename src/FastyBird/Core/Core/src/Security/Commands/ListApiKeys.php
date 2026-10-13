<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Commands;

use DateTimeInterface;
use FastyBird\Core\Logging;
use FastyBird\Core\Security\Entities;
use FastyBird\Core\Security\Models;
use FastyBird\Core\Security\Queries;
use FastyBird\Core\Values\Types\Sources;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Log;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_map;

/**
 * Lists every API key, in every state. Neither a secret nor a hash is ever printed.
 */
final class ListApiKeys extends Console\Command\Command
{

	public const string NAME = 'fb:api-key:list';

	public function __construct(
		private readonly Models\ApiKeys\Repository $repository,
		private readonly ClockInterface $clock,
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
			->setDescription('List API keys');
	}

	#[Override]
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		try {
			/** @var Queries\FindApiKeys<Entities\ApiKeys\ApiKey> $findQuery */
			$findQuery = new Queries\FindApiKeys();

			$apiKeys = $this->repository->findAllBy($findQuery);
		} catch (Throwable $ex) {
			$this->logger->error('API keys could not be listed', [
				'source' => Sources\Core::CORE->value,
				'capability' => 'security',
				'type' => 'api-key-list-command',
				'exception' => Logging\Logger::buildException($ex, false),
			]);

			$io->error('API keys could not be listed. Please try again later.');

			return self::FAILURE;
		}

		if ($apiKeys === []) {
			$io->info('There are no API keys.');

			return self::SUCCESS;
		}

		$now = $this->clock->now();

		$io->table(
			['Identifier', 'Name', 'Account', 'State', 'Expires at', 'Created at'],
			array_map(
				static fn (Entities\ApiKeys\ApiKey $apiKey): array => [
					$apiKey->getId()->toString(),
					$apiKey->getName(),
					$apiKey->getAccount()->toString(),
					$apiKey->isActive() && $apiKey->isExpired($now) ? 'expired' : $apiKey->getState()->value,
					$apiKey->getExpiresAt()?->format(DateTimeInterface::ATOM) ?? 'never',
					$apiKey->getCreatedAt()?->format(DateTimeInterface::ATOM) ?? '',
				],
				$apiKeys,
			),
		);

		return self::SUCCESS;
	}

}
