<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Commands;

use DateTimeImmutable;
use Doctrine\DBAL;
use FastyBird\Core\Logging;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Models;
use FastyBird\Core\Security\Types;
use FastyBird\Core\Values\Types\Sources;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Log;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function is_string;
use function mb_strlen;
use function sprintf;
use function trim;

/**
 * Creates an API key for an account and prints its secret, the only time it is ever shown.
 * Non-interactive: everything comes from the options. The account must be one that can sign in,
 * as Identity\AccountIdentityProvider decides; without a provider no key can be created.
 */
final class CreateApiKey extends Console\Command\Command
{

	public const string NAME = 'fb:api-key:create';

	public const int NAME_MAX_LENGTH = 100;

	public function __construct(
		private readonly Models\ApiKeys\Manager $manager,
		private readonly ClockInterface $clock,
		private readonly Identity\AccountIdentityProvider|null $accountIdentities = null,
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
			->setDescription('Create an API key for an account and print its secret once')
			->addOption('name', null, Input\InputOption::VALUE_REQUIRED, 'Name of the key')
			->addOption(
				'account',
				null,
				Input\InputOption::VALUE_REQUIRED,
				'Identifier (UUID) of the account the key authenticates as',
			)
			->addOption(
				'expires-at',
				null,
				Input\InputOption::VALUE_REQUIRED,
				'When the key stops authenticating, as a date and time in the future (ISO 8601). It never expires when omitted.',
			);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	#[Override]
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$name = $input->getOption('name');
		$name = is_string($name) ? trim($name) : '';

		if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
			$io->error(sprintf('Provide the key name with --name, 1 to %d characters.', self::NAME_MAX_LENGTH));

			return self::FAILURE;
		}

		$account = $input->getOption('account');

		if (!is_string($account) || !Uuid\Uuid::isValid($account)) {
			$io->error('Provide the account identifier (UUID) with --account.');

			return self::FAILURE;
		}

		// A key for an account that cannot sign in would be refused on every request; refuse it here,
		// by the same rule Services\Auth applies
		try {
			$identity = $this->accountIdentities?->forAccount(Uuid\Uuid::fromString($account));
		} catch (Throwable $ex) {
			$this->logger->error('Account of an API key could not be resolved', [
				'source' => Sources\Core::CORE->value,
				'capability' => 'security',
				'type' => 'api-key-create-command',
				'exception' => Logging\Logger::buildException($ex, false),
			]);

			$io->error('The account could not be resolved. Please try again later.');

			return self::FAILURE;
		}

		if ($identity === null) {
			$io->error(sprintf('There is no account %s that can sign in.', $account));

			return self::FAILURE;
		}

		$expiresAt = $input->getOption('expires-at');

		if ($expiresAt !== null) {
			try {
				$expiresAt = is_string($expiresAt) && trim($expiresAt) !== ''
					? new DateTimeImmutable($expiresAt)
					: null;
			} catch (Throwable) {
				$expiresAt = null;
			}

			if ($expiresAt === null || $expiresAt <= $this->clock->now()) {
				$io->error('The value of --expires-at must be a date and time in the future.');

				return self::FAILURE;
			}
		}

		try {
			$secret = Identity\ApiKeySecret::generate();

			$apiKey = $this->manager->create([
				'name' => $name,
				'account' => Uuid\Uuid::fromString($account),
				'hash' => Identity\ApiKeySecret::hash($secret),
				'state' => Types\ApiKeyState::ACTIVE,
				'expiresAt' => $expiresAt,
			]);
		} catch (DBAL\Exception\UniqueConstraintViolationException) {
			// The driver's message for a duplicate entry quotes the duplicated value, the hash,
			// so none of it is logged
			$this->logger->error('API key could not be created, its hash is not unique', [
				'source' => Sources\Core::CORE->value,
				'capability' => 'security',
				'type' => 'api-key-create-command',
			]);

			$io->error('The API key could not be created. Please try again.');

			return self::FAILURE;
		} catch (Throwable $ex) {
			$this->logger->error('API key could not be created', [
				'source' => Sources\Core::CORE->value,
				'capability' => 'security',
				'type' => 'api-key-create-command',
				// Not rendered: a rendered blue screen dumps the stack's arguments, and the
				// secret and its hash are among them
				'exception' => Logging\Logger::buildException($ex, false),
			]);

			$io->error('The API key could not be created. Please try again later.');

			return self::FAILURE;
		}

		$io->success(sprintf(
			'API key "%s" (%s) was created for account %s.',
			$apiKey->getName(),
			$apiKey->getId()->toString(),
			$apiKey->getAccount()->toString(),
		));

		$io->warning('Store the secret below now. It is shown only this once and cannot be read back.');

		// Printed even with --quiet, so a script can capture it as the command's only output
		$output->writeln($secret, Output\OutputInterface::VERBOSITY_QUIET);

		return self::SUCCESS;
	}

}
