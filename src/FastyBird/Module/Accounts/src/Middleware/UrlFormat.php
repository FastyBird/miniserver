<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Middleware;

use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Security;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Localization;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use function str_replace;
use function str_starts_with;
use function strval;

/**
 * Response url formatter
 */
final readonly class UrlFormat implements MiddlewareInterface
{

	public function __construct(
		private bool $usePrefix,
		private Security\User $user,
		private Localization\Translator $translator,
	)
	{
	}

	/**
	 * @throws Exceptions\JsonApi
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	#[Override]
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $handler->handle($request);

		if (
			$this->user->isLoggedIn()
			&& (
				str_starts_with(
					$request->getUri()->getPath(),
					'/' . Http\Routing\Router::API_PREFIX
					. ($this->usePrefix ? '/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX : '')
					. '/v1/session',
				)
				|| str_starts_with(
					$request->getUri()->getPath(),
					'/' . Http\Routing\Router::API_PREFIX
					. ($this->usePrefix ? '/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX : '')
					. '/v1/me',
				)
			)
		) {
			if ($this->user->getAccount() === null) {
				throw new Exceptions\JsonApiError(
					StatusCodeInterface::STATUS_BAD_REQUEST,
					strval($this->translator->translate('//accounts-module.base.messages.failed.heading')),
					strval($this->translator->translate('//accounts-module.base.messages.failed.message')),
				);
			}

			$body = $response->getBody();
			$body->rewind();

			$content = $body->getContents();
			$content = str_replace(
				'\/api\/v1\/emails',
				'\/api\/v1\/me\/emails',
				$content,
			);
			$content = str_replace(
				'\/api\/v1\/identities',
				'\/api\/v1\/me\/identities',
				$content,
			);
			$content = str_replace(
				'\/api\/v1\/accounts\/' . $this->user->getAccount()->getId()->toString(),
				'\/api\/v1\/me',
				$content,
			);
			$content = str_replace(
				'\/api\/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX . '\/v1\/emails',
				'\/api\/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX . '\/v1\/me\/emails',
				$content,
			);
			$content = str_replace(
				'\/api\/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX . '\/v1\/identities',
				'\/api\/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX . '\/v1\/me\/identities',
				$content,
			);
			$content = str_replace(
				'\/api\/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX . '\/v1\/accounts\/' . $this->user->getAccount()->getId()->toString(),
				'\/api\/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX . '\/v1\/me',
				$content,
			);

			$response = $response->withBody(Http\Stream::fromBodyString($content));
		}

		return $response;
	}

}
