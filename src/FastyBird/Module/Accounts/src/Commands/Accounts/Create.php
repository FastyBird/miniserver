<?php declare(strict_types = 1);

/**
 * Create.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Commands\Accounts;

use Doctrine;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Types;
use FastyBird\SimpleAuth;
use FastyBird\SimpleAuth\Models as SimpleAuthModels;
use FastyBird\SimpleAuth\Security as SimpleAuthSecurity;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette\Localization;
use Nette\Utils;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function in_array;
use function is_string;
use function strval;

/**
 * Account creation command
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Create extends Console\Command\Command
{

	public const NAME = 'fb:accounts-module:create:account';

	public function __construct(
		private readonly Models\Entities\Accounts\AccountsManager $accountsManager,
		private readonly Models\Entities\Emails\EmailsRepository $emailsRepository,
		private readonly Models\Entities\Emails\EmailsManager $emailsManager,
		private readonly Models\Entities\Identities\IdentitiesManager $identitiesManager,
		private readonly Localization\Translator $translator,
		private readonly SimpleAuthSecurity\EnforcerFactory $enforcerFactory,
		private readonly SimpleAuthModels\Policies\Repository $policiesRepository,
		private readonly Persistence\ManagerRegistry $managerRegistry,
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
			->addArgument(
				'lastName',
				Input\InputArgument::OPTIONAL,
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.lastName.title'),
			)
			->addArgument(
				'firstName',
				Input\InputArgument::OPTIONAL,
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.firstName.title'),
			)
			->addArgument(
				'email',
				Input\InputArgument::OPTIONAL,
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.email.title'),
			)
			->addArgument(
				'password',
				Input\InputArgument::OPTIONAL,
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.password.title'),
			)
			->addArgument(
				'role',
				Input\InputArgument::OPTIONAL,
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.role.title'),
			)
			->addOption('noconfirm', null, Input\InputOption::VALUE_NONE, 'do not ask for any confirmation')
			->addOption('injected', null, Input\InputOption::VALUE_NONE, 'do not show all outputs')
			->setDescription('Create account.');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		if (!$input->hasOption('injected')) {
			$io->title('FB accounts module - create account');
		}

		$lastName = $input->hasArgument('lastName')
			&& is_string($input->getArgument('lastName'))
			&& $input->getArgument('lastName') !== ''
			 ? $input->getArgument('lastName')
			: $io->ask(
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.lastName.title'),
			);

		$firstName = $input->hasArgument('firstName')
			&& is_string($input->getArgument('firstName'))
			&& $input->getArgument('firstName') !== ''
			 ? $input->getArgument('firstName')
			: $io->ask(
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.firstName.title'),
			);

		$emailAddress = $input->hasArgument('email')
			&& is_string($input->getArgument('email'))
			&& $input->getArgument('email') !== ''
			 ? $input->getArgument('email')
			: $io->ask(
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.email.title'),
			);

		do {
			if (!Utils\Validators::isEmail(strval($emailAddress))) {
				$io->error(
					(string) $this->translator->translate(
						'//accounts-module.cmd.accountCreate.validation.email.invalid',
						['email' => $emailAddress],
					),
				);

				$repeat = true;
			} else {
				$email = $this->emailsRepository->findOneByAddress(strval($emailAddress));

				$repeat = $email !== null;

				if ($repeat) {
					$io->error(
						(string) $this->translator->translate(
							'//accounts-module.cmd.accountCreate.validation.email.taken',
							['email' => $emailAddress],
						),
					);
				}
			}

			if ($repeat) {
				$emailAddress = $io->ask(
					(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.email.title'),
				);
			}
		} while ($repeat);

		$repeat = true;

		if ($input->hasArgument('role') && in_array($input->getArgument('role'), [
			SimpleAuth\Constants::ROLE_USER,
			SimpleAuth\Constants::ROLE_MANAGER,
			SimpleAuth\Constants::ROLE_ADMINISTRATOR,
		], true)
		) {
			$findRoleQuery = new Queries\Entities\FindRoles();
			$findRoleQuery->byName($input->getArgument('role'));

			$role = $this->policiesRepository->findOneBy($findRoleQuery, Entities\Roles\Role::class);

			if ($role === null) {
				$io->error('Entered unknown role name.');

				return 1;
			}
		} else {
			do {
				$roleName = $io->choice(
					(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.role.title'),
					[
						'U' => (string) $this->translator->translate(
							'//accounts-module.cmd.accountCreate.inputs.role.values.user',
						),
						'M' => (string) $this->translator->translate(
							'//accounts-module.cmd.accountCreate.inputs.role.values.manager',
						),
						'A' => (string) $this->translator->translate(
							'//accounts-module.cmd.accountCreate.inputs.role.values.administrator',
						),
					],
					'U',
				);

				switch ($roleName) {
					case 'U':
						$roleName = SimpleAuth\Constants::ROLE_USER;

						break;
					case 'M':
						$roleName = SimpleAuth\Constants::ROLE_MANAGER;

						break;
					case 'A':
						$roleName = SimpleAuth\Constants::ROLE_ADMINISTRATOR;

						break;
				}

				$findRoleQuery = new Queries\Entities\FindRoles();
				$findRoleQuery->byName(strval($roleName));

				$role = $this->policiesRepository->findOneBy($findRoleQuery, Entities\Roles\Role::class);

				if ($role !== null) {
					$repeat = false;
				}
			} while ($repeat);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$id = Uuid\Uuid::uuid4();

			$create = new Utils\ArrayHash();
			$create->offsetSet('id', $id);
			$create->offsetSet('entity', Entities\Accounts\Account::class);
			$create->offsetSet('state', Types\AccountState::ACTIVE);

			$details = new Utils\ArrayHash();
			$details->offsetSet('entity', Entities\Details\Details::class);
			$details->offsetSet('firstName', $firstName);
			$details->offsetSet('lastName', $lastName);

			$create->offsetSet('details', $details);

			$this->enforcerFactory->getEnforcer()->addRoleForUser($id->toString(), $role->getName());

			$account = $this->accountsManager->create($create);

			// Create new email entity for user
			$create = new Utils\ArrayHash();
			$create->offsetSet('account', $account);
			$create->offsetSet('address', $emailAddress);
			$create->offsetSet('default', true);

			// Create new email entity
			$this->emailsManager->create($create);

			$this->enforcerFactory->getEnforcer()->deleteRolesForUser($account->getId()->toString());
			$this->enforcerFactory->getEnforcer()->addRoleForUser($account->getId()->toString(), $role->getName());
			$this->enforcerFactory->getEnforcer()->invalidateCache();

			// Commit all changes into database
			$this->getOrmConnection()->commit();
		} catch (Throwable $ex) {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$io->error($ex->getMessage());

			$io->error(
				(string) $this->translator->translate(
					'//accounts-module.cmd.accountCreate.validation.account.wasNotCreated',
					['error' => $ex->getMessage()],
				),
			);

			return $ex->getCode();
		}

		$password = $input->hasArgument('password')
			&& is_string($input->getArgument('password'))
			&& $input->getArgument('password') !== ''
			 ? $input->getArgument('password')
			: $io->askHidden(
				(string) $this->translator->translate('//accounts-module.cmd.accountCreate.inputs.password.title'),
			);

		$email = $account->getEmail();

		if ($email === null) {
			$io->warning(
				(string) $this->translator->translate(
					'//accounts-module.cmd.accountCreate.validation.identity.noEmail',
				),
			);

			return 0;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			// Create new email entity for user
			$create = new Utils\ArrayHash();
			$create->offsetSet('entity', Entities\Identities\Identity::class);
			$create->offsetSet('account', $account);
			$create->offsetSet('uid', $email->getAddress());
			$create->offsetSet('password', $password);
			$create->offsetSet('state', Types\IdentityState::ACTIVE);

			$this->identitiesManager->create($create);

			// Commit all changes into database
			$this->getOrmConnection()->commit();
		} catch (Throwable $ex) {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$io->error($ex->getMessage());

			$io->error(
				(string) $this->translator->translate(
					'//accounts-module.cmd.accountCreate.validation.identity.wasNotCreated',
					['error' => $ex->getMessage()],
				),
			);

			return $ex->getCode();
		}

		$io->success(
			(string) $this->translator->translate(
				'//accounts-module.cmd.accountCreate.success',
				['name' => $account->getName()],
			),
		);

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

		throw new Exceptions\Runtime('Transformer manager could not be loaded');
	}

}
