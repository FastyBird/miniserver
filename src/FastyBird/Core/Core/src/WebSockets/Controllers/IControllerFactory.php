<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers;

use FastyBird\Core\Exceptions;

/**
 * Responsible for creating a new instance of given controller
 */
interface IControllerFactory
{

	/**
	 * Generates and checks presenter class name
	 *
	 * @return string class name
	 *
	 * @throws Exceptions\InvalidController
	 */
	public function getControllerClass(string &$name): string;

	/**
	 * Creates new controller instance
	 */
	public function createController(string $name): RequestController;

}
