<?php declare(strict_types = 1);

/**
 * InitializeCommand.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           14.12.20
 */

namespace FastyBird\MiniServer\Commands;

use Doctrine\DBAL;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence;
use Exception;
use FastyBird\MiniServer\Exceptions;
use FastyBird\Module\Accounts\Models as AccountsModuleModels;
use FastyBird\Module\Accounts\Queries as AccountsModuleQueries;
use FastyBird\SimpleAuth;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Monolog;
use Nette\Utils;
use RuntimeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function assert;
use function count;
use function in_array;
use function is_bool;

/**
 * Application initialize command
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:miniserver:initialize';

	public function __construct(
		private readonly AccountsModuleModels\Accounts\AccountsRepository $accountsRepository,
		private readonly AccountsModuleModels\Roles\RolesRepository $rolesRepository,
		private readonly AccountsModuleModels\Roles\RolesManager $rolesManager,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		private readonly Monolog\Logger $logger,
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
			->addOption('noconfirm', null, Input\InputOption::VALUE_NONE, 'do not ask for any confirmation')
			->setDescription('Initialize application.');
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return 1;
		}

		$io = new Style\SymfonyStyle($input, $output);

		$io->title('FB MiniServer - initialization');

		$io->note(
			'This action will create or update MiniServer database structure, create initial data and initialize administrator account.',
		);

		$continue = $io->ask('Would you like to continue?', 'n', static function ($answer): bool {
			if (!in_array($answer, ['y', 'Y', 'n', 'N'], true)) {
				throw new RuntimeException('You must type Y or N');
			}

			return in_array($answer, ['y', 'Y'], true);
		});
		assert(is_bool($continue));

		if (!$continue) {
			return 0;
		}

		$io->section('Preparing MiniServer database');

		$databaseCmd = $symfonyApp->find('orm:schema-tool:update');

		$result = $databaseCmd->run(new Input\ArrayInput([
			'--force' => true,
		]), $output);

		if ($result !== 0) {
			$io->error('Something went wrong, initialization could not be finished.');

			return 1;
		}

		$databaseProxiesCmd = $symfonyApp->find('orm:generate-proxies');

		$result = $databaseProxiesCmd->run(new Input\ArrayInput([
			'--quiet' => true,
		]), $output);

		if ($result !== 0) {
			$io->error('Something went wrong, initialization could not be finished.');

			return 1;
		}

		$io->newLine();

		$io->section('Preparing initial data');

		$allRoles = [
			SimpleAuth\Constants::ROLE_ANONYMOUS,
			SimpleAuth\Constants::ROLE_VISITOR,
			SimpleAuth\Constants::ROLE_USER,
			SimpleAuth\Constants::ROLE_MANAGER,
			SimpleAuth\Constants::ROLE_ADMINISTRATOR,
		];

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$parent = null;

			// Roles initialization
			foreach ($allRoles as $roleName) {
				$findRole = new AccountsModuleQueries\FindRoles();
				$findRole->byName($roleName);

				$role = $this->rolesRepository->findOneBy($findRole);

				if ($role === null) {
					$create = new Utils\ArrayHash();
					$create->offsetSet('name', $roleName);
					$create->offsetSet('comment', $roleName);
					$create->offsetSet('parent', $parent);

					$parent = $this->rolesManager->create($create);
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->logger->error($ex->getMessage());

			$io->error('Initial data could not be created.');

			return $ex->getCode();
		}

		$io->success('All initial data has been successfully created.');

		$io->newLine();

		$io->section('Checking for administrator account');

		$findRole = new AccountsModuleQueries\FindRoles();
		$findRole->byName(SimpleAuth\Constants::ROLE_ADMINISTRATOR);

		$administratorRole = $this->rolesRepository->findOneBy($findRole);

		if ($administratorRole !== null) {
			$findAccounts = new AccountsModuleQueries\FindAccounts();
			$findAccounts->inRole($administratorRole);

			$accounts = $this->accountsRepository->findAllBy($findAccounts);

			if (count($accounts) === 0) {
				$accountCmd = $symfonyApp->find('fb:accounts-module:create:account');

				$result = $accountCmd->run(new Input\ArrayInput([
					'role' => SimpleAuth\Constants::ROLE_ADMINISTRATOR,
					'--injected' => true,
				]), $output);

				if ($result !== 0) {
					$io->error('Something went wrong, initialization could not be finished.');

					return 1;
				}
			} else {
				$io->success('There is existing administrator account.');
			}
		} else {
			$io->error('Something went wrong, administrator role could not be found.');

			return 1;
		}

		$io->newLine(3);

		$io->success('This MiniServer has been successfully initialized and can be now started.');

		return 0;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	protected function getOrmConnection(): Connection
	{
		$connection = $this->managerRegistry->getConnection();

		if ($connection instanceof Connection) {
			return $connection;
		}

		throw new Exceptions\Runtime('Entity manager could not be loaded');
	}

}
