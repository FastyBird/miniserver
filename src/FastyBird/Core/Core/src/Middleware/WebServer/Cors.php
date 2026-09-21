<?php declare(strict_types = 1);

/**
 * Router.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           08.05.21
 */

namespace FastyBird\Core\Middleware\WebServer;

use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function implode;

/**
 * CORS middleware
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Cors
{

	/**
	 * @param array<string> $allowMethods
	 * @param array<string> $allowHeaders
	 */
	public function __construct(
		private bool $enabled,
		private string $allowOrigin,
		private array $allowMethods,
		private bool $allowCredentials,
		private array $allowHeaders,
	)
	{
	}

	/**
	 * @param Closure(ServerRequestInterface $request): ResponseInterface $next
	 *
	 * @throws InvalidArgumentException
	 */
	public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
	{
		$response = $next($request);

		if (!$this->enabled) {
			return $response;
		}

		// Setup content type
		return $response
			// CORS headers
			->withHeader('Access-Control-Allow-Origin', $this->allowOrigin)
			->withHeader('Access-Control-Allow-Methods', implode(',', $this->allowMethods))
			->withHeader('Access-Control-Allow-Credentials', $this->allowCredentials ? 'true' : 'false')
			->withHeader('Access-Control-Allow-Headers', implode(',', $this->allowHeaders));
	}

}
