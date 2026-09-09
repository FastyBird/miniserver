<?php declare(strict_types = 1);

/**
 * AccountPresenter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Presenters
 * @since          1.0.0
 *
 * @date           05.07.24
 */

namespace FastyBird\Module\Accounts\Presenters;

use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\SimpleAuth\Security as SimpleAuthSecurity;
use Nette\Application;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function assert;

/**
 * Account presenter
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
class AccountPresenter extends BasePresenter
{

	public function __construct(
		protected readonly Models\Entities\Accounts\AccountsRepository $accountsRepository,
		protected readonly Models\Entities\Emails\EmailsRepository $emailsRepository,
	)
	{
		parent::__construct();
	}

	/**
	 * @throws Application\BadRequestException
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function actionDefault(): void
	{
		$this->loadAccount();
		$this->loadEmails();
	}

	/**
	 * @throws Application\BadRequestException
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function actionProfile(): void
	{
		$this->loadAccount();
		$this->loadEmails();
	}

	/**
	 * @throws Application\BadRequestException
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function actionPassword(): void
	{
		$this->loadAccount();
		$this->loadEmails();
	}

	/**
	 * @throws Application\BadRequestException
	 * @throws Utils\JsonException
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function loadAccount(): void
	{
		assert($this->simpleUser instanceof SimpleAuthSecurity\User);
		assert($this->simpleUser->getId() instanceof Uuid\UuidInterface);

		$findQuery = new Queries\Entities\FindAccounts();
		$findQuery->byId($this->simpleUser->getId());

		$account = $this->accountsRepository->findOneBy($findQuery);

		if ($account === null) {
			throw new Application\BadRequestException('Account not found');
		}

		$this->template->account = Utils\Json::encode($account->toArray());
	}

	/**
	 * @throws Application\BadRequestException
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws ToolsExceptions\InvalidState
	 */
	protected function loadEmails(): void
	{
		assert($this->simpleUser instanceof SimpleAuthSecurity\User);
		assert($this->simpleUser->getId() instanceof Uuid\UuidInterface);

		$findQuery = new Queries\Entities\FindAccounts();
		$findQuery->byId($this->simpleUser->getId());

		$account = $this->accountsRepository->findOneBy($findQuery);

		if ($account === null) {
			throw new Application\BadRequestException('Account not found');
		}

		$findQuery = new Queries\Entities\FindEmails();
		$findQuery->forAccount($account);

		$emails = $this->emailsRepository->findAllBy($findQuery);

		$this->template->emails = Utils\Json::encode(
			array_map(
				static fn (Entities\Emails\Email $email): array => $email->toArray(),
				$emails,
			),
		);
	}

}
