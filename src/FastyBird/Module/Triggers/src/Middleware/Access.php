<?php declare(strict_types = 1);

/**
 * Access.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           24.07.20
 */

namespace FastyBird\Module\Triggers\Middleware;

use FastyBird\Core\Exceptions\SimpleAuth as SimpleAuthExceptions;
use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
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
 * @package        FastyBird:TriggersModule!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Access implements MiddlewareInterface
{

	public function __construct(private Localization\Translator $translator)
	{
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		} catch (SimpleAuthExceptions\UnauthorizedAccess) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				strval($this->translator->translate('//triggers-module.base.messages.unauthorized.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.unauthorized.message')),
			);
		} catch (SimpleAuthExceptions\ForbiddenAccess) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_FORBIDDEN,
				strval($this->translator->translate('//triggers-module.base.messages.forbidden.heading')),
				strval($this->translator->translate('//triggers-module.base.messages.forbidden.message')),
			);
		}
	}

}
