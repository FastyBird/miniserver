<?php declare(strict_types = 1);

/**
 * IRouteGroup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Routing
 * @since          0.1.0
 *
 * @date           14.03.20
 */

namespace FastyBird\Core\Routing\SlimRouter;

use Psr\Http\Server\MiddlewareInterface;

interface IRouteGroup
{

	/**
	 * Add middleware to the route group
	 */
	public function addMiddleware(MiddlewareInterface $middleware): void;

	/**
	 * Get the RouteGroup's pattern
	 */
	public function getPattern(): string;

	public function getRouteCollector(): IRouteCollector;

}
