<?php declare(strict_types = 1);

/**
 * Access.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           24.07.20
 */

namespace FastyBird\Module\Ui\Middleware;

use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Exceptions as SimpleAuthExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function strval;

/**
 * Access check middleware
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Access implements MiddlewareInterface
{

	public function __construct(private readonly Localization\Translator $translator)
	{
	}

	/**
	 * @throws ApiExceptions\JsonApiError
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		} catch (SimpleAuthExceptions\UnauthorizedAccess) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				strval($this->translator->translate('//ui-module.base.messages.unauthorized.heading')),
				strval($this->translator->translate('//ui-module.base.messages.unauthorized.message')),
			);
		} catch (SimpleAuthExceptions\ForbiddenAccess) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_FORBIDDEN,
				strval($this->translator->translate('//ui-module.base.messages.forbidden.heading')),
				strval($this->translator->translate('//ui-module.base.messages.forbidden.message')),
			);
		}
	}

}
