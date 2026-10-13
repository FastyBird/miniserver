<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Middleware;

use FastyBird\Core\Http;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Security;
use InvalidArgumentException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use function str_replace;
use function str_starts_with;

/**
 * Response url formatter
 */
final readonly class UrlFormat implements MiddlewareInterface
{

	public function __construct(
		private bool $usePrefix,
		private Security\User $user,
	)
	{
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	#[Override]
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $handler->handle($request);

		// Only a signed-in Accounts identity has an account to rewrite the URLs for. A request
		// signed in by Core's API key has none: its response passes through unchanged, which for
		// these routes is the controller's own 403, rather than an exception thrown out here,
		// outside the JSON:API error middleware.
		if (
			$this->user->getAccount() !== null
			&& (
				str_starts_with(
					$request->getUri()->getPath(),
					'/' . Http\Routing\Router::API_PREFIX
					. ($this->usePrefix ? '/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX : '')
					. '/v1/session',
				)
				|| str_starts_with(
					$request->getUri()->getPath(),
					'/' . Http\Routing\Router::API_PREFIX
					. ($this->usePrefix ? '/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX : '')
					. '/v1/me',
				)
			)
		) {
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
				'\/api\/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/emails',
				'\/api\/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me\/emails',
				$content,
			);
			$content = str_replace(
				'\/api\/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/identities',
				'\/api\/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me\/identities',
				$content,
			);
			$content = str_replace(
				'\/api\/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/accounts\/' . $this->user->getAccount()->getId()->toString(),
				'\/api\/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me',
				$content,
			);

			$response = $response->withBody(Http\Stream::fromBodyString($content));
		}

		return $response;
	}

}
