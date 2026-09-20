<?php declare(strict_types = 1);

/**
 * UrlFormat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           21.06.20
 */

namespace FastyBird\Module\Accounts\Middleware;

use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Core\Constants\Metadata;
use FastyBird\Core\Http\SlimRouter as SlimRouterHttp;
use FastyBird\Module\Accounts\Security;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Localization;
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
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
	 * @throws JsonApiExceptions\JsonApi
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $handler->handle($request);

		if (
			$this->user->isLoggedIn()
			&& (
				str_starts_with(
					$request->getUri()->getPath(),
					'/' . Metadata\Constants::ROUTER_API_PREFIX
					. ($this->usePrefix ? '/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX : '')
					. '/v1/session',
				)
				|| str_starts_with(
					$request->getUri()->getPath(),
					'/' . Metadata\Constants::ROUTER_API_PREFIX
					. ($this->usePrefix ? '/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX : '')
					. '/v1/me',
				)
			)
		) {
			if ($this->user->getAccount() === null) {
				throw new JsonApiExceptions\JsonApiError(
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
				'\/v1\/me',
				$content,
			);
			$content = str_replace(
				'\/api\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/emails',
				'\/api\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me\/emails',
				$content,
			);
			$content = str_replace(
				'\/api\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/identities',
				'\/api\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me\/identities',
				$content,
			);
			$content = str_replace(
				'\/api\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/accounts\/' . $this->user->getAccount()->getId()->toString(),
				'\/api\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me',
				$content,
			);

			$response = $response->withBody(SlimRouterHttp\Stream::fromBodyString($content));
		}

		return $response;
	}

}
