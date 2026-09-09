<?php declare(strict_types = 1);

/**
 * TEmail.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           25.06.20
 */

namespace FastyBird\Module\Accounts\Controllers\Finders;

use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Psr\Http\Message;
use Ramsey\Uuid;
use function strval;

/**
 * @property-read Localization\ITranslator $translator
 * @property-read Models\Entities\Emails\EmailsRepository $emailsRepository
 */
trait TEmail
{

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws ToolsExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function findEmail(
		Message\ServerRequestInterface $request,
		Entities\Accounts\Account|null $account = null,
	): Entities\Emails\Email
	{
		if (!Uuid\Uuid::isValid(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notFound.message')),
			);
		}

		$findQuery = new Queries\Entities\FindEmails();
		$findQuery->byId(Uuid\Uuid::fromString(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID))));

		if ($account !== null) {
			$findQuery->forAccount($account);
		}

		$email = $this->emailsRepository->findOneBy($findQuery);

		if ($email === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//accounts-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//accounts-module.base.messages.notFound.message')),
			);
		}

		return $email;
	}

}
