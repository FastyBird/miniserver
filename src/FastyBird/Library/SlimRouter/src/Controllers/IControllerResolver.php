<?php declare(strict_types = 1);

/**
 * IControllerResolver.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           14.04.19
 */

namespace FastyBird\Library\SlimRouter\Controllers;

/**
 * Endpoint controller callback resolver interface
 *
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IControllerResolver
{

	/**
	 * Resolve $toResolve into a callable
	 *
	 * @param string|callable|array<mixed> $toResolve
	 */
	public function resolve(string|callable|array $toResolve): callable;

}
